<?php
require_once __DIR__ . '/../db.php';

class Vehicle {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new vehicle
    public function create($vehicle_number, $vehicle_type, $owner_type, $owner_id) {
        $stmt = $this->conn->prepare("INSERT INTO Vehicle (Vehicle_number, Vehicle_Type, Owner_type, Owner_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $vehicle_number, $vehicle_type, $owner_type, $owner_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all vehicles
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Vehicle");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read vehicle by number
    public function readByNumber($vehicle_number) {
        $stmt = $this->conn->prepare("SELECT * FROM Vehicle WHERE Vehicle_number = ?");
        $stmt->bind_param("s", $vehicle_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $vehicle = $result->fetch_assoc();
        $stmt->close();
        return $vehicle;
    }

    // Update vehicle
    public function update($vehicle_number, $vehicle_type, $owner_type, $owner_id) {
        $stmt = $this->conn->prepare("UPDATE Vehicle SET Vehicle_Type = ?, Owner_type = ?, Owner_id = ? WHERE Vehicle_number = ?");
        $stmt->bind_param("ssss", $vehicle_type, $owner_type, $owner_id, $vehicle_number);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete vehicle
    public function delete($vehicle_number) {
        $stmt = $this->conn->prepare("DELETE FROM Vehicle WHERE Vehicle_number = ?");
        $stmt->bind_param("s", $vehicle_number);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Get vehicles by owner type and ID
    public function getVehiclesByOwner($owner_type, $owner_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Vehicle WHERE Owner_type = ? AND Owner_id = ?");
        $stmt->bind_param("ss", $owner_type, $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vehicles = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $vehicles;
    }
    
    // Get vehicle numbers by owner type and ID
    public function getVehicleNumbersByOwner($owner_type, $owner_id) {
        $stmt = $this->conn->prepare("SELECT Vehicle_number FROM Vehicle WHERE Owner_type = ? AND Owner_id = ?");
        $stmt->bind_param("ss", $owner_type, $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vehicle_numbers = [];
        while ($row = $result->fetch_assoc()) {
            $vehicle_numbers[] = $row['Vehicle_number'];
        }
        $stmt->close();
        return $vehicle_numbers;
    }
}
?>
