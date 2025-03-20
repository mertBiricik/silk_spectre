<?php
// Include session initialization file (which will start the session)
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for messages
$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Get all polls
$polls_stmt = $pdo->query("SELECT * FROM polls ORDER BY is_active DESC, created_at DESC");
$polls = $polls_stmt->fetchAll(PDO::FETCH_ASSOC);

// Load header but prevent automatic display of message from the URL parameter
$prevent_auto_message = true;
include '../includes/header.php';
// Reset the variable after header inclusion
$prevent_auto_message = false;
?>

<div class="mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-dracula-purple mb-2">Admin Dashboard</h1>
            <p class="text-dracula-comment">Welcome, <?php echo htmlspecialchars($admin['username']); ?>! Manage your polls here.</p>
        </div>
        <div class="flex gap-3">
            <a href="create_poll.php" class="bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create New Poll
            </a>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div x-data="{ show: true }" 
         x-init="setTimeout(() => show = false, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         class="<?php if (strpos($message, 'Poll created successfully') !== false): ?>bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-foreground<?php else: ?>bg-dracula-purple bg-opacity-20 border-l-4 border-dracula-purple text-dracula-foreground<?php endif; ?> p-4 mb-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <svg class="w-6 h-6 mr-2 <?php echo (strpos($message, 'Poll created successfully') !== false) ? 'text-dracula-green' : 'text-dracula-purple'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <p><?php echo $message; ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="bg-dracula-currentLine rounded-xl p-6 shadow-md mb-8">
    <h2 class="text-xl font-semibold text-dracula-purple mb-4">All Polls</h2>
    
    <?php if (empty($polls)): ?>
        <p class="text-dracula-comment text-center py-10">No polls have been created yet. Create your first poll to get started.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-dracula-selection">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Votes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-dracula-currentLine divide-y divide-dracula-selection">
                    <?php foreach ($polls as $poll): ?>
                        <?php
                        // Get vote count for this poll
                        $vote_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM votes WHERE poll_id = ?");
                        $vote_stmt->execute([$poll['id']]);
                        $vote_count = $vote_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <tr class="transform transition-all duration-200 hover:bg-dracula-selection hover:bg-opacity-40">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-dracula-foreground"><?php echo htmlspecialchars($poll['title']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($poll['is_active']): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-dracula-green bg-opacity-20 text-dracula-green">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-dracula-comment bg-opacity-20 text-dracula-comment">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-dracula-comment">
                                <?php echo date('M j, Y', strtotime($poll['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-dracula-comment">
                                <?php echo $vote_count; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="view_results.php?id=<?php echo $poll['id']; ?>" class="text-dracula-cyan hover:text-dracula-pink transition-colors duration-300">
                                        Results
                                    </a>
                                    <?php if (!$poll['is_active']): ?>
                                        <a href="activate_poll.php?id=<?php echo $poll['id']; ?>" class="text-dracula-green hover:text-dracula-pink transition-colors duration-300">
                                            Activate
                                        </a>
                                    <?php else: ?>
                                        <a href="deactivate_poll.php?id=<?php echo $poll['id']; ?>" class="text-dracula-orange hover:text-dracula-pink transition-colors duration-300">
                                            Deactivate
                                        </a>
                                    <?php endif; ?>
                                    <a href="edit_poll.php?id=<?php echo $poll['id']; ?>" class="text-dracula-purple hover:text-dracula-pink transition-colors duration-300">
                                        Edit
                                    </a>
                                    <a href="delete_poll.php?id=<?php echo $poll['id']; ?>" class="text-dracula-red hover:text-dracula-pink transition-colors duration-300" 
                                       onclick="return confirm('Are you sure you want to delete this poll? This action cannot be undone.')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-dracula-currentLine rounded-xl p-6 shadow-md">
        <h2 class="text-xl font-semibold text-dracula-purple mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
            </svg>
            Quick Stats
        </h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-dracula-selection bg-opacity-30 p-4 rounded-lg">
                <p class="text-dracula-comment text-sm mb-1">Total Polls</p>
                <p class="text-2xl font-bold text-dracula-purple"><?php echo count($polls); ?></p>
            </div>
            <div class="bg-dracula-selection bg-opacity-30 p-4 rounded-lg">
                <p class="text-dracula-comment text-sm mb-1">Active Polls</p>
                <p class="text-2xl font-bold text-dracula-green">
                    <?php 
                    $active_count = 0;
                    foreach ($polls as $poll) {
                        if ($poll['is_active']) $active_count++;
                    }
                    echo $active_count;
                    ?>
                </p>
            </div>
            <div class="bg-dracula-selection bg-opacity-30 p-4 rounded-lg">
                <p class="text-dracula-comment text-sm mb-1">Total Votes</p>
                <p class="text-2xl font-bold text-dracula-cyan">
                    <?php 
                    $total_votes_stmt = $pdo->query("SELECT COUNT(*) as count FROM votes");
                    $total_votes = $total_votes_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    echo $total_votes;
                    ?>
                </p>
            </div>
            <div class="bg-dracula-selection bg-opacity-30 p-4 rounded-lg">
                <p class="text-dracula-comment text-sm mb-1">Avg Votes per Poll</p>
                <p class="text-2xl font-bold text-dracula-orange">
                    <?php 
                    echo count($polls) > 0 ? round($total_votes / count($polls), 1) : 0;
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-dracula-currentLine rounded-xl p-6 shadow-md">
        <h2 class="text-xl font-semibold text-dracula-purple mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Quick Actions
        </h2>
        <div class="grid grid-cols-1 gap-4">
            <a href="create_poll.php" class="group bg-dracula-selection bg-opacity-30 p-4 rounded-lg flex items-center transform transition-transform duration-300 hover:translate-x-2">
                <div class="bg-dracula-purple text-white p-3 rounded-lg mr-4 group-hover:bg-dracula-pink transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-dracula-foreground">Create New Poll</h3>
                    <p class="text-sm text-dracula-comment">Add a new poll for users to vote on</p>
                </div>
            </a>
            
            <a href="manage_sequences.php" class="group bg-dracula-selection bg-opacity-30 p-4 rounded-lg flex items-center transform transition-transform duration-300 hover:translate-x-2">
                <div class="bg-dracula-green text-dracula-bg p-3 rounded-lg mr-4 group-hover:bg-dracula-pink transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-dracula-foreground">Manage Poll Sequences</h3>
                    <p class="text-sm text-dracula-comment">Configure sequential polls with branching logic</p>
                </div>
            </a>
            
            <a href="export.php" class="group bg-dracula-selection bg-opacity-30 p-4 rounded-lg flex items-center transform transition-transform duration-300 hover:translate-x-2">
                <div class="bg-dracula-cyan text-dracula-bg p-3 rounded-lg mr-4 group-hover:bg-dracula-pink transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-dracula-foreground">Export Data</h3>
                    <p class="text-sm text-dracula-comment">Export poll data as CSV</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 