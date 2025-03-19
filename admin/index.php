<?php
session_start();
require_once '../includes/database.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle poll activation/deactivation
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $pollId = (int)$_GET['id'];
    
    if ($_GET['action'] === 'activate') {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // First deactivate all polls
            $stmt = $pdo->prepare("UPDATE polls SET is_active = 0");
            $stmt->execute();
            
            // Then activate the selected poll
            $stmt = $pdo->prepare("UPDATE polls SET is_active = 1 WHERE id = ?");
            $stmt->execute([$pollId]);
            
            // Commit transaction
            $pdo->commit();
            
            $message = "Poll activated successfully!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "Error activating poll: " . $e->getMessage();
        }
    } elseif ($_GET['action'] === 'deactivate') {
        try {
            $stmt = $pdo->prepare("UPDATE polls SET is_active = 0 WHERE id = ?");
            $stmt->execute([$pollId]);
            $message = "Poll deactivated successfully!";
        } catch (PDOException $e) {
            $error = "Error deactivating poll: " . $e->getMessage();
        }
    } elseif ($_GET['action'] === 'delete') {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete options and votes first (due to foreign key constraints)
            $stmt = $pdo->prepare("DELETE FROM options WHERE poll_id = ?");
            $stmt->execute([$pollId]);
            
            // Then delete the poll
            $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
            $stmt->execute([$pollId]);
            
            // Commit transaction
            $pdo->commit();
            
            $message = "Poll deleted successfully!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "Error deleting poll: " . $e->getMessage();
        }
    }
}

// Get all polls
$stmt = $pdo->query("SELECT p.*, 
                     (SELECT COUNT(*) FROM options o WHERE o.poll_id = p.id) AS option_count,
                     (SELECT COUNT(*) FROM votes v JOIN options o ON v.option_id = o.id WHERE o.poll_id = p.id) AS vote_count
                     FROM polls p ORDER BY created_at DESC");
$polls = $stmt->fetchAll();

// Include admin header
include_once 'admin_header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-dracula-pink mb-2">Poll Management</h1>
        <p class="text-dracula-comment">Create, edit and manage your polls</p>
    </div>
    <div>
        <a href="create_poll.php" class="bg-dracula-green hover:bg-dracula-green/80 text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none transition-colors">
            Create New Poll
        </a>
    </div>
</div>

<?php if (isset($message)): ?>
    <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-green p-4 mb-6" role="alert">
        <p><?php echo $message; ?></p>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['message'])): ?>
    <div class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-green p-4 mb-6" role="alert">
        <p><?php echo htmlspecialchars($_GET['message']); ?></p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
    </div>
<?php endif; ?>

<div class="bg-dracula-currentLine shadow-md rounded-lg overflow-hidden">
    <?php if (count($polls) > 0): ?>
        <table class="min-w-full divide-y divide-dracula-selection">
            <thead class="bg-dracula-selection">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">
                        Title
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">
                        Created
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">
                        Options
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">
                        Votes
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dracula-purple uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-dracula-bg divide-y divide-dracula-selection">
                <?php foreach ($polls as $poll): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-dracula-foreground"><?php echo htmlspecialchars($poll['title']); ?></div>
                            <?php if (!empty($poll['description'])): ?>
                                <div class="text-sm text-dracula-comment"><?php echo htmlspecialchars(substr($poll['description'], 0, 50)) . (strlen($poll['description']) > 50 ? '...' : ''); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-dracula-foreground"><?php echo date('M j, Y', strtotime($poll['created_at'])); ?></div>
                            <div class="text-sm text-dracula-comment"><?php echo date('h:i A', strtotime($poll['created_at'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($poll['is_active']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-dracula-green bg-opacity-10 text-dracula-green">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-dracula-comment bg-opacity-10 text-dracula-comment">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-foreground">
                            <?php echo $poll['option_count']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-dracula-foreground">
                            <?php echo $poll['vote_count']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="edit_poll.php?id=<?php echo $poll['id']; ?>" class="text-dracula-cyan hover:text-dracula-green">
                                    Edit
                                </a>
                                
                                <a href="view_results.php?id=<?php echo $poll['id']; ?>" class="text-dracula-purple hover:text-dracula-pink">
                                    Results
                                </a>
                                
                                <?php if ($poll['is_active']): ?>
                                    <a href="index.php?action=deactivate&id=<?php echo $poll['id']; ?>" class="text-dracula-yellow hover:text-dracula-orange" onclick="return confirm('Are you sure you want to deactivate this poll?')">
                                        Deactivate
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?action=activate&id=<?php echo $poll['id']; ?>" class="text-dracula-green hover:text-dracula-cyan" onclick="return confirm('Activating this poll will deactivate any currently active poll. Continue?')">
                                        Activate
                                    </a>
                                <?php endif; ?>
                                
                                <a href="index.php?action=delete&id=<?php echo $poll['id']; ?>" class="text-dracula-red hover:text-dracula-orange" onclick="return confirm('Are you sure you want to delete this poll? This action cannot be undone and will delete all associated votes.')">
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="p-6 text-center text-dracula-comment">
            <p>No polls found. <a href="create_poll.php" class="text-dracula-purple hover:text-dracula-pink">Create your first poll</a></p>
        </div>
    <?php endif; ?>
</div>

<?php
// Include admin footer
include_once 'admin_footer.php';
?> 