<?php
// Include session initialization file (which will start the session)
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if poll ID is provided
if (!isset($_GET['poll_id']) || !is_numeric($_GET['poll_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
    exit;
}

$poll_id = (int)$_GET['poll_id'];

try {
    // Get options for the specified poll
    $stmt = $pdo->prepare("SELECT id, option_text FROM options WHERE poll_id = ? ORDER BY id ASC");
    $stmt->execute([$poll_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'options' => $options]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error fetching options: ' . $e->getMessage()]);
}
?> 