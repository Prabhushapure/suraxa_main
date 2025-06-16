<?php
// dashboard-utils.php - Utility functions for dashboard data retrieval

// Function to get program statistics
function getProgramStats($programID, $conn) {
    $stats = [
        'invited' => 0,
        'pass' => 0,
        'fail' => 0,
        'inProgress' => 0,
        'notStarted' => 0,
        'passUsers' => '',
        'failUsers' => '',
        'inProgressUsers' => '',
        'notStartedUsers' => ''
    ];
    
    try {
        // Get number of users invited (UserStatus 2,3 means invited)
        $invitedSql = "SELECT COUNT(`UserID`) as invited FROM `user` WHERE UserStatus IN (2,3)";
        if ($stmt = $conn->prepare($invitedSql)) {
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['invited'] = $row['invited'];
            $stmt->close();
        }
        
        // Get users who passed (completed and passed)
        $passSql = "SELECT DISTINCT `user_program_playno`.`UserID` 
                    FROM `user_program_playno`
                    JOIN `user` ON `user_program_playno`.`UserID` = `user`.`UserID`
                    WHERE `ProgramID` = ? AND `Status` = 'Completed' AND `Pass` = TRUE AND user.UserStatus != 0";
        
        if ($stmt = $conn->prepare($passSql)) {
            $stmt->bind_param("s", $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            $passUsers = [];
            while ($row = $result->fetch_assoc()) {
                $passUsers[] = $row['UserID'];
            }
            $stats['pass'] = count($passUsers);
            $stats['passUsers'] = implode(',', $passUsers);
            $stmt->close();
        }
        
        // Get users who failed (completed but failed and never passed)
        $failSql = "SELECT DISTINCT `user_program_playno`.`UserID` 
                    FROM `user_program_playno`
                    JOIN `user` ON `user_program_playno`.`UserID` = `user`.`UserID`
                    WHERE `ProgramID` = ? AND `Status` = 'Completed' AND user.UserStatus != 0
                    AND `Pass` = FALSE AND `user_program_playno`.`UserID` NOT IN(
                        SELECT DISTINCT (`UserID`)
                        FROM `user_program_playno`
                        WHERE `ProgramID` = ? AND `Pass` = TRUE
                    )";
        
        if ($stmt = $conn->prepare($failSql)) {
            $stmt->bind_param("ss", $programID, $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            $failUsers = [];
            while ($row = $result->fetch_assoc()) {
                $failUsers[] = $row['UserID'];
            }
            $stats['fail'] = count($failUsers);
            $stats['failUsers'] = implode(',', $failUsers);
            $stmt->close();
        }
        
        // Get users in progress (started but not completed)
        $inProgressSql = "SELECT DISTINCT `user_program_playno`.`UserID` 
                          FROM `user_program_playno`
                          JOIN `user` ON `user_program_playno`.`UserID` = `user`.`UserID`
                          WHERE `ProgramID` = ? AND `Status` = 'Open' AND `PlayNo` = 1 AND user.UserStatus != 0";
        
        if ($stmt = $conn->prepare($inProgressSql)) {
            $stmt->bind_param("s", $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            $inProgressUsers = [];
            while ($row = $result->fetch_assoc()) {
                $inProgressUsers[] = $row['UserID'];
            }
            $stats['inProgress'] = count($inProgressUsers);
            $stats['inProgressUsers'] = implode(',', $inProgressUsers);
            $stmt->close();
        }
        
        // Get users who haven't started yet
        $notStartedSql = "SELECT `UserID` FROM `user` WHERE `UserID` NOT IN(
                            SELECT DISTINCT `UserID`
                            FROM `user_program_playno`
                            WHERE `ProgramID` = ?
                        ) AND `UserStatus` NOT IN (0, 1)";
        
        if ($stmt = $conn->prepare($notStartedSql)) {
            $stmt->bind_param("s", $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            $notStartedUsers = [];
            while ($row = $result->fetch_assoc()) {
                $notStartedUsers[] = $row['UserID'];
            }
            $stats['notStarted'] = count($notStartedUsers);
            $stats['notStartedUsers'] = implode(',', $notStartedUsers);
            $stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Error getting program stats: " . $e->getMessage());
    }
    
    return $stats;
}

// Function to get detailed user information for a specific category
function getUserDetails($userIds, $conn) {
    if (empty($userIds)) {
        return [];
    }
    
    $users = [];
    $userIdsArray = explode(',', $userIds);
    
    // Create placeholders for the IN clause
    $placeholders = str_repeat('?,', count($userIdsArray) - 1) . '?';
    
    $sql = "SELECT UserID, UserName, LoginID, Role, UserOrg, Region, City 
            FROM user 
            WHERE UserID IN ($placeholders) AND UserStatus != 0
            ORDER BY UserName";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(str_repeat('s', count($userIdsArray)), ...$userIdsArray);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
    }
    
    return $users;
}
?> 