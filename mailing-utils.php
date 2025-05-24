<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication check and ensure user is authenticated first
require_once 'includes/auth.php';
requireAuth();

// Include database connection
require_once 'includes/db_connect.php';

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'sendInvites':
                if (isset($_POST['userIds']) && is_array($_POST['userIds'])) {
                    // TODO: Implement email sending logic here
                    // For now, just return success
                    $response = [
                        'success' => true,
                        'message' => 'Invites will be implemented soon',
                        'userIds' => $_POST['userIds']
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'No users selected or invalid user data'
                    ];
                }
                break;
            default:
                $response = [
                    'success' => false,
                    'message' => 'Unknown action'
                ];
                break;
        }
    }

    // Return JSON response
    echo json_encode($response);
    exit;
}
?> 