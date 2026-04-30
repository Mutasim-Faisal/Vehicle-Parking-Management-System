<?php
require_once __DIR__ . '/../db.php';

class SlotRequest {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new slot request
    public function create($slot_number, $slot_type, $location, $request_type) {
        $stmt = $this->conn->prepare("INSERT INTO Slot_Request (Slot_number, Slot_type, Location, Request_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $slot_number, $slot_type, $location, $request_type);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all pending requests (for admin)
    public function readPendingRequests() {
        $result = $this->conn->query("SELECT sr.*, u.Username as Processed_by_username FROM Slot_Request sr LEFT JOIN Users u ON sr.Processed_by = u.User_id WHERE sr.Status = 'Pending' ORDER BY sr.Request_date ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read requests by user (not applicable for slots, but keeping for consistency)
    public function readByUserId($user_id) {
        // Slot requests are not tied to specific users in this system
        return [];
    }

    // Read request by ID
    public function readById($request_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Slot_Request WHERE Request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();
        return $request;
    }

    // Approve a slot request
    public function approve($request_id, $processed_by) {
        $stmt = $this->conn->prepare("UPDATE Slot_Request SET Status = 'Approved', Processed_date = NOW(), Processed_by = ? WHERE Request_id = ?");
        $stmt->bind_param("ii", $processed_by, $request_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Reject a slot request
    public function reject($request_id, $processed_by) {
        $stmt = $this->conn->prepare("UPDATE Slot_Request SET Status = 'Rejected', Processed_date = NOW(), Processed_by = ? WHERE Request_id = ?");
        $stmt->bind_param("ii", $processed_by, $request_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Get request statistics
    public function getRequestStats() {
        $result = $this->conn->query("SELECT Status, COUNT(*) as count FROM Slot_Request GROUP BY Status");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>