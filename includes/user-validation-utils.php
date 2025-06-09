<?php
// Shared validation utility functions

// Helper function to check for existing email addresses
function checkExistingEmails($loginIds, $conn) {
    // Convert single email to array if needed
    if (!is_array($loginIds)) {
        $loginIds = array($loginIds);
    }
    
    $escapedIds = array_map(function($id) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $id) . "'";
    }, $loginIds);
    
    $idList = implode(',', $escapedIds);
    $sql = "SELECT LoginID FROM user WHERE LoginID IN ($idList)";
    
    $result = mysqli_query($conn, $sql);
    
    $existingEmails = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $existingEmails[] = $row['LoginID'];
    }
    
    if (!empty($existingEmails)) {
        return array(
            'success' => false,
            'message' => 'The following email address(es) already exist in the database: ' . implode(', ', $existingEmails)
        );
    }
    return array('success' => true);
}
?> 