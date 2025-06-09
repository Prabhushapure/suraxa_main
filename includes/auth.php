<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dev mode flag - SET THIS TO FALSE BEFORE PRODUCTION!
define('DEV_MODE', true);
define('DEV_USER_ID', 'U0005'); // Your test user ID

/**
 * Check if user is authenticated
 * @return bool True if authenticated, false otherwise
 */
function isAuthenticated() {
    global $conn;
    
    // In development mode, auto-set the session variables if not set
    if (DEV_MODE) {
        $_SESSION["userID"] = DEV_USER_ID;
    }
    
    // Check if session ID exists
    if (!isset($_SESSION["userID"]) || empty($_SESSION["userID"])) {
        error_log("Authentication failed: No session ID found");
        return false;
    }
    
    // Get user ID from session
    $userId = $_SESSION["userID"];
    
    // Include database connection
    if (!isset($conn) || !($conn instanceof mysqli) || !$conn->ping()) {
        $conn = require_once 'db_connect.php';
        if (!$conn) {
            error_log("Authentication failed: Could not establish database connection");
            return false;
        }
    }
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT UserID, CompanyID FROM user WHERE UserID = ?");
    if (!$stmt) {
        error_log("Authentication failed: Prepare statement failed - " . $conn->error);
        return false;
    }
    
    // Bind the user ID parameter
    $bindResult = $stmt->bind_param("s", $userId);
    if (!$bindResult) {
        error_log("Authentication failed: Parameter binding failed - " . $stmt->error);
        return false;
    }
    
    // Execute the query
    $execResult = $stmt->execute();
    if (!$execResult) {
        error_log("Authentication failed: Query execution failed - " . $stmt->error);
        return false;
    }
    
    // Get result
    $result = $stmt->get_result();
    
    // Check if user exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION["companyId"] = $row['CompanyID'];
        return true;
    }
    
    // User not found in database
    error_log("Authentication failed: User ID '$userId' not found in database");
    
    // Clear the invalid session
    session_unset();
    session_destroy();
    return false;
}

/**
 * Redirect to login page if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        error_log("Auth required but failed - redirecting to login page");
        // Store the requested URL for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: /login.php");
        exit();
    }
}
?>