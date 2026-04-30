<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'models/ParkingSlot.php';
require_once 'models/SlotRequest.php';

$slotModel = new ParkingSlot();
$slotRequestModel = new SlotRequest();

$message = '';
$error = '';

// Get user role
$user_role = $_SESSION['role'];
$is_admin = ($user_role === 'Admin');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_slot_request']) && !$is_admin) {
        // Non-admin users can only submit requests
        $slot_number = trim($_POST['slot_number']);
        $slot_type = $_POST['slot_type'];
        $location = trim($_POST['location']);

        if (empty($slot_number) || empty($slot_type) || empty($location)) {
            $error = 'Please fill in all fields.';
        } else {
            // Create a request instead of directly adding
            $result = $slotRequestModel->create($slot_number, $slot_type, $location, 'Add');
            if ($result) {
                $message = 'Parking slot request submitted successfully! Please wait for admin approval.';
            } else {
                $error = 'Failed to submit parking slot request.';
            }
        }
    } elseif (isset($_POST['add_slot']) && $is_admin) {
        // Only admin can directly add slots
        $slot_number = trim($_POST['slot_number']);
        $slot_type = $_POST['slot_type'];
        $location = trim($_POST['location']);

        if (empty($slot_number) || empty($slot_type) || empty($location)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = $slotModel->create($slot_number, $slot_type, 'no', $location);
            if ($result) {
                $message = 'Parking slot added successfully!';
            } else {
                $error = 'Failed to add parking slot.';
            }
        }
    } elseif (isset($_POST['update_slot']) && $is_admin) {
        // Only admin can update slots
        $parking_slot_id = $_POST['parking_slot_id'];
        $slot_number = trim($_POST['slot_number']);
        $slot_type = $_POST['slot_type'];
        $is_occupied = $_POST['is_occupied'];
        $location = trim($_POST['location']);

        $result = $slotModel->update($parking_slot_id, $slot_number, $slot_type, $is_occupied, $location);
        if ($result) {
            $message = 'Parking slot updated successfully!';
        } else {
            $error = 'Failed to update parking slot.';
        }
    } elseif (isset($_POST['delete_slot']) && $is_admin) {
        // Only admin can delete slots
        $parking_slot_id = $_POST['parking_slot_id'];
        $result = $slotModel->delete($parking_slot_id);
        if ($result) {
            $message = 'Parking slot deleted successfully!';
        } else {
            $error = 'Failed to delete parking slot.';
        }
    } elseif (isset($_POST['approve_request']) && $is_admin) {
        // Admin can approve requests
        $request_id = $_POST['request_id'];
        $request = $slotRequestModel->readById($request_id);
        
        if ($request) {
            // Approve addition
            $result = $slotModel->create($request['Slot_number'], $request['Slot_type'], 'no', $request['Location']);
            if ($result) {
                $slotRequestModel->approve($request_id, $_SESSION['user_id']);
                $message = 'Slot request approved and added successfully!';
            } else {
                $error = 'Failed to add slot.';
            }
        } else {
            $error = 'Invalid request.';
        }
    } elseif (isset($_POST['reject_request']) && $is_admin) {
        // Admin can reject requests
        $request_id = $_POST['request_id'];
        $result = $slotRequestModel->reject($request_id, $_SESSION['user_id']);
        if ($result) {
            $message = 'Request rejected successfully!';
        } else {
            $error = 'Failed to reject request.';
        }
    }
}

// Get all slots
$slots = $slotModel->readAll();

// Get pending requests (for admin)
$pending_requests = [];
if ($is_admin) {
    $pending_requests = $slotRequestModel->readPendingRequests();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Manage Slots</title>
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
        <h2 class="mb-4">Manage Parking Slots</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add New Slot Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo $is_admin ? 'Add New Parking Slot' : 'Request New Parking Slot'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="slot_number" class="form-label">Slot Number</label>
                            <input type="text" class="form-control" id="slot_number" name="slot_number" required>
                        </div>
                        <div class="col-md-3">
                            <label for="slot_type" class="form-label">Slot Type</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="slot_type" name="slot_type" required>
                                    <option value="Car">Car</option>
                                    <option value="Bike">Bike</option>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <?php if ($is_admin): ?>
                                <button type="submit" name="add_slot" class="btn btn-primary btn-sm w-100">Add Slot</button>
                            <?php else: ?>
                                <button type="submit" name="add_slot_request" class="btn btn-primary btn-sm w-100">Request Slot</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slots List -->
        <div class="card">
            <div class="card-header">
                <h5>All Parking Slots</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Slot Number</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($slot['Parking_slot_id']); ?></td>
                                <td><?php echo htmlspecialchars($slot['Slot_number']); ?></td>
                                <td><?php echo htmlspecialchars($slot['Slot_type']); ?></td>
                                <td>
                                    <span class="badge <?php echo $slot['Is_occupied'] === 'yes' ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $slot['Is_occupied'] === 'yes' ? 'Occupied' : 'Available'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($slot['Location']); ?></td>
                                <td>
                                    <?php if ($is_admin): ?>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-warning" onclick="editSlot(<?php echo $slot['Parking_slot_id']; ?>, '<?php echo htmlspecialchars($slot['Slot_number']); ?>', '<?php echo htmlspecialchars($slot['Slot_type']); ?>', '<?php echo htmlspecialchars($slot['Is_occupied']); ?>', '<?php echo htmlspecialchars($slot['Location']); ?>')">Edit</button>
                                            <form method="POST" action="" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="parking_slot_id" value="<?php echo $slot['Parking_slot_id']; ?>">
                                                <button type="submit" name="delete_slot" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this slot?')">Delete</button>
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
                    <h5 class="modal-title">Edit Parking Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="parking_slot_id" id="edit_parking_slot_id">
                        <div class="mb-3">
                            <label for="edit_slot_number" class="form-label">Slot Number</label>
                            <input type="text" class="form-control" id="edit_slot_number" name="slot_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_slot_type" class="form-label">Slot Type</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="edit_slot_type" name="slot_type" required>
                                    <option value="Car">Car</option>
                                    <option value="Bike">Bike</option>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_is_occupied" class="form-label">Status</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="edit_is_occupied" name="is_occupied" required>
                                    <option value="no">Available</option>
                                    <option value="yes">Occupied</option>
                                </select>
                                <i class="fas fa-chevron-down select-icon"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_location" name="location" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_slot" class="btn btn-primary">Update Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        function editSlot(id, slotNumber, slotType, isOccupied, location) {
            document.getElementById('edit_parking_slot_id').value = id;
            document.getElementById('edit_slot_number').value = slotNumber;
            document.getElementById('edit_slot_type').value = slotType;
            document.getElementById('edit_is_occupied').value = isOccupied;
            document.getElementById('edit_location').value = location;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>

    <?php if ($is_admin && !empty($pending_requests)): ?>
    <!-- Pending Requests Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Pending Slot Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Slot Number</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['Slot_number']); ?></td>
                            <td><?php echo htmlspecialchars($request['Slot_type']); ?></td>
                            <td><?php echo htmlspecialchars($request['Location']); ?></td>
                            <td><?php echo htmlspecialchars($request['Request_date']); ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline-block; margin-right: 5px;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['Request_id']); ?>">
                                    <button type="submit" name="approve_request" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="POST" action="" style="display: inline-block;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['Request_id']); ?>">
                                    <button type="submit" name="reject_request" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
