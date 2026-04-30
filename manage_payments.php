<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role
$user_role = $_SESSION['role'];
$is_admin = ($user_role === 'Admin');

// Set timezone to Bangladesh (UTC+6)
date_default_timezone_set('Asia/Dhaka');

// Function to format datetime to DD-MM-YYYY 12-hour format
function formatDateTime($datetime) {
    if (!$datetime || $datetime === 'Active') return 'Active';
    $dt = new DateTime($datetime);
    return $dt->format('d-m-Y h:i A');
}

require_once 'models/Payment.php';
require_once 'models/ParkingLog.php';
require_once 'models/Vehicle.php';

$paymentModel = new Payment();
$logModel = new ParkingLog();
$vehicleModel = new Vehicle();

$message = '';
$error = '';

// Handle form submissions - only admin can update payments
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment'])) {
        $log_id = $_POST['log_id'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];

        if (empty($log_id) || empty($amount) || empty($payment_method)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = $paymentModel->create($log_id, $amount, date('Y-m-d H:i:s'), $payment_method);
            if ($result) {
                $message = 'Payment recorded successfully!';
            } else {
                $error = 'Failed to record payment.';
            }
        }
    } elseif (isset($_POST['update_payment'])) {
        $payment_id = $_POST['payment_id'];
        $log_id = $_POST['log_id'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];

        $result = $paymentModel->update($payment_id, $log_id, $amount, date('Y-m-d H:i:s'), $payment_method);
        if ($result) {
            $message = 'Payment updated successfully!';
        } else {
            $error = 'Failed to update payment.';
        }
    } elseif (isset($_POST['delete_payment'])) {
        $payment_id = $_POST['payment_id'];
        $result = $paymentModel->delete($payment_id);
        if ($result) {
            $message = 'Payment deleted successfully!';
        } else {
            $error = 'Failed to delete payment.';
        }
    }
}

// Get payments - filter for regular users to show only their payments
if ($is_admin) {
    // Admin sees all payments
    $payments = $paymentModel->readAll();
} else {
    // Regular users see only their payments
    $user_vehicles = [];
    
    // Get user's vehicles based on role
    if ($user_role === 'Student') {
        $user_vehicles = $vehicleModel->getVehicleNumbersByOwner('Student', $_SESSION['user_id']);
    } elseif ($user_role === 'Faculty') {
        $user_vehicles = $vehicleModel->getVehicleNumbersByOwner('Faculty', $_SESSION['user_id']);
    } elseif ($user_role === 'Staff') {
        $user_vehicles = $vehicleModel->getVehicleNumbersByOwner('Staff', $_SESSION['user_id']);
    }
    
    // Get payments for user's vehicles
    $payments = $paymentModel->getPaymentsByVehicleNumbers($user_vehicles);
}

$activeLogs = $logModel->getActiveLogs();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Manage Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-brand">
                <img src="assets/images/logo.png" alt="Logo" class="header-logo">
                <h1>EWU Parking System</h1>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                <a href="change_password.php" class="btn-change-password">Change Password</a>
                <a href="dashboard.php" class="btn-logout">Dashboard</a>
                <a href="dashboard.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container mt-4">
        <h2 class="mb-4">Manage Payments</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <!-- Add New Payment Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Record New Payment</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="log_id" class="form-label">Parking Session</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="log_id" name="log_id" required>
                                    <option value="">Select Session</option>
                                    <?php foreach ($activeLogs as $log): ?>
                                        <option value="<?php echo $log['log_id']; ?>">
                                            Session #<?php echo $log['log_id']; ?> - <?php echo htmlspecialchars($log['vehicle_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="amount" class="form-label">Amount (৳)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Digital">Digital Wallet</option>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_payment" class="btn btn-primary btn-sm w-100">Record Payment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Only administrators can record new payments. You can view your payment history below.</div>
        <?php endif; ?>

        <!-- Payments List -->
        <div class="card">
            <div class="card-header">
                <h5>All Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Session ID</th>
                                <th>Vehicle</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['Payment_id']); ?></td>
                                <td><?php echo htmlspecialchars($payment['Log_id']); ?></td>
                                <td><?php
                                    $log = $logModel->readById($payment['Log_id']);
                                    echo htmlspecialchars($log ? $log['vehicle_number'] : 'N/A');
                                ?></td>
                                <td>৳<?php echo number_format($payment['Amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['Payment_method']); ?></td>
                                <td><?php echo htmlspecialchars(formatDateTime($payment['Payment_time'])); ?></td>
                                <td>
                                    <?php if ($is_admin): ?>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-warning" onclick="editPayment(<?php echo $payment['Payment_id']; ?>, <?php echo $payment['Log_id']; ?>, <?php echo $payment['Amount']; ?>, '<?php echo htmlspecialchars($payment['Payment_method']); ?>')">Edit</button>
                                            <form method="POST" action="" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment['Payment_id']; ?>">
                                                <button type="submit" name="delete_payment" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this payment?')">Delete</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="edit_payment_id">
                        <div class="mb-3">
                            <label for="edit_log_id" class="form-label">Parking Session</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="edit_log_id" name="log_id" required>
                                    <?php foreach ($activeLogs as $log): ?>
                                        <option value="<?php echo $log['log_id']; ?>">
                                            Session #<?php echo $log['log_id']; ?> - <?php echo htmlspecialchars($log['vehicle_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Amount (৳)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_payment_method" class="form-label">Payment Method</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="edit_payment_method" name="payment_method" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Digital">Digital Wallet</option>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        function editPayment(paymentId, logId, amount, paymentMethod) {
            document.getElementById('edit_payment_id').value = paymentId;
            document.getElementById('edit_log_id').value = logId;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit_payment_method').value = paymentMethod;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>
