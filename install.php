<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    // Create admins table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create polls table with additional fields for sequencing
    $pdo->exec("CREATE TABLE IF NOT EXISTS polls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        /* New fields for sequential polling */
        sequence_id INT DEFAULT NULL,
        sequence_position INT DEFAULT NULL,
        duration_minutes INT DEFAULT 60,
        start_time DATETIME DEFAULT NULL,
        end_time DATETIME DEFAULT NULL,
        show_results_duration_seconds INT DEFAULT 30,
        is_results_visible BOOLEAN DEFAULT 0
    )");
    
    // Create options table
    $pdo->exec("CREATE TABLE IF NOT EXISTS options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        poll_id INT NOT NULL,
        option_text VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )");
    
    // Create votes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        poll_id INT NOT NULL,
        option_id INT NOT NULL,
        voter_ip VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE
    )");
    
    // Create new poll_sequences table
    $pdo->exec("CREATE TABLE IF NOT EXISTS poll_sequences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create new poll_branching table for conditional logic
    $pdo->exec("CREATE TABLE IF NOT EXISTS poll_branching (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sequence_id INT NOT NULL,
        source_poll_id INT NOT NULL,
        option_id INT NOT NULL,
        target_poll_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sequence_id) REFERENCES poll_sequences(id) ON DELETE CASCADE,
        FOREIGN KEY (source_poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE,
        FOREIGN KEY (target_poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )");
    
    // Create test admin account
    $username = 'testadmin';
    $password = password_hash('password', PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        echo "Admin account created with username 'testadmin' and password 'password'.<br>";
    } else {
        echo "Admin account 'testadmin' already exists.<br>";
    }
    
    echo "Database setup complete! You can now start using the application.<br>";
    echo "For security, please <strong>delete this file</strong> after installation.<br>";
    echo "<a href=\"index.php\">Go to homepage</a>";
    
} catch (PDOException $e) {
    die("Database setup error: " . $e->getMessage());
}
?> 