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
                            bg: '#1F1F1F',
                            currentLine: '#2B2B2B',
                            selection: '#4A4A4A',
                            foreground: '#D1D1D1',
                            comment: '#6B5B95',
                            cyan: '#76D7C4',
                            green: '#5E8B7E',
                            orange: '#D8A7B1',
                            pink: '#C3447A',
                            purple: '#5D9B9B',
                            red: '#A93226',
                            yellow: '#B08D57',
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="bg-dracula-bg text-dracula-foreground min-h-screen flex flex-col" 
      x-data="{ pageLoaded: false }" 
      x-init="setTimeout(() => pageLoaded = true, 100)" 
      :class="{'opacity-0': !pageLoaded, 'opacity-100 transition-opacity duration-500': pageLoaded}">
    <!--
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
    -->
    <main class="container mx-auto px-4 py-6 flex-grow flex flex-col animate-fade-in">
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