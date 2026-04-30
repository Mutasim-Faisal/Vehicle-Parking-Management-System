<?php
require_once __DIR__ . '/../db.php';

class User {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new user
    public function create($username, $password_hash, $role) {
        $stmt = $this->conn->prepare("INSERT INTO Users (Username, Password_hash, Role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password_hash, $role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all users
    public function readAll() {
        $result = $this->conn->query("SELECT User_id, Username, Role FROM Users");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read user by ID
    public function readById($user_id) {
        $stmt = $this->conn->prepare("SELECT User_id, Username, Role FROM Users WHERE User_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    // Read user by username
    public function readByUsername($username) {
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    // Update user
    public function update($user_id, $username, $password_hash, $role) {
        $stmt = $this->conn->prepare("UPDATE Users SET Username = ?, Password_hash = ?, Role = ? WHERE User_id = ?");
        $stmt->bind_param("sssi", $username, $password_hash, $role, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete user
    public function delete($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM Users WHERE User_id = ?");
        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Authenticate user
    public function authenticate($username, $password) {
        $user = $this->readByUsername($username);
        if ($user && password_verify($password, $user['Password_hash'])) {
            return $user;
        }
        return false;
    }
}
?>
