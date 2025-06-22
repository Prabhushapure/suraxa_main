<?php
// reports-utils.php - Utility functions for generating reports

/**
 * Generate user report data based on filters
 * @param array $programIds - Array of program IDs or 'all'
 * @param string $startDate - Start date filter (YYYY-MM-DD or 'NA')
 * @param string $endDate - End date filter (YYYY-MM-DD or 'NA')
 * @param string $nameFilter - Name search filter
 * @param object $conn - Database connection
 * @param bool $isAllPrograms - Whether all programs are selected
 * @param int $limit - Number of records to return (optional)
 * @param int $offset - Number of records to skip (optional)
 * @return array - Report data
 */
function generateUserReport($programIds, $startDate, $endDate, $nameFilter, $conn, $isAllPrograms, $limit = null, $offset = null) {
    $reportData = [];
    
    if ($isAllPrograms) {
        // Generate report for all programs
        $reportData = getAllProgramsReport($startDate, $endDate, $nameFilter, $conn, $limit, $offset);
    } else {
        // Generate report for specific programs
        $reportData = getSpecificProgramsReport($programIds, $startDate, $endDate, $nameFilter, $conn, $limit, $offset);
    }
    
    return $reportData;
}

/**
 * Get report for all programs
 */
function getAllProgramsReport($startDate, $endDate, $nameFilter, $conn, $limit = null, $offset = null) {
    $sql = "SELECT user_program_playno.UserID, user.UserName, user.LoginID, user.UserOrg,
            user.Region, user.City, program.ProgramName, 
            user_program_playno.Status, user_program_playno.StartTime, user_program_playno.EndTime,
            user_program_playno.Pass, MAX(user_program_playno.ScorePercentage) AS ScorePercentage
            FROM user_program_playno
            JOIN user ON user_program_playno.UserID = user.UserID
            JOIN program ON user_program_playno.ProgramID = program.ProgramID
            WHERE user_program_playno.PlayNo >= 1 AND user.UserStatus != 0";
    
    $params = [];
    $types = "";
    
    // Add name filter
    if (!empty($nameFilter)) {
        $sql .= " AND user.UserName LIKE ?";
        $params[] = "%$nameFilter%";
        $types .= "s";
    }
    
    // Add date filters
    if ($startDate !== 'NA' && !empty($startDate)) {
        $sql .= " AND DATE(user_program_playno.StartTime) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate !== 'NA' && !empty($endDate)) {
        $sql .= " AND DATE(user_program_playno.StartTime) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    $sql .= " GROUP BY user_program_playno.UserID, user_program_playno.ProgramID ORDER BY user.UserName, program.ProgramName";
    
    // Add pagination if specified
    if ($limit !== null && $offset !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
    }
    
    return executeReportQuery($sql, $params, $types, $conn);
}

/**
 * Get report for specific programs
 */
function getSpecificProgramsReport($programIds, $startDate, $endDate, $nameFilter, $conn, $limit = null, $offset = null) {
    $reportData = [];
    
    foreach ($programIds as $programId) {
        $programData = getSingleProgramReport($programId, $startDate, $endDate, $nameFilter, $conn);
        $reportData = array_merge($reportData, $programData);
    }
    
    // Apply pagination to the combined results
    if ($limit !== null && $offset !== null) {
        $reportData = array_slice($reportData, $offset, $limit);
    }
    
    return $reportData;
}

/**
 * Get report for a single program (includes users who haven't started)
 */
function getSingleProgramReport($programId, $startDate, $endDate, $nameFilter, $conn) {
    // First query: Users who have started the program
    $sql1 = "SELECT user.UserID, user.UserName, user.LoginID, user.UserOrg, user.Region, user.City, 
             program.ProgramName, MAX(user_program_playno.ScorePercentage) AS ScorePercentage,
             CASE WHEN user_program_playno.Status = 'Completed' THEN 'Complete' ELSE 'In-Progress' END AS ProgramStatus,
             CASE WHEN user_program_playno.Status = 'Completed' THEN 
                 CASE WHEN user_program_playno.Pass = 1 THEN 'Pass' ELSE 'Fail' END 
                 ELSE 'NA' END AS ProgramResult,
             user_program_playno.StartTime AS StartDate, user_program_playno.EndTime AS EndDate
             FROM user_program_playno 
             JOIN user ON user_program_playno.UserID = user.UserID
             JOIN program ON user_program_playno.ProgramID = program.ProgramID
             WHERE user_program_playno.ProgramID = ? AND user.UserStatus != 0";
    
    $params1 = [$programId];
    $types1 = "s";
    
    // Add name filter
    if (!empty($nameFilter)) {
        $sql1 .= " AND user.UserName LIKE ?";
        $params1[] = "%$nameFilter%";
        $types1 .= "s";
    }
    
    // Add date filters
    if ($startDate !== 'NA' && !empty($startDate)) {
        $sql1 .= " AND DATE(user_program_playno.StartTime) >= ?";
        $params1[] = $startDate;
        $types1 .= "s";
    }
    
    if ($endDate !== 'NA' && !empty($endDate)) {
        $sql1 .= " AND DATE(user_program_playno.StartTime) <= ?";
        $params1[] = $endDate;
        $types1 .= "s";
    }
    
    $sql1 .= " GROUP BY user_program_playno.UserID";
    
    // Second query: Users who haven't started the program
    $sql2 = "SELECT user.UserID, user.UserName, user.LoginID, user.UserOrg, user.Region, user.City,
             program.ProgramName, NULL AS ScorePercentage, 'Not Started' AS ProgramStatus, 
             'NA' AS ProgramResult, NULL AS StartDate, NULL AS EndDate
             FROM user, program
             WHERE program.ProgramID = ? AND user.UserID NOT IN(
                 SELECT DISTINCT UserID FROM user_program_playno WHERE ProgramID = ?";
    
    $params2 = [$programId, $programId];
    $types2 = "ss";
    
    // Add date filter to exclusion if dates are specified
    if ($startDate !== 'NA' && !empty($startDate)) {
        $sql2 .= " AND DATE(StartTime) >= ?";
        $params2[] = $startDate;
        $types2 .= "s";
    }
    
    if ($endDate !== 'NA' && !empty($endDate)) {
        $sql2 .= " AND DATE(StartTime) <= ?";
        $params2[] = $endDate;
        $types2 .= "s";
    }
    
    $sql2 .= ") AND user.UserStatus NOT IN(0, 1)";
    
    // Add name filter for second query
    if (!empty($nameFilter)) {
        $sql2 .= " AND user.UserName LIKE ?";
        $params2[] = "%$nameFilter%";
        $types2 .= "s";
    }
    
    // Execute both queries and merge results
    $data1 = executeReportQuery($sql1, $params1, $types1, $conn);
    $data2 = executeReportQuery($sql2, $params2, $types2, $conn);
    
    return array_merge($data1, $data2);
}

/**
 * Execute report query and format results
 */
function executeReportQuery($sql, $params, $types, $conn) {
    $reportData = [];
    
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Format the data
            $formattedRow = [
                'UserID' => $row['UserID'] ?? '',
                'UserName' => $row['UserName'] ?? '',
                'LoginID' => $row['LoginID'] ?? '',
                'UserOrg' => $row['UserOrg'] ?? '',
                'Region' => $row['Region'] ?? '',
                'City' => $row['City'] ?? '',
                'ProgramName' => $row['ProgramName'] ?? '',
                'ScorePercentage' => $row['ScorePercentage'] ?? null,
                'ProgramStatus' => $row['ProgramStatus'] ?? ($row['Status'] == 'Completed' ? 'Complete' : 
                                  ($row['Status'] ? 'In-Progress' : 'Not Started')),
                'ProgramResult' => $row['ProgramResult'] ?? (
                    isset($row['Status']) && $row['Status'] == 'Completed' ? 
                    (isset($row['Pass']) && $row['Pass'] == 1 ? 'Pass' : 'Fail') : 'NA'
                ),
                'StartDate' => formatDate($row['StartDate'] ?? $row['StartTime'] ?? null),
                'EndDate' => formatDate($row['EndDate'] ?? $row['EndTime'] ?? null)
            ];
            
            $reportData[] = $formattedRow;
        }
        $stmt->close();
    }
    
    return $reportData;
}

