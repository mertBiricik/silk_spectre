<?php
session_start();
require_once '../includes/database.php';

$error = '';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Please enter both username and password';
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Silk Spectre</title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📊</text></svg>">
    <!-- Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Dracula theme config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dracula: {
                            bg: '#282a36',
                            currentLine: '#44475a',
                            selection: '#44475a',
                            foreground: '#f8f8f2',
                            comment: '#6272a4',
                            cyan: '#8be9fd',
                            green: '#50fa7b',
                            orange: '#ffb86c',
                            pink: '#ff79c6',
                            purple: '#bd93f9',
                            red: '#ff5555',
                            yellow: '#f1fa8c',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-dracula-bg text-dracula-foreground min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-8 bg-dracula-currentLine rounded-lg shadow-lg">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-dracula-purple">Silk Spectre</h1>
            <p class="text-dracula-comment mt-2">Admin Login</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="username" class="block text-dracula-cyan text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" id="username" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-dracula-cyan text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" id="password" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none transition-colors">
                    Sign In
                </button>
                <a href="../index.php" class="inline-block align-baseline font-bold text-sm text-dracula-cyan hover:text-dracula-green">
                    Back to Polls
                </a>
            </div>
        </form>
    </div>
</body>
</html> 