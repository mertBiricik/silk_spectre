# Silk Spectre - Stylish Poll Application

A mobile-friendly poll creation and voting application built with PHP and Tailwind CSS, featuring the Dracula theme.

## Features

- Create polls with multiple options
- Vote on polls
- View real-time results with graphical representation
- Share polls via URL
- Mobile-friendly responsive design
- Simple API for integration with other applications
- Dracula theme for a dark, stylish experience

## Requirements

- PHP 7.4 or higher
- MySQL or MariaDB
- Web server (Apache, Nginx, etc.)

## Installation

1. Clone or download this repository to your web server's document root.

2. Create a MySQL database for the application.

3. Update the database connection settings in `includes/database.php` with your database credentials:

```php
$host = 'localhost';     // Your database host
$dbname = 'poll_app';    // Your database name
$username = 'polluser';  // Your database username
$password = 'pollpassword';  // Your database password
```

4. Navigate to `includes/setup_db.php` in your browser to set up the database schema:

```
http://yourdomain.com/includes/setup_db.php
```

5. Delete the setup file after successful database setup (for security):

```
rm includes/setup_db.php
```

## Usage

### Creating a Poll

1. Navigate to the application homepage.
2. Click on "Create Poll" button.
3. Enter a title and description for your poll.
4. Add at least two options for your poll.
5. Click "Create Poll" to save it.

### Voting on a Poll

1. Navigate to the application homepage.
2. Click on a poll from the list.
3. Select one option and click "Submit Vote".
4. View the results after voting.

### Sharing a Poll

1. View a poll.
2. Click the "Share Poll" button.
3. Copy the URL or use the built-in sharing functionality on compatible devices.

## API

The application includes a simple JSON API for retrieving poll data:

### Get Poll Data

**Endpoint**: `/api/get_poll.php?id={poll_id}`

**Method**: GET

**Response Example**:

```json
{
  "success": true,
  "message": "",
  "data": {
    "id": 1,
    "title": "Favorite Programming Language",
    "description": "What is your favorite programming language?",
    "created_at": "2023-07-15 10:30:00",
    "total_votes": 25,
    "options": [
      {
        "id": 2,
        "text": "JavaScript",
        "votes": 10,
        "percentage": 40
      },
      {
        "id": 1,
        "text": "PHP",
        "votes": 8,
        "percentage": 32
      },
      {
        "id": 3,
        "text": "Python",
        "votes": 7,
        "percentage": 28
      }
    ]
  }
}
```

## Dracula Theme

This application uses the Dracula color scheme, a dark theme designed to be easy on the eyes while providing good contrast for development and use. The theme features:

- Dark backgrounds with rich, vibrant accent colors
- Carefully selected contrasting colors for readability
- A cohesive design language throughout the application

## Security Considerations

- The application uses PDO with prepared statements to prevent SQL injection.
- Input data is sanitized using `htmlspecialchars()` when displayed to prevent XSS attacks.
- IP-based voting restriction is implemented to prevent multiple votes from the same user.

## License

This project is released under the MIT License.

## Acknowledgements

- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS framework
- [PHP](https://www.php.net/) - Server-side scripting language
- [Dracula Theme](https://draculatheme.com/) - Dark theme for code editors and apps 