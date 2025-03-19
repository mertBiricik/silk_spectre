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
    
    // Create polls table
    $sql = "CREATE TABLE IF NOT EXISTS polls (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
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
    
} catch(PDOException $e) {
    die("ERROR: Could not create database tables. " . $e->getMessage());
}
?> 