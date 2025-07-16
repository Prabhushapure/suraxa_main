<?php
// Database configuration constants
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'prabhu@antiz');
define('DB_NAME', 'suraxa_schneider_fs');

// Global connection variable
global $conn;

// Function to get database connection
function getConnection() {
    global $conn;
    
    // If connection already exists and is alive, return it
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        return $conn;
    }
    
    // Create new connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // Log the error to a secure log file (not visible to users)
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Display a generic error message (for security)
        die("Database connection error. Please contact the administrator.");
    }
    
    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $conn->error);
    }
    
    return $conn;
}

// Initialize connection and ensure it's globally available
$conn = getConnection();
return $conn; // This ensures the connection is returned when the file is required
?>