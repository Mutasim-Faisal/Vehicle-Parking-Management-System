<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP MySQL user
define('DB_PASS', ''); // Default XAMPP MySQL password (empty)
define('DB_NAME', 'ewu_parking_system');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Close connection
function closeDBConnection($conn) {
    $conn->close();
}
?>
