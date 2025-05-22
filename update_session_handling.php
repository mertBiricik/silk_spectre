<?php
// Script to update all admin files to use the new session handling approach

// Get all PHP files in the admin directory
$adminFiles = glob('admin/*.php');

foreach ($adminFiles as $file) {
    // Skip files we've already updated
    if ($file === 'admin/index.php' || $file === 'admin/login.php') {
        continue;
    }
    
    // Read the file content
    $content = file_get_contents($file);
    
    // Check if file starts with session_start()
    if (strpos($content, '<?php' . PHP_EOL . 'session_start();') === 0) {
        // Replace session_start with our new approach
        $newContent = str_replace(
            '<?php' . PHP_EOL . 'session_start();' . PHP_EOL . 'require_once \'../includes/database.php\';',
            '<?php' . PHP_EOL . 'require_once \'../includes/session.php\';' . PHP_EOL . 'require_once \'../includes/config.php\';' . PHP_EOL . 'require_once \'../includes/db.php\';',
            $content
        );
        
        // If no database.php include was found, just replace session_start
        if ($newContent === $content) {
            $newContent = str_replace(
                '<?php' . PHP_EOL . 'session_start();',
                '<?php' . PHP_EOL . 'require_once \'../includes/session.php\';',
                $content
            );
        }
        
        // Write the updated content back to the file
        file_put_contents($file, $newContent);
        
        echo "Updated: $file\n";
    } else {
        echo "Skipped: $file (does not start with session_start())\n";
    }
}

echo "All admin files have been updated.\n";
?> 