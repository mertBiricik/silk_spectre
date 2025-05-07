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

// Check if sequence ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_sequences.php');
    exit;
}

$sequence_id = (int)$_GET['id'];

// Get admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sequence info
$stmt = $pdo->prepare("SELECT * FROM poll_sequences WHERE id = ?");
$stmt->execute([$sequence_id]);
$sequence = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sequence) {
    header('Location: manage_sequences.php');
    exit;
}

// Process form submission to update sequence details
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sequence'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $error = 'Sequence name is required';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE poll_sequences SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $sequence_id]);
            $message = 'Sequence updated successfully!';
            
            // Refresh sequence data
            $stmt = $pdo->prepare("SELECT * FROM poll_sequences WHERE id = ?");
            $stmt->execute([$sequence_id]);
            $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = 'Error updating sequence: ' . $e->getMessage();
        }
    }
}

// Add a poll to the sequence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_poll'])) {
    $poll_id = (int)$_POST['poll_id'];
    $position = (int)$_POST['position'];
    $duration = (int)$_POST['duration'];
    $show_results_duration = (int)$_POST['show_results_duration'];
    
    try {
        // Update the poll to be part of this sequence
        $stmt = $pdo->prepare("UPDATE polls SET sequence_id = ?, sequence_position = ?, duration_minutes = ?, show_results_duration_seconds = ? WHERE id = ?");
        $stmt->execute([$sequence_id, $position, $duration, $show_results_duration, $poll_id]);
        $message = 'Poll added to sequence successfully!';
    } catch (PDOException $e) {
        $error = 'Error adding poll to sequence: ' . $e->getMessage();
    }
}

// Remove a poll from the sequence
if (isset($_GET['remove_poll'])) {
    $poll_id = (int)$_GET['remove_poll'];
    
    try {
        // Update the poll to remove it from this sequence
        $stmt = $pdo->prepare("UPDATE polls SET sequence_id = NULL, sequence_position = NULL WHERE id = ? AND sequence_id = ?");
        $stmt->execute([$poll_id, $sequence_id]);
        $message = 'Poll removed from sequence successfully!';
    } catch (PDOException $e) {
        $error = 'Error removing poll from sequence: ' . $e->getMessage();
    }
}

// Add branching logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $source_poll_id = (int)$_POST['source_poll_id'];
    $option_id = (int)$_POST['option_id'];
    $target_poll_id = (int)$_POST['target_poll_id'];
    
    try {
        // Check if this branch already exists
        $check_stmt = $pdo->prepare("SELECT id FROM poll_branching WHERE sequence_id = ? AND source_poll_id = ? AND option_id = ?");
        $check_stmt->execute([$sequence_id, $source_poll_id, $option_id]);
        
        if ($check_stmt->fetch()) {
            // Update existing branch
            $stmt = $pdo->prepare("UPDATE poll_branching SET target_poll_id = ? WHERE sequence_id = ? AND source_poll_id = ? AND option_id = ?");
            $stmt->execute([$target_poll_id, $sequence_id, $source_poll_id, $option_id]);
        } else {
            // Create new branch
            $stmt = $pdo->prepare("INSERT INTO poll_branching (sequence_id, source_poll_id, option_id, target_poll_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sequence_id, $source_poll_id, $option_id, $target_poll_id]);
        }
        
        $message = 'Branching logic added successfully!';
    } catch (PDOException $e) {
        $error = 'Error adding branching logic: ' . $e->getMessage();
    }
}

// Remove branching logic
if (isset($_GET['remove_branch'])) {
    $branch_id = (int)$_GET['remove_branch'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM poll_branching WHERE id = ? AND sequence_id = ?");
        $stmt->execute([$branch_id, $sequence_id]);
        $message = 'Branching logic removed successfully!';
    } catch (PDOException $e) {
        $error = 'Error removing branching logic: ' . $e->getMessage();
    }
}

// Get all polls in this sequence
$sequence_polls_stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM votes v WHERE v.poll_id = p.id) as vote_count
    FROM polls p 
    WHERE p.sequence_id = ? 
    ORDER BY p.sequence_position ASC
");
$sequence_polls_stmt->execute([$sequence_id]);
$sequence_polls = $sequence_polls_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available polls not in any sequence
$available_polls_stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM votes v WHERE v.poll_id = p.id) as vote_count
    FROM polls p 
    WHERE p.sequence_id IS NULL 
    ORDER BY p.created_at DESC
