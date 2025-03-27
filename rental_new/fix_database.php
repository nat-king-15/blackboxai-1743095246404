<?php
/**
 * House Rental Management System
 * Database Fix Script
 * This script creates all required tables and fixes database issues
 */

// Include initialization file
require_once 'includes/init.php';

// Initialize database
$db = new Database();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Array to track created tables
$created_tables = [];
$updated_tables = [];

echo "<h1>House Rental Management System - Database Fix</h1>";
echo "<p>This script will create all required tables and fix any database issues.</p>";

// Function to check if table exists
function tableExists($db, $table) {
    try {
        $db->query("SHOW TABLES LIKE '$table'");
        $result = $db->resultSet();
        return count($result) > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to check if column exists in table
function columnExists($db, $table, $column) {
    try {
        $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        $result = $db->resultSet();
        return count($result) > 0;
    } catch (Exception $e) {
        return false;
    }
}

// 1. Create or update categories table
if (!tableExists($db, 'categories')) {
    $db->query("CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'categories';
    echo "<p>✅ Table 'categories' created successfully.</p>";
} else {
    echo "<p>✓ Table 'categories' already exists.</p>";
}

// 2. Create or update houses table
if (!tableExists($db, 'houses')) {
    $db->query("CREATE TABLE IF NOT EXISTS `houses` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'houses';
    echo "<p>✅ Table 'houses' created successfully.</p>";
} else {
    echo "<p>✓ Table 'houses' already exists.</p>";
}

// 3. Create or update tenant_accounts table
if (!tableExists($db, 'tenant_accounts')) {
    $db->query("CREATE TABLE IF NOT EXISTS `tenant_accounts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `firstname` varchar(100) NOT NULL,
        `middlename` varchar(100) DEFAULT '',
        `lastname` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `contact` varchar(50) NOT NULL,
        `username` varchar(200) NOT NULL,
        `password` text NOT NULL,
        `reset_token` varchar(255) DEFAULT NULL,
        `reset_expiry` datetime DEFAULT NULL,
        `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'tenant_accounts';
    echo "<p>✅ Table 'tenant_accounts' created successfully.</p>";
} else {
    echo "<p>✓ Table 'tenant_accounts' already exists.</p>";
    
    // Check if reset_token and reset_expiry columns exist
    if (!columnExists($db, 'tenant_accounts', 'reset_token')) {
        $db->query("ALTER TABLE `tenant_accounts` ADD COLUMN `reset_token` varchar(255) DEFAULT NULL");
        $db->execute();
        $updated_tables[] = 'tenant_accounts (added reset_token column)';
        echo "<p>✅ Added 'reset_token' column to tenant_accounts table.</p>";
    }
    
    if (!columnExists($db, 'tenant_accounts', 'reset_expiry')) {
        $db->query("ALTER TABLE `tenant_accounts` ADD COLUMN `reset_expiry` datetime DEFAULT NULL");
        $db->execute();
        $updated_tables[] = 'tenant_accounts (added reset_expiry column)';
        echo "<p>✅ Added 'reset_expiry' column to tenant_accounts table.</p>";
    }
}

// 4. Create or update booking_requests table
if (!tableExists($db, 'booking_requests')) {
    $db->query("CREATE TABLE IF NOT EXISTS `booking_requests` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'booking_requests';
    echo "<p>✅ Table 'booking_requests' created successfully.</p>";
} else {
    echo "<p>✓ Table 'booking_requests' already exists.</p>";
}

// 5. Create or update users table
if (!tableExists($db, 'users')) {
    $db->query("CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` text NOT NULL,
        `username` varchar(200) NOT NULL,
        `password` text NOT NULL,
        `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1=Admin,2=Staff',
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'users';
    echo "<p>✅ Table 'users' created successfully.</p>";
    
    // Insert default admin user
    $admin_password = md5('admin123'); // Default password: admin123
    $db->query("INSERT INTO users (name, username, password, type) VALUES ('Administrator', 'admin', :password, 1)");
    $db->bind(':password', $admin_password);
    $db->execute();
    echo "<p>✅ Default admin user created (username: admin, password: admin123).</p>";
} else {
    echo "<p>✓ Table 'users' already exists.</p>";
}

// 6. Create or update tenants table
if (!tableExists($db, 'tenants')) {
    $db->query("CREATE TABLE IF NOT EXISTS `tenants` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'tenants';
    echo "<p>✅ Table 'tenants' created successfully.</p>";
} else {
    echo "<p>✓ Table 'tenants' already exists.</p>";
}

// 7. Create or update payments table
if (!tableExists($db, 'payments')) {
    $db->query("CREATE TABLE IF NOT EXISTS `payments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'payments';
    echo "<p>✅ Table 'payments' created successfully.</p>";
} else {
    echo "<p>✓ Table 'payments' already exists.</p>";
    
    // Check if status column exists
    if (!columnExists($db, 'payments', 'status')) {
        $db->query("ALTER TABLE `payments` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Pending, 1=Approved, 2=Rejected'");
        $db->execute();
        $updated_tables[] = 'payments (added status column)';
        echo "<p>✅ Added 'status' column to payments table.</p>";
    }
}

// 8. Create or update maintenance_requests table
if (!tableExists($db, 'maintenance_requests')) {
    $db->query("CREATE TABLE IF NOT EXISTS `maintenance_requests` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'maintenance_requests';
    echo "<p>✅ Table 'maintenance_requests' created successfully.</p>";
} else {
    echo "<p>✓ Table 'maintenance_requests' already exists.</p>";
}

// 9. Create or update system_settings table
if (!tableExists($db, 'system_settings')) {
    $db->query("CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `value` text NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'system_settings';
    echo "<p>✅ Table 'system_settings' created successfully.</p>";
    
    // Insert default system settings
    $default_settings = [
        ['site_name', 'House Rental Management System'],
        ['site_email', 'info@rentalmanagement.com'],
        ['site_contact', '+1 (555) 123-4567'],
        ['currency_symbol', '₹'],
        ['about_content', 'Welcome to the House Rental Management System, a comprehensive solution for managing rental properties.'],
        ['terms_and_conditions', 'Please read these terms and conditions carefully before using our services.']
    ];
    
    foreach ($default_settings as $setting) {
        $db->query("INSERT INTO system_settings (name, value) VALUES (:name, :value)");
        $db->bind(':name', $setting[0]);
        $db->bind(':value', $setting[1]);
        $db->execute();
    }
    echo "<p>✅ Default system settings created.</p>";
} else {
    echo "<p>✓ Table 'system_settings' already exists.</p>";
}

// 10. Create or update settings table
if (!tableExists($db, 'settings')) {
    $db->query("CREATE TABLE IF NOT EXISTS `settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `site_name` varchar(255) NOT NULL DEFAULT 'House Rental System',
        `site_email` varchar(255) NOT NULL DEFAULT 'admin@example.com',
        `currency_symbol` varchar(10) NOT NULL DEFAULT '$',
        `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
        `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'settings';
    echo "<p>✅ Table 'settings' created successfully.</p>";
    
    // Insert default settings
    $db->query("INSERT INTO settings (site_name, site_email, currency_symbol, date_format, maintenance_mode) 
               VALUES ('House Rental System', 'admin@example.com', '₹', 'Y-m-d', 0)");
    $db->execute();
    echo "<p>✅ Default settings created.</p>";
} else {
    echo "<p>✓ Table 'settings' already exists.</p>";
}

// 11. Create or update notifications table
if (!tableExists($db, 'notifications')) {
    $db->query("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `user_type` varchar(20) NOT NULL,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`,`user_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'notifications';
    echo "<p>✅ Table 'notifications' created successfully.</p>";
} else {
    echo "<p>✓ Table 'notifications' already exists.</p>";
}

// 12. Create or update admin_notifications table
if (!tableExists($db, 'admin_notifications')) {
    $db->query("CREATE TABLE IF NOT EXISTS `admin_notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'admin_notifications';
    echo "<p>✅ Table 'admin_notifications' created successfully.</p>";
} else {
    echo "<p>✓ Table 'admin_notifications' already exists.</p>";
}

// 13. Create or update tenant_documents table
if (!tableExists($db, 'tenant_documents')) {
    $db->query("CREATE TABLE IF NOT EXISTS `tenant_documents` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text,
        `file_path` varchar(255) NOT NULL,
        `file_type` varchar(50) NOT NULL,
        `file_size` int(11) NOT NULL,
        `is_verified` tinyint(1) NOT NULL DEFAULT '0',
        `admin_notes` text,
        `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute();
    $created_tables[] = 'tenant_documents';
    echo "<p>✅ Table 'tenant_documents' created successfully.</p>";
} else {
    echo "<p>✓ Table 'tenant_documents' already exists.</p>";
}

// Create upload directories if they don't exist
$upload_dirs = [
    'assets/uploads',
    'assets/uploads/houses',
    'assets/uploads/receipts',
    'assets/uploads/tenant_documents',
    'logs'
];

foreach ($upload_dirs as $dir) {
    $full_path = ROOT_PATH . '/' . $dir;
    if (!is_dir($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            echo "<p>✅ Directory '$dir' created successfully.</p>";
        } else {
            echo "<p>❌ Failed to create directory '$dir'.</p>";
        }
    } else {
        echo "<p>✓ Directory '$dir' already exists.</p>";
    }
}

// Summary
echo "<h2>Database Fix Summary:</h2>";
if (count($created_tables) > 0) {
    echo "<p>The following tables were created:</p>";
    echo "<ul>";
    foreach ($created_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No new tables were created. Your database structure is up to date.</p>";
}

if (count($updated_tables) > 0) {
    echo "<p>The following tables were updated:</p>";
    echo "<ul>";
    foreach ($updated_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
}

echo "<div style='margin-top: 20px; padding: 15px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
echo "<h3 style='color: #3c763d;'>Database fix completed successfully!</h3>";
echo "<p>Your database structure is now complete and ready to use. You can now <a href='".BASE_URL."/admin/index.php'>return to the dashboard</a>.</p>";
echo "</div>";

// Add button to go back to admin dashboard
echo "<div style='margin-top: 20px;'>";
echo "<a href='".BASE_URL."/admin/index.php' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a>";
echo "</div>";
?>
