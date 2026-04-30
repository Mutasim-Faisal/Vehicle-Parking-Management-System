<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Set timezone to Bangladesh (UTC+6)
date_default_timezone_set('Asia/Dhaka');

// Function to format datetime to DD-MM-YYYY 12-hour format
function formatDateTime($datetime) {
    if (!$datetime || $datetime === 'Active') return 'Active';
    $dt = new DateTime($datetime);
    return $dt->format('d-m-Y h:i A');
}

require_once 'models/ParkingSlot.php';
require_once 'models/Vehicle.php';
require_once 'models/ParkingLog.php';
require_once 'models/Payment.php';

$slotModel = new ParkingSlot();
$vehicleModel = new Vehicle();
$logModel = new ParkingLog();
$paymentModel = new Payment();

// Get statistics
$totalSlots = count($slotModel->readAll());
$availableSlots = count($slotModel->getAvailableSlots());
$occupiedSlots = $totalSlots - $availableSlots;
$totalVehicles = count($vehicleModel->readAll());
$totalSessions = count($logModel->readAll());
$activeSessions = count($logModel->getActiveLogs());
$completedSessions = $totalSessions - $activeSessions;
$totalPayments = count($paymentModel->readAll());

// Calculate total revenue
$payments = $paymentModel->readAll();
$totalRevenue = 0;
foreach ($payments as $payment) {
    $totalRevenue += $payment['Amount'];
}

// Get recent sessions
$recentSessions = $logModel->readAll();
usort($recentSessions, function($a, $b) {
    return strtotime($b['entry_time']) - strtotime($a['entry_time']);
});
$recentSessions = array_slice($recentSessions, 0, 10);

// Get slot utilization by type
$slots = $slotModel->readAll();
$slotTypes = [];
foreach ($slots as $slot) {
    $type = $slot['Slot_type'];
    if (!isset($slotTypes[$type])) {
        $slotTypes[$type] = ['total' => 0, 'occupied' => 0];
    }
    $slotTypes[$type]['total']++;
    if ($slot['Is_occupied'] === 'yes') {
        $slotTypes[$type]['occupied']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>EWU Parking System</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                <a href="dashboard.php" class="btn-logout">Dashboard</a>
                <a href="dashboard.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container mt-4">
        <h2 class="mb-4">System Reports & Analytics</h2>

        <!-- Key Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalSlots; ?></h5>
                        <p class="card-text">Total Parking Slots</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $availableSlots; ?></h5>
                        <p class="card-text">Available Slots</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalVehicles; ?></h5>
                        <p class="card-text">Registered Vehicles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">৳<?php echo number_format($totalRevenue, 2); ?></h5>
                        <p class="card-text">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Session Statistics</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="sessionChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Slot Utilization by Type</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="slotChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sessions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Recent Parking Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Vehicle Number</th>
                                <th>Slot</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSessions as $session): ?>
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
                                        <?php echo htmlspecialchars(ucfirst($session['status'])); ?>
                                    </span>
                                </td>
                                <td><?php
                                    if ($session['exit_time']) {
                                        $entry = strtotime($session['entry_time']);
                                        $exit = strtotime($session['exit_time']);
                                        $duration = $exit - $entry;
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $hours . 'h ' . $minutes . 'm';
                                    } else {
                                        echo 'Active';
                                    }
                                ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="card">
            <div class="card-header">
                <h5>Payment Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4><?php echo $totalPayments; ?></h4>
                            <p>Total Payments</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4>৳<?php echo number_format($totalRevenue, 2); ?></h4>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4>৳<?php echo $totalPayments > 0 ? number_format($totalRevenue / $totalPayments, 2) : '0.00'; ?></h4>
                            <p>Average Payment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Session Statistics Chart
        const sessionCtx = document.getElementById('sessionChart').getContext('2d');
        new Chart(sessionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active Sessions', 'Completed Sessions'],
                datasets: [{
                    data: [<?php echo $activeSessions; ?>, <?php echo $completedSessions; ?>],
                    backgroundColor: ['#28a745', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Slot Utilization Chart
        const slotCtx = document.getElementById('slotChart').getContext('2d');
        new Chart(slotCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($slotTypes)); ?>,
                datasets: [{
                    label: 'Occupied',
                    data: <?php echo json_encode(array_column($slotTypes, 'occupied')); ?>,
                    backgroundColor: '#dc3545'
                }, {
                    label: 'Available',
                    data: <?php
                        $available = [];
                        foreach ($slotTypes as $type => $data) {
                            $available[] = $data['total'] - $data['occupied'];
                        }
                        echo json_encode($available);
                    ?>,
                    backgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });
    </script>
</body>
</html>
