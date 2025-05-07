# Script to create MySQL database and user for the Sequential Polling System

$DbName = "poll_app"
$DbUser = "polluser"
# IMPORTANT: For a production environment, use a strong, unique password.
# This password must match the DB_PASS value in includes/config.php
$DbPass = "pollpassword"

Write-Host "This script will attempt to create the MySQL database '$DbName' and user '$DbUser'."
Write-Host "It assumes you have MySQL installed and the 'mysql.exe' command-line tool in your system's PATH."
Write-Host "You will be prompted for your MySQL root user password."
Write-Host "--------------------------------------------------------------------------"

$MysqlRootUser = Read-Host -Prompt "Enter your MySQL root username (e.g., root)"
if ([string]::IsNullOrWhiteSpace($MysqlRootUser)) {
    $MysqlRootUser = "root"
    Write-Host "No root username entered, defaulting to 'root'."
}

Write-Host "You will now be prompted to enter the password for MySQL user '$MysqlRootUser'."
Write-Host "Note: The password input will be hidden for security."
Write-Host "--------------------------------------------------------------------------"

# Construct SQL commands
# Using IF NOT EXISTS to prevent errors if the database or user already exist.
$SqlCommands = @"
CREATE DATABASE IF NOT EXISTS \`$DbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DbUser'@'localhost' IDENTIFIED BY '$DbPass';
GRANT ALL PRIVILEGES ON \`$DbName\`.* TO '$DbUser'@'localhost';
FLUSH PRIVILEGES;
SELECT 'Database "$DbName" and user "$DbUser" setup tasks attempted.' AS status, 'Ensure includes/config.php matches these credentials.' as reminder;
"@

# Prepare to execute the SQL commands
Write-Host "The following SQL commands will be attempted (password for '$DbUser' is '$DbPass'):"
Write-Host $SqlCommands
Write-Host "--------------------------------------------------------------------------"
Write-Host "Attempting to execute SQL commands via 'mysql.exe -u $MysqlRootUser -p'..."
Write-Host "You will be prompted for the MySQL '$MysqlRootUser' password by the mysql client."

try {
    # Pipe the SQL commands to mysql.exe
    # The -p flag alone will cause mysql.exe to prompt for the password.
    Set-Content -Path "temp_sql_commands.sql" -Value $SqlCommands -Encoding UTF8
    Get-Content -Path "temp_sql_commands.sql" | mysql.exe -u $MysqlRootUser -p
    Remove-Item "temp_sql_commands.sql"
    
    Write-Host ""
    Write-Host "--------------------------------------------------------------------------"
    Write-Host "MySQL command execution process completed."
    Write-Host "Please check the output above from MySQL for success or error messages."
    Write-Host "If successful, the database '$DbName' and user '$DbUser' should be ready."
    Write-Host "--------------------------------------------------------------------------"
    Write-Host ""
    Write-Host "Next Steps:"
    Write-Host "1. Verify 'includes/config.php' has the correct database details:"
    Write-Host "   define('DB_HOST', 'localhost');"
    Write-Host "   define('DB_NAME', '$DbName');"
    Write-Host "   define('DB_USER', '$DbUser');"
    Write-Host "   define('DB_PASS', '$DbPass');"
    Write-Host "   (The script used these defaults. If you changed them in config.php, ensure they match what was used or update config.php)."
    Write-Host ""
    Write-Host "2. IMPORTANT: Run the application's installation script through your web browser."
    Write-Host "   Navigate to: http://your-domain.com/install.php"
    Write-Host "   (e.g., http://localhost/your_project_directory/install.php if running locally)."
    Write-Host "   This step creates the necessary tables in the database."
    Write-Host ""
    Write-Host "3. SECURITY: After the installation script at install.php runs successfully in your browser,"
    Write-Host "   DELETE the 'install.php' file from your server."
    Write-Host "   In PowerShell, you can use: Remove-Item -Path "install.php" -Force"
    Write-Host "--------------------------------------------------------------------------"

} catch {
    Write-Error "An error occurred while trying to execute MySQL commands using mysql.exe."
    Write-Error "Details: $($_.Exception.Message)"
    Write-Error "Please ensure MySQL server is running and 'mysql.exe' is accessible in your system's PATH."
    if (Get-Command "temp_sql_commands.sql" -ErrorAction SilentlyContinue) {
        Remove-Item "temp_sql_commands.sql"
    }
} 