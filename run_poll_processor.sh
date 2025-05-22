#!/bin/bash

# Poll Sequence Processor Runner
# This script runs the poll sequence processor in the background to handle
# automatic transitions between polls. Use this to simulate a cron job
# if you're testing the application locally.

# Change to the directory of this script
cd "$(dirname "$0")"

# Function to run the processor
run_processor() {
    while true; do
        echo "Running poll sequence processor..."
        php cron_tasks/process_poll_sequences.php
        
        # Sleep for 10 seconds before checking again
        echo "Waiting for next check..."
        sleep 10
    done
}

# Run the processor
if [ "$1" == "daemonize" ]; then
    # Run as a background daemon
    echo "Starting poll processor daemon..."
    run_processor > logs/poll_processor.log 2>&1 &
    echo $! > poll_processor.pid
    echo "Poll processor daemon started with PID $(cat poll_processor.pid)"
else
    # Run in the foreground
    echo "Starting poll processor in foreground mode..."
    echo "Press Ctrl+C to stop"
    
    # Create logs directory if it doesn't exist
    mkdir -p logs
    
    # Run the processor
    run_processor
fi 