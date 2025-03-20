# Sequential Polling System

A PHP-based polling application that supports sequential polls with branching logic and timed transitions.

## Features

- **Sequential Polls**: Create sequences of polls that automatically transition from one to the next
- **Branching Logic**: Define conditional paths based on poll results
- **Timed Transitions**: Set duration for polls and results display
- **Admin Dashboard**: Manage polls, sequences, and view detailed results
- **Mobile-Friendly UI**: Clean, responsive design using Tailwind CSS and Alpine.js
- **Results Export**: Export poll results to CSV for analysis

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/sequential-polls.git
   cd sequential-polls
   ```

2. Set up your web server (Apache, Nginx, etc.) to point to the project directory

3. Create a MySQL database for the application

4. Update the database configuration in `includes/config.php`

5. Run the installation script by visiting:
   ```
   http://your-domain.com/install.php
   ```

6. After installation, delete the `install.php` file for security:
   ```
   rm install.php
   ```

7. Log in to the admin dashboard with the default credentials:
   - Username: `admin`
   - Password: `admin123`
   
   (Remember to change the default password immediately)

## Setting Up Sequential Polls

### 1. Create a Poll Sequence

1. Log in to the admin dashboard
2. Click on "Manage Poll Sequences"
3. Click "Create New Sequence" and provide a name and description
4. Save the sequence

### 2. Add Polls to the Sequence

1. Navigate to the sequence editor by clicking "Edit" on your sequence
2. Create polls and specify their position in the sequence
3. Set duration (how long the poll will be active) and results display time for each poll

### 3. Configure Branching Logic (Optional)

1. In the sequence editor, click "Add Branching Rule" on a poll
2. Select a winning option that will trigger the branch
3. Choose the target poll to branch to if the selected option wins
4. Add default branching rules to handle cases where no specific rule matches

### 4. Activate the Sequence

1. On the "Manage Poll Sequences" page, click "Activate" on your sequence
2. Only one sequence can be active at a time

## Running the Poll Processor

The poll processor is responsible for handling automatic transitions between polls. There are two ways to run it:

### 1. Cron Job (Recommended for Production)

Set up a cron job to run the processor every minute:

```
* * * * * php /path/to/your/app/cron_tasks/process_poll_sequences.php
```

### 2. Background Script (For Testing)

Use the included shell script to run the processor in the background:

```
# Run in the foreground (Ctrl+C to stop)
./run_poll_processor.sh

# Run as a daemon in the background
./run_poll_processor.sh daemonize

# To stop the background daemon
kill $(cat poll_processor.pid)
```

## User Experience

1. Users visit the homepage to see the currently active poll
2. A countdown timer shows the remaining time for the current poll
3. After voting, users see the poll results
4. When the poll ends, results are displayed for the configured time
5. The system automatically advances to the next poll in the sequence
6. If branching is configured, the next poll is determined by the winning option

## License

This project is licensed under the MIT License - see the LICENSE file for details. 