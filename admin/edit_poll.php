<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
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

// Fetch poll options
$stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
$stmt->execute([$pollId]);
$options = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['title'])) {
        $error = 'Poll title is required';
    } elseif (empty($_POST['options']) || count(array_filter($_POST['options'])) < 2) {
        $error = 'At least two options are required';
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Determine if this should be the active poll
            $isActive = isset($_POST['make_active']) ? 1 : 0;
            
            // If making this poll active and it's not already active, deactivate all other polls
            if ($isActive && !$poll['is_active']) {
                $stmt = $pdo->prepare("UPDATE polls SET is_active = 0");
                $stmt->execute();
            }
            
            // Update poll
            $stmt = $pdo->prepare("UPDATE polls SET title = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$_POST['title'], $_POST['description'], $isActive, $pollId]);
            
            // First delete all existing options (this will also delete associated votes)
            if (isset($_POST['reset_votes']) && $_POST['reset_votes'] == 1) {
                $stmt = $pdo->prepare("DELETE FROM options WHERE poll_id = ?");
                $stmt->execute([$pollId]);
                
                // Now insert the new options
                $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
                foreach ($_POST['options'] as $option) {
                    if (!empty(trim($option))) {
                        $stmt->execute([$pollId, trim($option)]);
                    }
                }
            } else {
                // Handle existing options and new options
                $existingOptionIds = [];
                if (isset($_POST['option_ids'])) {
                    $existingOptionIds = $_POST['option_ids'];
                }
                
                // Delete options that are no longer in the form
                $stmt = $pdo->prepare("DELETE FROM options WHERE poll_id = ? AND id NOT IN (" . 
                    implode(',', array_merge($existingOptionIds, [0])) . ")");
                $stmt->execute([$pollId]);
                
                // Update existing options
                if (!empty($existingOptionIds)) {
                    $stmt = $pdo->prepare("UPDATE options SET option_text = ? WHERE id = ? AND poll_id = ?");
                    foreach ($existingOptionIds as $i => $optionId) {
                        $stmt->execute([$_POST['options'][$i], $optionId, $pollId]);
                    }
                }
                
                // Add new options
                $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
                for ($i = count($existingOptionIds); $i < count($_POST['options']); $i++) {
                    if (!empty(trim($_POST['options'][$i]))) {
                        $stmt->execute([$pollId, trim($_POST['options'][$i])]);
                    }
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            $message = 'Poll updated successfully!';
            
            // Redirect to dashboard
            header("Location: index.php?message=Poll updated successfully!");
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = 'Error updating poll: ' . $e->getMessage();
        }
    }
}

// Include admin header
include_once 'admin_header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-dracula-pink mb-2">Edit Poll</h1>
    <p class="text-dracula-comment">Update your poll details and options</p>
</div>

<?php if (!empty($error)): ?>
    <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<div class="bg-dracula-currentLine shadow-md rounded-lg p-6">
    <form method="POST" action="" id="pollForm">
        <div class="mb-4">
            <label for="title" class="block text-dracula-cyan text-sm font-bold mb-2">Poll Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($poll['title']); ?>" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-dracula-cyan text-sm font-bold mb-2">Description (Optional)</label>
            <textarea name="description" id="description" rows="3" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple"><?php echo htmlspecialchars($poll['description']); ?></textarea>
        </div>
        
        <div class="mb-6">
            <label class="block text-dracula-cyan text-sm font-bold mb-2">Poll Options</label>
            <p class="text-sm text-dracula-comment mb-2">Add at least two options for your poll</p>
            
            <div id="optionsContainer">
                <?php foreach ($options as $index => $option): ?>
                    <div class="mb-2 flex items-center">
                        <input type="hidden" name="option_ids[]" value="<?php echo $option['id']; ?>">
                        <input type="text" name="options[]" value="<?php echo htmlspecialchars($option['option_text']); ?>" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option <?php echo $index + 1; ?>" required>
                        <?php if (count($options) > 2): ?>
                            <button type="button" class="ml-2 bg-dracula-red hover:bg-dracula-red/80 text-dracula-bg font-bold py-1 px-2 rounded focus:outline-none delete-option transition-colors">
                                &times;
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="addOption" class="mt-2 bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground font-bold py-2 px-4 rounded focus:outline-none transition-colors">
                Add Option
            </button>
        </div>
        
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <input type="checkbox" id="make_active" name="make_active" <?php echo $poll['is_active'] ? 'checked' : ''; ?> class="h-4 w-4 text-dracula-purple focus:ring-dracula-purple border-dracula-selection rounded">
                <label for="make_active" class="ml-2 block text-dracula-cyan">
                    Make this the active poll (will deactivate any currently active poll)
                </label>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="reset_votes" name="reset_votes" value="1" class="h-4 w-4 text-dracula-red focus:ring-dracula-red border-dracula-selection rounded">
                <label for="reset_votes" class="ml-2 block text-dracula-red">
                    Reset all votes (Warning: This will delete all existing votes for this poll)
                </label>
            </div>
        </div>
        
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none transition-colors">
                Update Poll
            </button>
            <a href="index.php" class="inline-block align-baseline font-bold text-sm text-dracula-cyan hover:text-dracula-green">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const optionsContainer = document.getElementById('optionsContainer');
        const addOptionButton = document.getElementById('addOption');
        let optionCount = <?php echo count($options); ?>;
        
        addOptionButton.addEventListener('click', function() {
            optionCount++;
            const optionDiv = document.createElement('div');
            optionDiv.className = 'mb-2 flex items-center';
            optionDiv.innerHTML = `
                <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option ${optionCount}" required>
                <button type="button" class="ml-2 bg-dracula-red hover:bg-dracula-red/80 text-dracula-bg font-bold py-1 px-2 rounded focus:outline-none delete-option transition-colors">
                    &times;
                </button>
            `;
            optionsContainer.appendChild(optionDiv);
            
            // Add event listener to delete button
            const deleteButton = optionDiv.querySelector('.delete-option');
            deleteButton.addEventListener('click', function() {
                optionsContainer.removeChild(optionDiv);
            });
        });
        
        // Add event listeners to existing delete buttons
        const deleteButtons = document.querySelectorAll('.delete-option');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                button.parentElement.remove();
            });
        });
    });
</script>

<?php
// Include admin footer
include_once 'admin_footer.php';
?> 