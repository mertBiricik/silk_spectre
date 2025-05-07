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
    <style>
        /* Dracula Theme Colors */
        :root {
            --dracula-bg: #282a36;
            --dracula-currentLine: #44475a;
            --dracula-selection: #44475a;
            --dracula-foreground: #f8f8f2;
            --dracula-comment: #6272a4;
            --dracula-cyan: #8be9fd;
            --dracula-green: #50fa7b;
            --dracula-orange: #ffb86c;
            --dracula-pink: #ff79c6;
            --dracula-purple: #bd93f9;
            --dracula-red: #ff5555;
            --dracula-yellow: #f1fa8c;
        }
        
        body {
            background-color: var(--dracula-bg);
            color: var(--dracula-foreground);
        }
        
        /* Tailwind Color Extensions */
        .bg-dracula-bg { background-color: var(--dracula-bg); }
        .bg-dracula-currentLine { background-color: var(--dracula-currentLine); }
        .bg-dracula-selection { background-color: var(--dracula-selection); }
        .bg-dracula-foreground { background-color: var(--dracula-foreground); }
        .bg-dracula-comment { background-color: var(--dracula-comment); }
        .bg-dracula-cyan { background-color: var(--dracula-cyan); }
        .bg-dracula-green { background-color: var(--dracula-green); }
        .bg-dracula-orange { background-color: var(--dracula-orange); }
        .bg-dracula-pink { background-color: var(--dracula-pink); }
        .bg-dracula-purple { background-color: var(--dracula-purple); }
        .bg-dracula-red { background-color: var(--dracula-red); }
        .bg-dracula-yellow { background-color: var(--dracula-yellow); }
        
        .text-dracula-bg { color: var(--dracula-bg); }
        .text-dracula-currentLine { color: var(--dracula-currentLine); }
        .text-dracula-selection { color: var(--dracula-selection); }
        .text-dracula-foreground { color: var(--dracula-foreground); }
        .text-dracula-comment { color: var(--dracula-comment); }
        .text-dracula-cyan { color: var(--dracula-cyan); }
        .text-dracula-green { color: var(--dracula-green); }
        .text-dracula-orange { color: var(--dracula-orange); }
        .text-dracula-pink { color: var(--dracula-pink); }
        .text-dracula-purple { color: var(--dracula-purple); }
        .text-dracula-red { color: var(--dracula-red); }
        .text-dracula-yellow { color: var(--dracula-yellow); }
        
        .border-dracula-bg { border-color: var(--dracula-bg); }
        .border-dracula-currentLine { border-color: var(--dracula-currentLine); }
        .border-dracula-selection { border-color: var(--dracula-selection); }
        .border-dracula-foreground { border-color: var(--dracula-foreground); }
        .border-dracula-comment { border-color: var(--dracula-comment); }
        .border-dracula-cyan { border-color: var(--dracula-cyan); }
        .border-dracula-green { border-color: var(--dracula-green); }
        .border-dracula-orange { border-color: var(--dracula-orange); }
        .border-dracula-pink { border-color: var(--dracula-pink); }
        .border-dracula-purple { border-color: var(--dracula-purple); }
        .border-dracula-red { border-color: var(--dracula-red); }
        .border-dracula-yellow { border-color: var(--dracula-yellow); }
        
        /* Custom Styles */
        .nav-link {
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            transition: background-color 0.2s;
        }
        
        .nav-link:hover {
            background-color: var(--dracula-selection);
        }
        
        .nav-link.active {
            background-color: var(--dracula-selection);
            color: var(--dracula-pink);
        }
    </style>
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