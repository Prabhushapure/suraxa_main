<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dev mode flag - SET THIS TO FALSE BEFORE PRODUCTION!
define('DEV_MODE', true);
define('DEV_USER_ID', 'U0005'); // Your test user ID
define('DEV_COMPANY_ID', 'C0001'); // Your test company ID
/**
 * Check if user is authenticated
 * @return bool True if authenticated, false otherwise
 */
function isAuthenticated() {
    // In development mode, auto-set the session variables if not set
    if (DEV_MODE) {
        $_SESSION["id"] = DEV_USER_ID;
        $_SESSION["companyId"] = DEV_COMPANY_ID;
        return true;
    }
    
    // Check if session ID exists
    if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
        error_log("Authentication failed: No session ID found");
        return false;
    }
    
    // Get user ID from session
    $userId = $_SESSION["id"];
    
    // Include database connection
    require_once 'db_connect.php';
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT UserID FROM user WHERE UserID = ?");
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
    // In development mode, ensure session variables are set
    if (DEV_MODE) {
        $_SESSION["id"] = DEV_USER_ID;
        $_SESSION["companyId"] = DEV_COMPANY_ID;
        return;
    }

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