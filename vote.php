<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the poll_id and option_id from the form
    $poll_id = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
    $option_id = isset($_POST['option_id']) ? (int)$_POST['option_id'] : 0;
    
    // Validate the poll and option
    if ($poll_id <= 0 || $option_id <= 0) {
        header('Location: index.php?error=Invalid poll or option');
        exit;
    }
    
    // Check if the poll exists and is active
    $poll_stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ? AND is_active = 1");
    $poll_stmt->execute([$poll_id]);
    $poll = $poll_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$poll) {
        header('Location: index.php?error=Poll not found or not active');
        exit;
    }
    
    // Check if the poll has ended
    if ($poll['end_time'] && strtotime($poll['end_time']) < time()) {
        header('Location: index.php?error=This poll has ended');
        exit;
    }
    
    // Check if the option belongs to the poll
    $option_stmt = $pdo->prepare("SELECT * FROM options WHERE id = ? AND poll_id = ?");
    $option_stmt->execute([$option_id, $poll_id]);
    $option = $option_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        header('Location: index.php?error=Invalid option');
        exit;
    }
    
    // Get the voter's IP address
    $voter_ip = $_SERVER['REMOTE_ADDR'];
    
    // Check if the voter has already voted in this poll
    $voted_stmt = $pdo->prepare("SELECT * FROM votes WHERE poll_id = ? AND voter_ip = ?");
    $voted_stmt->execute([$poll_id, $voter_ip]);
    $has_voted = $voted_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($has_voted) {
        header('Location: index.php?error=You have already voted in this poll');
        exit;
    }
    
    // Record the vote
    try {
        $vote_stmt = $pdo->prepare("INSERT INTO votes (poll_id, option_id, voter_ip, created_at) VALUES (?, ?, ?, NOW())");
        $vote_stmt->execute([$poll_id, $option_id, $voter_ip]);
        
        header('Location: index.php?message=Your vote has been recorded!');
        exit;
    } catch (PDOException $e) {
        error_log("Vote recording error: " . $e->getMessage());
        header('Location: index.php?error=There was an error recording your vote. Please try again.');
        exit;
    }
} else {
    // Redirect to the homepage if someone tries to access this page directly
    header('Location: index.php');
    exit;
}
?> 