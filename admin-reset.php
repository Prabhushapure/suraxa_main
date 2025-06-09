<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication check and ensure user is authenticated first
require_once 'includes/auth.php';
requireAuth();

// Check common session variable names for user ID
if (isset($_SESSION['userID'])) {
    require_once 'includes/db_connect.php';

    $sql = "SELECT id FROM user WHERE UserID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $_SESSION['userID']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentAdminId = $row['id'];
        }
        $stmt->close();
    }

    if ($currentAdminId) {
        header("Location: edit-user.php?id=" . $currentAdminId);
        exit;
    } else {
        header("Location: manage-users.php?error=unable_to_identify_user");
        exit;
    }    
}

?> 