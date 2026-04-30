<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is security staff or admin
$is_security = ($_SESSION['role'] === 'Security');
$is_admin = ($_SESSION['role'] === 'Admin');
$can_manage_sessions = ($is_security || $is_admin);

// Set timezone to Bangladesh (UTC+6)
date_default_timezone_set('Asia/Dhaka');

// Function to format datetime to DD-MM-YYYY 12-hour format
function formatDateTime($datetime) {
    if (!$datetime || $datetime === 'Active') return 'Active';
    $dt = new DateTime($datetime);
    return $dt->format('d-m-Y h:i A');
}

require_once 'models/ParkingLog.php';
require_once 'models/Vehicle.php';
require_once 'models/ParkingSlot.php';

$logModel = new ParkingLog();
$vehicleModel = new Vehicle();
$slotModel = new ParkingSlot();

$message = '';
$error = '';

// Handle form submissions - only security staff and admin can start/end sessions
if ($can_manage_sessions && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_session'])) {
        $vehicle_number = trim($_POST['vehicle_number']);
        $parking_slot_id = $_POST['parking_slot_id'];

        if (empty($vehicle_number) || empty($parking_slot_id)) {
            $error = 'Please fill in all fields.';
        } else {
            // Check if vehicle exists
            $vehicle = $vehicleModel->readByNumber($vehicle_number);
            if (!$vehicle) {
                $error = 'Vehicle not found.';
            } else {
                // Check if slot is available
                $slot = $slotModel->readById($parking_slot_id);
                if (!$slot || $slot['Is_occupied'] === 'yes') {
                    $error = 'Parking slot is not available.';
                } else {
                    // Start session
                    $result = $logModel->create($vehicle_number, $parking_slot_id, date('Y-m-d H:i:s'), null, 'active');
                    if ($result) {
                        // Update slot status
                        $slotModel->update($parking_slot_id, $slot['Slot_number'], $slot['Slot_type'], 'yes', $slot['Location']);
                        $message = 'Parking session started successfully!';
                    } else {
                        $error = 'Failed to start parking session.';
                    }
                }
            }
        }
    } elseif (isset($_POST['end_session'])) {
        $log_id = $_POST['log_id'];

        // End session
        // First getting the existing log data to preserve vehicle_number and parking_slot_id
        $existingLog = $logModel->readById($log_id);
        if ($existingLog) {
            $result = $logModel->update($log_id, $existingLog['vehicle_number'], $existingLog['parking_slot_id'], $existingLog['entry_time'], date('Y-m-d H:i:s'), 'deactive');
            if ($result) {
                // Get log details to update slot
                $log = $logModel->readById($log_id);
                if ($log) {
                    $slot = $slotModel->readById($log['parking_slot_id']);
                    if ($slot) {
                        // Update slot status to available
                        $slotModel->update($slot['Parking_slot_id'], $slot['Slot_number'], $slot['Slot_type'], 'no', $slot['Location']);
                    }
                }
                $message = 'Parking session ended successfully!';
            } else {
                $error = 'Failed to end parking session.';
            }
        } else {
            $error = 'Parking session not found.';
        }
    } elseif (isset($_POST['delete_session'])) {
        $log_id = $_POST['log_id'];
        $result = $logModel->delete($log_id);
        if ($result) {
            $message = 'Parking session deleted successfully!';
        } else {
            $error = 'Failed to delete parking session.';
        }
    }
}

// Get all sessions and available data
$sessions = $logModel->readAll();
$vehicles = $vehicleModel->readAll();
$availableSlots = $slotModel->getAvailableSlots();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Manage Sessions</title>
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
        <h2 class="mb-4">Manage Parking Sessions</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($can_manage_sessions): ?>
        <!-- Start New Session Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Start New Parking Session</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-5">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" required list="vehicles">
                            <datalist id="vehicles">
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo htmlspecialchars($vehicle['Vehicle_number']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-5">
                            <label for="parking_slot_id" class="form-label">Parking Slot</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="parking_slot_id" name="parking_slot_id" required>
                                    <option value="">Select Available Slot</option>
                                    <?php foreach ($availableSlots as $slot): ?>
                                        <option value="<?php echo $slot['Parking_slot_id']; ?>">
                                            <?php echo htmlspecialchars($slot['Slot_number']); ?> (<?php echo htmlspecialchars($slot['Slot_type']); ?>) - <?php echo htmlspecialchars($slot['Location']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="start_session" class="btn btn-success btn-sm w-100">Start Session</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Only administrators and security staff can start or end parking sessions.</div>
        <?php endif; ?>

        <!-- Active Sessions -->
        <div class="card">
            <div class="card-header">
                <h5>All Parking Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Vehicle Number</th>
                                <th>Slot</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['log_id']); ?></td>
                                <td><?php echo htmlspecialchars($session['vehicle_number']); ?></td>
                                <td><?php
                                    $slot = $slotModel->readById($session['parking_slot_id']);
                                    echo htmlspecialchars($slot ? $slot['Slot_number'] : 'N/A');
                                ?></td>
                                <td><?php echo htmlspecialchars(formatDateTime($session['entry_time'])); ?></td>
                                <td><?php echo htmlspecialchars(formatDateTime($session['exit_time'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $session['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($session['status'] === 'active' ? 'Checked In' : 'Checked Out'); ?>
                                    </span>
                                </td>
                                <td class="session-actions">
                                    <?php if ($can_manage_sessions): ?>
                                        <?php if ($session['status'] === 'active'): ?>
                                            <div class="btn-group" role="group">
                                                <form method="POST" action="" style="display: inline-block; margin: 0 0.125rem 0 0;">
                                                    <input type="hidden" name="log_id" value="<?php echo $session['log_id']; ?>">
                                                    <button type="submit" name="end_session" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to check out this vehicle?')">Check Out</button>
                                                </form>
                                                <form method="POST" action="" style="display: inline-block; margin: 0 0 0 0.125rem;">
                                                    <input type="hidden" name="log_id" value="<?php echo $session['log_id']; ?>">
                                                    <button type="submit" name="delete_session" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this session?')">Delete</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="log_id" value="<?php echo $session['log_id']; ?>">
                                                <button type="submit" name="delete_session" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this session?')">Delete</button>
                                            </form>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
