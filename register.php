<?php 
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Initialize variables to prevent undefined variable warnings
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'models/User.php';
    $userModel = new User();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username already exists
        $existingUser = $userModel->readByUsername($username);
        if ($existingUser) {
            $error = 'Username already exists.';
        } else {
            // Create new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $result = $userModel->create($username, $password_hash, $role);
            
            if ($result) {
                // Optional success message for 1 second (not required)
                $_SESSION['registration_success'] = 'Account created successfully! Please login.';
                header("Location: login.php");
                exit();
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWU Vehicle Parking Management - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="login-container">
        <div class="login-card">
            <h2>EWU Parking System</h2>
            <p class="text-muted mb-4">Create a new account</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <div class="select-wrapper">
                        <select id="role" name="role" required class="form-control">
                            <option value="">Select Role</option>
                            <option value="Security" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Security') ? 'selected' : ''; ?>>Security</option>
                            <option value="Student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Student') ? 'selected' : ''; ?>>Student</option>
                            <option value="Faculty" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Faculty') ? 'selected' : ''; ?>>Faculty</option>
                            <option value="Staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                        </select>
                        <i class="fas fa-chevron-down select-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye password-icon" id="passwordIcon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye confirm-password-icon" id="confirmPasswordIcon"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Create Account</button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted">Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('passwordIcon').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('confirmPasswordIcon').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                confirmPasswordIcon.classList.remove('fa-eye');
                confirmPasswordIcon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                confirmPasswordIcon.classList.remove('fa-eye-slash');
                confirmPasswordIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>