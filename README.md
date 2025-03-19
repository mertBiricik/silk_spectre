# Silk Spectre - Stylish Poll Application

A mobile-friendly poll creation and voting application built with PHP, Alpine.js, and Tailwind CSS, featuring the Dracula theme.

## Features

- **Admin Interface**
  - Secure admin login with session management
  - Create and manage polls with multiple options
  - Make polls active/inactive (only one active poll at a time)
  - View detailed poll results with graphical representation
  - Export poll results to CSV in summary or detailed format
  
- **Public Interface**
  - Vote on active polls
  - View results with animated progress bars
  - Separate display sections for active and previous polls
  - Mobile-friendly responsive design
  - Interactive UI with Alpine.js animations and transitions
  
- **Security & Data**
  - Session-based admin authentication
  - IP-based voting restrictions
  - Data validation and sanitization
  - PDO with prepared statements for database queries
  - Cookie-based session tracking
  
- **Styling**
  - Responsive design with Tailwind CSS
  - Dark mode using the Dracula theme
  - Interactive elements with Alpine.js
  - Animated transitions and poll results

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.3+
- Web server (Apache, Nginx, etc.)

## Installation

1. Clone or download this repository to your web server's document root.

2. Create a MySQL database for the application.

3. Update the database connection settings in `includes/db.php` with your database credentials:

```php
$host = 'localhost';     // Your database host
$dbname = 'poll_app';    // Your database name
$username = 'polluser';  // Your database username
$password = 'pollpassword';  // Your database password
```

4. Navigate to `install.php` in your browser to set up the database schema and create an admin user:

```
http://yourdomain.com/install.php
```

5. Delete the install file after successful setup (for security):

```
rm install.php
```

## Usage

### Admin Access

1. Navigate to the admin login page: `http://yourdomain.com/admin/login.php`
2. Enter your admin credentials to log in
3. Access the admin dashboard to manage polls and view results

### Creating a Poll (Admin Only)

1. Log in to the admin interface
2. Click on "Create Poll" button
3. Enter a title and description for your poll
4. Add at least two options for your poll
5. Check "Make Active" to set this as the current active poll
6. Click "Create Poll" to save it

### Voting on a Poll

1. Navigate to the application homepage
2. The active poll will be prominently displayed
3. Previous polls will be listed below
4. Select one option and click "Submit Vote"
5. A loading animation will appear, followed by results

### Viewing and Exporting Results (Admin Only)

1. Log in to the admin interface
2. View the list of polls
3. Click "View Results" for a specific poll
4. View detailed vote counts and percentages with visual representation
5. Click "Export Results" to download data in CSV format
   - Choose "Summary" for poll totals only
   - Choose "Full" for detailed voter information

## Technical Implementation

### Front-end Technologies

- **Tailwind CSS**: For responsive design and styling
- **Alpine.js**: For interactive elements without requiring a full JavaScript framework
  - Used for tabs on the homepage
  - Animations for poll results
  - Form validation and enhanced UX
  - Auto-dismissing notifications
  - Loading and transition effects

### Back-end Technologies

- **PHP**: Server-side application logic
- **MySQL/MariaDB**: Database for storing polls, options, and votes
- **PDO**: PHP Data Objects for secure database connectivity
- **Sessions**: PHP sessions for admin authentication and state management

### Database Schema

The application uses a relational database with the following tables:
- `admins`: Stores admin user credentials
- `polls`: Contains poll information (title, description, active status)
- `options`: Stores options for each poll
- `votes`: Records votes cast for options (with IP address tracking)

## Security Considerations

- **Authentication**: Session-based admin authentication with secure password hashing
- **Data Protection**: PDO with prepared statements to prevent SQL injection
- **Input Sanitization**: All user input is validated and sanitized
- **XSS Prevention**: Output is escaped using `htmlspecialchars()` to prevent cross-site scripting
- **Cookie Security**: HttpOnly flag set on session cookies to mitigate XSS risks
- **IP Tracking**: System tracks voter IPs to prevent multiple votes on the same poll

## Mobile Access

The application is fully responsive and optimized for mobile devices:
- Adaptive layouts that work on any screen size
- Touch-friendly interface elements
- Fast-loading and efficient interface
- Accessible from any device on the same network with proper firewall configuration

## License

This project is released under the MIT License.

## Acknowledgements

- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS framework
- [Alpine.js](https://alpinejs.dev/) - Lightweight JavaScript framework
- [PHP](https://www.php.net/) - Server-side scripting language
- [Dracula Theme](https://draculatheme.com/) - Dark theme for code editors and apps 