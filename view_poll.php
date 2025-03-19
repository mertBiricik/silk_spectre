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
    
    // Process vote submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['option']) && !$hasVoted) {
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
    
    // If we have results but no cached data, get the results
    if ($hasVoted && empty($results)) {
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
        
        // Get updated total votes
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes WHERE poll_id = ?");
        $stmt->execute([$pollId]);
        $totalVotes = $stmt->fetch()['total'];
    }
}

// Include header
include_once 'includes/header.php';
?>

<?php if ($error && $error === 'Poll not found'): ?>
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-6 text-center">
        <h2 class="text-2xl font-bold text-dracula-red mb-4">Poll Not Found</h2>
        <p class="text-dracula-foreground mb-6">The poll you're looking for doesn't exist or has been removed.</p>
        <a href="index.php" class="bg-dracula-purple text-dracula-bg px-4 py-2 rounded hover:bg-dracula-pink">Back to Polls</a>
    </div>
<?php elseif ($poll): ?>
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-dracula-pink mb-2"><?php echo htmlspecialchars($poll['title']); ?></h1>
        <?php if (!empty($poll['description'])): ?>
            <p class="text-dracula-foreground"><?php echo htmlspecialchars($poll['description']); ?></p>
        <?php endif; ?>
        <div class="mt-2 text-sm text-dracula-comment">
            Created: <?php echo date('F j, Y, g:i a', strtotime($poll['created_at'])); ?>
        </div>
    </div>
    
    <div class="bg-dracula-currentLine shadow-md rounded-lg overflow-hidden">
        <?php if (!$hasVoted): ?>
            <!-- Voting Form -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-dracula-cyan mb-4">Cast Your Vote</h2>
                <form method="POST" action="">
                    <div class="space-y-3">
                        <?php foreach ($options as $option): ?>
                            <div class="flex items-center">
                                <input type="radio" id="option_<?php echo $option['id']; ?>" name="option" value="<?php echo $option['id']; ?>" class="h-4 w-4 text-dracula-purple focus:ring-dracula-purple border-dracula-selection rounded">
                                <label for="option_<?php echo $option['id']; ?>" class="ml-3 block text-dracula-foreground">
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="w-full bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none">
                            Submit Vote
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Results Display -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-dracula-cyan mb-4">Poll Results</h2>
                <div class="mb-2 text-dracula-comment">Total votes: <?php echo $totalVotes; ?></div>
                <div class="space-y-4">
                    <?php foreach ($results as $result): 
                        $percentage = ($totalVotes > 0) ? round(($result['vote_count'] / $totalVotes) * 100) : 0;
                    ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-dracula-foreground"><?php echo htmlspecialchars($result['option_text']); ?></span>
                                <span class="text-dracula-foreground"><?php echo $result['vote_count']; ?> votes (<?php echo $percentage; ?>%)</span>
                            </div>
                            <div class="w-full bg-dracula-selection rounded-full h-4">
                                <div class="bg-dracula-purple h-4 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-6 flex justify-between">
        <a href="index.php" class="bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground font-bold py-2 px-4 rounded focus:outline-none">
            Back to Polls
        </a>
        <div class="flex space-x-2">
            <button class="bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none" onclick="sharePoll()">
                Share Poll
            </button>
        </div>
    </div>
    
    <script>
        function sharePoll() {
            const pollUrl = window.location.href.split('?')[0] + '?id=<?php echo $pollId; ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes(htmlspecialchars($poll['title'])); ?>',
                    text: 'Check out this poll: <?php echo addslashes(htmlspecialchars($poll['title'])); ?>',
                    url: pollUrl
                })
                .catch(error => console.log('Error sharing:', error));
            } else {
                // Fallback for browsers that don't support the Web Share API
                prompt('Copy this link to share the poll:', pollUrl);
            }
        }
    </script>
<?php endif; ?>

<?php
// Include footer
include_once 'includes/footer.php';
?> 