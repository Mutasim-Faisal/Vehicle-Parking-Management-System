<?php
require_once __DIR__ . '/../db.php';

class Payment {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function __destruct() {
        closeDBConnection($this->conn);
    }

    // Create a new payment
    public function create($log_id, $amount, $payment_time, $payment_method) {
        $stmt = $this->conn->prepare("INSERT INTO Payment (Log_id, Amount, Payment_time, Payment_method) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $log_id, $amount, $payment_time, $payment_method);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Read all payments
    public function readAll() {
        $result = $this->conn->query("SELECT * FROM Payment");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Read payment by ID
    public function readById($payment_id) {
        $stmt = $this->conn->prepare("SELECT * FROM Payment WHERE Payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        $stmt->close();
        return $payment;
    }

    // Update payment
    public function update($payment_id, $log_id, $amount, $payment_time, $payment_method) {
        $stmt = $this->conn->prepare("UPDATE Payment SET Log_id = ?, Amount = ?, Payment_time = ?, Payment_method = ? WHERE Payment_id = ?");
        $stmt->bind_param("idssi", $log_id, $amount, $payment_time, $payment_method, $payment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Delete payment
    public function delete($payment_id) {
        $stmt = $this->conn->prepare("DELETE FROM Payment WHERE Payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Get payments by vehicle numbers
    public function getPaymentsByVehicleNumbers($vehicle_numbers) {
        if (empty($vehicle_numbers)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($vehicle_numbers) - 1) . '?';
        $stmt = $this->conn->prepare("SELECT p.* FROM Payment p JOIN Parking_Log l ON p.Log_id = l.log_id WHERE l.vehicle_number IN ($placeholders)");
        $types = str_repeat('s', count($vehicle_numbers));
        $stmt->bind_param($types, ...$vehicle_numbers);
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $payments;
    }
}
?>
