<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Silk Spectre Polling System</title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📊</text></svg>">
    <!-- Alpine.js (before Tailwind to prevent FOUC) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js"></script>
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
                    },
                    borderRadius: {
                        'xl': '1rem',
                        '2xl': '1.5rem',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        fadeOut: {
                            '0%': { opacity: '1' },
                            '100%': { opacity: '0' }
                        },
                        pulse: {
                            '0%, 100%': {
                                opacity: '1'
                            },
                            '50%': {
                                opacity: '0.5'
                            }
                        },
                        spin: {
                            '0%': {
                                transform: 'rotate(0deg)'
                            },
                            '100%': {
                                transform: 'rotate(360deg)'
                            }
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in-out',
                        'fade-out': 'fadeOut 0.3s ease-in-out',
                        'pulse': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'spin': 'spin 1s linear infinite'
                    }
                }
            }
        }
    </script>
    <!-- Mobile Optimization -->
    <style>
        /* Custom scrollbar for webkit browsers */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #282a36;
        }
        ::-webkit-scrollbar-thumb {
            background: #6272a4;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #bd93f9;
        }
        
        /* Tap highlight color */
        * {
            -webkit-tap-highlight-color: rgba(189, 147, 249, 0.2);
        }
        
        /* Better touch targets for mobile */
        button, a, input[type="radio"], input[type="checkbox"] {
            min-height: 44px;
            min-width: 44px;
        }
        
        /* Adjust form control heights for mobile */
        @media (max-width: 640px) {
            input[type="text"], input[type="password"], input[type="email"], 
            textarea, select {
                font-size: 16px; /* Prevents iOS zoom on focus */
            }
        }

        /* Page transition styles */
        .page-transition {
            transition: opacity 0.3s ease-in-out;
        }
        
        /* Loading animation */
        .dots-loading {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }
        .dots-loading div {
            position: absolute;
            top: 33px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #bd93f9;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }
        .dots-loading div:nth-child(1) {
            left: 8px;
            animation: dots1 0.6s infinite;
        }
        .dots-loading div:nth-child(2) {
            left: 8px;
            animation: dots2 0.6s infinite;
        }
        .dots-loading div:nth-child(3) {
            left: 32px;
            animation: dots2 0.6s infinite;
        }
        .dots-loading div:nth-child(4) {
            left: 56px;
            animation: dots3 0.6s infinite;
        }
        @keyframes dots1 {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }
        @keyframes dots3 {
            0% { transform: scale(1); }
            100% { transform: scale(0); }
        }
        @keyframes dots2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(24px, 0); }
        }
    </style>
</head>
<body class="bg-dracula-bg text-dracula-foreground min-h-screen flex flex-col" 
      x-data="{ pageLoaded: false }" 
      x-init="setTimeout(() => pageLoaded = true, 100)" 
      :class="{'opacity-0': !pageLoaded, 'opacity-100 transition-opacity duration-500': pageLoaded}">
    <header class="bg-dracula-currentLine shadow-md rounded-b-lg">
        <div class="container mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between">
            <div class="flex items-center mb-4 sm:mb-0">
                <span class="text-3xl mr-2">📊</span>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'admin/') !== false) ? '../' : ''; ?>index.php" class="text-2xl font-bold text-dracula-purple hover:text-dracula-pink transition-colors duration-300">
                    Silk Spectre
                </a>
            </div>
            <nav>
                <ul class="flex flex-wrap justify-center items-center space-x-1 sm:space-x-4">
                    <li>
                        <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'admin/') !== false) ? '../' : ''; ?>index.php" class="px-3 py-2 rounded-lg hover:bg-dracula-selection transition-colors duration-300 flex items-center">
                            <span>Polls</span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['admin_id'])): ?>
                        <li class="hidden xs:block">
                            <span class="text-dracula-comment">|</span>
                        </li>
                        <li>
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'admin/') === false) ? 'admin/' : ''; ?>index.php" class="px-3 py-2 rounded-lg hover:bg-dracula-selection transition-colors duration-300 flex items-center">
                                <span>Admin</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'admin/') === false) ? 'admin/' : ''; ?>logout.php" class="px-3 py-2 rounded-lg hover:bg-dracula-selection transition-colors duration-300 flex items-center">
                                <span>Logout</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <?php if (strpos($_SERVER['PHP_SELF'], 'admin/') === false): ?>
                            <li>
                                <a href="admin/login.php" class="px-3 py-2 rounded-lg hover:bg-dracula-selection transition-colors duration-300 flex items-center">
                                    <span>Admin Login</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container mx-auto px-4 py-6 flex-grow animate-fade-in">
    <!-- Main content starts here -->
    <?php if(isset($message) && !empty($message) && (!isset($prevent_auto_message) || $prevent_auto_message !== true)): ?>
        <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-green p-4 mb-6" role="alert">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>
    <?php if(isset($error) && !empty($error) && (!isset($prevent_auto_message) || $prevent_auto_message !== true)): ?>
        <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?> 