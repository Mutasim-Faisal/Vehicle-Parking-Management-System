<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'models/Vehicle.php';
require_once 'models/Student.php';
require_once 'models/Faculty.php';
require_once 'models/Staff.php';
require_once 'models/VehicleRequest.php';

$vehicleModel = new Vehicle();
$studentModel = new Student();
$facultyModel = new Faculty();
$staffModel = new Staff();
$vehicleRequestModel = new VehicleRequest();

$message = '';
$error = '';

// Get user role and determine allowed owner types
$user_role = $_SESSION['role'];
$allowed_owner_types = [];
$is_admin = ($user_role === 'Admin');
$is_security = ($user_role === 'Security');

// For security staff, redirect to a dedicated page
if ($is_security) {
    // We'll handle security separately
    // For now, just showing their own requests if any
}

if ($is_admin) {
    $allowed_owner_types = ['Student', 'Faculty', 'Staff', 'Security', 'Visitor'];
    $default_owner_type = '';
} elseif ($user_role === 'Student') {
    $allowed_owner_types = ['Student'];
    $default_owner_type = 'Student';
} elseif ($user_role === 'Faculty') {
    $allowed_owner_types = ['Faculty'];
    $default_owner_type = 'Faculty';
} elseif ($user_role === 'Staff') {
    $allowed_owner_types = ['Staff'];
    $default_owner_type = 'Staff';
} elseif ($user_role === 'Security') {
    $allowed_owner_types = ['Security'];
    $default_owner_type = 'Security';
} else {
    $allowed_owner_types = ['Visitor'];
    $default_owner_type = 'Visitor';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_vehicle_request']) && !$is_admin) {
        // Non-admin users can only submit requests
        $vehicle_number = trim($_POST['vehicle_number']);
        $vehicle_type = $_POST['vehicle_type'];
        $owner_type = $_POST['owner_type'];
        $owner_id = trim($_POST['owner_id']);

        if (empty($vehicle_number) || empty($vehicle_type) || empty($owner_type)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Create a request instead of directly adding
            $result = $vehicleRequestModel->create($vehicle_number, $vehicle_type, $owner_type, $owner_id, 'Add');
            if ($result) {
                $message = 'Vehicle addition request submitted successfully! Please wait for admin approval.';
            } else {
                $error = 'Failed to submit vehicle addition request.';
            }
        }
    } elseif (isset($_POST['add_vehicle']) && $is_admin) {
        // Only admin can directly add vehicles
        $vehicle_number = trim($_POST['vehicle_number']);
        $vehicle_type = $_POST['vehicle_type'];
        $owner_type = $_POST['owner_type'];
        $owner_id = trim($_POST['owner_id']);

        if (empty($vehicle_number) || empty($vehicle_type) || empty($owner_type)) {
            $error = 'Please fill in all required fields.';
        } else {
            $result = $vehicleModel->create($vehicle_number, $vehicle_type, $owner_type, $owner_id);
            if ($result) {
                $message = 'Vehicle added successfully!';
            } else {
                $error = 'Failed to add vehicle.';
            }
        }
    } elseif (isset($_POST['update_vehicle']) && $is_admin) {
        // Only admin can update vehicles
        $vehicle_number = $_POST['vehicle_number'];
        $vehicle_type = $_POST['vehicle_type'];
        $owner_type = $_POST['owner_type'];
        $owner_id = trim($_POST['owner_id']);

        $result = $vehicleModel->update($vehicle_number, $vehicle_type, $owner_type, $owner_id);
        if ($result) {
            $message = 'Vehicle updated successfully!';
        } else {
            $error = 'Failed to update vehicle.';
        }
    } elseif (isset($_POST['delete_vehicle_request']) && !$is_admin) {
        // Non-admin users can only submit delete requests
        $vehicle_number = $_POST['vehicle_number'];
        
        // Get the vehicle to ensure it belongs to the user
        $vehicle = $vehicleModel->readByNumber($vehicle_number);
        if ($vehicle && 
            (($user_role === 'Student' && $vehicle['Owner_type'] === 'Student' && $vehicle['Owner_id'] == $_SESSION['user_id']) ||
             ($user_role === 'Faculty' && $vehicle['Owner_type'] === 'Faculty' && $vehicle['Owner_id'] == $_SESSION['user_id']) ||
             ($user_role === 'Staff' && $vehicle['Owner_type'] === 'Staff' && $vehicle['Owner_id'] == $_SESSION['user_id']))) {
            
            $result = $vehicleRequestModel->create($vehicle_number, $vehicle['Vehicle_Type'], $vehicle['Owner_type'], $vehicle['Owner_id'], 'Delete');
            if ($result) {
                $message = 'Vehicle deletion request submitted successfully! Please wait for admin approval.';
            } else {
                $error = 'Failed to submit vehicle deletion request.';
            }
        } else {
            $error = 'You can only request deletion of your own vehicles.';
        }
    } elseif (isset($_POST['delete_vehicle']) && $is_admin) {
        // Only admin can directly delete vehicles
        $vehicle_number = $_POST['vehicle_number'];
        $result = $vehicleModel->delete($vehicle_number);
        if ($result) {
            $message = 'Vehicle deleted successfully!';
        } else {
            $error = 'Failed to delete vehicle.';
        }
    } elseif (isset($_POST['approve_request']) && $is_admin) {
        // Admin can approve requests
        $request_id = $_POST['request_id'];
        $request = $vehicleRequestModel->readById($request_id);
        
        if ($request) {
            if ($request['Request_type'] === 'Add') {
                // Approve addition
                $result = $vehicleModel->create($request['Vehicle_number'], $request['Vehicle_Type'], $request['Owner_type'], $request['Owner_id']);
                if ($result) {
                    $vehicleRequestModel->approve($request_id, $_SESSION['user_id']);
                    $message = 'Vehicle addition approved and added successfully!';
                } else {
                    $error = 'Failed to add vehicle.';
                }
            } elseif ($request['Request_type'] === 'Delete') {
                // Approve deletion
                $result = $vehicleModel->delete($request['Vehicle_number']);
                if ($result) {
                    $vehicleRequestModel->approve($request_id, $_SESSION['user_id']);
                    $message = 'Vehicle deletion approved and removed successfully!';
                } else {
                    $error = 'Failed to delete vehicle.';
                }
            }
        } else {
            $error = 'Invalid request.';
        }
    } elseif (isset($_POST['reject_request']) && $is_admin) {
        // Admin can reject requests
        $request_id = $_POST['request_id'];
        $result = $vehicleRequestModel->reject($request_id, $_SESSION['user_id']);
        if ($result) {
            $message = 'Request rejected successfully!';
        } else {
            $error = 'Failed to reject request.';
        }
    }
}

