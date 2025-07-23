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
        $userId = trim($row[0]);
        if ($userId === '') {
            // Generate UserID using the incremented number
            $userId = 'U' . sprintf('%04d', $nextUserNumber++);
        } else {
            // Check if this UserID already exists
            $checkUserIdSql = "SELECT COUNT(*) as cnt FROM user WHERE UserID = '" . mysqli_real_escape_string($conn, $userId) . "'";
            $result = mysqli_query($conn, $checkUserIdSql);
            $rowCheck = mysqli_fetch_assoc($result);
            if ($rowCheck['cnt'] > 0) {
                return array(
                    'success' => false,
                    'message' => 'The User ID ' . $userId . ' already exists. Please choose a different User ID or leave blank for auto-generation.'
                );
            }
        }
        $userName = mysqli_real_escape_string($conn, trim($row[1]));
        $gender = mysqli_real_escape_string($conn, trim($row[2]));
        $loginId = mysqli_real_escape_string($conn, trim($row[3]));
        $password = md5(trim($row[4])); // Using MD5 as per existing code
        $role = mysqli_real_escape_string($conn, trim($row[5]));
        $creatorAccess = (int)trim($row[6]);
        $playerAccess = (int)trim($row[7]);
        $adminAccess = (int)trim($row[8]);
        $org = mysqli_real_escape_string($conn, trim($row[9]));
        $region = mysqli_real_escape_string($conn, trim($row[10]));
        $city = mysqli_real_escape_string($conn, trim($row[11]));
        $mobile = mysqli_real_escape_string($conn, trim($row[12]));
        $employeeStatus = mysqli_real_escape_string($conn, trim($row[13]));
        $employeeLevel = mysqli_real_escape_string($conn, trim($row[14]));
        $division = mysqli_real_escape_string($conn, trim($row[15]));
        $department = mysqli_real_escape_string($conn, trim($row[16]));
        $designation = mysqli_real_escape_string($conn, trim($row[17]));
        $location = mysqli_real_escape_string($conn, trim($row[18]));
        $block = mysqli_real_escape_string($conn, trim($row[19]));
        $function = mysqli_real_escape_string($conn, trim($row[20]));
        
        $values[] = "(
            '$userId', '$password', '$userName', '$gender', '$companyId', 
            '$loginId', '$role', $creatorAccess, $playerAccess, $adminAccess,
            1, '$org', '$region', '$city', '$mobile', '$employeeStatus', '$employeeLevel', '$division', '$department', '$designation', '$location', '$block', '$function'
        )";
    }
    
    if (!empty($values)) {
        // Start transaction to ensure UserID consistency
        mysqli_begin_transaction($conn);
        
        try {
            $sql = "INSERT INTO user (
                UserID, Password, UserName, Gender, CompanyID,
                LoginID, Role, CreatorAccess, PlayerAccess, AdminAccess,
                UserStatus, UserOrg, Region, City, Mobile, EmpRollStatus, EmpLevel, Division, Department, Designation, Location, Block, JobFunction
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
        'UserID',
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
        'City',
        'Mobile',
        'EmployeeStatus',
        'EmployeeLevel',
        'Division',
        'Department',
        'Designation',
        'Location',
        'Block',
        'JobFunction'
    ));

    // Add a sample row to help users understand the format
    fputcsv($output, array(
        '', // UserID left blank for auto-generation
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
        'Bengaluru',
        '', '', '', '', '', '', '', '', ''
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
        $userIds = array();
        
        if (($handle = fopen($_FILES['csvFile']['tmp_name'], "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            // Read data rows
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[1])) { // Only process rows with a username (now at index 1)
                    // Pad missing fields with empty string
                    $data = array_pad($data, 21, '');
                    $rows[] = $data;
                    $userIds[] = trim($data[0]); // UserID column
                    $orgs[] = trim($data[9]); // Organization column
                    $regions[] = trim($data[10]); // Region column
                    $loginIds[] = trim($data[3]); // LoginID column
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