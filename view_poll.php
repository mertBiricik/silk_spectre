<?php
require_once 'includes/database.php';

$message = '';
$error = '';
$hasVoted = false;
$results = [];
$totalVotes = 0;

// Check for messages in URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$pollId = (int)$_GET['id'];

// Get poll details
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->execute([$pollId]);
$poll = $stmt->fetch();

if (!$poll) {
    $error = 'Poll not found';
} else {
    // Get poll options
    $stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll();
    
    // Get total votes for this poll
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    $totalVotes = $stmt->fetch()['total'];
    
    // Calculate results
    if ($totalVotes > 0) {
        $stmt = $pdo->prepare("
            SELECT o.id, o.option_text, COUNT(v.id) as vote_count 
            FROM options o
            LEFT JOIN votes v ON o.id = v.option_id
            WHERE o.poll_id = ?
            GROUP BY o.id
            ORDER BY vote_count DESC
        ");
        $stmt->execute([$pollId]);
        $results = $stmt->fetchAll();
    }
    
    // Check if user has already voted (using IP address)
    $voterIp = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as voted FROM votes WHERE poll_id = ? AND voter_ip = ?");
    $stmt->execute([$pollId, $voterIp]);
    $hasVoted = ($stmt->fetch()['voted'] > 0);
    
    // Process vote submission - only allow voting if poll is active
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['option']) && !$hasVoted && $poll['is_active']) {
        $optionId = (int)$_POST['option'];
        
        // Verify the option belongs to this poll
        $stmt = $pdo->prepare("SELECT COUNT(*) as valid FROM options WHERE id = ? AND poll_id = ?");
        $stmt->execute([$optionId, $pollId]);
        
        if ($stmt->fetch()['valid'] > 0) {
            try {
                // Record the vote
                $stmt = $pdo->prepare("INSERT INTO votes (poll_id, option_id, voter_ip) VALUES (?, ?, ?)");
                $stmt->execute([$pollId, $optionId, $voterIp]);
                
                $message = 'Your vote has been recorded!';
                $hasVoted = true;
                
                // Refresh the page to show results
                header("Location: view_poll.php?id=$pollId&message=Your vote has been recorded!");
                exit;
            } catch (PDOException $e) {
                $error = 'Error recording vote: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid option selected';
        }
    }
}

// Include header
include_once 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-dracula-pink mb-2"><?php echo $poll ? htmlspecialchars($poll['title']) : 'Poll Not Found'; ?></h1>
            <?php if ($poll && !empty($poll['description'])): ?>
                <p class="text-dracula-comment"><?php echo htmlspecialchars($poll['description']); ?></p>
            <?php endif; ?>
        </div>
        <a href="index.php" class="bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground px-3 py-1 rounded text-sm">
            Back to Polls
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-green p-4 mb-6" role="alert">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<?php if ($poll): ?>
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-6">
        <?php if (!$poll['is_active']): ?>
            <div class="bg-dracula-comment bg-opacity-20 border-l-4 border-dracula-comment text-dracula-comment p-4 mb-6" role="alert">
                <p>This poll is no longer active. You can view the results below.</p>
            </div>
        <?php endif; ?>
        
        <?php if (!$hasVoted && $poll['is_active']): ?>
            <!-- Vote Form -->
            <form method="POST" action="">
                <h2 class="text-xl font-semibold text-dracula-cyan mb-4">Cast Your Vote</h2>
                
                <?php if (!empty($options)): ?>
                    <div class="space-y-3 mb-6">
                        <?php foreach ($options as $option): ?>
                            <div class="flex items-center">
                                <input type="radio" id="option-<?php echo $option['id']; ?>" name="option" value="<?php echo $option['id']; ?>" class="h-4 w-4 text-dracula-purple focus:ring-dracula-pink border-dracula-selection">
                                <label for="option-<?php echo $option['id']; ?>" class="ml-3 block text-dracula-foreground">
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-2 px-4 rounded transition-colors">
                        Submit Vote
                    </button>
                <?php else: ?>
                    <p class="text-dracula-comment">No options available for this poll.</p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <!-- Results Section -->
            <h2 class="text-xl font-semibold text-dracula-cyan mb-4">Poll Results</h2>
            
            <?php if ($totalVotes > 0): ?>
                <p class="text-dracula-comment mb-4">Total votes: <?php echo $totalVotes; ?></p>
                
                <div class="space-y-4">
                    <?php foreach ($results as $result): ?>
                        <?php 
                            $percentage = ($result['vote_count'] / $totalVotes) * 100;
                            $percentage = round($percentage, 1);
                        ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-dracula-foreground">
                                    <?php echo htmlspecialchars($result['option_text']); ?>
                                </span>
                                <span class="text-dracula-purple">
                                    <?php echo $result['vote_count']; ?> votes (<?php echo $percentage; ?>%)
                                </span>
                            </div>
                            <div class="w-full bg-dracula-selection rounded-full h-2.5">
                                <div class="bg-dracula-purple h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($hasVoted): ?>
                    <div class="mt-6">
                        <p class="text-dracula-green">You have already voted in this poll.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-dracula-comment">No votes have been cast yet.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
// Include footer
include_once 'includes/footer.php';
?> 