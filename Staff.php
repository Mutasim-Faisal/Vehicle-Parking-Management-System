<?php
require_once __DIR__ . '/../db.php';

class Staff {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new staff
    public function create($staff_id, $staff_name, $email, $contact_number) {
        $stmt = $this->conn->prepare("INSERT INTO Staff (Staff_id, Staff_name, Email, Contact_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $staff_id, $staff_name, $email, $contact_number);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all staff
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Staff");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read staff by ID
    public function readById($staff_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Staff WHERE Staff_id = ?");
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();
        return $staff;
    }

    // Update staff
    public function update($staff_id, $staff_name, $email, $contact_number) {
        $stmt = $this->conn->prepare("UPDATE Staff SET Staff_name = ?, Email = ?, Contact_number = ? WHERE Staff_id = ?");
        $stmt->bind_param("ssss", $staff_name, $email, $contact_number, $staff_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete staff
    public function delete($staff_id) {
        $stmt = $this->conn->prepare("DELETE FROM Staff WHERE Staff_id = ?");
        $stmt->bind_param("s", $staff_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
