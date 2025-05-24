<?php
// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'lorenzezz0987@gmail.com');
define('SMTP_PASSWORD', 'ceqd fmip fgld mlgb');  // Updated to the new App Password
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'lorenzezz0987@gmail.com');
define('SMTP_FROM_NAME', 'BUPC Computer Science Department');

// Database Configuration
define('DB_HOST', 'localhost');     // Add your database host
define('DB_USER', 'root');         // Add your database username
define('DB_PASS', '');             // Add your database password
define('DB_NAME', 'bupc_lms');     // Add your database name

// Application Settings
define('PASSWORD_RESET_EXPIRY', 15); // Password reset code expiry in minutes

// Debug Settings
define('SMTP_DEBUG', true);        // Enable SMTP debugging
define('ERROR_REPORTING', true);   // Enable detailed error reporting

// If error reporting is enabled
if (ERROR_REPORTING) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error.log');
}
?> 
