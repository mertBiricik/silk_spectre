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

// Process form submission to create new sequence
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_sequence'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $error = 'Sequence name is required';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO poll_sequences (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $message = 'Sequence created successfully!';
        } catch (PDOException $e) {
            $error = 'Error creating sequence: ' . $e->getMessage();
        }
    }
}

// Process activation/deactivation
if (isset($_GET['activate'])) {
    $sequence_id = (int)$_GET['activate'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Deactivate all sequences first
        $stmt = $pdo->prepare("UPDATE poll_sequences SET is_active = 0");
        $stmt->execute();
        
        // Activate the selected sequence
        $stmt = $pdo->prepare("UPDATE poll_sequences SET is_active = 1 WHERE id = ?");
        $stmt->execute([$sequence_id]);
        
        // Commit transaction
        $pdo->commit();
        $message = 'Sequence activated successfully!';
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = 'Error activating sequence: ' . $e->getMessage();
    }
}

if (isset($_GET['deactivate'])) {
    $sequence_id = (int)$_GET['deactivate'];
    
    try {
        $stmt = $pdo->prepare("UPDATE poll_sequences SET is_active = 0 WHERE id = ?");
        $stmt->execute([$sequence_id]);
        $message = 'Sequence deactivated successfully!';
    } catch (PDOException $e) {
        $error = 'Error deactivating sequence: ' . $e->getMessage();
    }
}

// Delete sequence
if (isset($_GET['delete'])) {
    $sequence_id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM poll_sequences WHERE id = ?");
        $stmt->execute([$sequence_id]);
        $message = 'Sequence deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting sequence: ' . $e->getMessage();
    }
}

// Get all sequences
$sequences_stmt = $pdo->query("SELECT * FROM poll_sequences ORDER BY is_active DESC, created_at DESC");
$sequences = $sequences_stmt->fetchAll(PDO::FETCH_ASSOC);

// Load header but prevent automatic display of message from the URL parameter
$prevent_auto_message = true;
include '../includes/header.php';
// Reset the variable after header inclusion
$prevent_auto_message = false;
?>

<div class="mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-dracula-purple mb-2">Manage Poll Sequences</h1>
            <p class="text-dracula-comment">Create and manage sequences of polls with conditional branching.</p>
        </div>
        <div class="flex gap-3">
            <a href="index.php" class="bg-dracula-cyan hover:bg-dracula-pink text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
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
         class="bg-dracula-green bg-opacity-20 border-l-4 border-dracula-green text-dracula-foreground p-4 mb-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <svg class="w-6 h-6 mr-2 text-dracula-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <p><?php echo $message; ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div x-data="{ show: true }" 
         x-init="setTimeout(() => show = false, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-foreground p-4 mb-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <svg class="w-6 h-6 mr-2 text-dracula-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <p><?php echo $error; ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Create Sequence Form -->
<div class="bg-dracula-currentLine rounded-xl p-6 shadow-md mb-8">
    <h2 class="text-xl font-semibold text-dracula-purple mb-4">Create New Poll Sequence</h2>
    
    <form method="POST" action="">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="name" class="block text-dracula-foreground mb-2">Sequence Name</label>
                <input type="text" id="name" name="name" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
            </div>
            
            <div>
                <label for="description" class="block text-dracula-foreground mb-2">Description</label>
                <textarea id="description" name="description" rows="3" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple"></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" name="create_sequence" class="bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-300">
                    Create Sequence
                </button>
            </div>
        </div>
    </form>
</div>

<!-- List of Sequences -->
<div class="bg-dracula-currentLine rounded-xl p-6 shadow-md mb-8">
    <h2 class="text-xl font-semibold text-dracula-purple mb-4">All Poll Sequences</h2>
    
    <?php if (empty($sequences)): ?>
        <p class="text-dracula-comment text-center py-10">No sequences have been created yet. Create your first sequence to get started.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-dracula-selection">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Polls</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-dracula-currentLine divide-y divide-dracula-selection">
                    <?php foreach ($sequences as $sequence): ?>
                        <?php
                        // Get poll count for this sequence
                        $poll_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM polls WHERE sequence_id = ?");
                        $poll_stmt->execute([$sequence['id']]);
                        $poll_count = $poll_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <tr class="transform transition-all duration-200 hover:bg-dracula-selection hover:bg-opacity-40">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-dracula-foreground"><?php echo htmlspecialchars($sequence['name']); ?></div>
                                <div class="text-sm text-dracula-comment"><?php echo htmlspecialchars($sequence['description']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($sequence['is_active']): ?>
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
                                <?php echo date('M j, Y', strtotime($sequence['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-dracula-comment">
                                <?php echo $poll_count; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="edit_sequence.php?id=<?php echo $sequence['id']; ?>" class="text-dracula-purple hover:text-dracula-pink transition-colors duration-300">
                                        Edit
                                    </a>
                                    
                                    <?php if (!$sequence['is_active']): ?>
                                        <a href="manage_sequences.php?activate=<?php echo $sequence['id']; ?>" class="text-dracula-green hover:text-dracula-pink transition-colors duration-300">
                                            Activate
                                        </a>
                                    <?php else: ?>
                                        <a href="manage_sequences.php?deactivate=<?php echo $sequence['id']; ?>" class="text-dracula-orange hover:text-dracula-pink transition-colors duration-300">
                                            Deactivate
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="delete_sequence.php?id=<?php echo $sequence['id']; ?>" class="text-dracula-red hover:text-dracula-pink transition-colors duration-300" 
                                       onclick="return confirm('Are you sure you want to delete this sequence? This will remove all associated polls and branches. This action cannot be undone.')">
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

<?php include '../includes/footer.php'; ?> 