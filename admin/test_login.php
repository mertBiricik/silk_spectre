<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Clear session
session_unset();
session_destroy();
session_start();

$error = '';
$debug_output = '';

// Test login with testadmin credentials
$username = 'testadmin';
$password = 'testpass123';

try {
    // Debug connection
    $debug_output .= "Connected to database successfully\n";
    
    // Get admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $debug_output .= "Admin found:\n";
        $debug_output .= "ID: " . $admin['id'] . "\n";
        $debug_output .= "Username: " . $admin['username'] . "\n";
        $debug_output .= "Password hash: " . $admin['password'] . "\n";
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            $debug_output .= "Password verified successfully!\n";
            
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            $debug_output .= "Session variables set:\n";
            $debug_output .= "admin_id: " . $_SESSION['admin_id'] . "\n";
            $debug_output .= "admin_username: " . $_SESSION['admin_username'] . "\n";
            
            // Store session ID
            $debug_output .= "Session ID: " . session_id() . "\n";
            
            // Redirect with debug output first
            $success = true;
        } else {
            $debug_output .= "Password verification failed!\n";
            $error = 'Invalid password';
        }
    } else {
        $debug_output .= "Admin not found!\n";
        $error = 'Admin not found';
    }
} catch (PDOException $e) {
    $debug_output .= "Database error: " . $e->getMessage() . "\n";
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Admin Login Debug</title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 0 auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Test Admin Login Debug</h1>
    
    <?php if (isset($success) && $success): ?>
        <div class="success">
            <p>Login successful! You should be redirected to the admin panel.</p>
            <p>If automatic redirect doesn't work, <a href="index.php">click here</a>.</p>
            <script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 5000);
            </script>
        </div>
    <?php elseif ($error): ?>
        <div class="error">
            <p>Login error: <?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <h2>Debug Output</h2>
    <pre><?php echo $debug_output; ?></pre>
    
    <h2>Session Data</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>Cookie Data</h2>
    <pre><?php print_r($_COOKIE); ?></pre>
    
    <p><a href="login.php">Go back to regular login</a></p>
</body>
</html> 