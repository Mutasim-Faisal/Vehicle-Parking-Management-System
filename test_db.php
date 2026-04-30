<?php
require_once 'db.php';

try {
    $conn = getDBConnection();
    echo "Database connection successful!\n";
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM Parking_Slot");
    $row = $result->fetch_assoc();
    echo "Parking slots count: " . $row['count'] . "\n";
    
    closeDBConnection($conn);
    echo "Database connection closed.\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>