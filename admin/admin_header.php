<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poll System Admin</title>
    <!-- Alpine.js - fully qualified URL and specific version -->
    <script defer src="https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js"></script>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="bg-dracula-currentLine shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-dracula-pink mr-6">Poll System</h1>
                    <nav class="hidden md:flex space-x-1">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                        <a href="create_poll.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_poll.php' ? 'active' : ''; ?>">Create Poll</a>
                        <a href="../index.php" class="nav-link">View Site</a>
                    </nav>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['admin_username'])): ?>
                        <span class="text-dracula-comment">Hello, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                        <a href="logout.php" class="bg-dracula-red hover:bg-dracula-red/80 text-dracula-bg px-3 py-1 rounded text-sm transition-colors">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div class="md:hidden mt-4">
                <nav class="flex flex-col space-y-2">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="create_poll.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_poll.php' ? 'active' : ''; ?>">Create Poll</a>
                    <a href="../index.php" class="nav-link">View Site</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container mx-auto px-4 py-6">
        <!-- Small Alpine.js test component -->
        <div x-data="{ alpineLoaded: true }" class="hidden">
            <span x-show="alpineLoaded">Alpine.js is working</span>
        </div>
        
        <!-- Main content will be inserted here --> 
    </main>
</body>
</html> 