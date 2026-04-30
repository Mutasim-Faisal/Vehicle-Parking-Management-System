<?php
require_once __DIR__ . '/../db.php';

class Faculty {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new faculty
    public function create($faculty_id, $faculty_name, $department, $e_mail, $contact_number) {
        $stmt = $this->conn->prepare("INSERT INTO Faculty (Faculty_id, Faculty_name, Department, E_mail, Contact_Number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $faculty_id, $faculty_name, $department, $e_mail, $contact_number);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all faculty
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Faculty");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read faculty by ID
    public function readById($faculty_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Faculty WHERE Faculty_id = ?");
        $stmt->bind_param("s", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $faculty = $result->fetch_assoc();
        $stmt->close();
        return $faculty;
    }

    // Update faculty
    public function update($faculty_id, $faculty_name, $department, $e_mail, $contact_number) {
        $stmt = $this->conn->prepare("UPDATE Faculty SET Faculty_name = ?, Department = ?, E_mail = ?, Contact_Number = ? WHERE Faculty_id = ?");
        $stmt->bind_param("sssss", $faculty_name, $department, $e_mail, $contact_number, $faculty_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete faculty
    public function delete($faculty_id) {
        $stmt = $this->conn->prepare("DELETE FROM Faculty WHERE Faculty_id = ?");
        $stmt->bind_param("s", $faculty_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
