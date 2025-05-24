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

// Find the active sequence
$active_sequence_stmt = $pdo->query("SELECT * FROM poll_sequences WHERE is_active = 1 LIMIT 1");
$active_sequence = $active_sequence_stmt->fetch(PDO::FETCH_ASSOC);

$active_poll = null;
$next_polls = [];

if ($active_sequence) {
    // Check if a poll is currently active (based on start and end times)
    $now = date('Y-m-d H:i:s');
    
    // Try to find a poll that's currently active
    $active_poll_stmt = $pdo->prepare("
        SELECT * FROM polls 
        WHERE sequence_id = ? 
        AND start_time IS NOT NULL 
        AND end_time IS NOT NULL 
        AND start_time <= ? 
        AND end_time >= ?
        LIMIT 1
    ");
    $active_poll_stmt->execute([$active_sequence['id'], $now, $now]);
    $active_poll = $active_poll_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$active_poll) {
        // If no poll is currently active, get the first poll in the sequence
        // that hasn't started yet or the first poll if none have started
        $next_poll_stmt = $pdo->prepare("
            SELECT * FROM polls 
            WHERE sequence_id = ? 
            AND (start_time IS NULL OR start_time > ?)
            ORDER BY sequence_position ASC
            LIMIT 1
        ");
        $next_poll_stmt->execute([$active_sequence['id'], $now]);
        $next_poll = $next_poll_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($next_poll) {
            // If we found a next poll, mark it as active
            $active_poll = $next_poll;
            
            // Set its start time to now and end time based on duration
            $start_time = date('Y-m-d H:i:s');
            $end_time = date('Y-m-d H:i:s', strtotime("+{$active_poll['duration_minutes']} minutes"));
            
            $update_stmt = $pdo->prepare("
                UPDATE polls 
                SET start_time = ?, end_time = ?, is_active = 1, is_results_visible = 0
                WHERE id = ?
            ");
            $update_stmt->execute([$start_time, $end_time, $active_poll['id']]);
            
            // Update the poll object with the new times
            $active_poll['start_time'] = $start_time;
            $active_poll['end_time'] = $end_time;
            $active_poll['is_active'] = 1;
            $active_poll['is_results_visible'] = 0;
        }
    }
} else {
    // No active sequence, look for standalone polls that are active
    $standalone_poll_stmt = $pdo->prepare("
        SELECT * FROM polls 
        WHERE (sequence_id IS NULL OR sequence_id = 0)
        AND is_active = 1
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $standalone_poll_stmt->execute();
    $active_poll = $standalone_poll_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If we found a standalone poll, make sure it has a start and end time
    if ($active_poll && ($active_poll['start_time'] === NULL || $active_poll['end_time'] === NULL)) {
        // Set start time to now if not set
        $start_time = $active_poll['start_time'] ?? date('Y-m-d H:i:s');
        
        // Set end time based on duration (default to 5 minutes if not set)
        $duration = $active_poll['duration_minutes'] ?? 1;
        $end_time = date('Y-m-d H:i:s', strtotime("+{$duration} minutes", strtotime($start_time)));
        
        $update_stmt = $pdo->prepare("
            UPDATE polls 
            SET start_time = ?, end_time = ?
            WHERE id = ?
        ");
        $update_stmt->execute([$start_time, $end_time, $active_poll['id']]);
        
        // Update the poll object with the new times
        $active_poll['start_time'] = $start_time;
        $active_poll['end_time'] = $end_time;
    }
}

if ($active_poll) {
    // Check if poll has ended and results should be shown
    if (strtotime($active_poll['end_time']) < time() && !$active_poll['is_results_visible']) {
        // Set results visibility to true
        $update_stmt = $pdo->prepare("UPDATE polls SET is_results_visible = 1 WHERE id = ?");
        $update_stmt->execute([$active_poll['id']]);
        $active_poll['is_results_visible'] = 1;
    }
    
    // Get the options for the active poll
    $options_stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ? ORDER BY id ASC");
    $options_stmt->execute([$active_poll['id']]);
    $options = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get vote counts for each option
    foreach ($options as &$option) {
        $vote_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM votes WHERE option_id = ?");
        $vote_stmt->execute([$option['id']]);
        $vote_count = $vote_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $option['vote_count'] = $vote_count;
    }
    unset($option);

    // Calculate total votes and percentages
    $total_votes = 0;
    foreach ($options as $option) {
        $total_votes += $option['vote_count'];
    }
    
    foreach ($options as &$option) {
        $option['percentage'] = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100) : 0;
    }
    unset($option);
    
    // Check if the current user has voted
    $voter_ip = $_SERVER['REMOTE_ADDR'];
    $has_voted_stmt = $pdo->prepare("SELECT * FROM votes WHERE poll_id = ? AND voter_ip = ? LIMIT 1");
    $has_voted_stmt->execute([$active_poll['id'], $voter_ip]);
    $has_voted = $has_voted_stmt->fetch() ? true : false;
}

include 'includes/header.php';
?>

<div class="animate-fade-in flex flex-col flex-grow">
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

    <?php if ($active_poll): ?>
        <div class="bg-dracula-currentLine rounded-xl p-6 shadow-lg transform hover:scale-[1.01] transition-transform duration-300 mb-6 flex flex-col flex-grow"
             x-data="{
                 pollEndsAt: '<?php echo $active_poll['end_time']; ?>',
                 timeRemaining: 0,
                 hasVoted: <?php echo $has_voted ? 'true' : 'false'; ?>,
                 showResults: <?php echo ($has_voted || $active_poll['is_results_visible'] || strtotime($active_poll['end_time']) < time()) ? 'true' : 'false'; ?>,
                 calculateTimeRemaining() {
                     const now = new Date();
                     const endTime = new Date(this.pollEndsAt);
                     const diff = Math.max(0, Math.floor((endTime - now) / 1000));
                     this.timeRemaining = diff;
                     
                     if (diff <= 0 && !this.showResults) {
                         this.showResults = true;
                         setTimeout(() => window.location.reload(), <?php echo $active_poll['show_results_duration_seconds'] * 1000; ?>);
                     }
                     
                     return diff;
                 },
                 formatTime(seconds) {
                     const minutes = Math.floor(seconds / 60);
                     const remainingSeconds = seconds % 60;
                     return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
                 }
             }"
             x-init="
                 calculateTimeRemaining();
                 setInterval(() => calculateTimeRemaining(), 1000);
             ">
            
            <!-- Poll Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-dracula-foreground mb-2">
                        <?php echo htmlspecialchars($active_poll['title']); ?>
                    </h2>
                    <?php if (!empty($active_poll['description'])): ?>
                        <p class="text-dracula-comment mb-4"><?php echo nl2br(htmlspecialchars($active_poll['description'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Timer Countdown -->
                <div class="mt-4 md:mt-0 bg-dracula-selection bg-opacity-30 rounded-lg px-4 py-2 text-center min-w-[120px]">
                    <p class="text-sm text-dracula-comment mb-1">Time Remaining</p>
                    <p class="text-2xl font-mono text-dracula-pink" x-text="formatTime(timeRemaining)"></p>
                </div>
            </div>
            
            <!-- Progress bar for time remaining -->
            <div class="w-full h-2 bg-dracula-selection rounded-full mb-6 overflow-hidden">
                <div class="h-full bg-dracula-pink transition-all duration-1000 ease-linear" 
                     :style="`width: ${Math.min(100, (timeRemaining / (<?php echo $active_poll['duration_minutes'] * 60; ?>) * 100))}%`"></div>
            </div>
            
            <!-- Voting Form (if not voted and results not visible) -->
            <div x-show="!hasVoted && !showResults" class="flex flex-col flex-grow">
                <form method="POST" action="vote.php" x-data="{ selectedOption: null }" class="flex flex-col flex-grow">
                    <input type="hidden" name="poll_id" value="<?php echo $active_poll['id']; ?>">
                    
                    <div class="space-y-6 mb-6 flex flex-col flex-grow justify-around">
                        <?php 
                        // Using $index to alternate colors
                        foreach ($options as $index => $option): 
                            $bgColorClass = ($index % 2 == 0) ? 'bg-dracula-purple hover:bg-dracula-purple/80' : 'bg-dracula-red hover:bg-dracula-red/80';
                            $textColorClass = 'text-dracula-foreground'; // Keep text color consistent for readability
                            $borderColorClass = ($index % 2 == 0) ? 'border-dracula-purple/50' : 'border-dracula-red/50';
                        ?>
                            <label for="option-<?php echo $option['id']; ?>" 
                                 class="w-full <?php echo $bgColorClass; ?> <?php echo $textColorClass; ?> border-2 <?php echo $borderColorClass; ?> rounded-lg p-12 text-4xl text-center font-semibold shadow-md transition-all duration-200 ease-in-out transform hover:scale-105 cursor-pointer focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-dracula-yellow flex flex-grow items-center justify-center"
                                 @click="selectedOption = <?php echo $option['id']; ?>">
                                <input type="radio" id="option-<?php echo $option['id']; ?>" name="option_id" 
                                       value="<?php echo $option['id']; ?>" x-model="selectedOption"
                                       class="sr-only"> <!-- Visually hide the radio button -->
                                <?php echo htmlspecialchars($option['option_text']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" 
                            :class="{'opacity-50 cursor-not-allowed': !selectedOption, 'hover:bg-dracula-pink transform hover:scale-105': selectedOption}"
                            :disabled="!selectedOption"
                            class="w-full sm:w-auto bg-dracula-purple text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 shadow-md">
                        Submit Vote
                    </button>
                </form>
            </div>
            
            <!-- Results Display (if voted or poll ended) -->
            <div x-show="hasVoted || showResults" x-cloak>
                <h3 class="text-xl font-semibold text-dracula-purple mb-4">Poll Results</h3>
                
                <div class="space-y-6 mb-6">
                    <?php foreach ($options as $index => $option): 
                        $colors = ['dracula-purple', 'dracula-pink', 'dracula-cyan', 'dracula-green', 'dracula-orange', 'dracula-yellow'];
                        $color = $colors[$index % count($colors)];
                    ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-dracula-foreground"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                <span class="text-dracula-comment"><?php echo $option['percentage']; ?>% (<?php echo $option['vote_count']; ?> votes)</span>
                            </div>
                            <div class="w-full bg-dracula-selection bg-opacity-40 rounded-full h-4 overflow-hidden">
                                <div class="result-bar h-4 bg-<?php echo $color; ?> rounded-full transition-all duration-1000 ease-out w-0"
                                     data-percentage="<?php echo $option['percentage']; ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="text-center text-dracula-comment">
                    <span x-show="timeRemaining > 0">Voting ends in <span x-text="formatTime(timeRemaining)"></span></span>
                    <span x-show="timeRemaining <= 0">
                        <?php if (isset($next_poll)): ?>
                            Next poll will begin shortly...
                        <?php else: ?>
                            This was the final poll in the sequence.
                        <?php endif; ?>
                    </span>
                </p>
                
                <script>
                    // Animate the result bars after a short delay
                    setTimeout(() => {
                        document.querySelectorAll('.result-bar').forEach((bar, index) => {
                            setTimeout(() => {
                                bar.style.width = bar.dataset.percentage + '%';
                            }, index * 200);
                        });
                    }, 300);
                </script>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-dracula-currentLine rounded-xl p-6 shadow-md text-center">
            <?php if ($active_sequence): ?>
                <p class="text-dracula-comment mb-4">No active polls in the current sequence. Please check back later.</p>
            <?php else: ?>
                <p class="text-dracula-comment mb-4">No active polls available at the moment. Please check back later when an administrator has created a poll.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 