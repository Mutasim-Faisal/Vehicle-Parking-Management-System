<?php
require_once __DIR__ . '/../db.php';

class ParkingSlot {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new parking slot
    public function create($slot_number, $slot_type, $is_occupied, $location) {
        $stmt = $this->conn->prepare("INSERT INTO Parking_Slot (Slot_number, Slot_type, Is_occupied, Location) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $slot_number, $slot_type, $is_occupied, $location);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all parking slots
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Parking_Slot");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read parking slot by ID
    public function readById($parking_slot_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Parking_Slot WHERE Parking_slot_id = ?");
        $stmt->bind_param("i", $parking_slot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $slot = $result->fetch_assoc();
        $stmt->close();
        return $slot;
    }

    // Update parking slot
    public function update($parking_slot_id, $slot_number, $slot_type, $is_occupied, $location) {
        $stmt = $this->conn->prepare("UPDATE Parking_Slot SET Slot_number = ?, Slot_type = ?, Is_occupied = ?, Location = ? WHERE Parking_slot_id = ?");
        $stmt->bind_param("ssssi", $slot_number, $slot_type, $is_occupied, $location, $parking_slot_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete parking slot
    public function delete($parking_slot_id) {
        $stmt = $this->conn->prepare("DELETE FROM Parking_Slot WHERE Parking_slot_id = ?");
        $stmt->bind_param("i", $parking_slot_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Get available slots
    public function getAvailableSlots() {
        $result = $this->conn->query("SELECT * FROM Parking_Slot WHERE Is_occupied = 'no'");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
