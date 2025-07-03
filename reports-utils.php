<?php
// reports-utils.php - Utility functions for generating reports

// Handle CSV download request
if (isset($_GET['download_csv']) && $_GET['download_csv'] == '1') {
    require_once 'includes/db_connect.php';
    
    // Get parameters
    $programParam = isset($_GET['p']) ? $_GET['p'] : [];
    $startDate = isset($_GET['sd']) ? $_GET['sd'] : '';
    $endDate = isset($_GET['ed']) ? $_GET['ed'] : '';
    
    // Generate CSV and download
    generateCSVDownload($programParam, $startDate, $endDate, $conn);
    exit;
}

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
            user_program_playno.Pass, user_program_playno.ScorePercentage AS ScorePercentage
            FROM user_program_playno
            JOIN user ON user_program_playno.UserID = user.UserID
            JOIN program ON user_program_playno.ProgramID = program.ProgramID
            JOIN (
                SELECT UserID, ProgramID, MAX(PlayNo) as MaxPlayNo
                FROM user_program_playno 
                GROUP BY UserID, ProgramID
            ) latest ON user_program_playno.UserID = latest.UserID 
                     AND user_program_playno.ProgramID = latest.ProgramID 
                     AND user_program_playno.PlayNo = latest.MaxPlayNo
            WHERE user.UserStatus != 0";
    
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
    
    $sql .= " ORDER BY user.UserName, program.ProgramName";
    
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
    // First query: Users who have started the program (get the row with highest PlayNo for each user)
    $sql1 = "SELECT user.UserID, user.UserName, user.LoginID, user.UserOrg, user.Region, user.City, 
             program.ProgramName, user_program_playno.ScorePercentage AS ScorePercentage,
             CASE WHEN user_program_playno.Status = 'Completed' THEN 'Complete' ELSE 'In-Progress' END AS ProgramStatus,
             CASE WHEN user_program_playno.Status = 'Completed' THEN 
                 CASE WHEN user_program_playno.Pass = 1 THEN 'Pass' ELSE 'Fail' END 
                 ELSE 'NA' END AS ProgramResult,
             user_program_playno.StartTime AS StartDate, user_program_playno.EndTime AS EndDate
             FROM user_program_playno 
             JOIN user ON user_program_playno.UserID = user.UserID
             JOIN program ON user_program_playno.ProgramID = program.ProgramID
             JOIN (
                 SELECT UserID, ProgramID, MAX(PlayNo) as MaxPlayNo
                 FROM user_program_playno 
                 WHERE ProgramID = ? 
                 GROUP BY UserID, ProgramID
             ) latest ON user_program_playno.UserID = latest.UserID 
                      AND user_program_playno.ProgramID = latest.ProgramID 
                      AND user_program_playno.PlayNo = latest.MaxPlayNo
             WHERE user_program_playno.ProgramID = ? AND user.UserStatus != 0";
    
    $params1 = [$programId, $programId];
    $types1 = "ss";
    
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

/**
 * Generate and download CSV report
 */
function generateCSVDownload($programParam, $startDate, $endDate, $conn) {
    // Handle program selection
    $selectedPrograms = [];
    $isAllPrograms = false;
    
    if ($programParam === 'all') {
        // All programs selected
        $isAllPrograms = true;
        $sql = "SELECT ProgramID FROM program ORDER BY ProgramName ASC";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $selectedPrograms[] = $row['ProgramID'];
            }
            $stmt->close();
        }
    } else if (!empty($programParam) && is_array($programParam)) {
        // Specific programs selected
        $selectedPrograms = $programParam;
    }
    
    // Get full report data (without name filter and pagination)
    $reportData = [];
    if (!empty($selectedPrograms)) {
        $reportData = generateUserReport($selectedPrograms, $startDate, $endDate, '', $conn, $isAllPrograms);
    }
    
    // Generate CSV content
    $csvContent = generateCSVContent($reportData);
    
    // Set headers for file download
    $filename = 'user_report_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output CSV content
    echo $csvContent;
}

/**
 * Generate CSV content from report data
 */
function generateCSVContent($reportData) {
    $csv = '';
    
    // Add CSV headers
    $headers = [
        'User ID',
        'Name', 
        'Email',
        'Program Name',
        'Org/Vendor',
        'Region',
        'City',
        'Score',
        'Status',
        'Result',
        'Start Date',
        'End Date'
    ];
    
    $csv .= implode(',', array_map('escapeCsvField', $headers)) . "\n";
    
    // Add data rows
    foreach ($reportData as $row) {
        $csvRow = [
            $row['UserID'],
            $row['UserName'],
            $row['LoginID'],
            $row['ProgramName'],
            $row['UserOrg'],
            $row['Region'],
            $row['City'],
            $row['ScorePercentage'] !== null ? number_format($row['ScorePercentage'], 1) . '%' : '-',
            $row['ProgramStatus'],
            $row['ProgramResult'],
            $row['StartDate'],
            $row['EndDate']
        ];
        
        $csv .= implode(',', array_map('escapeCsvField', $csvRow)) . "\n";
    }
    
    return $csv;
}

/**
 * Escape CSV field for proper formatting
 */
function escapeCsvField($field) {
    // Convert null to empty string
    if ($field === null) {
        $field = '';
    }
    
    // If field contains comma, quote, or newline, wrap in quotes and escape quotes
    if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
        $field = '"' . str_replace('"', '""', $field) . '"';
    }
    
    return $field;
}
?> 