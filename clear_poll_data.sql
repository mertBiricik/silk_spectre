SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE poll_branching;
TRUNCATE TABLE votes;
TRUNCATE TABLE options;
TRUNCATE TABLE polls;
TRUNCATE TABLE poll_sequences;
SET FOREIGN_KEY_CHECKS = 1;
SELECT 'All poll and sequence tables have been cleared.' AS status;

