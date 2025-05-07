<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?message=Invalid poll ID');
    exit;
}

$pollId = (int)$_GET['id'];

// Verify the poll exists
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->execute([$pollId]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$poll) {
    header('Location: index.php?message=Poll not found');
    exit;
}

// Activate the poll (deactivate all others first)
try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // First deactivate all polls
    $stmt = $pdo->prepare("UPDATE polls SET is_active = 0");
    $stmt->execute();
    
    // Then activate the selected poll
    $stmt = $pdo->prepare("UPDATE polls SET is_active = 1 WHERE id = ?");
    $stmt->execute([$pollId]);
    
    // Commit transaction
    $pdo->commit();
    
    header('Location: index.php?message=Poll activated successfully');
    exit;
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: index.php?message=Error activating poll: ' . $e->getMessage());
    exit;
}
?> 