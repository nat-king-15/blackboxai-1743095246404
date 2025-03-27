<?php
/**
 * House Rental Management System
 * Configuration file
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define error log path
$error_log_path = __DIR__ . '/../logs/app.log';
ini_set('error_log', $error_log_path);

// Base URL - Change this based on your server configuration
define('BASE_URL', 'http://localhost/rental_new');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'house_rental_db');

// Application Settings
define('APP_NAME', 'House Rental Management System');
define('APP_EMAIL', 'info@rentalmanagement.com');
define('APP_CONTACT', '+1 (555) 123-4567');

// Directory Paths
define('ROOT_PATH', dirname(__DIR__));
define('ASSET_PATH', BASE_URL . '/assets');
define('CSS_PATH', ASSET_PATH . '/css');
define('JS_PATH', ASSET_PATH . '/js');
define('IMG_PATH', ASSET_PATH . '/img');
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Site Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Other Settings
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('TIMEZONE', 'Asia/Kolkata');

// Set default timezone
date_default_timezone_set(TIMEZONE);

// PHP Settings
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

// Version
define('APP_VERSION', '2.0.0'); 