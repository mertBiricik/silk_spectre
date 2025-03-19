<?php
require_once 'includes/database.php';

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
            
            // Insert poll
            $stmt = $pdo->prepare("INSERT INTO polls (title, description) VALUES (?, ?)");
            $stmt->execute([$_POST['title'], $_POST['description']]);
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
            
            // Redirect to the new poll
            header("Location: view_poll.php?id=$pollId&message=Poll created successfully!");
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = 'Error creating poll: ' . $e->getMessage();
        }
    }
}

// Include header
include_once 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-dracula-pink mb-2">Create New Poll</h1>
    <p class="text-dracula-comment">Create a poll and share it with others!</p>
</div>

<div class="bg-dracula-currentLine shadow-md rounded-lg p-6">
    <form method="POST" action="" id="pollForm">
        <div class="mb-4">
            <label for="title" class="block text-dracula-cyan text-sm font-bold mb-2">Poll Title</label>
            <input type="text" name="title" id="title" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" required>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-dracula-cyan text-sm font-bold mb-2">Description (Optional)</label>
            <textarea name="description" id="description" rows="3" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple"></textarea>
        </div>
        
        <div class="mb-4">
            <label class="block text-dracula-cyan text-sm font-bold mb-2">Poll Options</label>
            <p class="text-sm text-dracula-comment mb-2">Add at least two options for your poll</p>
            
            <div id="optionsContainer">
                <div class="mb-2 flex items-center">
                    <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option 1" required>
                </div>
                <div class="mb-2 flex items-center">
                    <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option 2" required>
                </div>
            </div>
            
            <button type="button" id="addOption" class="mt-2 bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground font-bold py-2 px-4 rounded focus:outline-none">
                Add Option
            </button>
        </div>
        
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-dracula-purple hover:bg-dracula-pink text-dracula-bg font-bold py-2 px-4 rounded focus:outline-none">
                Create Poll
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
        let optionCount = 2;
        
        addOptionButton.addEventListener('click', function() {
            optionCount++;
            const optionDiv = document.createElement('div');
            optionDiv.className = 'mb-2 flex items-center';
            optionDiv.innerHTML = `
                <input type="text" name="options[]" class="shadow appearance-none bg-dracula-bg border border-dracula-selection rounded w-full py-2 px-3 text-dracula-foreground leading-tight focus:outline-none focus:border-dracula-purple" placeholder="Option ${optionCount}" required>
                <button type="button" class="ml-2 bg-dracula-red hover:bg-dracula-red/80 text-dracula-bg font-bold py-1 px-2 rounded focus:outline-none delete-option">
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
include_once 'includes/footer.php';
?> 