<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/database.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Poll ID is required';
    echo json_encode($response);
    exit;
}

$pollId = (int)$_GET['id'];

try {
    // Get poll details
    $stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch();
    
    if (!$poll) {
        $response['message'] = 'Poll not found';
        echo json_encode($response);
        exit;
    }
    
    // Get poll options and votes
    $stmt = $pdo->prepare("
        SELECT o.id, o.option_text, COUNT(v.id) as vote_count 
        FROM options o
        LEFT JOIN votes v ON o.id = v.option_id
        WHERE o.poll_id = ?
        GROUP BY o.id
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll();
    
    // Get total votes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    $totalVotes = $stmt->fetch()['total'];
    
    // Format the response
    $formattedOptions = [];
    foreach ($options as $option) {
        $percentage = ($totalVotes > 0) ? round(($option['vote_count'] / $totalVotes) * 100, 2) : 0;
        $formattedOptions[] = [
            'id' => $option['id'],
            'text' => $option['option_text'],
            'votes' => (int)$option['vote_count'],
            'percentage' => $percentage
        ];
    }
    
    $response['success'] = true;
    $response['data'] = [
        'id' => $poll['id'],
        'title' => $poll['title'],
        'description' => $poll['description'],
        'created_at' => $poll['created_at'],
        'total_votes' => (int)$totalVotes,
        'options' => $formattedOptions
    ];
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?> 