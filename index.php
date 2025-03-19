<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$message = '';
$error = '';

// Check if there are any messages passed via GET
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Get active poll
$active_poll = null;
$active_poll_stmt = $pdo->prepare("SELECT * FROM polls WHERE is_active = 1 LIMIT 1");
$active_poll_stmt->execute();
$active_poll = $active_poll_stmt->fetch(PDO::FETCH_ASSOC);

// Get inactive polls
$inactive_polls_stmt = $pdo->prepare("SELECT * FROM polls WHERE is_active = 0 ORDER BY created_at DESC");
$inactive_polls_stmt->execute();
$inactive_polls = $inactive_polls_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div x-data="{ tab: 'active' }" class="animate-fade-in">
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

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-dracula-purple mb-2">Polls</h1>
        <p class="text-dracula-comment">Vote on polls created by administrators.</p>
    </div>

    <!-- Tab Navigation -->
    <div class="flex justify-center mb-6">
        <div class="bg-dracula-currentLine rounded-lg p-1 inline-flex shadow-md">
            <button @click="tab = 'active'" 
                    :class="{ 'bg-dracula-selection text-dracula-purple': tab === 'active', 'text-dracula-comment hover:text-dracula-foreground': tab !== 'active' }"
                    class="px-4 py-2 rounded-lg transition-all duration-300 ease-in-out font-medium">
                Active Poll
            </button>
            <button @click="tab = 'previous'" 
                    :class="{ 'bg-dracula-selection text-dracula-purple': tab === 'previous', 'text-dracula-comment hover:text-dracula-foreground': tab !== 'previous' }"
                    class="px-4 py-2 rounded-lg transition-all duration-300 ease-in-out font-medium">
                Previous Polls
            </button>
        </div>
    </div>
    
    <!-- Active Poll Tab -->
    <div x-show="tab === 'active'" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-4"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform -translate-x-4">
        
        <?php if ($active_poll): ?>
            <div class="bg-dracula-currentLine rounded-xl p-6 shadow-lg transform hover:scale-[1.01] transition-transform duration-300 mb-6">
                <h2 class="text-2xl font-bold text-dracula-foreground mb-2">
                    <?php echo htmlspecialchars($active_poll['title']); ?>
                </h2>
                <?php if (!empty($active_poll['description'])): ?>
                    <p class="text-dracula-comment mb-4"><?php echo nl2br(htmlspecialchars($active_poll['description'])); ?></p>
                <?php endif; ?>
                
                <div class="flex justify-between items-center text-sm text-dracula-comment mb-4">
                    <div>Created: <?php echo date('M j, Y', strtotime($active_poll['created_at'])); ?></div>
                </div>
                
                <a href="view_poll.php?id=<?php echo $active_poll['id']; ?>" 
                   class="inline-block bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-3 px-6 rounded-lg shadow-md transition-colors duration-300 transform hover:scale-105">
                    Vote Now
                </a>
            </div>
        <?php else: ?>
            <div class="bg-dracula-currentLine rounded-xl p-6 shadow-md text-center">
                <p class="text-dracula-comment mb-4">No active polls available at the moment. Please check back later when an administrator has created a poll.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Previous Polls Tab -->
    <div x-show="tab === 'previous'" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-4"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform -translate-x-4">
         
        <?php if (count($inactive_polls) > 0): ?>
            <div class="grid gap-6 md:grid-cols-2">
                <?php foreach ($inactive_polls as $poll): ?>
                    <div class="bg-dracula-currentLine rounded-xl p-6 shadow-md transform hover:scale-[1.01] transition-transform duration-300">
                        <h2 class="text-xl font-bold text-dracula-foreground mb-2">
                            <?php echo htmlspecialchars($poll['title']); ?>
                        </h2>
                        <?php if (!empty($poll['description'])): ?>
                            <p class="text-dracula-comment mb-4 line-clamp-2"><?php echo nl2br(htmlspecialchars($poll['description'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center text-sm text-dracula-comment mb-4">
                            <div>Created: <?php echo date('M j, Y', strtotime($poll['created_at'])); ?></div>
                        </div>
                        
                        <a href="view_poll.php?id=<?php echo $poll['id']; ?>" 
                           class="inline-block bg-dracula-purple hover:bg-dracula-pink text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-300">
                            View Results
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-dracula-currentLine rounded-xl p-6 shadow-md text-center">
                <p class="text-dracula-comment mb-4">No previous polls available yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-10 text-center">
        <p class="text-dracula-comment">Only administrators can create new polls</p>
        <a href="admin/login.php" class="inline-block mt-2 text-dracula-purple hover:text-dracula-pink transition-colors duration-300">
            Admin Login
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 