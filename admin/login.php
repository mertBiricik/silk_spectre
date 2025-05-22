<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

$error = '';
$message = '';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Check for message parameter
if (isset($_GET['message'])) {
    $message = $_GET['message'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
                    },
                    screens: {
                        'xs': '480px',
                        // Default Tailwind breakpoints
                        'sm': '640px',
                        'md': '768px',
                        'lg': '1024px',
                        'xl': '1280px',
                        '2xl': '1536px',
                    }
                }
            }
        }
    </script>
    <!-- Custom Login CSS -->
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="bg-dracula-bg text-dracula-foreground min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md p-6 sm:p-8 bg-dracula-currentLine rounded-lg shadow-lg">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <span class="text-4xl mr-2">📊</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-dracula-purple">Silk Spectre</h1>
            </div>
            <p class="text-dracula-comment">Admin Login</p>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-green p-4 mb-6" role="alert">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-5">
            <div>
                <label for="username" class="block text-dracula-cyan text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" id="username" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
            </div>
            
            <div>
                <label for="password" class="block text-dracula-cyan text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" id="password" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <button type="submit" class="w-full sm:w-auto bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-3 px-6 rounded focus:outline-none transition-colors">
                    Sign In
                </button>
                <a href="../index.php" class="w-full sm:w-auto text-center bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground font-bold py-3 px-6 rounded focus:outline-none transition-colors">
                    Back to Polls
                </a>
            </div>
        </form>
    </div>
</body>
</html> 