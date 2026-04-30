<?php
require_once __DIR__ . '/../db.php';

class Student {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new student
    public function create($student_id, $student_name, $department, $email, $contact_number) {
        $stmt = $this->conn->prepare("INSERT INTO Student (Student_id, Student_name, Department, Email, Contact_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $student_id, $student_name, $department, $email, $contact_number);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all students
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Student");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read student by ID
    public function readById($student_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Student WHERE Student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        return $student;
    }

    // Update student
    public function update($student_id, $student_name, $department, $email, $contact_number) {
        $stmt = $this->conn->prepare("UPDATE Student SET Student_name = ?, Department = ?, Email = ?, Contact_number = ? WHERE Student_id = ?");
        $stmt->bind_param("sssss", $student_name, $department, $email, $contact_number, $student_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete student
    public function delete($student_id) {
        $stmt = $this->conn->prepare("DELETE FROM Student WHERE Student_id = ?");
        $stmt->bind_param("s", $student_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
