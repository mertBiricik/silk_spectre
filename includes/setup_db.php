<?php
// Database connection settings
$host = 'localhost';
$username = 'polluser';
$password = 'pollpassword';

try {
    // The database should already be created by the previous command
    // Connect to the poll_app database
    $pdo = new PDO("mysql:host=$host;dbname=poll_app", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create polls table with is_active field
    $sql = "CREATE TABLE IF NOT EXISTS polls (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    echo "Polls table created successfully<br>";
    
    // Create options table
    $sql = "CREATE TABLE IF NOT EXISTS options (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        poll_id INT(11) NOT NULL,
        option_text VARCHAR(255) NOT NULL,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    
    echo "Options table created successfully<br>";
    
    // Create votes table
    $sql = "CREATE TABLE IF NOT EXISTS votes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        poll_id INT(11) NOT NULL,
        option_id INT(11) NOT NULL,
        voter_ip VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    
    echo "Votes table created successfully<br>";
    
    // Create admin table 
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    echo "Admins table created successfully<br>";
    
    // Add a default admin if it doesn't exist
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($checkAdmin == 0) {
        // Default admin with password "admin123"
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hashedPassword]);
        echo "Default admin created (username: admin, password: admin123)<br>";
    }
    
    // Update all existing polls to ensure is_active field exists and is set to 0
    $pdo->exec("ALTER TABLE polls ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT 0");
    
    // Make sure only one poll can be active at a time
    $pdo->exec("UPDATE polls SET is_active = 0");
    
    echo "Database setup completed successfully!<br>";
    
} catch(PDOException $e) {
    die("ERROR: Could not create database tables. " . $e->getMessage());
}
?> 