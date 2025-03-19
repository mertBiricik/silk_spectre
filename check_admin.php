<?php
// Include database connection
require_once 'includes/database.php';

try {
    // Check if admin exists
    $stmt = $pdo->query("SELECT * FROM admins");
    $admins = $stmt->fetchAll();
    
    echo "Number of admin accounts: " . count($admins) . "\n";
    
    if (count($admins) > 0) {
        foreach ($admins as $admin) {
            echo "Admin ID: " . $admin['id'] . "\n";
            echo "Username: " . $admin['username'] . "\n";
            echo "Password hash: " . $admin['password'] . "\n";
            echo "Created at: " . $admin['created_at'] . "\n";
            
            // Test if the default password works
            $testPassword = 'admin123';
            $passwordVerified = password_verify($testPassword, $admin['password']);
            echo "Password 'admin123' verified: " . ($passwordVerified ? 'YES' : 'NO') . "\n\n";
        }
    } else {
        echo "No admin accounts found. Please run setup_db.php\n";
    }
    
    // Create a new admin account to test
    echo "Creating a new admin account with known password...\n";
    $newUsername = 'testadmin';
    $newPassword = 'testpass123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Check if testadmin already exists
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$newUsername]);
    $existingAdmin = $stmt->fetch();
    
    if (!$existingAdmin) {
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$newUsername, $hashedPassword]);
        echo "New admin created with username: $newUsername and password: $newPassword\n";
    } else {
        echo "Test admin already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 