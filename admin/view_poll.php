<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get the admin info
$admin_id = $_SESSION['admin_id'];
$admin_stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$admin_stmt->execute([$admin_id]);
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=Invalid poll ID');
    exit;
}

$poll_id = (int)$_GET['id'];

// Get poll information
$poll_stmt = $pdo->prepare("SELECT p.*, s.name as sequence_name, s.id as sequence_id FROM polls p LEFT JOIN poll_sequences s ON p.sequence_id = s.id WHERE p.id = ?");
$poll_stmt->execute([$poll_id]);
$poll = $poll_stmt->fetch(PDO::FETCH_ASSOC);

if (!$poll) {
    header('Location: index.php?error=Poll not found');
    exit;
}

// Get options and vote counts
$options_stmt = $pdo->prepare("
    SELECT o.*, COUNT(v.id) as vote_count 
    FROM options o 
    LEFT JOIN votes v ON o.id = v.option_id 
    WHERE o.poll_id = ? 
    GROUP BY o.id
    ORDER BY o.id ASC
");
$options_stmt->execute([$poll_id]);
$options = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total votes
$total_votes = 0;
foreach ($options as $option) {
    $total_votes += $option['vote_count'];
}

// Calculate percentages
foreach ($options as &$option) {
    $option['percentage'] = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100) : 0;
}

