<?php
// Application configuration settings

// Define the application environment - development, testing, production
define('APP_ENV', 'development');

// Error reporting settings
if (APP_ENV === 'development') {
    // Show all errors in development
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Hide errors in production
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'poll_app');
define('DB_USER', 'polluser');
define('DB_PASS', 'pollpassword');
define('DB_CHARSET', 'utf8mb4');

// Application paths and URLs
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Time settings
date_default_timezone_set('UTC');

// Application specific settings
define('POLLS_PER_PAGE', 10);
define('MAX_POLL_OPTIONS', 10);
?> 