<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'models/ParkingSlot.php';
require_once 'models/Vehicle.php';
require_once 'models/ParkingLog.php';
require_once 'models/Payment.php';

// Get statistics
$slotModel = new ParkingSlot();
$vehicleModel = new Vehicle();
$logModel = new ParkingLog();
$paymentModel = new Payment();

$totalSlots = count($slotModel->readAll());
$availableSlots = count($slotModel->getAvailableSlots());
$totalVehicles = count($vehicleModel->readAll());
$activeSessions = count($logModel->getActiveLogs());
$pendingPayments = count($paymentModel->readAll()); // In a real app, you'd filter for unpaid

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-page">
    <header class="header">
        <div class="container">
            <div class="header-brand">
                <img src="assets/images/logo.png" alt="Logo" class="header-logo">
                <h1>EWU Parking System</h1>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                <a href="change_password.php" class="btn-change-password">Change Password</a>
                <a href="?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="container">
            <?php if (isset($_SESSION['login_success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['login_success']); ?>
                </div>
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>

            <h2 class="mb-4">Dashboard Overview</h2>

            <div class="dashboard-grid">
                <?php if ($_SESSION['role'] === 'Security'): ?>
                <div class="card">
                    <h3>Security Check</h3>
                    <p>Check user and vehicle information</p>
                    <div class="stats">
                        <a href="security_check.php" class="btn btn-primary btn-sm">Check Users</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] !== 'Security'): ?>
                <div class="card">
                    <h3>Parking Slots</h3>
                    <p>Manage parking slots and availability</p>
                    <div class="stats">
                        <div>
                            <div class="number"><?php echo $availableSlots; ?>/<?php echo $totalSlots; ?></div>
                            <small>Available</small>
                        </div>
                        <a href="manage_slots.php" class="btn btn-primary btn-sm">Manage Slots</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($_SESSION['role'] !== 'Security'): ?>
                <div class="card">
                    <h3>Vehicles</h3>
                    <p>View and manage registered vehicles</p>
                    <div class="stats">
                        <div>
                            <div class="number"><?php echo $totalVehicles; ?></div>
                            <small>Total Vehicles</small>
                        </div>
                        <a href="manage_vehicles.php" class="btn btn-primary btn-sm">Manage Vehicles</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($_SESSION['role'] !== 'Security'): ?>
                <div class="card">
                    <h3>Active Sessions</h3>
                    <p>Currently checked-in vehicles</p>
                    <div class="stats">
                        <div>
                            <div class="number"><?php echo $activeSessions; ?></div>
                            <small>Active</small>
                        </div>
                        <a href="manage_sessions.php" class="btn btn-primary btn-sm">View Sessions</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($_SESSION['role'] !== 'Security'): ?>
                <div class="card">
                    <h3>Payments</h3>
                    <p>Manage payments and transactions</p>
                    <div class="stats">
                        <div>
                            <div class="number"><?php echo $pendingPayments; ?></div>
                            <small>Total Payments</small>
                        </div>
                        <a href="manage_payments.php" class="btn btn-primary btn-sm">Manage Payments</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'Admin'): ?>
                <div class="card">
                    <h3>Users</h3>
                    <p>Manage system users and roles</p>
                    <div class="stats">
                        <a href="manage_users.php" class="btn btn-primary btn-sm">Manage Users</a>
                    </div>
                </div>

                <div class="card">
                    <h3>Reports</h3>
                    <p>View system reports and analytics</p>
                    <div class="stats">
                        <a href="reports.php" class="btn btn-primary btn-sm">View Reports</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
