<?php
/**
 * Simple Development Server
 * 
 * This script allows you to start a PHP built-in web server for development
 * purposes. It can be executed from the command line with:
 * php server.php
 * 
 * The server will start on localhost:8000 by default
 */

$host = '0.0.0.0';
$port = 8000;

// Command to start the server
$command = sprintf(
    'php -S %s:%d -t %s',
    $host,
    $port,
    __DIR__
);

// Display info
echo "Starting PHP development server on http://$host:$port\n";
echo "Press Ctrl+C to stop the server\n\n";

// Execute the command (will keep running until manually stopped)
system($command);
?> 