/**
 * Format date to dd/mm/yyyy or return 'NA'
 */
function formatDate($dateString) {
    if (empty($dateString) || $dateString === null) {
        return 'NA';
    }
    
    try {
        $date = new DateTime($dateString);
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return 'NA';
    }
}

/**
 * Get total count of report records (for pagination if needed)
 */
function getReportCount($programIds, $startDate, $endDate, $nameFilter, $conn, $isAllPrograms) {
    // Similar logic to generateUserReport but just counting
    if ($isAllPrograms) {
        return getAllProgramsReportCount($startDate, $endDate, $nameFilter, $conn);
    } else {
        return getSpecificProgramsReportCount($programIds, $startDate, $endDate, $nameFilter, $conn);
    }
}

/**
 * Count records for all programs report
 */
function getAllProgramsReportCount($startDate, $endDate, $nameFilter, $conn) {
    $sql = "SELECT COUNT(*) as total
            FROM user_program_playno
            JOIN user ON user_program_playno.UserID = user.UserID
            JOIN program ON user_program_playno.ProgramID = program.ProgramID
            WHERE user_program_playno.PlayNo >= 1 AND user.UserStatus != 0";
    
    $params = [];
    $types = "";
    
    if (!empty($nameFilter)) {
        $sql .= " AND user.UserName LIKE ?";
        $params[] = "%$nameFilter%";
        $types .= "s";
    }
    
    if ($startDate !== 'NA' && !empty($startDate)) {
        $sql .= " AND DATE(user_program_playno.StartTime) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate !== 'NA' && !empty($endDate)) {
        $sql .= " AND DATE(user_program_playno.StartTime) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    return executeCountQuery($sql, $params, $types, $conn);
}

/**
 * Count records for specific programs report
 */
function getSpecificProgramsReportCount($programIds, $startDate, $endDate, $nameFilter, $conn) {
    $totalCount = 0;
    
    foreach ($programIds as $programId) {
        $count = getSingleProgramReportCount($programId, $startDate, $endDate, $nameFilter, $conn);
        $totalCount += $count;
    }
    
    return $totalCount;
}

/**
 * Count records for single program report
 */
function getSingleProgramReportCount($programId, $startDate, $endDate, $nameFilter, $conn) {
    // Count users who started + users who haven't started
    $sql1 = "SELECT COUNT(DISTINCT user_program_playno.UserID) as total
             FROM user_program_playno 
             JOIN user ON user_program_playno.UserID = user.UserID
             WHERE user_program_playno.ProgramID = ? AND user.UserStatus != 0";
    
    $params1 = [$programId];
    $types1 = "s";
    
    if (!empty($nameFilter)) {
        $sql1 .= " AND user.UserName LIKE ?";
        $params1[] = "%$nameFilter%";
        $types1 .= "s";
    }
    
    if ($startDate !== 'NA' && !empty($startDate)) {
        $sql1 .= " AND DATE(user_program_playno.StartTime) >= ?";
        $params1[] = $startDate;
        $types1 .= "s";
    }
    
    if ($endDate !== 'NA' && !empty($endDate)) {
        $sql1 .= " AND DATE(user_program_playno.StartTime) <= ?";
        $params1[] = $endDate;
        $types1 .= "s";
    }
    
    $count1 = executeCountQuery($sql1, $params1, $types1, $conn);
    
    // Count users who haven't started
    $sql2 = "SELECT COUNT(*) as total
             FROM user
             WHERE user.UserID NOT IN(
                 SELECT DISTINCT UserID FROM user_program_playno WHERE ProgramID = ?";
    
    $params2 = [$programId];
    $types2 = "s";
    
    if ($startDate !== 'NA' && !empty($startDate)) {
        $sql2 .= " AND DATE(StartTime) >= ?";
        $params2[] = $startDate;
        $types2 .= "s";
    }
    
    if ($endDate !== 'NA' && !empty($endDate)) {
        $sql2 .= " AND DATE(StartTime) <= ?";
        $params2[] = $endDate;
        $types2 .= "s";
    }
    
    $sql2 .= ") AND user.UserStatus NOT IN(0, 1)";
    
    if (!empty($nameFilter)) {
        $sql2 .= " AND user.UserName LIKE ?";
        $params2[] = "%$nameFilter%";
        $types2 .= "s";
    }
    
    $count2 = executeCountQuery($sql2, $params2, $types2, $conn);
    
    return $count1 + $count2;
}

/**
 * Execute count query
 */
function executeCountQuery($sql, $params, $types, $conn) {
    $count = 0;
    
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $count = $row['total'];
        }
        $stmt->close();
    }
    
    return $count;
}
?> 