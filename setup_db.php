<?php
// Generate bcrypt hash for 'admin123'
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Generated hash for 'admin123': $hash<br><br>";

// Database setup
require_once 'db.php';

$conn = getDBConnection();

// Read and execute schema.sql
$sql = file_get_contents('schema.sql');

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

// Execute each statement
$errors = [];
foreach ($statements as $statement) {
    if (!empty($statement)) {
        // Replace the placeholder hash with the generated hash
        $statement = str_replace('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', $hash, $statement);

        if ($conn->query($statement) === FALSE) {
            $errors[] = "Error executing: " . $statement . "<br>Error: " . $conn->error;
        }
    }
}

closeDBConnection($conn);

if (empty($errors)) {
    echo "<div style='color: green; font-weight: bold;'>Database setup completed successfully!</div>";
    echo "<p>You can now <a href='login.php'>login</a> with username: admin, password: admin123</p>";
    echo "<p>Or <a href='register.php'>register</a> a new account</p>";
} else {
    echo "<div style='color: red; font-weight: bold;'>Database setup failed:</div>";
    foreach ($errors as $error) {
        echo "<div style='color: red;'>$error</div>";
    }
}
?>