// Get branching rules
$branching_stmt = $pdo->prepare("SELECT b.*, o.option_text, p.title as target_poll_title 
                                FROM poll_branching b 
                                LEFT JOIN options o ON b.source_option_id = o.id 
                                LEFT JOIN polls p ON b.target_poll_id = p.id
                                WHERE b.source_poll_id = ?");
$branching_stmt->execute([$poll_id]);
$branching_rules = $branching_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process actions (toggle active status)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'toggle_active') {
            try {
                // If making active, make all other polls inactive first
                if ($poll['is_active'] == 0) {
                    $deactivate_stmt = $pdo->prepare("UPDATE polls SET is_active = 0");
                    $deactivate_stmt->execute();
                }
                
                // Toggle this poll's active status
                $is_active = $poll['is_active'] == 1 ? 0 : 1;
                $update_stmt = $pdo->prepare("UPDATE polls SET is_active = ? WHERE id = ?");
                $update_stmt->execute([$is_active, $poll_id]);
                
                $message = $is_active ? 'Poll activated successfully' : 'Poll deactivated successfully';
                
                // Refresh poll data
                $poll['is_active'] = $is_active;
            } catch (PDOException $e) {
                $error = 'Error updating poll status: ' . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-dracula-purple">Poll Details</h1>
        <div class="flex space-x-3">
            <?php if ($poll['sequence_id']): ?>
                <a href="edit_sequence.php?id=<?php echo $poll['sequence_id']; ?>" class="bg-dracula-cyan hover:bg-opacity-80 text-white px-4 py-2 rounded-lg transition-colors">
                    Edit Sequence
                </a>
            <?php endif; ?>
            <a href="index.php" class="bg-dracula-currentLine hover:bg-dracula-comment text-dracula-foreground px-4 py-2 rounded-lg transition-colors">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-foreground p-4 mb-6 rounded-lg">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-foreground p-4 mb-6 rounded-lg">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Poll Info -->
        <div class="lg:col-span-1">
            <div class="bg-dracula-currentLine rounded-xl p-6 shadow-lg mb-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-dracula-foreground"><?php echo htmlspecialchars($poll['title']); ?></h2>
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $poll['is_active'] ? 'bg-dracula-green text-black' : 'bg-dracula-selection text-dracula-comment'; ?>">
                        <?php echo $poll['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                
                <?php if (!empty($poll['description'])): ?>
                    <p class="text-dracula-comment mb-4"><?php echo nl2br(htmlspecialchars($poll['description'])); ?></p>
                <?php endif; ?>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span class="text-dracula-comment">Created:</span>
                        <span class="text-dracula-foreground"><?php echo date('M j, Y g:i A', strtotime($poll['created_at'])); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-dracula-comment">Total Votes:</span>
                        <span class="text-dracula-foreground"><?php echo $total_votes; ?></span>
                    </div>
                    
                    <?php if ($poll['sequence_id']): ?>
                        <div class="flex justify-between">
                            <span class="text-dracula-comment">Sequence:</span>
                            <span class="text-dracula-foreground"><?php echo htmlspecialchars($poll['sequence_name']); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-dracula-comment">Position:</span>
                            <span class="text-dracula-foreground"><?php echo $poll['sequence_position']; ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-dracula-comment">Duration:</span>
                            <span class="text-dracula-foreground"><?php echo $poll['duration_minutes']; ?> minutes</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-dracula-comment">Results Display:</span>
                            <span class="text-dracula-foreground"><?php echo $poll['show_results_duration_seconds']; ?> seconds</span>
                        </div>
                        
                        <?php if ($poll['start_time']): ?>
                            <div class="flex justify-between">
                                <span class="text-dracula-comment">Started:</span>
                                <span class="text-dracula-foreground"><?php echo date('M j, Y g:i A', strtotime($poll['start_time'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($poll['end_time']): ?>
                            <div class="flex justify-between">
                                <span class="text-dracula-comment">Ends:</span>
                                <span class="text-dracula-foreground"><?php echo date('M j, Y g:i A', strtotime($poll['end_time'])); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="space-y-2">
                    <form method="POST" class="inline-block">
                        <input type="hidden" name="action" value="toggle_active">
                        <button type="submit" class="w-full bg-<?php echo $poll['is_active'] ? 'dracula-red' : 'dracula-green'; ?> hover:bg-opacity-80 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            <?php echo $poll['is_active'] ? 'Deactivate Poll' : 'Activate Poll'; ?>
                        </button>
                    </form>
                    
                    <a href="edit_poll.php?id=<?php echo $poll['id']; ?>" class="block w-full text-center bg-dracula-purple hover:bg-dracula-pink text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                        Edit Poll
                    </a>
                    
                    <a href="export_results.php?id=<?php echo $poll['id']; ?>" class="block w-full text-center bg-dracula-orange hover:bg-opacity-80 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                        Export Results (CSV)
                    </a>
                </div>
            </div>
            
            <!-- Branching Rules -->
            <?php if ($poll['sequence_id'] && count($branching_rules) > 0): ?>
                <div class="bg-dracula-currentLine rounded-xl p-6 shadow-lg">
                    <h3 class="text-xl font-semibold text-dracula-purple mb-4">Branching Rules</h3>
                    
                    <div class="space-y-4">
                        <?php foreach ($branching_rules as $rule): ?>
                            <div class="border border-dracula-comment rounded-lg p-4 bg-dracula-selection bg-opacity-10">
                                <?php if ($rule['source_option_id'] == 0): ?>
                                    <div class="mb-2 font-semibold text-dracula-orange">Default Rule</div>
                                <?php else: ?>
                                    <div class="mb-2 font-semibold text-dracula-cyan">If option wins: "<?php echo htmlspecialchars($rule['option_text']); ?>"</div>
                                <?php endif; ?>
                                
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-dracula-purple mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                    <span>Go to: <?php echo htmlspecialchars($rule['target_poll_title']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Results Data -->
        <div class="lg:col-span-2">
            <div class="bg-dracula-currentLine rounded-xl p-6 shadow-lg">
                <h2 class="text-2xl font-bold text-dracula-foreground mb-6">Poll Results</h2>
                
                <div class="space-y-6 mb-6">
                    <?php if (count($options) > 0): ?>
                        <?php foreach ($options as $index => $option): 
                            $colors = ['dracula-purple', 'dracula-pink', 'dracula-cyan', 'dracula-green', 'dracula-orange', 'dracula-yellow'];
                            $color = $colors[$index % count($colors)];
                        ?>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-dracula-foreground"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    <span class="text-dracula-comment">
                                        <?php echo $option['percentage']; ?>% (<?php echo $option['vote_count']; ?> <?php echo $option['vote_count'] === 1 ? 'vote' : 'votes'; ?>)
                                    </span>
                                </div>
                                <div class="w-full bg-dracula-selection bg-opacity-40 rounded-full h-4 overflow-hidden">
                                    <div class="h-4 bg-<?php echo $color; ?> rounded-full" style="width: <?php echo $option['percentage']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-dracula-comment">No options found for this poll.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Votes -->
                <h3 class="text-xl font-semibold text-dracula-purple mb-4">Recent Votes</h3>
                
                <?php
                // Get recent votes
                $recent_votes_stmt = $pdo->prepare("
                    SELECT v.*, o.option_text 
                    FROM votes v 
                    JOIN options o ON v.option_id = o.id 
                    WHERE v.poll_id = ? 
                    ORDER BY v.created_at DESC 
                    LIMIT 10
                ");
                $recent_votes_stmt->execute([$poll_id]);
                $recent_votes = $recent_votes_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (count($recent_votes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-dracula-selection bg-opacity-20 rounded-lg overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">IP Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Option Selected</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-dracula-selection">
                                <?php foreach ($recent_votes as $vote): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-mono text-dracula-foreground"><?php echo htmlspecialchars($vote['voter_ip']); ?></td>
                                        <td class="px-4 py-3 text-sm text-dracula-foreground"><?php echo htmlspecialchars($vote['option_text']); ?></td>
                                        <td class="px-4 py-3 text-sm text-dracula-comment">
                                            <?php echo date('M j, Y g:i A', strtotime($vote['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-dracula-comment">No votes recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 