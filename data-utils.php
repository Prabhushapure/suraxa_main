<?php
// Include database connection and get the connection
$conn = require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure we have a valid database connection
if (!isset($conn) || !($conn instanceof mysqli) || !$conn->ping()) {
    error_log("Database connection failed");
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please try again later.'
    ]);
    exit;
}

// Function to add new organization if it doesn't exist
if (isset($_POST['action']) && $_POST['action'] === 'addNewOrg') {
    $orgName = trim($_POST['orgName']);
    
    // First check if organization already exists
    $checkSql = "SELECT Organization FROM user_organization WHERE Organization = ?";
    if ($stmt = $conn->prepare($checkSql)) {
        $stmt->bind_param("s", $orgName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Organization already exists']);
            exit;
        }
        $stmt->close();
    }
    
    // If we get here, organization doesn't exist, so add it
    $insertSql = "INSERT INTO user_organization (Organization) VALUES (?)";
    if ($stmt = $conn->prepare($insertSql)) {
        $stmt->bind_param("s", $orgName);
        $success = $stmt->execute();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Organization added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add organization']);
        }
        $stmt->close();
    }
    exit;
}

// Function to add new region if it doesn't exist
if (isset($_POST['action']) && $_POST['action'] === 'addNewRegion') {
    $regionName = trim($_POST['regionName']);
    
    // First check if region already exists
    $checkSql = "SELECT Region FROM user_region WHERE Region = ?";
    if ($stmt = $conn->prepare($checkSql)) {
        $stmt->bind_param("s", $regionName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Region already exists']);
            exit;
        }
        $stmt->close();
    }
    
    // If we get here, region doesn't exist, so add it
    $insertSql = "INSERT INTO user_region (Region) VALUES (?)";
    if ($stmt = $conn->prepare($insertSql)) {
        $stmt->bind_param("s", $regionName);
        $success = $stmt->execute();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Region added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add region']);
        }
        $stmt->close();
    }
    exit;
}

// Function to create new user
if (isset($_POST['action']) && $_POST['action'] === 'createUser') {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Log the incoming data
    error_log("Creating user with data: " . print_r($_POST, true));
    
    // Ensure user is authenticated
    requireAuth();
    error_log("Authentication passed");
    
    // Get the next available UserID
    $sql = "SELECT CASE WHEN ISNULL(MAX(SUBSTRING(UserID, 2, 4))) = 1 
            THEN 0 ELSE MAX(SUBSTRING(UserID, 2, 4)) END AS maximum 
            FROM user";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextNum = sprintf('%04d', $row['maximum'] + 1);
        $userId = 'U' . $nextNum;
        $stmt->close();
        
        // Get form data
        $loginId = $_POST['loginId'];
        $username = $_POST['username'];
        $password = md5($_POST['password']); // Consider using better hashing in production
        $gender = $_POST['gender'];
        $role = $_POST['role'] ?? '';
        $userAccess = json_decode($_POST['userAccess']);
        $companyId = $_SESSION['companyId'];
        
        // Set access flags
        $admin = in_array('Admin', $userAccess) ? 1 : 0;
        $creator = in_array('Creator', $userAccess) ? 1 : 0;
        $player = in_array('Player', $userAccess) ? 1 : 0;
        
        // Get optional fields
        $org = $_POST['userOrg'] ?? null;
        $region = $_POST['userRegion'] ?? null;
        $city = $_POST['city'] ?? null;
        
        // Insert new user
        $insertSql = "INSERT INTO user (UserID, Password, UserName, Gender, CompanyID, LoginID, Role, 
                     AdminAccess, CreatorAccess, PlayerAccess, UserStatus, UserOrg, Region, City) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($insertSql)) {
            $stmt->bind_param("sssssssiiisss", 
                $userId, $password, $username, $gender, $companyId, $loginId, $role,
                $admin, $creator, $player, $org, $region, $city
            );
            
            $success = $stmt->execute();
            
            if ($success) {
                error_log("User created successfully with ID: " . $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'User created successfully',
                    'userId' => $userId
                ]);
            } else {
                error_log("Failed to create user. MySQL Error: " . $stmt->error);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create user: ' . $stmt->error
                ]);
            }
            $stmt->close();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare statement: ' . $conn->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate UserID: ' . $conn->error
        ]);
    }
    exit;
}

// Function to delete user
if (isset($_POST['action']) && $_POST['action'] === 'deleteUser') {
    if (isset($_POST['userId'])) {
        // Set UserStatus to 0 to mark as deleted (soft delete)
        $userId = $_POST['userId'];
        $sql = "UPDATE user SET UserStatus = 0 WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $userId);
            if ($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'User deleted successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to delete user: ' . $conn->error
                ];
            }
            $stmt->close();
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to prepare delete statement: ' . $conn->error
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'No user ID provided'
        ];
    }
    echo json_encode($response);
    exit;
}

// Function to update existing user
if (isset($_POST['action']) && $_POST['action'] === 'updateUser') {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Log the incoming data
    error_log("Updating user with data: " . print_r($_POST, true));
    
    // Ensure user is authenticated
    requireAuth();
    error_log("Authentication passed");
    
    // Get form data
    $userId = intval($_POST['userId']);
    $username = $_POST['username'];
    $loginId = $_POST['loginId'];
    $gender = $_POST['gender'];
    $role = $_POST['role'] ?? '';
    $userAccess = json_decode($_POST['userAccess']);
    
    // Set access flags
    $admin = in_array('Admin', $userAccess) ? 1 : 0;
    $creator = in_array('Creator', $userAccess) ? 1 : 0;
    $player = in_array('Player', $userAccess) ? 1 : 0;
    
    // Get optional fields
    $org = $_POST['userOrg'] ?? null;
    $region = $_POST['userRegion'] ?? null;
    $city = $_POST['city'] ?? null;
    
    // Update user data
    $sql = "UPDATE user SET 
            UserName = ?, Gender = ?, LoginID = ?, Role = ?,
            AdminAccess = ?, CreatorAccess = ?, PlayerAccess = ?,
            UserOrg = ?, Region = ?, City = ?
            WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssiiiissi", 
            $username, $gender, $loginId, $role,
            $admin, $creator, $player, $org, $region, $city, $userId
        );
        $success = $stmt->execute();
        
        if ($success) {
            error_log("User updated successfully. ID: " . $userId);
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } else {
            error_log("Failed to update user. MySQL Error: " . $stmt->error);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update user: ' . $stmt->error
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare statement: ' . $conn->error
        ]);
    }
    exit;
}

// Default response for unknown action
$response = [
    'success' => false,
    'message' => 'Unknown action'
];
echo json_encode($response);
?> 