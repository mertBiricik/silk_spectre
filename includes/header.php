<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silk Spectre</title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📊</text></svg>">
    <!-- Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom styles with Dracula theme -->
    <style type="text/tailwindcss">
        @layer utilities {
            .container-custom {
                @apply mx-auto max-w-6xl px-4 sm:px-6 lg:px-8;
            }
        }
        
        @layer base {
            :root {
                --dracula-background: #282a36;
                --dracula-current-line: #44475a;
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
        }
    </style>
    <!-- Inline tailwind config for Dracula colors -->
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
<body class="bg-dracula-bg text-dracula-foreground min-h-screen">
    <header class="bg-dracula-selection shadow-md">
        <div class="container-custom py-4">
            <nav class="flex justify-between items-center">
                <a href="index.php" class="text-dracula-purple text-2xl font-bold">Silk Spectre</a>
                <div class="flex space-x-4">
                    <a href="index.php" class="text-dracula-foreground hover:text-dracula-cyan px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="create_poll.php" class="bg-dracula-pink text-dracula-bg hover:bg-dracula-purple px-3 py-2 rounded-md text-sm font-medium">Create Poll</a>
                </div>
            </nav>
        </div>
    </header>
    <main class="container-custom py-8"><?php if(isset($message)): ?>
        <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-green p-4 mb-6" role="alert">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?> 