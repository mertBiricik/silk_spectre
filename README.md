# Sequential Polling System

A PHP-based polling application that supports sequential polls with branching logic and timed transitions.

## Overview

The Sequential Polling System allows administrators to create complex polling sequences where the next poll shown to a user can depend on their answer to the previous one. It supports timed transitions, a mobile-friendly interface, and provides an admin dashboard for managing polls and viewing results.

**Key Technologies:**
- PHP
- MySQL
- Tailwind CSS (for styling)
- Alpine.js (for client-side interactivity)

## Features

- **Sequential Polls**: Create sequences of polls that automatically transition from one to the next
- **Branching Logic**: Define conditional paths based on poll results
- **Timed Transitions**: Set duration for polls and results display
- **Admin Dashboard**: Manage polls, sequences, and view detailed results
- **Mobile-Friendly UI**: Clean, responsive design using Tailwind CSS and Alpine.js
- **Results Export**: Export poll results to CSV for analysis

## Prerequisites

- PHP (version 7.4 or higher recommended)
- MySQL (or MariaDB)
- A web server (like Apache or Nginx if not using the built-in PHP server for development)
- Git (for cloning the repository)

## Installation and Setup

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/yourusername/sequential-polls.git
    cd sequential-polls
    ```

2.  **Database Setup**:
    *   Ensure your MySQL service is running. For systems using systemd (like many Linux distributions):
        ```bash
        sudo systemctl start mysql
        # Or: sudo systemctl start mysqld
        ```
        On other systems, use the appropriate command (e.g., `brew services start mysql` on macOS with Homebrew).
    *   Log in to your MySQL client:
        ```bash
        mysql -u root -p
        ```
    *   Create the database and user. Replace `'pollpassword'` with a secure password if desired (and update `includes/config.php` accordingly).
        ```sql
        CREATE DATABASE IF NOT EXISTS poll_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS 'polluser'@'localhost' IDENTIFIED BY 'pollpassword';
        GRANT ALL PRIVILEGES ON poll_app.* TO 'polluser'@'localhost';
        FLUSH PRIVILEGES;
        EXIT;
        ```

3.  **Configure Application**:
    *   The default database configuration is in `includes/config.php`. It's set to use:
        *   Host: `localhost`
        *   Database: `poll_app`
        *   User: `polluser`
        *   Password: `pollpassword`
    *   If you changed the database name, user, or password in the step above, update `includes/config.php` accordingly.

4.  **Run Installation Script**:
    *   Start the PHP development server (see "Running the Server" section below for more details).
    *   Open your web browser and navigate to:
        ```
        http://localhost:8000/install.php
        ```
    *   This script will create the necessary tables in the `poll_app` database.

5.  **Secure Installation**:
    *   After the installation script completes successfully, **delete the `install.php` file** for security:
        ```bash
        rm install.php
        ```

6.  **Admin Access**:
    *   Log in to the admin dashboard at `http://localhost:8000/admin/` with the default credentials:
        *   Username: `admin`
        *   Password: `admin123`
    *   **Important**: Change the default admin password immediately after your first login.

## Running the Server (Development)

For development, you can use the built-in PHP server.

1.  Ensure your MySQL service is running (see step 2 in Installation).
2.  Navigate to the project root directory.
3.  Start the server using the provided script:
    ```bash
    ./start_server.sh
    ```
    This script executes `php server.php`, which starts the server on `http://localhost:8000`.
    Alternatively, you can run `php server.php` directly.

    The application will be accessible at `http://localhost:8000`.

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