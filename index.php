<?php
require_once 'includes/database.php';

// Fetch all polls from the database
$stmt = $pdo->query("SELECT * FROM polls ORDER BY created_at DESC");
$polls = $stmt->fetchAll();

// Include header
include_once 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-dracula-pink mb-2">Available Polls</h1>
    <p class="text-dracula-comment">Vote on existing polls or create your own!</p>
</div>

<?php if (empty($polls)): ?>
    <div class="bg-dracula-currentLine shadow-md rounded-lg p-6 mb-6">
        <p class="text-dracula-foreground">No polls available yet. Be the first to create one!</p>
        <a href="create_poll.php" class="mt-4 inline-block bg-dracula-purple text-dracula-bg px-4 py-2 rounded hover:bg-dracula-pink">Create Poll</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($polls as $poll): ?>
            <div class="bg-dracula-currentLine shadow-md rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-dracula-cyan mb-2"><?php echo htmlspecialchars($poll['title']); ?></h2>
                    <p class="text-dracula-foreground mb-4 line-clamp-2"><?php echo htmlspecialchars($poll['description']); ?></p>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-dracula-comment">Created: <?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                        <a href="view_poll.php?id=<?php echo $poll['id']; ?>" class="bg-dracula-purple text-dracula-bg px-4 py-2 rounded text-sm hover:bg-dracula-pink">Vote Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="mt-8 text-center">
    <a href="create_poll.php" class="bg-dracula-purple text-dracula-bg px-6 py-3 rounded-lg text-lg font-medium hover:bg-dracula-pink transition-colors duration-300">Create New Poll</a>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?> 