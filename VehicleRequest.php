<?php
require_once __DIR__ . '/../db.php';

class VehicleRequest {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new vehicle request
    public function create($vehicle_number, $vehicle_type, $owner_type, $owner_id, $request_type) {
        $stmt = $this->conn->prepare("INSERT INTO Vehicle_Request (Vehicle_number, Vehicle_Type, Owner_type, Owner_id, Request_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $vehicle_number, $vehicle_type, $owner_type, $owner_id, $request_type);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all pending requests (for admin)
    public function readPendingRequests() {
        $result = $this->conn->query("SELECT vr.*, u.Username as Processed_by_username FROM Vehicle_Request vr LEFT JOIN Users u ON vr.Processed_by = u.User_id WHERE vr.Status = 'Pending' ORDER BY vr.Request_date ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read requests by user
    public function readByUserId($owner_type, $owner_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Vehicle_Request WHERE Owner_type = ? AND Owner_id = ? ORDER BY Request_date DESC");
        $stmt->bind_param("ss", $owner_type, $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $requests;
    }

    // Read request by ID
    public function readById($request_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Vehicle_Request WHERE Request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();
        return $request;
    }

    // Approve a vehicle request
    public function approve($request_id, $processed_by) {
        $stmt = $this->conn->prepare("UPDATE Vehicle_Request SET Status = 'Approved', Processed_date = NOW(), Processed_by = ? WHERE Request_id = ?");
        $stmt->bind_param("ii", $processed_by, $request_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Reject a vehicle request
    public function reject($request_id, $processed_by) {
        $stmt = $this->conn->prepare("UPDATE Vehicle_Request SET Status = 'Rejected', Processed_date = NOW(), Processed_by = ? WHERE Request_id = ?");
        $stmt->bind_param("ii", $processed_by, $request_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Get request statistics
    public function getRequestStats() {
        $result = $this->conn->query("SELECT Status, COUNT(*) as count FROM Vehicle_Request GROUP BY Status");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>