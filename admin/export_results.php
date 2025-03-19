<?php
session_start();
require_once '../includes/database.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=Invalid poll ID');
    exit;
}

$pollId = (int)$_GET['id'];

// Fetch poll details
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->execute([$pollId]);
$poll = $stmt->fetch();

if (!$poll) {
    header('Location: index.php?error=Poll not found');
    exit;
}

// Fetch poll options with vote counts
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(v.id) as vote_count 
    FROM options o 
    LEFT JOIN votes v ON o.id = v.option_id 
    WHERE o.poll_id = ? 
    GROUP BY o.id
    ORDER BY vote_count DESC
");
$stmt->execute([$pollId]);
$options = $stmt->fetchAll();

// Get voter details
$stmt = $pdo->prepare("
    SELECT v.id, v.voter_name, v.voter_email, v.ip_address, v.voted_at, o.option_text, o.id as option_id
    FROM votes v
    JOIN options o ON v.option_id = o.id
    WHERE o.poll_id = ?
    ORDER BY v.voted_at DESC
");
$stmt->execute([$pollId]);
$voters = $stmt->fetchAll();

// Determine export type
$exportType = isset($_GET['type']) ? $_GET['type'] : 'summary';

// Set headers for CSV download
$filename = sanitizeFileName($poll['title']) . '_results_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

if ($exportType === 'full') {
    // Full export with voter details
    
    // Add poll information
    fputcsv($output, ['Poll Information']);
    fputcsv($output, ['Title', $poll['title']]);
    fputcsv($output, ['Description', $poll['description']]);
    fputcsv($output, ['Created at', $poll['created_at']]);
    fputcsv($output, ['Status', $poll['is_active'] ? 'Active' : 'Inactive']);
    fputcsv($output, []);
    
    // Add options and vote counts
    fputcsv($output, ['Vote Summary']);
    fputcsv($output, ['Option', 'Votes', 'Percentage']);
    
    $totalVotes = 0;
    foreach ($options as $option) {
        $totalVotes += $option['vote_count'];
    }
    
    foreach ($options as $option) {
        $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 2) . '%' : '0%';
        fputcsv($output, [$option['option_text'], $option['vote_count'], $percentage]);
    }
    
    fputcsv($output, ['Total', $totalVotes, '100%']);
    fputcsv($output, []);
    
    // Add individual votes
    fputcsv($output, ['Individual Votes']);
    fputcsv($output, ['ID', 'Name', 'Email', 'Option Selected', 'IP Address', 'Date/Time']);
    
    foreach ($voters as $voter) {
        fputcsv($output, [
            $voter['id'],
            $voter['voter_name'] ?: 'Anonymous',
            $voter['voter_email'] ?: 'N/A',
            $voter['option_text'],
            $voter['ip_address'],
            $voter['voted_at']
        ]);
    }
    
} else {
    // Summary export (default)
    
    // Add poll information
    fputcsv($output, ['Poll Results: ' . $poll['title']]);
    fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Add options and vote counts
    fputcsv($output, ['Option', 'Votes', 'Percentage']);
    
    $totalVotes = 0;
    foreach ($options as $option) {
        $totalVotes += $option['vote_count'];
    }
    
    foreach ($options as $option) {
        $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 2) . '%' : '0%';
        fputcsv($output, [$option['option_text'], $option['vote_count'], $percentage]);
    }
    
    fputcsv($output, ['Total', $totalVotes, '100%']);
}

// Close the file pointer
fclose($output);
exit;

/**
 * Sanitize a string to be used as a filename
 * 
 * @param string $string The string to sanitize
 * @return string The sanitized string
 */
function sanitizeFileName($string) {
    // Replace spaces with underscores
    $string = str_replace(' ', '_', $string);
    
    // Remove special characters
    $string = preg_replace('/[^A-Za-z0-9_.-]/', '', $string);
    
    // Limit length
    $string = substr($string, 0, 50);
    
    // Ensure it's not empty
    if (empty($string)) {
        $string = 'poll_results';
    }
    
    return $string;
}
?> 