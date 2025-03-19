<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$message = '';
$error = '';
$hasVoted = false;
$results = [];
$totalVotes = 0;

// Check for messages in URL
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Check if poll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$pollId = (int)$_GET['id'];

// Get poll details
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->execute([$pollId]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$poll) {
    $error = 'Poll not found';
} else {
    // Get poll options
    $stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total votes for this poll
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes WHERE poll_id = ?");
    $stmt->execute([$pollId]);
    $totalVotes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate results
    if ($totalVotes > 0) {
        $stmt = $pdo->prepare("
            SELECT o.id, o.option_text, COUNT(v.id) as vote_count 
            FROM options o
            LEFT JOIN votes v ON o.id = v.option_id
            WHERE o.poll_id = ?
            GROUP BY o.id
            ORDER BY vote_count DESC
        ");
        $stmt->execute([$pollId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if user has already voted (using IP address)
    $voterIp = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as voted FROM votes WHERE poll_id = ? AND voter_ip = ?");
    $stmt->execute([$pollId, $voterIp]);
    $hasVoted = ($stmt->fetch(PDO::FETCH_ASSOC)['voted'] > 0);
    
    // Process vote submission - only allow voting if poll is active
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['option']) && !$hasVoted && $poll['is_active']) {
        $optionId = (int)$_POST['option'];
        
        // Verify the option belongs to this poll
        $stmt = $pdo->prepare("SELECT COUNT(*) as valid FROM options WHERE id = ? AND poll_id = ?");
        $stmt->execute([$optionId, $pollId]);
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)['valid'] > 0) {
            try {
                // Record the vote
                $stmt = $pdo->prepare("INSERT INTO votes (poll_id, option_id, voter_ip) VALUES (?, ?, ?)");
                $stmt->execute([$pollId, $optionId, $voterIp]);
                
                $message = 'Your vote has been recorded!';
                $hasVoted = true;
                
                // Refresh the page to show results but with a just_voted parameter
                header("Location: view_poll.php?id=$pollId&message=Your vote has been recorded!&just_voted=1");
                exit;
            } catch (PDOException $e) {
                $error = 'Error recording vote: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid option selected';
        }
    }
}

// Check if user just voted
$justVoted = isset($_GET['just_voted']) && $_GET['just_voted'] == '1';

// Include header
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-dracula-purple mb-2"><?php echo $poll ? htmlspecialchars($poll['title']) : 'Poll Not Found'; ?></h1>
            <?php if ($poll && !empty($poll['description'])): ?>
                <p class="text-dracula-comment"><?php echo nl2br(htmlspecialchars($poll['description'])); ?></p>
            <?php endif; ?>
        </div>
        <a href="index.php" class="bg-dracula-comment hover:bg-dracula-selection text-dracula-foreground px-4 py-2 rounded-lg text-center w-full sm:w-auto transition-colors duration-300">
            Back to Polls
        </a>
    </div>
</div>

<?php if ($message): ?>
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

<?php if ($error): ?>
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

<?php if ($poll): ?>
    <div class="bg-dracula-currentLine shadow-md rounded-xl p-6 transition-all duration-300 hover:shadow-lg" 
         x-data="{
             showResults: <?php echo (!$poll['is_active'] || $hasVoted) ? 'true' : 'false'; ?>,
             pollActive: <?php echo $poll['is_active'] ? 'true' : 'false'; ?>,
             hasVoted: <?php echo $hasVoted ? 'true' : 'false'; ?>,
             justVoted: <?php echo $justVoted ? 'true' : 'false'; ?>,
             waitingTime: 8, // Seconds to wait before showing results
             waitingTimer: null,
             countDown: 8,
             animateResults: function() {
                 setTimeout(() => {
                     document.querySelectorAll('.result-bar').forEach((bar, index) => {
                         setTimeout(() => {
                             bar.classList.add('transition-all', 'duration-1000');
                             bar.style.width = bar.dataset.percentage + '%';
                         }, index * 200);
                     });
                 }, 300);
             }
         }"
         x-init="
             if(justVoted) {
                 waitingTimer = setInterval(() => {
                     countDown--;
                     if(countDown <= 0) {
                         clearInterval(waitingTimer);
                         showResults = true;
                         setTimeout(() => animateResults(), 300);
                     }
                 }, 1000);
             }
             if(showResults && !justVoted) {
                 setTimeout(() => animateResults(), 300);
             }
         ">
        
        <?php if (!$poll['is_active']): ?>
            <div class="bg-dracula-comment bg-opacity-20 border-l-4 border-dracula-comment text-dracula-foreground p-4 mb-6 rounded-lg shadow-md" role="alert">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-2 text-dracula-comment" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>This poll is no longer active. You can view the results below.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Waiting Animation (after just voted) -->
        <div x-show="justVoted && !showResults" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="text-center py-8">
            <h2 class="text-xl font-semibold text-dracula-purple mb-6">Processing Your Vote...</h2>
            
            <div class="dots-loading mx-auto mb-6">
                <div></div><div></div><div></div><div></div>
            </div>
            
            <p class="text-dracula-comment mb-2">Results will be available in <span x-text="countDown" class="text-dracula-pink font-bold"></span> seconds</p>
            <p class="text-dracula-comment">Thank you for participating!</p>
        </div>
        
        <!-- Voting Form (if not voted and poll is active) -->
        <div x-show="!hasVoted && pollActive && !justVoted" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <form method="POST" action="" x-data="{ selectedOption: null }">
                <h2 class="text-xl font-semibold text-dracula-purple mb-6">Cast Your Vote</h2>
                
                <?php if (!empty($options)): ?>
                    <div class="space-y-4 mb-6">
                        <?php foreach ($options as $option): ?>
                            <div class="flex items-center bg-dracula-selection bg-opacity-20 p-3 rounded-lg hover:bg-opacity-40 transition-colors cursor-pointer"
                                 @click="selectedOption = <?php echo $option['id']; ?>">
                                <input type="radio" id="option-<?php echo $option['id']; ?>" name="option" 
                                       value="<?php echo $option['id']; ?>" x-model="selectedOption"
                                       class="h-5 w-5 text-dracula-purple focus:ring-dracula-pink border-dracula-selection">
                                <label for="option-<?php echo $option['id']; ?>" 
                                       class="ml-3 block text-dracula-foreground w-full cursor-pointer">
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" 
                            :class="{'opacity-50 cursor-not-allowed': !selectedOption, 'hover:bg-dracula-pink transform hover:scale-105': selectedOption}"
                            :disabled="!selectedOption"
                            class="w-full sm:w-auto bg-dracula-purple text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 shadow-md">
                        Submit Vote
                    </button>
                <?php else: ?>
                    <p class="text-dracula-comment">No options available for this poll.</p>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Results Section -->
        <div x-show="showResults"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">
            <h2 class="text-xl font-semibold text-dracula-purple mb-4">Poll Results</h2>
            
            <?php if ($totalVotes > 0): ?>
                <p class="text-dracula-comment mb-6">Total votes: <span class="text-dracula-pink font-bold"><?php echo $totalVotes; ?></span></p>
                
                <div class="space-y-6">
                    <?php foreach ($results as $result): ?>
                        <?php 
                            $percentage = ($result['vote_count'] / $totalVotes) * 100;
                            $percentage = round($percentage, 1);
                        ?>
                        <div class="transform transition-all duration-300 hover:translate-x-1">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-2">
                                <span class="text-dracula-foreground font-medium mb-1 sm:mb-0">
                                    <?php echo htmlspecialchars($result['option_text']); ?>
                                </span>
                                <span class="text-dracula-purple">
                                    <span class="font-bold"><?php echo $result['vote_count']; ?></span> votes (<span class="font-bold"><?php echo $percentage; ?>%</span>)
                                </span>
                            </div>
                            <div class="w-full bg-dracula-selection rounded-full h-6 overflow-hidden">
                                <div class="result-bar bg-dracula-purple h-6 rounded-full" 
                                     style="width: 0%" 
                                     data-percentage="<?php echo $percentage; ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($hasVoted): ?>
                    <div class="mt-8 p-4 bg-dracula-green/10 border-l-4 border-dracula-green rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-2 text-dracula-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-dracula-green">Your vote has been recorded. Thank you for participating!</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-dracula-currentLine p-8 rounded-lg text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-dracula-comment" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-dracula-comment text-xl">No votes have been cast yet.</p>
                    <p class="text-dracula-comment mt-2">Be the first to vote!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?> 