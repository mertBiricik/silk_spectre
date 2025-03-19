<?php
session_start();

echo "Testing session functionality...\n\n";

// Display current session data
echo "Current session data:\n";
print_r($_SESSION);
echo "\n";

// Set some session data
$_SESSION['test_value'] = 'This is a test value set at ' . date('Y-m-d H:i:s');
echo "Set test session value\n";

// Get session ID
echo "Session ID: " . session_id() . "\n";
echo "Session name: " . session_name() . "\n";

// Display session configuration
echo "\nSession configuration:\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";

// Instructions
echo "\nSession test complete. To verify persistence, refresh this page and check if the test value remains.\n";
?> 