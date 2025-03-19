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
            
            // If making this poll active, deactivate all other polls
            if ($isActive) {
                $stmt = $pdo->prepare("UPDATE polls SET is_active = 0");
                $stmt->execute();
            }
            
            // Insert poll
            $stmt = $pdo->prepare("INSERT INTO polls (title, description, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['title'], $_POST['description'], $isActive]);
            $pollId = $pdo->lastInsertId();
            
            // Insert options
            $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
            foreach ($_POST['options'] as $option) {
                if (!empty(trim($option))) {
                    $stmt->execute([$pollId, trim($option)]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            $message = 'Poll created successfully!';
            
            // Redirect to dashboard
            header("Location: index.php?message=Poll created successfully!");
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = 'Error creating poll: ' . $e->getMessage();
        }
    }
}

// Include admin header
include_once 'admin_header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-dracula-pink mb-2">Create New Poll</h1>
    <p class="text-dracula-comment">Create a new poll for your audience</p>
</div>

<?php if (!empty($error)): ?>
    <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-red p-4 mb-6" role="alert">
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<div class="bg-dracula-currentLine shadow-md rounded-lg p-4 sm:p-6">
    <form method="POST" action="" id="pollForm" class="space-y-4">
        <div>
            <label for="title" class="block text-dracula-cyan text-sm font-bold mb-2">Poll Title</label>
            <input type="text" name="title" id="title" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
        </div>
        
        <div>
            <label for="description" class="block text-dracula-cyan text-sm font-bold mb-2">Description (Optional)</label>
            <textarea name="description" id="description" rows="3" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple"></textarea>
        </div>
        
        <div>
            <label class="block text-dracula-cyan text-sm font-bold mb-2">Poll Options</label>
            <p class="text-sm text-dracula-comment mb-2">Add at least two options for your poll</p>
            
            <div id="optionsContainer" class="space-y-3">
                <div class="flex items-center">
                    <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option 1" required>
                </div>
                <div class="flex items-center">
                    <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option 2" required>
                </div>
            </div>
            
            <button type="button" id="addOption" class="mt-4 bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground font-bold py-3 px-4 rounded focus:outline-none transition-colors w-full sm:w-auto">
                Add Option
            </button>
        </div>
        
        <div>
            <div class="flex items-center">
                <input type="checkbox" id="make_active" name="make_active" class="h-5 w-5 text-dracula-purple focus:ring-dracula-purple border-dracula-selection rounded">
                <label for="make_active" class="ml-3 block text-dracula-cyan text-sm">
                    Make this the active poll (will deactivate any currently active poll)
                </label>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <button type="submit" class="w-full sm:w-auto bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-3 px-6 rounded focus:outline-none transition-colors">
                Create Poll
            </button>
            <a href="index.php" class="w-full sm:w-auto text-center bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground font-bold py-3 px-6 rounded focus:outline-none transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const optionsContainer = document.getElementById('optionsContainer');
        const addOptionButton = document.getElementById('addOption');
        let optionCount = 2;
        
        addOptionButton.addEventListener('click', function() {
            optionCount++;
            const optionDiv = document.createElement('div');
            optionDiv.className = 'flex items-center';
            optionDiv.innerHTML = `
                <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-3 px-4 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option ${optionCount}" required>
                <button type="button" class="ml-2 bg-dracula-red hover:bg-dracula-red/80 text-dracula-bg font-bold py-2 px-3 rounded focus:outline-none delete-option transition-colors">
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
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?> 