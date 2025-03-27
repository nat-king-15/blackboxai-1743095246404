<?php
/**
 * Initialize application
 * This file sets up the database, includes required files, and starts session
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Check if logs directory exists, create if not
if (!is_dir(dirname($error_log_path))) {
    mkdir(dirname($error_log_path), 0755, true);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Autoload classes
spl_autoload_register(function($class_name) {
    $class_path = ROOT_PATH . '/classes/' . $class_name . '.php';
    if (file_exists($class_path)) {
        require_once $class_path;
    }
});

// Create database object
$db = new Database();

// Define database tables schema
$tables_schema = [
    "CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `houses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `house_no` varchar(50) NOT NULL,
        `category_id` int(11) NOT NULL,
        `description` text NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Available, 1=Occupied',
        `image_path` varchar(255) DEFAULT 'assets/img/house-default.jpg',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `house_no` (`house_no`),
        KEY `category_id` (`category_id`),
        CONSTRAINT `houses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `tenant_accounts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `firstname` varchar(100) NOT NULL,
        `middlename` varchar(100) DEFAULT '',
        `lastname` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `contact` varchar(50) NOT NULL,
        `username` varchar(200) NOT NULL,
        `password` text NOT NULL,
        `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `booking_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) NOT NULL,
        `house_id` int(11) NOT NULL,
        `move_in_date` date NOT NULL,
        `message` text,
        `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Pending, 1=Approved, 2=Rejected',
        `notes` text,
        `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `house_id` (`house_id`),
        CONSTRAINT `booking_requests_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenant_accounts` (`id`) ON DELETE CASCADE,
        CONSTRAINT `booking_requests_ibfk_2` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` text NOT NULL,
        `username` varchar(200) NOT NULL,
        `password` text NOT NULL,
        `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1=Admin,2=Staff',
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `tenants` (
        `id` int(30) NOT NULL AUTO_INCREMENT,
        `firstname` varchar(100) NOT NULL,
        `middlename` varchar(100) NOT NULL,
        `lastname` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `contact` varchar(50) NOT NULL,
        `house_id` int(30) NOT NULL,
        `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0= inactive',
        `date_in` date NOT NULL,
        `booking_request_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `house_id` (`house_id`),
        KEY `booking_request_id` (`booking_request_id`),
        CONSTRAINT `tenants_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE,
        CONSTRAINT `tenants_ibfk_2` FOREIGN KEY (`booking_request_id`) REFERENCES `booking_requests` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `invoice` varchar(50) NOT NULL,
        `payment_method` varchar(50) NOT NULL,
        `reference_number` varchar(100) DEFAULT NULL,
        `notes` text,
        `receipt_path` varchar(255) DEFAULT NULL,
        `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Pending, 1=Approved, 2=Rejected',
        `admin_notes` text,
        `date_created` datetime NOT NULL,
        `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `maintenance_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) NOT NULL,
        `house_id` int(11) NOT NULL,
        `request_type` varchar(50) NOT NULL,
        `description` text NOT NULL,
        `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Pending, 1=In Progress, 2=Completed, 3=Rejected',
        `notes` text,
        `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `house_id` (`house_id`),
        CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
        CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `value` text NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// Create tables
foreach ($tables_schema as $table_query) {
    $db->query($table_query);
    $db->execute();
}

// Check if default admin user exists, if not create it
$db->query("SELECT * FROM users WHERE username = 'admin'");
$admin = $db->single();

if (!$admin) {
    $admin_password = md5('admin123'); // Default password: admin123
    $db->query("INSERT INTO users (name, username, password, type) VALUES ('Administrator', 'admin', :password, 1)");
    $db->bind(':password', $admin_password);
    $db->execute();
    
    error_log("Default admin user created.");
}

// Insert default system settings if they don't exist
$default_settings = [
    ['site_name', 'House Rental Management System'],
    ['site_email', 'info@rentalmanagement.com'],
    ['site_contact', '+1 (555) 123-4567'],
    ['currency_symbol', '₹'],
    ['about_content', 'Welcome to the House Rental Management System, a comprehensive solution for managing rental properties.'],
    ['terms_and_conditions', 'Please read these terms and conditions carefully before using our services.']
];

foreach ($default_settings as $setting) {
    $db->query("SELECT id FROM system_settings WHERE name = :name");
    $db->bind(':name', $setting[0]);
    
    if ($db->rowCount() === 0) {
        $db->query("INSERT INTO system_settings (name, value) VALUES (:name, :value)");
        $db->bind(':name', $setting[0]);
        $db->bind(':value', $setting[1]);
        $db->execute();
    }
}

// Helper functions
function get_setting($name) {
    global $db;
    $db->query("SELECT value FROM system_settings WHERE name = :name");
    $db->bind(':name', $name);
    $result = $db->single();
    
    return $result ? $result['value'] : null;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($message, $type = 'success') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function display_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $type_class = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        
        echo '<div class="alert alert-' . $type_class . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span>';
        echo '</button>';
        echo '</div>';
        
        unset($_SESSION['flash']);
    }
}

// Set timezone
date_default_timezone_set(TIMEZONE); 