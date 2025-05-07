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

// Delete the poll and related data
try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete votes first (due to foreign key constraints)
    $stmt = $pdo->prepare("DELETE FROM votes WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    
    // Delete options
    $stmt = $pdo->prepare("DELETE FROM options WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    
    // Finally, delete the poll
    $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
    $stmt->execute([$pollId]);
    
    // Commit transaction
    $pdo->commit();
    
    header('Location: index.php?message=Poll deleted successfully');
    exit;
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: index.php?message=Error deleting poll: ' . $e->getMessage());
    exit;
}
?> 