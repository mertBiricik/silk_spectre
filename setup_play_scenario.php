<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Default values for polls
const DEFAULT_DURATION_MINUTES = 5;
const DEFAULT_RESULTS_DURATION_SECONDS = 3;

try {
    $pdo->beginTransaction();

    // 1. Create the Poll Sequence
    $stmt = $pdo->prepare("INSERT INTO poll_sequences (name, description, is_active) VALUES (?, ?, ?)");
    $play_name = 'Hayal Et - Interactive Play';
    $play_description = 'An interactive adaptation of the play "Hayal Et", where audience choices determine the narrative path.';
    $stmt->execute([$play_name, $play_description, 0]); // Set to not active initially
    $sequence_id = $pdo->lastInsertId();
    echo "Created Poll Sequence: '$play_name' (ID: $sequence_id)\n";

    // Helper function to create a poll
    function createPoll($pdo, $sequence_id, $title, $description, $position, $duration_minutes = DEFAULT_DURATION_MINUTES, $results_duration = DEFAULT_RESULTS_DURATION_SECONDS) {
        $stmt = $pdo->prepare(
            "INSERT INTO polls (sequence_id, title, description, sequence_position, duration_minutes, show_results_duration_seconds, is_active, is_results_visible) 
             VALUES (?, ?, ?, ?, ?, ?, 0, 0)"
        );
        $stmt->execute([$sequence_id, $title, $description, $position, $duration_minutes, $results_duration]);
        $poll_id = $pdo->lastInsertId();
        echo "  Created Poll: '$title' (ID: $poll_id)\n";
        return $poll_id;
    }

    // Helper function to create an option
    function createOption($pdo, $poll_id, $option_text) {
        $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
        $stmt->execute([$poll_id, $option_text]);
        $option_id = $pdo->lastInsertId();
        echo "    Created Option: '$option_text' (ID: $option_id) for Poll ID: $poll_id\n";
        return $option_id;
    }

    // Helper function to create branching
    function createBranching($pdo, $sequence_id, $source_poll_id, $option_id, $target_poll_id) {
        $stmt = $pdo->prepare("INSERT INTO poll_branching (sequence_id, source_poll_id, option_id, target_poll_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sequence_id, $source_poll_id, $option_id, $target_poll_id]);
        echo "    Created Branching: Poll $source_poll_id (Option $option_id) -> Poll $target_poll_id\n";
    }

    // --- Define Polls based on play_scenario.md ---

    // P1: Act 2: Early Morning - The Television (Decision Point 1)
    $p1_title = "Act 2: Early Morning - The Television";
    $p1_desc = "The Ghost remembers its childhood, specifically being under the table at 5 AM. How do you proceed to the living room?";
    $poll1_id = createPoll($pdo, $sequence_id, $p1_title, $p1_desc, 1);
    $p1_option_a_id = createOption($pdo, $poll1_id, "Walk quietly to the living room.");
    $p1_option_b_id = createOption($pdo, $poll1_id, "Run to the living room, making noise.");

    // P2: Act 3 - Quiet Approach (Dream Scene Decision)
    $p2_title = "Act 3: In the Dream";
    $p2_desc = "You chose to walk quietly. You fell asleep watching cartoons and now wake up under the table in a dream-like state. What do you do in the dream?";
    $poll2_id = createPoll($pdo, $sequence_id, $p2_title, $p2_desc, 2); // Position 2, Path A
    $p2_option_c_id = createOption($pdo, $poll2_id, "Stay in the dream.");
    $p2_option_d_id = createOption($pdo, $poll2_id, "Wake up.");

    // P3: Act 4 - Noisy Approach (Nightmare Scene Decision)
    $p3_title = "Act 4: In the Nightmare";
    $p3_desc = "You chose to run and make noise. Your father woke up angry. Later, you wake up under the table in a nightmare. You see shadow figures, run, hide, and look in a mirror. What do you do in the nightmare?";
    $poll3_id = createPoll($pdo, $sequence_id, $p3_title, $p3_desc, 2); // Position 2, Path B
    $p3_option_e_id = createOption($pdo, $poll3_id, "Stay in the Nightmare.");
    $p3_option_d2_id = createOption($pdo, $poll3_id, "Wake up (to a crash).");

    // P4: Peer Gynt Ending (END)
    $p4_title = "Peer Gynt Ending";
    $p4_desc = "You chose to stay in the dream. Lost in your own world, the lines between reality and fantasy blur indefinitely. [END OF PATH]";
    $poll4_id = createPoll($pdo, $sequence_id, $p4_title, $p4_desc, 3); // Position 3, Path A.1

    // P5: Woyzeck Opsiyonu - Kötü Son 1 (END)
    $p5_title = "Woyzeck Ending - Nightmare Becomes Reality";
    $p5_desc = "You chose to stay in the nightmare. In the mirror, you see yourself as a creature. You have become the nightmare. (Blackout. GAME OVER.) [END OF PATH]";
    $poll5_id = createPoll($pdo, $sequence_id, $p5_title, $p5_desc, 3); // Position 3, Path B.1

    // P6: Act 6: Waking Up - Under the Table Game (Reality Intrusion Decision)
    // This poll is reached from P2/Option D or P3/Option D2
    $p6_title = "Act 6: Waking Up - Under the Table Game";
    $p6_desc = "You chose to wake up. You find yourself in bed, then start playing a pirate game under the table. Suddenly, a loud crash! Real-life intrudes. 'Korkuyorum.' (I'm scared.) What do you do now?";
    $poll6_id = createPoll($pdo, $sequence_id, $p6_title, $p6_desc, 3); // Position 3, Converging Path D/D2
    $p6_option_f_id = createOption($pdo, $poll6_id, "Get out from under the table.");
    $p6_option_g_id = createOption($pdo, $poll6_id, "Stay under the table.");

    // P7: OPTION F - Revolutionary Ending (END)
    $p7_title = "Revolutionary Ending";
    $p7_desc = "You get out. You see the reality: your father's tyranny. You 'break' the table, take your mother, and leave. 'Monsters are real, but they can\'t break my pirate soul... I am human, and I don\'t accept this. Break your tables, or your tables will become your graves. The choice is yours.' (Blackout) [END OF PATH]";
    $poll7_id = createPoll($pdo, $sequence_id, $p7_title, $p7_desc, 4); // Position 4, Path D/D2.1

    // P8: OPTION G - Ghost Ending (END)
    $p8_title = "Ghost Ending";
    $p8_desc = "You stay under the table. 'I\'ve been under this table for years... A soul caught between two worlds... It\'s too late now. I will stay here. You will leave... You will either break the table, or you will remain as ghosts playing under the table. The choice is yours.' (Blackout) [END OF PATH]";
    $poll8_id = createPoll($pdo, $sequence_id, $p8_title, $p8_desc, 4); // Position 4, Path D/D2.2

    // --- Define Branching Logic ---
    // P1 branches
    createBranching($pdo, $sequence_id, $poll1_id, $p1_option_a_id, $poll2_id); // P1/OptA -> P2
    createBranching($pdo, $sequence_id, $poll1_id, $p1_option_b_id, $poll3_id); // P1/OptB -> P3

    // P2 (Dream Path) branches
    createBranching($pdo, $sequence_id, $poll2_id, $p2_option_c_id, $poll4_id); // P2/OptC -> P4 (Peer Gynt)
    createBranching($pdo, $sequence_id, $poll2_id, $p2_option_d_id, $poll6_id); // P2/OptD -> P6 (Waking Game)

    // P3 (Nightmare Path) branches
    createBranching($pdo, $sequence_id, $poll3_id, $p3_option_e_id, $poll5_id); // P3/OptE -> P5 (Woyzeck)
    createBranching($pdo, $sequence_id, $poll3_id, $p3_option_d2_id, $poll6_id); // P3/OptD2 -> P6 (Waking Game, D2 implies slightly different P6 entry text handled in P6 desc)

    // P6 (Waking Game) branches
    createBranching($pdo, $sequence_id, $poll6_id, $p6_option_f_id, $poll7_id); // P6/OptF -> P7 (Revolutionary)
    createBranching($pdo, $sequence_id, $poll6_id, $p6_option_g_id, $poll8_id); // P6/OptG -> P8 (Ghost)
    
    // Note: Polls P4, P5, P7, P8 are terminal. No outgoing branches.
    // The sequence_position helps if branching fails or for listing, but branching is primary.

    $pdo->commit();
    echo "\n-------------------------------------\n";
    echo "Successfully set up the 'Hayal Et - Interactive Play' scenario.\n";
    echo "To activate it, go to the admin panel and activate sequence ID: $sequence_id.\n";
    echo "The first poll in this sequence is '$p1_title' (Poll ID: $poll1_id).\n";
    echo "Ensure your cron job for 'cron_tasks/process_poll_sequences.php' is running to handle transitions.\n";
    echo "All polls in this sequence have a default duration of " . DEFAULT_DURATION_MINUTES . " minutes and a results display/transition time of " . DEFAULT_RESULTS_DURATION_SECONDS . " seconds.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Database error during scenario setup: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    $pdo->rollBack();
    die("General error during scenario setup: " . $e->getMessage() . "\n");
}

?> 