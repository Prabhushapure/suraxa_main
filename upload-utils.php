<?php
// Include database connection and get the connection
$conn = require_once 'includes/db_connect.php';
require_once 'includes/auth.php';
require_once 'includes/user-validation-utils.php';

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

// Helper function to validate row count
function validateRowCount($rows) {
    if (count($rows) > 1000) {
        return array(
            'success' => false,
            'message' => 'Each upload should only have a maximum of 1000 users. Please trim down the rows and re-upload. And do this multiple times.'
        );
    }
    return array('success' => true);
}



// Helper function to validate password lengths
function validatePasswords($rows) {
    $invalidPasswords = array();
    foreach ($rows as $index => $row) {
        if (strlen($row[3]) < 6) { // Password is at index 3
            $invalidPasswords[] = "Row " . ($index + 1) . " (User: " . $row[0] . ")";
        }
    }
    
    if (!empty($invalidPasswords)) {
        return array(
            'success' => false,
            'message' => 'Password length must be at least 6 characters long. Invalid passwords found in: ' . implode(', ', $invalidPasswords)
        );
    }
    return array('success' => true);
}

// Helper function to add new organizations
function addNewOrganizations($orgs, $conn) {
    if (empty($orgs)) return;
    
    $values = array_map(function($org) use ($conn) {
        return "('" . mysqli_real_escape_string($conn, trim($org)) . "')";
    }, array_unique($orgs));
    
    $sql = "INSERT IGNORE INTO user_organization (Organization) VALUES " . implode(',', $values);
    mysqli_query($conn, $sql);
}

// Helper function to add new regions
function addNewRegions($regions, $conn) {
    if (empty($regions)) return;
    
    $values = array_map(function($region) use ($conn) {
        return "('" . mysqli_real_escape_string($conn, trim($region)) . "')";
    }, array_unique($regions));
    
    $sql = "INSERT IGNORE INTO user_region (Region) VALUES " . implode(',', $values);
    mysqli_query($conn, $sql);
}

// Helper function to get the starting UserID number
function getStartingUserIdNumber($conn) {
    $sql = "SELECT SUBSTRING(UserID, 2) as user_num 
            FROM user 
            WHERE UserID REGEXP '^U[0-9]{4}$'
            ORDER BY CAST(SUBSTRING(UserID, 2) AS UNSIGNED) DESC 
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row['user_num']);
    }
    return 0;
}

// Helper function to create users
function createUsers($rows, $conn) {
    if (!isset($_SESSION['companyId'])) {
        return array(
            'success' => false,
            'message' => 'Company ID not found in session. Please log in again.'
        );
    }

    $values = array();
    $companyId = $_SESSION['companyId'];
    
    // Get the starting UserID number once
    $nextUserNumber = getStartingUserIdNumber($conn) + 1;
    
    foreach ($rows as $row) {
        $userName = mysqli_real_escape_string($conn, trim($row[0]));
        $gender = mysqli_real_escape_string($conn, trim($row[1]));
        $loginId = mysqli_real_escape_string($conn, trim($row[2]));
        $password = md5(trim($row[3])); // Using MD5 as per existing code
        $role = mysqli_real_escape_string($conn, trim($row[4]));
        $creatorAccess = (int)trim($row[5]);
        $playerAccess = (int)trim($row[6]);
        $adminAccess = (int)trim($row[7]);
        $org = mysqli_real_escape_string($conn, trim($row[8]));
        $region = mysqli_real_escape_string($conn, trim($row[9]));
        $city = mysqli_real_escape_string($conn, trim($row[10]));
        
        // Generate UserID using the incremented number
        $userId = 'U' . sprintf('%04d', $nextUserNumber++);
        
        $values[] = "(
            '$userId', '$password', '$userName', '$gender', '$companyId', 
            '$loginId', '$role', $creatorAccess, $playerAccess, $adminAccess,
            1, '$org', '$region', '$city'
        )";
    }
    
    if (!empty($values)) {
        // Start transaction to ensure UserID consistency
        mysqli_begin_transaction($conn);
        
        try {
            $sql = "INSERT INTO user (
                UserID, Password, UserName, Gender, CompanyID,
                LoginID, Role, CreatorAccess, PlayerAccess, AdminAccess,
                UserStatus, UserOrg, Region, City
            ) VALUES " . implode(',', $values);
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Error creating users: ' . mysqli_error($conn));
            }
            
            mysqli_commit($conn);
            
            return array(
                'success' => true,
                'message' => count($values) . ' users have been created successfully!'
            );
        } catch (Exception $e) {
            mysqli_rollback($conn);
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    return array(
        'success' => false,
        'message' => 'No valid users to create.'
    );
}

// Handle download sample CSV request
if (isset($_POST["downloadSample"])) {
    $filename = "SampleUserUpload.csv";
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Create output stream
    ob_clean();
    $output = fopen("php://output", "w");
    
    // Add CSV headers - these match the required fields for user creation
    fputcsv($output, array(
        'UserName',
        'Gender(M/F)',
        'LoginID(Email)',
        'Password',
        'Role',
        'CreatorAccess(1/0)',
        'PlayerAccess(1/0)',
        'AdminAccess(1/0)',
        'Organization/Vendor',
        'Region',
        'City'
    ));

    // Add a sample row to help users understand the format
    fputcsv($output, array(
        'Rajesh Krishna',
        'M',
        'rajesh.k@gmail.com',
        'password123',
        'Employee',
        '0',
        '1',
        '0',
        'ABC platform',
        'North',
        'Bengaluru'
    ));

    fclose($output);
    exit;
}

// Handle CSV upload and user creation
if (isset($_POST['action']) && $_POST['action'] === 'uploadCSV') {
    $response = array('success' => false, 'message' => '');

    try {
        // Validate file upload
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
            throw new Exception('Error uploading file. Please try again.');
        }

        // Validate file type
        $fileType = pathinfo($_FILES['csvFile']['name'], PATHINFO_EXTENSION);
        if ($fileType != 'csv') {
            throw new Exception('Please upload only CSV files.');
        }

        // Read CSV file
        $rows = array();
        $orgs = array();
        $regions = array();
        $loginIds = array();
        
        if (($handle = fopen($_FILES['csvFile']['tmp_name'], "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            // Read data rows
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0])) { // Only process rows with a username
                    $rows[] = $data;
                    $orgs[] = trim($data[8]); // Organization column
                    $regions[] = trim($data[9]); // Region column
                    $loginIds[] = trim($data[2]); // LoginID column
                }
            }
            fclose($handle);
        }

        // Run validations
        $rowCountValidation = validateRowCount($rows);
        if (!$rowCountValidation['success']) {
            throw new Exception($rowCountValidation['message']);
        }

        $emailValidation = checkExistingEmails($loginIds, $conn);
        if (!$emailValidation['success']) {
            throw new Exception($emailValidation['message']);
        }

        $passwordValidation = validatePasswords($rows);
        if (!$passwordValidation['success']) {
            throw new Exception($passwordValidation['message']);
        }

        // Add new organizations and regions
        addNewOrganizations($orgs, $conn);
        addNewRegions($regions, $conn);

        // Create users
        $result = createUsers($rows, $conn);
        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        $response = $result;

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?> 