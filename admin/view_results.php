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

// Calculate total votes
$totalVotes = 0;
foreach ($options as $option) {
    $totalVotes += $option['vote_count'];
}

// Get voter details if requested
$voters = [];
if (isset($_GET['show_voters']) && $_GET['show_voters'] == 1) {
    $stmt = $pdo->prepare("
        SELECT v.id, v.voter_name, v.voter_email, v.ip_address, v.voted_at, o.option_text
        FROM votes v
        JOIN options o ON v.option_id = o.id
        WHERE o.poll_id = ?
        ORDER BY v.voted_at DESC
    ");
    $stmt->execute([$pollId]);
    $voters = $stmt->fetchAll();
}

// Include admin header
include_once 'admin_header.php';
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-dracula-pink mb-2"><?php echo htmlspecialchars($poll['title']); ?> - Results</h1>
        <div>
            <a href="index.php" class="bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground px-3 py-1 rounded text-sm mr-2 transition-colors">
                Back to Polls
            </a>
            <a href="edit_poll.php?id=<?php echo $pollId; ?>" class="bg-dracula-cyan hover:bg-dracula-cyan/80 text-dracula-bg px-3 py-1 rounded text-sm transition-colors">
                Edit Poll
            </a>
        </div>
    </div>
    
    <?php if (!empty($poll['description'])): ?>
        <p class="text-dracula-comment mb-2"><?php echo htmlspecialchars($poll['description']); ?></p>
    <?php endif; ?>
    
    <div class="flex items-center text-sm text-dracula-comment">
        <span class="mr-3">Created: <?php echo date('M j, Y, h:i A', strtotime($poll['created_at'])); ?></span>
        <span class="mr-3">Status: 
            <?php if ($poll['is_active']): ?>
                <span class="text-dracula-green">Active</span>
            <?php else: ?>
                <span>Inactive</span>
            <?php endif; ?>
        </span>
        <span>Total Votes: <span class="font-bold text-dracula-purple"><?php echo $totalVotes; ?></span></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Results Chart -->
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold text-dracula-orange mb-4">Vote Distribution</h2>
        
        <?php if ($totalVotes > 0): ?>
            <div class="space-y-4">
                <?php foreach ($options as $option): ?>
                    <?php 
                        $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 1) : 0;
                        // Generate a color based on the option index for variety
                        $colors = ['dracula-purple', 'dracula-green', 'dracula-orange', 'dracula-yellow', 'dracula-pink', 'dracula-cyan'];
                        $colorIndex = array_search($option, $options) % count($colors);
                        $color = $colors[$colorIndex];
                    ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-dracula-foreground"><?php echo htmlspecialchars($option['option_text']); ?></span>
                            <span class="text-sm text-dracula-<?php echo str_replace('dracula-', '', $color); ?>">
                                <?php echo $option['vote_count']; ?> votes (<?php echo $percentage; ?>%)
                            </span>
                        </div>
                        <div class="w-full bg-dracula-selection rounded-full h-2.5">
                            <div class="bg-<?php echo $color; ?> h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-dracula-comment text-center py-8">No votes have been recorded for this poll yet.</p>
        <?php endif; ?>
    </div>
    
    <!-- Vote statistics -->
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold text-dracula-purple mb-4">Vote Statistics</h2>
        
        <?php if ($totalVotes > 0): ?>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-dracula-selection rounded-lg p-4 text-center">
                        <h3 class="text-dracula-comment text-sm mb-1">Total Votes</h3>
                        <p class="text-3xl font-bold text-dracula-foreground"><?php echo $totalVotes; ?></p>
                    </div>
                    
                    <div class="bg-dracula-selection rounded-lg p-4 text-center">
                        <h3 class="text-dracula-comment text-sm mb-1">Leading Option</h3>
                        <?php if (!empty($options)): ?>
                            <p class="text-lg font-bold text-dracula-green"><?php echo htmlspecialchars($options[0]['option_text']); ?></p>
                            <p class="text-sm text-dracula-comment"><?php echo round(($options[0]['vote_count'] / $totalVotes) * 100, 1); ?>% of votes</p>
                        <?php else: ?>
                            <p class="text-lg text-dracula-comment">No options</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="bg-dracula-selection rounded-lg p-4">
                    <h3 class="text-dracula-purple text-sm mb-3">Vote Distribution</h3>
                    <div class="w-full h-40">
                        <!-- Simple bar chart visualization -->
                        <div class="flex items-end h-32 space-x-1">
                            <?php foreach ($options as $option): ?>
                                <?php 
                                    $percentage = $totalVotes > 0 ? ($option['vote_count'] / $totalVotes) * 100 : 0;
                                    $height = $percentage > 0 ? (($percentage / 100) * 128) : 0;
                                    $colors = ['dracula-purple', 'dracula-green', 'dracula-orange', 'dracula-yellow', 'dracula-pink', 'dracula-cyan'];
                                    $colorIndex = array_search($option, $options) % count($colors);
                                    $color = $colors[$colorIndex];
                                ?>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="relative w-full">
                                        <div class="bg-<?php echo $color; ?> rounded-t" style="height: <?php echo $height; ?>px;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex space-x-1 mt-1">
                            <?php foreach ($options as $option): ?>
                                <div class="flex-1 text-center">
                                    <span class="text-xs text-dracula-comment truncate block" title="<?php echo htmlspecialchars($option['option_text']); ?>">
                                        <?php echo strlen($option['option_text']) > 10 ? substr(htmlspecialchars($option['option_text']), 0, 7) . '...' : htmlspecialchars($option['option_text']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p class="text-dracula-comment text-center py-8">No vote statistics available yet.</p>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <?php if (isset($_GET['show_voters']) && $_GET['show_voters'] == 1): ?>
                <a href="?id=<?php echo $pollId; ?>" class="bg-dracula-comment text-dracula-bg px-3 py-1 rounded text-sm inline-block transition-colors hover:bg-dracula-comment/80">
                    Hide Voter Details
                </a>
            <?php else: ?>
                <a href="?id=<?php echo $pollId; ?>&show_voters=1" class="bg-dracula-purple text-dracula-bg px-3 py-1 rounded text-sm inline-block transition-colors hover:bg-dracula-purple/80">
                    Show Voter Details
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($_GET['show_voters']) && $_GET['show_voters'] == 1): ?>
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-dracula-pink mb-4">Voter Details</h2>
        
        <?php if (!empty($voters)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-dracula-selection">
                    <thead class="bg-dracula-selection">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">Voted For</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">IP Address</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-dracula-bg divide-y divide-dracula-selection">
                        <?php foreach ($voters as $voter): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-foreground">
                                    <?php echo htmlspecialchars($voter['voter_name'] ?: 'Anonymous'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-foreground">
                                    <?php echo htmlspecialchars($voter['voter_email'] ?: 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-foreground">
                                    <?php echo htmlspecialchars($voter['option_text']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-comment">
                                    <?php echo htmlspecialchars($voter['ip_address']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-comment">
                                    <?php echo date('M j, Y, h:i A', strtotime($voter['voted_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-dracula-comment text-center py-4">No voter details available.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="text-center mb-6">
    <a href="export_results.php?id=<?php echo $pollId; ?>" class="bg-dracula-green hover:bg-dracula-green/80 text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none transition-colors inline-flex items-center">
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
        Export Results as CSV
    </a>
</div>

<?php
// Include admin footer
include_once 'admin_footer.php';
?> 