// Get all vehicles
$vehicles = $vehicleModel->readAll();

// Get user's requests (for non-admin users)
$user_requests = [];
if (!$is_admin && !$is_security) {
    if ($user_role === 'Student') {
        $user_requests = $vehicleRequestModel->readByUserId('Student', $_SESSION['user_id']);
    } elseif ($user_role === 'Faculty') {
        $user_requests = $vehicleRequestModel->readByUserId('Faculty', $_SESSION['user_id']);
    } elseif ($user_role === 'Staff') {
        $user_requests = $vehicleRequestModel->readByUserId('Staff', $_SESSION['user_id']);
    }
}

// Get pending requests (for admin)
$pending_requests = [];
if ($is_admin) {
    $pending_requests = $vehicleRequestModel->readPendingRequests();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Manage Vehicles</title>
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
        <h2 class="mb-4">Manage Vehicles</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add New Vehicle Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo $is_admin ? 'Add New Vehicle' : 'Request New Vehicle'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" required>
                        </div>
                        <div class="col-md-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type</label>
                            <input type="text" class="form-control" id="vehicle_type" name="vehicle_type" required>
                        </div>
                        <div class="col-md-3">
                            <label for="owner_type" class="form-label">Owner Type</label>
                            <select class="form-control" id="owner_type" name="owner_type" required onchange="toggleOwnerId()" <?php echo !$is_admin ? 'disabled' : ''; ?>>
                                <?php if ($is_admin): ?>
                                    <option value="">Select Type</option>
                                <?php endif; ?>
                                <?php foreach ($allowed_owner_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (!$is_admin && $type === $default_owner_type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!$is_admin): ?>
                                <input type="hidden" name="owner_type" value="<?php echo htmlspecialchars($default_owner_type); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="owner_id" class="form-label">Owner ID</label>
                            <input type="text" class="form-control" id="owner_id" name="owner_id" <?php echo !$is_admin ? 'value="'.$_SESSION['user_id'].'" readonly' : ''; ?>>
                            <small class="text-muted">Leave empty for visitors</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php if ($is_admin): ?>
                            <button type="submit" name="add_vehicle" class="btn btn-primary">Add Vehicle</button>
                        <?php else: ?>
                            <button type="submit" name="add_vehicle_request" class="btn btn-primary">Request Vehicle</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vehicles List -->
        <div class="card">
            <div class="card-header">
                <h5>All Vehicles</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle Number</th>
                                <th>Type</th>
                                <th>Owner Type</th>
                                <th>Owner ID</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vehicle['Vehicle_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['Vehicle_Type']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['Owner_type']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['Owner_id'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($is_admin): ?>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-warning" onclick="editVehicle('<?php echo htmlspecialchars($vehicle['Vehicle_number']); ?>', '<?php echo htmlspecialchars($vehicle['Vehicle_Type']); ?>', '<?php echo htmlspecialchars($vehicle['Owner_type']); ?>', '<?php echo htmlspecialchars($vehicle['Owner_id'] ?? ''); ?>')">Edit</button>
                                            <form method="POST" action="" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="vehicle_number" value="<?php echo htmlspecialchars($vehicle['Vehicle_number']); ?>">
                                                <button type="submit" name="delete_vehicle" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this vehicle?')">Delete</button>
                                            </form>
                                        </div>
                                    <?php elseif (($user_role === 'Student' && $vehicle['Owner_type'] === 'Student' && $vehicle['Owner_id'] == $_SESSION['user_id']) ||
                                                 ($user_role === 'Faculty' && $vehicle['Owner_type'] === 'Faculty' && $vehicle['Owner_id'] == $_SESSION['user_id']) ||
                                                 ($user_role === 'Staff' && $vehicle['Owner_type'] === 'Staff' && $vehicle['Owner_id'] == $_SESSION['user_id'])): ?>
                                        <form method="POST" action="" style="display: inline-block; margin: 0;">
                                            <input type="hidden" name="vehicle_number" value="<?php echo htmlspecialchars($vehicle['Vehicle_number']); ?>">
                                            <button type="submit" name="delete_vehicle_request" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to request deletion of this vehicle?')">Request Deletion</button>
                                        </form>
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
                    <h5 class="modal-title">Edit Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="vehicle_number" id="edit_vehicle_number">
                        <div class="mb-3">
                            <label for="edit_vehicle_type" class="form-label">Vehicle Type</label>
                            <input type="text" class="form-control" id="edit_vehicle_type" name="vehicle_type" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_owner_type" class="form-label">Owner Type</label>
                            <select class="form-control" id="edit_owner_type" name="owner_type" required onchange="toggleEditOwnerId()" <?php echo !$is_admin ? 'disabled' : ''; ?>>
                                <?php foreach ($allowed_owner_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (!$is_admin && $type === $default_owner_type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_owner_id" class="form-label">Owner ID</label>
                            <input type="text" class="form-control" id="edit_owner_id" name="owner_id">
                            <small class="text-muted">Leave empty for visitors</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_vehicle" class="btn btn-primary">Update Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        function toggleOwnerId() {
            const ownerType = document.getElementById('owner_type').value;
            const ownerIdField = document.getElementById('owner_id');
            ownerIdField.required = ownerType !== 'Visitor';
        }

        function toggleEditOwnerId() {
            const ownerType = document.getElementById('edit_owner_type').value;
            const ownerIdField = document.getElementById('edit_owner_id');
            ownerIdField.required = ownerType !== 'Visitor';
        }

        function editVehicle(vehicleNumber, vehicleType, ownerType, ownerId) {
            document.getElementById('edit_vehicle_number').value = vehicleNumber;
            document.getElementById('edit_vehicle_type').value = vehicleType;
            document.getElementById('edit_owner_type').value = ownerType;
            document.getElementById('edit_owner_id').value = ownerId;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>

    <?php if (!$is_admin && !empty($user_requests)): ?>
    <!-- User Requests Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Your Vehicle Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Vehicle Number</th>
                            <th>Type</th>
                            <th>Request Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['Vehicle_number']); ?></td>
                            <td><?php echo htmlspecialchars($request['Vehicle_Type']); ?></td>
                            <td><?php echo htmlspecialchars($request['Request_type']); ?></td>
                            <td>
                                <?php if ($request['Status'] === 'Pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($request['Status'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['Request_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_admin && !empty($pending_requests)): ?>
    <!-- Pending Requests Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Pending Vehicle Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Vehicle Number</th>
                            <th>Type</th>
                            <th>Owner Type</th>
                            <th>Owner ID</th>
                            <th>Request Type</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['Vehicle_number']); ?></td>
                            <td><?php echo htmlspecialchars($request['Vehicle_Type']); ?></td>
                            <td><?php echo htmlspecialchars($request['Owner_type']); ?></td>
                            <td><?php echo htmlspecialchars($request['Owner_id']); ?></td>
                            <td><?php echo htmlspecialchars($request['Request_type']); ?></td>
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
