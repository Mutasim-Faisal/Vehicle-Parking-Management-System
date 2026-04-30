<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'models/Vehicle.php';

if (isset($_GET['number'])) {
    $vehicleModel = new Vehicle();
    $vehicle = $vehicleModel->readByNumber($_GET['number']);

    if ($vehicle) {
        header('Content-Type: application/json');
        echo json_encode($vehicle);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>
