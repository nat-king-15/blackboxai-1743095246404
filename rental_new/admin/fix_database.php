<?php
/**
 * House Rental Management System
 * Database Fix Script
 * This script creates any missing tables in the database
 */

// Include initialization file
require_once '../includes/init.php';

// Initialize database
$db = new Database();

// Array to track created tables
$created_tables = [];

// Check and create maintenance_requests table if it doesn't exist
try {
    $db->query("SELECT 1 FROM maintenance_requests LIMIT 1");
    $db->execute();
    echo "Table 'maintenance_requests' already exists.<br>";
} catch (PDOException $e) {
    // Table doesn't exist, create it
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
    echo "Table 'maintenance_requests' created successfully.<br>";
}

// Check and create admin_notifications table if it doesn't exist
try {
    $db->query("SELECT 1 FROM admin_notifications LIMIT 1");
    $db->execute();
    echo "Table 'admin_notifications' already exists.<br>";
} catch (PDOException $e) {
    // Table doesn't exist, create it
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
    echo "Table 'admin_notifications' created successfully.<br>";
}

// Check and create notifications table if it doesn't exist
try {
    $db->query("SELECT 1 FROM notifications LIMIT 1");
    $db->execute();
    echo "Table 'notifications' already exists.<br>";
} catch (PDOException $e) {
    // Table doesn't exist, create it
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
    echo "Table 'notifications' created successfully.<br>";
}

// Check and create tenant_documents table if it doesn't exist
try {
    $db->query("SELECT 1 FROM tenant_documents LIMIT 1");
    $db->execute();
    echo "Table 'tenant_documents' already exists.<br>";
} catch (PDOException $e) {
    // Table doesn't exist, create it
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
    echo "Table 'tenant_documents' created successfully.<br>";
}

// Add reset_token and reset_expiry columns to tenant_accounts if they don't exist
try {
    $db->query("SELECT reset_token FROM tenant_accounts LIMIT 1");
    $db->execute();
    echo "Column 'reset_token' already exists in tenant_accounts table.<br>";
} catch (PDOException $e) {
    // Column doesn't exist, add it
    $db->query("ALTER TABLE `tenant_accounts` ADD COLUMN `reset_token` varchar(255) DEFAULT NULL");
    $db->execute();
    echo "Column 'reset_token' added to tenant_accounts table.<br>";
}

try {
    $db->query("SELECT reset_expiry FROM tenant_accounts LIMIT 1");
    $db->execute();
    echo "Column 'reset_expiry' already exists in tenant_accounts table.<br>";
} catch (PDOException $e) {
    // Column doesn't exist, add it
    $db->query("ALTER TABLE `tenant_accounts` ADD COLUMN `reset_expiry` datetime DEFAULT NULL");
    $db->execute();
    echo "Column 'reset_expiry' added to tenant_accounts table.<br>";
}

// Summary
echo "<br><h3>Database Fix Summary:</h3>";
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

echo "<br><p>Database fix completed successfully. You can now <a href='".BASE_URL."/admin/index.php'>return to the dashboard</a>.</p>";
?>
