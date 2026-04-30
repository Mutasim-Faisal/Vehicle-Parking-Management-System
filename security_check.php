<?php
session_start();

// Check if user is logged in and is security staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Security') {
    header('Location: login.php');
    exit();
}

require_once 'models/Vehicle.php';
require_once 'models/Student.php';
require_once 'models/Faculty.php';
require_once 'models/Staff.php';
require_once 'models/ParkingLog.php';
require_once 'models/ParkingSlot.php';

$vehicleModel = new Vehicle();
$studentModel = new Student();
$facultyModel = new Faculty();
$staffModel = new Staff();
$logModel = new ParkingLog();
$slotModel = new ParkingSlot();

$message = '';
$error = '';
$search_result = null;
$vehicle_logs = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_user'])) {
        $search_term = trim($_POST['search_term']);
        
        if (empty($search_term)) {
            $error = 'Please enter a search term (ID or vehicle number).';
        } else {
            // First try to find by vehicle number
            $vehicle = $vehicleModel->readByNumber($search_term);
            
            if ($vehicle) {
                $search_result = $vehicle;
                
                // Get owner details based on owner type
                if ($vehicle['Owner_type'] === 'Student') {
                    $search_result['owner_details'] = $studentModel->readById($vehicle['Owner_id']);
                } elseif ($vehicle['Owner_type'] === 'Faculty') {
                    $search_result['owner_details'] = $facultyModel->readById($vehicle['Owner_id']);
                } elseif ($vehicle['Owner_type'] === 'Staff') {
                    $search_result['owner_details'] = $staffModel->readById($vehicle['Owner_id']);
                }
                
                // Get parking logs for this vehicle
                $vehicle_logs = $logModel->getLogsByVehicle($search_term);
            } else {
                // Try to find by owner ID
                $student = $studentModel->readById($search_term);
                $faculty = $facultyModel->readById($search_term);
                $staff = $staffModel->readById($search_term);
                
                if ($student) {
                    $search_result = [
                        'Owner_type' => 'Student',
                        'Owner_id' => $search_term,
                        'owner_details' => $student
                    ];
                    
                    // Get vehicles owned by this student
                    $search_result['vehicles'] = $vehicleModel->getVehiclesByOwner('Student', $search_term);
                } elseif ($faculty) {
                    $search_result = [
                        'Owner_type' => 'Faculty',
                        'Owner_id' => $search_term,
                        'owner_details' => $faculty
                    ];
                    
                    // Get vehicles owned by this faculty
                    $search_result['vehicles'] = $vehicleModel->getVehiclesByOwner('Faculty', $search_term);
                } elseif ($staff) {
                    $search_result = [
                        'Owner_type' => 'Staff',
                        'Owner_id' => $search_term,
                        'owner_details' => $staff
                    ];
                    
                    // Get vehicles owned by this staff
                    $search_result['vehicles'] = $vehicleModel->getVehiclesByOwner('Staff', $search_term);
                } else {
                    $error = 'No user or vehicle found with that ID or number.';
                }
            }
        }
    } elseif (isset($_POST['check_in'])) {
        // Handle check-in
        $vehicle_number = trim($_POST['vehicle_number']);
        $parking_slot_id = $_POST['parking_slot_id'];
        
        if (empty($vehicle_number) || empty($parking_slot_id)) {
            $error = 'Please select a vehicle and parking slot.';
        } else {
            // Check if vehicle exists
            $vehicle = $vehicleModel->readByNumber($vehicle_number);
            if (!$vehicle) {
                $error = 'Vehicle not found.';
            } else {
                // Check if slot is available
                $slot = $slotModel->readById($parking_slot_id);
                if (!$slot || $slot['Is_occupied'] === 'yes') {
                    $error = 'Selected parking slot is not available.';
                } else {
                    // Start session
                    $result = $logModel->create($vehicle_number, $parking_slot_id, date('Y-m-d H:i:s'), null, 'active');
                    if ($result) {
                        // Update slot status
                        $slotModel->update($parking_slot_id, $slot['Slot_number'], $slot['Slot_type'], 'yes', $slot['Location']);
                        $message = 'Vehicle checked in successfully!';
                        
                        // Refresh the search to show updated information
                        $search_result = $vehicleModel->readByNumber($vehicle_number);
                        
                        // Get owner details based on owner type
                        if ($search_result && isset($search_result['Owner_type'])) {
                            if ($search_result['Owner_type'] === 'Student') {
                                $search_result['owner_details'] = $studentModel->readById($search_result['Owner_id']);
                            } elseif ($search_result['Owner_type'] === 'Faculty') {
                                $search_result['owner_details'] = $facultyModel->readById($search_result['Owner_id']);
                            } elseif ($search_result['Owner_type'] === 'Staff') {
                                $search_result['owner_details'] = $staffModel->readById($search_result['Owner_id']);
                            }
                        }
                        
                        // Get parking logs for this vehicle
                        $vehicle_logs = $logModel->getLogsByVehicle($vehicle_number);
                    } else {
                        $error = 'Failed to check in vehicle.';
                    }
                }
            }
        }
    } elseif (isset($_POST['check_out'])) {
        // Handle check-out
        $log_id = $_POST['log_id'];
        
        // End session
        // First get the existing log data to preserve vehicle_number and parking_slot_id
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
                $message = 'Vehicle checked out successfully!';
                
                // Refresh the search to show updated information
                $search_result = $vehicleModel->readByNumber($existingLog['vehicle_number']);
                
                // Get owner details based on owner type
                if ($search_result && isset($search_result['Owner_type'])) {
                    if ($search_result['Owner_type'] === 'Student') {
                        $search_result['owner_details'] = $studentModel->readById($search_result['Owner_id']);
                    } elseif ($search_result['Owner_type'] === 'Faculty') {
                        $search_result['owner_details'] = $facultyModel->readById($search_result['Owner_id']);
                    } elseif ($search_result['Owner_type'] === 'Staff') {
                        $search_result['owner_details'] = $staffModel->readById($search_result['Owner_id']);
                    }
                }
                
                // Get parking logs for this vehicle
                $vehicle_logs = $logModel->getLogsByVehicle($existingLog['vehicle_number']);
            } else {
                $error = 'Failed to check out vehicle.';
            }
        } else {
            $error = 'Parking session not found.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Security Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <a href="dashboard.php" class="btn-logout">Dashboard</a>
                <a href="dashboard.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container mt-4">
        <h2 class="mb-4">Security Check</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Search User or Vehicle</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="search_term" class="form-label">Enter Vehicle Number or User ID</label>
                            <input type="text" class="form-control" id="search_term" name="search_term" placeholder="e.g., ABC123 or 2021001" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" name="search_user" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search Results -->
        <?php if ($search_result): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>Search Results</h5>
            </div>
            <div class="card-body">
                <?php if (isset($search_result['Vehicle_number'])): ?>
                    <!-- Display vehicle information -->
                    <h6>Vehicle Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($search_result['Vehicle_number']); ?></p>
                            <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($search_result['Vehicle_Type']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Owner Type:</strong> <?php echo htmlspecialchars($search_result['Owner_type']); ?></p>
                            <p><strong>Owner ID:</strong> <?php echo htmlspecialchars($search_result['Owner_id']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (isset($search_result['owner_details'])): ?>
                    <h6>Owner Information</h6>
                    <div class="row mb-3">
                        <?php if ($search_result['Owner_type'] === 'Student' && isset($search_result['owner_details']['Student_name'])): ?>
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Student_name']); ?></p>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Student_id']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Department']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Email']); ?></p>
                            </div>
                        <?php elseif ($search_result['Owner_type'] === 'Faculty' && isset($search_result['owner_details']['Faculty_name'])): ?>
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Faculty_name']); ?></p>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Faculty_id']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Department']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($search_result['owner_details']['E_mail']); ?></p>
                            </div>
                        <?php elseif ($search_result['Owner_type'] === 'Staff' && isset($search_result['owner_details']['Staff_name'])): ?>
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Staff_name']); ?></p>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Staff_id']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Email']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Contact_number']); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="col-12">
                                <p><strong>User details not available.</strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Check-in Form -->
                    <h6>Check-in Vehicle</h6>
                    <form method="POST" action="" class="mb-3">
                        <input type="hidden" name="vehicle_number" value="<?php echo htmlspecialchars($search_result['Vehicle_number']); ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="select-wrapper">
                                    <select class="form-control" name="parking_slot_id" required>
                                        <option value="">Select Available Parking Slot</option>
                                        <?php 
                                        $availableSlots = $slotModel->getAvailableSlots();
                                        foreach ($availableSlots as $slot): ?>
                                            <option value="<?php echo $slot['Parking_slot_id']; ?>">
                                                <?php echo htmlspecialchars($slot['Slot_number']); ?> (<?php echo htmlspecialchars($slot['Slot_type']); ?>) - <?php echo htmlspecialchars($slot['Location']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down select-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="check_in" class="btn btn-success w-100">Check In</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Display user information -->
                    <h6>User Information</h6>
                    <div class="row mb-3">
                        <?php if (isset($search_result['owner_details']) && $search_result['Owner_type'] === 'Student'): ?>
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Student_name'] ?? ''); ?></p>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Student_id'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Department'] ?? ''); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Email'] ?? ''); ?></p>
                            </div>
                        <?php elseif (isset($search_result['owner_details']) && $search_result['Owner_type'] === 'Faculty'): ?>
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Faculty_name'] ?? ''); ?></p>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Faculty_id'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Department'] ?? ''); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($search_result['owner_details']['E_mail'] ?? ''); ?></p>
                            </div>
                        <?php elseif (isset($search_result['owner_details']) && $search_result['Owner_type'] === 'Staff'): ?>
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Staff_name'] ?? ''); ?></p>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Staff_id'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Email'] ?? ''); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($search_result['owner_details']['Contact_number'] ?? ''); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="col-12">
                                <p><strong>No user details found for this ID.</strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($search_result['vehicles']) && !empty($search_result['vehicles'])): ?>
                    <h6>Vehicles</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Vehicle Number</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_result['vehicles'] as $vehicle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vehicle['Vehicle_number']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['Vehicle_Type']); ?></td>
                                    <td>
                                        <!-- Check-in Form for each vehicle -->
                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="vehicle_number" value="<?php echo htmlspecialchars($vehicle['Vehicle_number']); ?>">
                                            <div class="select-wrapper" style="display: inline-block; width: auto;">
                                                <select class="form-control form-control-sm" name="parking_slot_id" required style="display: inline-block; width: auto;">
                                                    <option value="">Slot</option>
                                                    <?php 
                                                    $availableSlots = $slotModel->getAvailableSlots();
                                                    foreach ($availableSlots as $slot): ?>
                                                        <option value="<?php echo $slot['Parking_slot_id']; ?>">
                                                            <?php echo htmlspecialchars($slot['Slot_number']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" name="check_in" class="btn btn-sm btn-success">Check In</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>No vehicles registered for this user.</p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($vehicle_logs)): ?>
                <h6>Parking History</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicle_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['entry_time']); ?></td>
                                <td><?php echo htmlspecialchars($log['exit_time'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($log['status'] === 'active'): ?>
                                        <span class="badge bg-success">Checked In</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Checked Out</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'active'): ?>
                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="log_id" value="<?php echo $log['log_id']; ?>">
                                            <button type="submit" name="check_out" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to check out this vehicle?')">Check Out</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>