");
$available_polls_stmt->execute();
$available_polls = $available_polls_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all branching logic for this sequence
$branching_stmt = $pdo->prepare("
    SELECT b.*, 
           sp.title as source_poll_title,
           tp.title as target_poll_title,
           o.option_text
    FROM poll_branching b
    JOIN polls sp ON b.source_poll_id = sp.id
    JOIN polls tp ON b.target_poll_id = tp.id
    JOIN options o ON b.option_id = o.id
    WHERE b.sequence_id = ?
    ORDER BY sp.sequence_position ASC, o.id ASC
");
$branching_stmt->execute([$sequence_id]);
$branching_rules = $branching_stmt->fetchAll(PDO::FETCH_ASSOC);

// Load header but prevent automatic display of message from the URL parameter
$prevent_auto_message = true;
include '../includes/header.php';
// Reset the variable after header inclusion
$prevent_auto_message = false;
?>

<div class="mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-dracula-purple mb-2">Edit Sequence: <?php echo htmlspecialchars($sequence['name']); ?></h1>
            <p class="text-dracula-comment">Manage the polls in this sequence and set up branching logic.</p>
        </div>
        <div class="flex gap-3">
            <a href="manage_sequences.php" class="bg-dracula-cyan hover:bg-dracula-pink text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Sequences
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

<!-- Sequence Details -->
<div class="bg-dracula-currentLine rounded-xl p-6 shadow-md mb-8">
    <h2 class="text-xl font-semibold text-dracula-purple mb-4">Sequence Details</h2>
    
    <form method="POST" action="">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="name" class="block text-dracula-foreground mb-2">Sequence Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($sequence['name']); ?>" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
            </div>
            
            <div>
                <label for="description" class="block text-dracula-foreground mb-2">Description</label>
                <textarea id="description" name="description" rows="3" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple"><?php echo htmlspecialchars($sequence['description']); ?></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" name="update_sequence" class="bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-300">
                    Update Sequence
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Polls in Sequence -->
<div class="bg-dracula-currentLine rounded-xl p-6 shadow-md mb-8">
    <h2 class="text-xl font-semibold text-dracula-purple mb-4">Polls in This Sequence</h2>
    
    <?php if (empty($sequence_polls)): ?>
        <p class="text-dracula-comment text-center py-10">No polls have been added to this sequence yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-dracula-selection">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Results Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Votes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-dracula-currentLine divide-y divide-dracula-selection">
                    <?php foreach ($sequence_polls as $poll): ?>
                        <tr class="transform transition-all duration-200 hover:bg-dracula-selection hover:bg-opacity-40">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-dracula-foreground"><?php echo $poll['sequence_position']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-dracula-foreground"><?php echo htmlspecialchars($poll['title']); ?></div>
                                <div class="text-sm text-dracula-comment"><?php echo htmlspecialchars(substr($poll['description'], 0, 50)) . (strlen($poll['description']) > 50 ? '...' : ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-dracula-comment">
                                <?php echo $poll['duration_minutes']; ?> minutes
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-dracula-comment">
                                <?php echo $poll['show_results_duration_seconds']; ?> seconds
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-dracula-comment">
                                <?php echo $poll['vote_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="edit_poll.php?id=<?php echo $poll['id']; ?>" class="text-dracula-purple hover:text-dracula-pink transition-colors duration-300">
                                        Edit
                                    </a>
                                    <a href="edit_sequence.php?id=<?php echo $sequence_id; ?>&remove_poll=<?php echo $poll['id']; ?>" class="text-dracula-red hover:text-dracula-pink transition-colors duration-300" 
                                       onclick="return confirm('Are you sure you want to remove this poll from the sequence?')">
                                        Remove
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- Add Poll to Sequence Form -->
    <div class="mt-8 pt-6 border-t border-dracula-selection">
        <h3 class="text-lg font-semibold text-dracula-foreground mb-4">Add Poll to Sequence</h3>
        
        <?php if (empty($available_polls)): ?>
            <p class="text-dracula-comment py-4">No available polls. <a href="create_poll.php" class="text-dracula-purple hover:text-dracula-pink">Create a new poll</a> to add to this sequence.</p>
        <?php else: ?>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="poll_id" class="block text-dracula-foreground mb-2">Select Poll</label>
                        <select id="poll_id" name="poll_id" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                            <option value="">Select a poll...</option>
                            <?php foreach ($available_polls as $poll): ?>
                                <option value="<?php echo $poll['id']; ?>"><?php echo htmlspecialchars($poll['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="position" class="block text-dracula-foreground mb-2">Position</label>
                        <input type="number" id="position" name="position" value="<?php echo count($sequence_polls) + 1; ?>" min="1" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                    </div>
                    
                    <div>
                        <label for="duration" class="block text-dracula-foreground mb-2">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" value="60" min="1" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                    </div>
                    
                    <div>
                        <label for="show_results_duration" class="block text-dracula-foreground mb-2">Results Time (seconds)</label>
                        <input type="number" id="show_results_duration" name="show_results_duration" value="30" min="5" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                    </div>
                    
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" name="add_poll" class="bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-300">
                            Add to Sequence
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Branching Logic -->
<div class="bg-dracula-currentLine rounded-xl p-6 shadow-md mb-8">
    <h2 class="text-xl font-semibold text-dracula-purple mb-4">Conditional Branching Logic</h2>
    
    <?php if (empty($sequence_polls)): ?>
        <p class="text-dracula-comment text-center py-4">Add polls to the sequence to set up branching logic.</p>
    <?php else: ?>
        <?php if (empty($branching_rules)): ?>
            <p class="text-dracula-comment py-4">No branching rules defined yet. Create rules to determine the path through the sequence based on poll results.</p>
        <?php else: ?>
            <div class="overflow-x-auto mb-8">
                <table class="min-w-full divide-y divide-dracula-selection">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Source Poll</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">If Option Selected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Go To Poll</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-dracula-comment uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-dracula-currentLine divide-y divide-dracula-selection">
                        <?php foreach ($branching_rules as $rule): ?>
                            <tr class="transform transition-all duration-200 hover:bg-dracula-selection hover:bg-opacity-40">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-dracula-foreground"><?php echo htmlspecialchars($rule['source_poll_title']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-dracula-foreground"><?php echo htmlspecialchars($rule['option_text']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-dracula-foreground"><?php echo htmlspecialchars($rule['target_poll_title']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="edit_sequence.php?id=<?php echo $sequence_id; ?>&remove_branch=<?php echo $rule['id']; ?>" class="text-dracula-red hover:text-dracula-pink transition-colors duration-300" 
                                       onclick="return confirm('Are you sure you want to remove this branching rule?')">
                                        Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Add Branching Logic Form -->
        <div class="pt-4 border-t border-dracula-selection">
            <h3 class="text-lg font-semibold text-dracula-foreground mb-4">Add Branching Rule</h3>
            
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="source_poll_id" class="block text-dracula-foreground mb-2">Source Poll</label>
                        <select id="source_poll_id" name="source_poll_id" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                            <option value="">Select source poll...</option>
                            <?php foreach ($sequence_polls as $poll): ?>
                                <option value="<?php echo $poll['id']; ?>"><?php echo htmlspecialchars($poll['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="option_id" class="block text-dracula-foreground mb-2">If Option Selected</label>
                        <select id="option_id" name="option_id" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                            <option value="">Select poll first...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="target_poll_id" class="block text-dracula-foreground mb-2">Go To Poll</label>
                        <select id="target_poll_id" name="target_poll_id" class="w-full bg-dracula-selection bg-opacity-30 border border-dracula-selection rounded-lg py-2 px-4 text-dracula-foreground focus:outline-none focus:border-dracula-purple" required>
                            <option value="">Select target poll...</option>
                            <?php foreach ($sequence_polls as $poll): ?>
                                <option value="<?php echo $poll['id']; ?>"><?php echo htmlspecialchars($poll['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" name="add_branch" class="bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-300">
                            Add Branching Rule
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for dynamic option selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sourcePollSelect = document.getElementById('source_poll_id');
    const optionSelect = document.getElementById('option_id');
    
    sourcePollSelect.addEventListener('change', function() {
        const pollId = this.value;
        
        if (!pollId) {
            optionSelect.innerHTML = '<option value="">Select poll first...</option>';
            return;
        }
        
        // Clear current options
        optionSelect.innerHTML = '<option value="">Loading options...</option>';
        
        // Fetch options via AJAX
        fetch(`get_poll_options.php?poll_id=${pollId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    optionSelect.innerHTML = '<option value="">Select an option...</option>';
                    data.options.forEach(option => {
                        const optElement = document.createElement('option');
                        optElement.value = option.id;
                        optElement.textContent = option.option_text;
                        optionSelect.appendChild(optElement);
                    });
                } else {
                    optionSelect.innerHTML = '<option value="">Error loading options</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching options:', error);
                optionSelect.innerHTML = '<option value="">Error loading options</option>';
            });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 