<?php
require_once __DIR__ . '/../db.php';

class SecurityStaff {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new security staff
    public function create($security_name, $shift, $contact_number) {
        $stmt = $this->conn->prepare("INSERT INTO Security_Staff (security_Name, Shift, Contact_Number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $security_name, $shift, $contact_number);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all security staff
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Security_Staff");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read security staff by ID
    public function readById($security_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Security_Staff WHERE Security_id = ?");
        $stmt->bind_param("i", $security_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();
        return $staff;
    }

    // Update security staff
    public function update($security_id, $security_name, $shift, $contact_number) {
        $stmt = $this->conn->prepare("UPDATE Security_Staff SET security_Name = ?, Shift = ?, Contact_Number = ? WHERE Security_id = ?");
        $stmt->bind_param("sssi", $security_name, $shift, $contact_number, $security_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete security staff
    public function delete($security_id) {
        $stmt = $this->conn->prepare("DELETE FROM Security_Staff WHERE Security_id = ?");
        $stmt->bind_param("i", $security_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
