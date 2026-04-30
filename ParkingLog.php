<?php
require_once __DIR__ . '/../db.php';

class ParkingLog {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new parking log entry
    public function create($vehicle_number, $parking_slot_id, $entry_time, $exit_time = null, $status = 'active') {
        $stmt = $this->conn->prepare("INSERT INTO Parking_Log (vehicle_number, parking_slot_id, entry_time, exit_time, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $vehicle_number, $parking_slot_id, $entry_time, $exit_time, $status);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all parking logs
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Parking_Log");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read parking log by ID
    public function readById($log_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Parking_Log WHERE log_id = ?");
        $stmt->bind_param("i", $log_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $log = $result->fetch_assoc();
        $stmt->close();
        return $log;
    }

    // Update parking log
    public function update($log_id, $vehicle_number, $parking_slot_id, $entry_time, $exit_time, $status) {
        $stmt = $this->conn->prepare("UPDATE Parking_Log SET vehicle_number = ?, parking_slot_id = ?, entry_time = ?, exit_time = ?, status = ? WHERE log_id = ?");
        $stmt->bind_param("sisssi", $vehicle_number, $parking_slot_id, $entry_time, $exit_time, $status, $log_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete parking log
    public function delete($log_id) {
        $stmt = $this->conn->prepare("DELETE FROM Parking_Log WHERE log_id = ?");
        $stmt->bind_param("i", $log_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Get active parking logs
    public function getActiveLogs() {
        $result = $this->conn->query("SELECT * FROM Parking_Log WHERE status = 'active'");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get parking logs by vehicle number
    public function getLogsByVehicle($vehicle_number) {
        $stmt = $this->conn->prepare("SELECT * FROM Parking_Log WHERE vehicle_number = ? ORDER BY entry_time DESC");
        $stmt->bind_param("s", $vehicle_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $logs;
    }
}
?>
