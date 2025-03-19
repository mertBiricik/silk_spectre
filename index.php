<?php
require_once 'includes/database.php';

// Fetch the active poll first
$stmt = $pdo->query("SELECT * FROM polls WHERE is_active = 1 LIMIT 1");
$activePoll = $stmt->fetch();

// Fetch all other polls
$stmt = $pdo->query("SELECT * FROM polls WHERE is_active = 0 ORDER BY created_at DESC");
$inactivePollsData = $stmt->fetchAll();

// Include header
include_once 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-dracula-pink mb-2">Polls</h1>
    <p class="text-dracula-comment">Vote on polls created by administrators</p>
</div>

<?php if ($activePoll): ?>
    <!-- Active Poll Section -->
    <div class="mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-dracula-green mb-4">Current Active Poll</h2>
        <div class="bg-dracula-currentLine shadow-md rounded-lg overflow-hidden">
            <div class="p-4 sm:p-6">
                <h3 class="text-xl sm:text-2xl font-semibold text-dracula-cyan mb-2"><?php echo htmlspecialchars($activePoll['title']); ?></h3>
                <?php if (!empty($activePoll['description'])): ?>
                    <p class="text-dracula-foreground mb-4"><?php echo htmlspecialchars($activePoll['description']); ?></p>
                <?php endif; ?>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <span class="text-sm text-dracula-comment">Created: <?php echo date('M j, Y', strtotime($activePoll['created_at'])); ?></span>
                    <a href="view_poll.php?id=<?php echo $activePoll['id']; ?>" class="w-full sm:w-auto bg-dracula-green text-dracula-bg px-6 py-3 rounded text-center text-lg font-medium hover:bg-dracula-green/80 transition-colors">Vote Now</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($activePoll) && empty($inactivePollsData)): ?>
    <!-- No Polls Available -->
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-4 sm:p-6 mb-6">
        <p class="text-dracula-foreground">No polls available yet. Please check back later when an administrator has created a poll.</p>
    </div>
<?php elseif (!empty($inactivePollsData)): ?>
    <!-- Other Polls Section -->
    <div class="mt-8">
        <h2 class="text-xl sm:text-2xl font-bold text-dracula-purple mb-4">Previous Polls</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($inactivePollsData as $poll): ?>
                <div class="bg-dracula-currentLine shadow-md rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold text-dracula-cyan mb-2"><?php echo htmlspecialchars($poll['title']); ?></h3>
                        <p class="text-dracula-foreground mb-4 line-clamp-2"><?php echo htmlspecialchars($poll['description']); ?></p>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <span class="text-sm text-dracula-comment">Created: <?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                            <a href="view_poll.php?id=<?php echo $poll['id']; ?>" class="w-full sm:w-auto bg-dracula-purple text-dracula-bg px-4 py-2 rounded text-center text-sm hover:bg-dracula-pink transition-colors">View Results</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="mt-8 text-center">
    <p class="text-dracula-comment mb-4">Only administrators can create new polls.</p>
    <a href="admin/login.php" class="inline-block bg-dracula-purple text-dracula-bg px-6 py-3 rounded-lg text-lg font-medium hover:bg-dracula-pink transition-colors duration-300">Admin Login</a>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?> 