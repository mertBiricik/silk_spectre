<?php
// Database connection setup

try {
    // Create connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // For development
    if (APP_ENV === 'development') {
        die("Database Connection Error: " . $e->getMessage());
    } else {
        // For production - log error and show generic message
        error_log("Database Connection Error: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }
}

/**
 * Helper function to execute an SQL query
 * 
 * @param string $sql      SQL query
 * @param array  $params   Parameters to bind
 * @return PDOStatement    The result set
 */
function dbQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row from the database
 * 
 * @param string $sql      SQL query
 * @param array  $params   Parameters to bind
 * @return array           The result row
 */
function dbFetchOne($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Fetch all rows from the database
 * 
 * @param string $sql      SQL query
 * @param array  $params   Parameters to bind
 * @return array           The result rows
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insert a record into the database
 * 
 * @param string $table    Table name
 * @param array  $data     Associative array of column => value
 * @return int             Last insert ID
 */
function dbInsert($table, $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    return $pdo->lastInsertId();
}
?> 