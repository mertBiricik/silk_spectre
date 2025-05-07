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

// Get the admin info
$admin_id = $_SESSION['admin_id'];
$admin_stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$admin_stmt->execute([$admin_id]);
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

// Get all sequences for dropdown
$sequences_stmt = $pdo->query("SELECT * FROM poll_sequences ORDER BY name ASC");
$sequences = $sequences_stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$title = '';
$description = '';
$sequence_id = 0;
$sequence_position = 0;
$duration_minutes = 5; // Default 5 minutes
$show_results_duration_seconds = 10; // Default 10 seconds
$options = ['', '', ''];
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $sequence_id = isset($_POST['sequence_id']) ? (int)$_POST['sequence_id'] : 0;
    $sequence_position = isset($_POST['sequence_position']) ? (int)$_POST['sequence_position'] : 0;
    $duration_minutes = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 5;
    $show_results_duration_seconds = isset($_POST['show_results_duration_seconds']) ? (int)$_POST['show_results_duration_seconds'] : 10;
    $options = isset($_POST['options']) ? $_POST['options'] : [];
    
    // Validate the form data
    if (empty($title)) {
        $errors[] = 'Poll title is required';
    }
    
    if (count($options) < 2) {
        $errors[] = 'At least two options are required';
    }
    
    // Filter out empty options
    $options = array_filter($options, function($option) {
        return trim($option) !== '';
    });
    
    if (count($options) < 2) {
        $errors[] = 'At least two non-empty options are required';
    }
    
    if ($duration_minutes < 1) {
        $errors[] = 'Poll duration must be at least 1 minute';
    }
    
    if ($show_results_duration_seconds < 1) {
        $errors[] = 'Results display duration must be at least 1 second';
    }
    
    // If there are no errors, create the poll
    if (empty($errors)) {
        try {
            // Begin a transaction
            $pdo->beginTransaction();
            
            // Insert the poll
            $poll_stmt = $pdo->prepare("
                INSERT INTO polls (
                    title, 
                    description, 
                    is_active, 
                    sequence_id, 
                    sequence_position, 
                    duration_minutes,
                    show_results_duration_seconds,
                    is_results_visible,
                    created_at
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // Default to inactive if part of a sequence
            $is_active = $sequence_id > 0 ? 0 : 1;
            
            $poll_stmt->execute([
                $title, 
                $description, 
                $is_active, 
                $sequence_id, 
                $sequence_position,
                $duration_minutes,
                $show_results_duration_seconds,
                0 // Initially results not visible
            ]);
            
            $poll_id = $pdo->lastInsertId();
            
            // Insert the options
            $option_stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
            
            foreach ($options as $option_text) {
                if (trim($option_text) !== '') {
                    $option_stmt->execute([$poll_id, $option_text]);
                }
            }
            
            // Commit the transaction
            $pdo->commit();
            
            // Redirect to the admin dashboard
            header('Location: index.php?message=Poll created successfully!');
            exit;
        } catch (PDOException $e) {
            // Roll back the transaction
            $pdo->rollBack();
            
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Include admin header
include_once 'admin_header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-dracula-purple">Create New Poll</h1>
        <a href="index.php" class="bg-dracula-currentLine hover:bg-dracula-comment text-dracula-foreground px-4 py-2 rounded-lg transition-colors">
            &larr; Back to Dashboard
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-dracula-red bg-opacity-20 border-l-4 border-dracula-red text-dracula-foreground p-4 mb-6 rounded-lg">
            <h3 class="font-bold mb-2">Please fix the following errors:</h3>
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="bg-dracula-currentLine rounded-xl p-6 shadow-lg">
        <form method="POST" action="" id="pollForm">
            <!-- Poll Title -->
            <div class="mb-6">
                <label for="title" class="block text-dracula-purple font-semibold mb-2">Poll Title <span class="text-dracula-red">*</span></label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    value="<?php echo htmlspecialchars($title); ?>" 
                    class="w-full bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                    placeholder="Enter a title for your poll"
                    required
                >
            </div>
            
            <!-- Poll Description -->
            <div class="mb-6">
                <label for="description" class="block text-dracula-purple font-semibold mb-2">Description (Optional)</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="w-full bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple h-24"
                    placeholder="Provide additional details about your poll"
                ><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            
            <!-- Sequence Settings -->
            <div class="mb-6 p-4 border border-dracula-comment rounded-lg bg-dracula-selection bg-opacity-10">
                <h3 class="text-xl font-semibold text-dracula-cyan mb-4">Sequence Settings</h3>
                
                <div class="mb-4">
                    <label for="sequence_id" class="block text-dracula-purple font-semibold mb-2">Add to Sequence</label>
                    <select 
                        id="sequence_id" 
                        name="sequence_id" 
                        class="w-full bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                    >
                        <option value="0">None (Standalone Poll)</option>
                        <?php foreach ($sequences as $sequence): ?>
                            <option value="<?php echo $sequence['id']; ?>" <?php echo $sequence_id == $sequence['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sequence['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-dracula-comment text-sm mt-1">Select a sequence to add this poll to, or leave as "None" for a standalone poll</p>
                </div>
                
                <div class="mb-4 sequence-dependent">
                    <label for="sequence_position" class="block text-dracula-purple font-semibold mb-2">Position in Sequence</label>
                    <input 
                        type="number" 
                        id="sequence_position" 
                        name="sequence_position" 
                        value="<?php echo $sequence_position ?: 1; ?>" 
                        min="1"
                        class="w-full bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                    >
                    <p class="text-dracula-comment text-sm mt-1">The position of this poll in the sequence (1 for first, 2 for second, etc.)</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="duration_minutes" class="block text-dracula-purple font-semibold mb-2">Poll Duration (minutes)</label>
                        <input 
                            type="number" 
                            id="duration_minutes" 
                            name="duration_minutes" 
                            value="<?php echo $duration_minutes; ?>" 
                            min="1"
                            class="w-full bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                        >
                        <p class="text-dracula-comment text-sm mt-1">How long the poll will be active for voting</p>
                    </div>
                    
                    <div>
                        <label for="show_results_duration_seconds" class="block text-dracula-purple font-semibold mb-2">Results Display (seconds)</label>
                        <input 
                            type="number" 
                            id="show_results_duration_seconds" 
                            name="show_results_duration_seconds" 
                            value="<?php echo $show_results_duration_seconds; ?>" 
                            min="1"
                            class="w-full bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                        >
                        <p class="text-dracula-comment text-sm mt-1">How long to display results before moving to the next poll</p>
                    </div>
                </div>
            </div>
            
            <!-- Poll Options -->
            <div class="mb-6">
                <label class="block text-dracula-purple font-semibold mb-2">Poll Options <span class="text-dracula-red">*</span></label>
                <p class="text-dracula-comment mb-4">Add at least two options for people to vote on</p>
                
                <div id="options-container">
                    <?php foreach (array_values($options) ?: ['', ''] as $index => $option): ?>
                        <div class="flex mb-2 option-row">
                            <input 
                                type="text" 
                                name="options[]" 
                                value="<?php echo htmlspecialchars($option); ?>"
                                class="flex-1 bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                                placeholder="Enter an option"
                                required
                            >
                            <?php if ($index > 1): ?>
                                <button 
                                    type="button" 
                                    class="remove-option ml-2 bg-dracula-red hover:bg-opacity-80 text-white px-3 py-2 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button 
                    type="button" 
                    id="add-option"
                    class="mt-2 bg-dracula-green hover:bg-opacity-80 text-white font-semibold px-4 py-2 rounded-lg transition-colors flex items-center"
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Option
                </button>
            </div>
            
            <!-- Submit Button -->
            <div class="mt-8">
                <button 
                    type="submit" 
                    class="bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-3 px-6 rounded-lg shadow-md transition-colors duration-300 transform hover:scale-105"
                >
                    Create Poll
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add option button
    const addOptionBtn = document.getElementById('add-option');
    const optionsContainer = document.getElementById('options-container');
    
    addOptionBtn.addEventListener('click', function() {
        const optionRow = document.createElement('div');
        optionRow.className = 'flex mb-2 option-row';
        optionRow.innerHTML = `
            <input 
                type="text" 
                name="options[]" 
                class="flex-1 bg-dracula-selection bg-opacity-20 border border-dracula-comment rounded-lg px-4 py-2 text-dracula-foreground focus:outline-none focus:border-dracula-purple"
                placeholder="Enter an option"
                required
            >
            <button 
                type="button" 
                class="remove-option ml-2 bg-dracula-red hover:bg-opacity-80 text-white px-3 py-2 rounded-lg transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        optionsContainer.appendChild(optionRow);
        
        // Add event listener to the new remove button
        const removeBtn = optionRow.querySelector('.remove-option');
        removeBtn.addEventListener('click', removeOption);
    });
    
    // Remove option function
    function removeOption() {
        // Only remove if there are more than 2 options
        if (document.querySelectorAll('.option-row').length > 2) {
            this.closest('.option-row').remove();
        }
    }
    
    // Add event listeners to existing remove buttons
    document.querySelectorAll('.remove-option').forEach(button => {
        button.addEventListener('click', removeOption);
    });
    
    // Sequence dependent fields
    const sequenceSelect = document.getElementById('sequence_id');
    const sequenceDependentFields = document.querySelectorAll('.sequence-dependent');
    
    function toggleSequenceDependentFields() {
        const isPartOfSequence = sequenceSelect.value > 0;
        sequenceDependentFields.forEach(field => {
            if (isPartOfSequence) {
                field.style.display = 'block';
            } else {
                field.style.display = 'none';
            }
        });
    }
    
    // Initialize sequence dependent fields
    toggleSequenceDependentFields();
    
    // Update on change
    sequenceSelect.addEventListener('change', toggleSequenceDependentFields);
});
</script>

<?php include '../includes/footer.php'; ?> 