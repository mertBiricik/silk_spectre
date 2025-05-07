<?php
/**
 * Poll Sequence Processor
 * 
 * This script is designed to be run as a cron job to handle the automatic
 * transition between polls in a sequence. It will:
 * 
 * 1. Check if any active polls have ended but results are still visible
 * 2. If a poll has ended and the result display time has passed, determine the next poll in the sequence
 * 3. Activate the next poll in the sequence or apply branching logic if configured
 * 
 * Recommended cron schedule: Every minute
 * * * * * * php /path/to/cron_tasks/process_poll_sequences.php
 */

// Set the execution time limit to 30 seconds
set_time_limit(30);

// Define the absolute path to the root directory
$root_dir = dirname(__DIR__);

// Include the configuration and database files
require_once $root_dir . '/includes/config.php';
require_once $root_dir . '/includes/db.php';

// Log function
function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

// Start processing
logMessage("Starting poll sequence processor");

try {
    // Find polls that have ended and have displayed results for the required time
    $now = date('Y-m-d H:i:s');
    
    // Get active sequences
    $active_sequence_stmt = $pdo->query("SELECT * FROM poll_sequences WHERE is_active = 1");
    $active_sequences = $active_sequence_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($active_sequences as $sequence) {
        logMessage("Processing sequence ID: " . $sequence['id'] . " - " . $sequence['name']);
        
        // Find polls in this sequence that have ended but results are still visible
        $ended_polls_stmt = $pdo->prepare("
            SELECT * FROM polls 
            WHERE sequence_id = ? 
            AND is_active = 1
            AND is_results_visible = 1
            AND end_time < ?
        ");
        $ended_polls_stmt->execute([$sequence['id'], date('Y-m-d H:i:s', strtotime("-{$sequence['results_display_seconds']} seconds"))]);
        $ended_polls = $ended_polls_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($ended_polls as $poll) {
            logMessage("Poll ID: " . $poll['id'] . " has ended and results display time has passed");
            
            // Deactivate the current poll
            $deactivate_stmt = $pdo->prepare("UPDATE polls SET is_active = 0 WHERE id = ?");
            $deactivate_stmt->execute([$poll['id']]);
            
            // Check if there's branching logic for this poll
            $branching_stmt = $pdo->prepare("SELECT * FROM poll_branching WHERE source_poll_id = ?");
            $branching_stmt->execute([$poll['id']]);
            $branching_rules = $branching_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $next_poll_id = null;
            
            if (count($branching_rules) > 0) {
                logMessage("Found branching rules for poll ID: " . $poll['id']);
                
                // Get the winning option
                $winning_option_stmt = $pdo->prepare("
                    SELECT o.id, o.option_text, COUNT(v.id) as vote_count
                    FROM options o
                    LEFT JOIN votes v ON o.id = v.option_id
                    WHERE o.poll_id = ?
                    GROUP BY o.id
                    ORDER BY vote_count DESC
                    LIMIT 1
                ");
                $winning_option_stmt->execute([$poll['id']]);
                $winning_option = $winning_option_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($winning_option) {
                    logMessage("Winning option: " . $winning_option['option_text'] . " with " . $winning_option['vote_count'] . " votes");
                    
                    // Find the branching rule for this option
                    foreach ($branching_rules as $rule) {
                        if ($rule['source_option_id'] == $winning_option['id']) {
                            $next_poll_id = $rule['target_poll_id'];
                            logMessage("Branching to poll ID: " . $next_poll_id . " based on winning option");
                            break;
                        }
                    }
                    
                    // If no specific rule for the winning option, look for a default rule (source_option_id = 0)
                    if (!$next_poll_id) {
                        foreach ($branching_rules as $rule) {
                            if ($rule['source_option_id'] == 0) {
                                $next_poll_id = $rule['target_poll_id'];
                                logMessage("Using default branching to poll ID: " . $next_poll_id);
                                break;
                            }
                        }
                    }
                }
            }
            
            // If no branching or no branching rule matched, get the next poll by sequence position
            if (!$next_poll_id) {
                $next_poll_stmt = $pdo->prepare("
                    SELECT * FROM polls 
                    WHERE sequence_id = ? 
                    AND sequence_position > ?
                    ORDER BY sequence_position ASC
                    LIMIT 1
                ");
                $next_poll_stmt->execute([$sequence['id'], $poll['sequence_position']]);
                $next_poll = $next_poll_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($next_poll) {
                    $next_poll_id = $next_poll['id'];
                    logMessage("Moving to next poll ID: " . $next_poll_id . " in sequence");
                } else {
                    logMessage("No more polls in this sequence - sequence is complete");
                }
            }
            
            // If we have a next poll, activate it
            if ($next_poll_id) {
                // Get the poll details
                $poll_stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
                $poll_stmt->execute([$next_poll_id]);
                $next_poll = $poll_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($next_poll) {
                    // Set start and end times
                    $start_time = date('Y-m-d H:i:s');
                    $end_time = date('Y-m-d H:i:s', strtotime("+{$next_poll['duration_minutes']} minutes"));
                    
                    // Activate the next poll
                    $activate_stmt = $pdo->prepare("
                        UPDATE polls 
                        SET is_active = 1, 
                            is_results_visible = 0,
                            start_time = ?,
                            end_time = ?
                        WHERE id = ?
                    ");
                    $activate_stmt->execute([$start_time, $end_time, $next_poll_id]);
                    
                    logMessage("Activated poll ID: " . $next_poll_id . " with end time: " . $end_time);
                }
            }
        }
    }
    
    logMessage("Poll sequence processing completed successfully");
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}
?> 