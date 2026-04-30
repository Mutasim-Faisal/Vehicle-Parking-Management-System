<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'models/ParkingSlot.php';

if (isset($_GET['id'])) {
    $slotModel = new ParkingSlot();
    $slot = $slotModel->readById($_GET['id']);

    if ($slot) {
        header('Content-Type: application/json');
        echo json_encode($slot);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Slot not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>
