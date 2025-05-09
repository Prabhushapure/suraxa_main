<?php
// Database configuration constants
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'suraxa_schneider_fs');

// Create connection using OO approach
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log the error to a secure log file (not visible to users)
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Display a generic error message (for security)
    die("Database connection error. Please contact the administrator.");
}
?>