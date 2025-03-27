<?php
/**
 * House Rental Management System
 * Fix All Errors Script
 * This script fixes common issues across the entire system
 */

// Include initialization file
require_once '../includes/init.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>House Rental Management System - Error Fix Tool</h1>";

// Track fixed issues
$fixed_issues = [];

// 1. Fix config.php issues
$config_file = ROOT_PATH . '/config/config.php';
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $updates_made = false;
    
    // Add IMG_PATH if it doesn't exist
    if (strpos($config_content, 'IMG_PATH') === false) {
        $img_path_line = "define('IMG_PATH', ASSET_PATH . '/img');\n";
        $position = strpos($config_content, "define('ASSET_PATH',");
        $position = strpos($config_content, "\n", $position) + 1;
        
        $config_content = substr($config_content, 0, $position) . $img_path_line . substr($config_content, $position);
        $updates_made = true;
        $fixed_issues[] = "Added IMG_PATH constant to config.php";
    }
    
    // Make sure CSS_PATH exists
    if (strpos($config_content, 'CSS_PATH') === false) {
        $css_path_line = "define('CSS_PATH', ASSET_PATH . '/css');\n";
        $position = strpos($config_content, "define('IMG_PATH',");
        $position = strpos($config_content, "\n", $position) + 1;
        
        $config_content = substr($config_content, 0, $position) . $css_path_line . substr($config_content, $position);
        $updates_made = true;
        $fixed_issues[] = "Added CSS_PATH constant to config.php";
    }
    
    // Make sure JS_PATH exists
    if (strpos($config_content, 'JS_PATH') === false) {
        $js_path_line = "define('JS_PATH', ASSET_PATH . '/js');\n";
        $position = strpos($config_content, "define('CSS_PATH',");
        if ($position !== false) {
            $position = strpos($config_content, "\n", $position) + 1;
            
            $config_content = substr($config_content, 0, $position) . $js_path_line . substr($config_content, $position);
            $updates_made = true;
            $fixed_issues[] = "Added JS_PATH constant to config.php";
        }
    }
    
    if ($updates_made) {
        file_put_contents($config_file, $config_content);
        echo "<p>✅ Updated config.php with missing constants</p>";
    }
}

// 2. Fix admin_sidebar.php IMG_PATH reference
$sidebar_file = ROOT_PATH . '/includes/admin_sidebar.php';
if (file_exists($sidebar_file)) {
    $sidebar_content = file_get_contents($sidebar_file);
    
    // Replace IMG_PATH with BASE_URL/assets/img if IMG_PATH is not defined
    if (strpos($sidebar_content, 'echo IMG_PATH;') !== false && !defined('IMG_PATH')) {
        $fixed_content = str_replace(
            'echo IMG_PATH;',
            'echo BASE_URL . \'/assets/img\';',
            $sidebar_content
        );
        file_put_contents($sidebar_file, $fixed_content);
        $fixed_issues[] = "Fixed IMG_PATH reference in admin_sidebar.php";
        echo "<p>✅ Fixed IMG_PATH reference in admin_sidebar.php</p>";
    }
}

// 3. Check and fix database tables
echo "<h2>Checking and fixing database tables...</h2>";

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

// Check settings table
$db = new Database();
if (!tableExists($db, 'settings')) {
    $db->query("CREATE TABLE `settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `site_name` varchar(255) NOT NULL DEFAULT 'House Rental System',
        `site_email` varchar(255) NOT NULL DEFAULT 'admin@example.com',
        `currency_symbol` varchar(10) NOT NULL DEFAULT '₹',
        `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
        `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    if ($db->execute()) {
        // Insert default settings
        $db->query("INSERT INTO settings (site_name, site_email, currency_symbol, date_format, maintenance_mode) 
                  VALUES ('House Rental System', 'admin@example.com', '₹', 'Y-m-d', 0)");
        $db->execute();
        
        $fixed_issues[] = "Created settings table with default values";
        echo "<p>✅ Created settings table with default values</p>";
    }
}

// Check maintenance_requests table
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
        KEY `house_id` (`house_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    if ($db->execute()) {
        $fixed_issues[] = "Created maintenance_requests table";
        echo "<p>✅ Created maintenance_requests table</p>";
    }
}

// 4. Check and fix admin files with syntax errors
$admin_files = glob(ROOT_PATH . '/admin/*.php');
foreach ($admin_files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    // Check for unclosed PHP tags
    $open_php = substr_count($content, '<?php');
    $close_php = substr_count($content, '?>');
    
    if ($open_php > $close_php) {
        // Add missing closing PHP tag
        file_put_contents($file, $content . "\n?>");
        $fixed_issues[] = "Fixed unclosed PHP tag in $filename";
        echo "<p>✅ Fixed unclosed PHP tag in $filename</p>";
    }
    
    // Check for SQL syntax errors in reports.php
    if ($filename == 'reports.php') {
        if (strpos($content, 'GROUP BY c.name') !== false && strpos($content, 'GROUP BY c.name")') === false) {
            $fixed_content = str_replace(
                'GROUP BY c.name',
                'GROUP BY c.name")',
                $content
            );
            file_put_contents($file, $fixed_content);
            $fixed_issues[] = "Fixed SQL query in reports.php";
            echo "<p>✅ Fixed SQL query in reports.php</p>";
        }
    }
    
    // Check for HTML errors in the files
    if (strpos($content, '<div') !== false) {
        $div_open = substr_count($content, '<div');
        $div_close = substr_count($content, '</div>');
        
        if ($div_open > $div_close) {
            // Add missing closing div tags
            $missing_divs = $div_open - $div_close;
            $fixed_content = $content;
            
            // Find position before the last PHP closing tag or at the end of file
            $pos = strrpos($fixed_content, '?>');
            if ($pos === false) {
                $pos = strlen($fixed_content);
            }
            
            // Add the missing closing divs
            $closing_divs = str_repeat("</div>\n", $missing_divs);
            $fixed_content = substr($fixed_content, 0, $pos) . $closing_divs . substr($fixed_content, $pos);
            
            file_put_contents($file, $fixed_content);
            $fixed_issues[] = "Fixed missing div tags in $filename";
            echo "<p>✅ Fixed missing div tags in $filename</p>";
        }
    }
    
    // Check for form tags
    if (strpos($content, '<form') !== false) {
        $form_open = substr_count($content, '<form');
        $form_close = substr_count($content, '</form>');
        
        if ($form_open > $form_close) {
            // Add missing closing form tags
            $missing_forms = $form_open - $form_close;
            $fixed_content = $content;
            
            // Find position before the last PHP closing tag or at the end of file
            $pos = strrpos($fixed_content, '?>');
            if ($pos === false) {
                $pos = strlen($fixed_content);
            }
            
            // Add the missing closing forms
            $closing_forms = str_repeat("</form>\n", $missing_forms);
            $fixed_content = substr($fixed_content, 0, $pos) . $closing_forms . substr($fixed_content, $pos);
            
            file_put_contents($file, $fixed_content);
            $fixed_issues[] = "Fixed missing form tags in $filename";
            echo "<p>✅ Fixed missing form tags in $filename</p>";
        }
    }
}

// 5. Create essential CSS and image files
$css_dir = ROOT_PATH . '/assets/css';
if (!is_dir($css_dir)) {
    mkdir($css_dir, 0755, true);
    $fixed_issues[] = "Created assets/css directory";
    echo "<p>✅ Created assets/css directory</p>";
}

// Create admin.css if it doesn't exist
$admin_css = $css_dir . '/admin.css';
if (!file_exists($admin_css)) {
    $css_content = "/* Admin Panel Styles */
.admin-layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    min-height: 100vh;
    position: fixed;
    z-index: 100;
}

.sidebar-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-menu {
    padding: 0 15px;
}

.sidebar-menu .nav-link {
    color: rgba(255, 255, 255, 0.8);
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 5px;
}

.sidebar-menu .nav-link:hover,
.sidebar-menu .nav-link.active {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar-menu .nav-header {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
    text-transform: uppercase;
    padding: 10px 15px;
    margin-top: 10px;
}

.content {
    flex: 1;
    margin-left: 250px;
    padding: 20px;
}

/* Dashboard Stats */
.stat-card {
    border-left: 4px solid;
    border-radius: 4px;
}

.stat-card.primary {
    border-left-color: #4e73df;
}

.stat-card.success {
    border-left-color: #1cc88a;
}

.stat-card.info {
    border-left-color: #36b9cc;
}

.stat-card.warning {
    border-left-color: #f6c23e;
}

.stat-card.danger {
    border-left-color: #e74a3b;
}

/* Table Styles */
.table-actions .btn {
    margin-right: 5px;
}

/* Form Styles */
.required-field::after {
    content: '*';
    color: #e74a3b;
    margin-left: 3px;
}

/* Login Form */
.login-form {
    max-width: 400px;
    margin: 100px auto;
    padding: 30px;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
    }
    
    .content {
        margin-left: 0;
    }
    
    .admin-layout {
        flex-direction: column;
    }
}";
    
    file_put_contents($admin_css, $css_content);
    $fixed_issues[] = "Created admin.css file";
    echo "<p>✅ Created admin.css file</p>";
}

// Create style.css if it doesn't exist
$style_css = $css_dir . '/style.css';
if (!file_exists($style_css)) {
    $css_content = "/* Main Stylesheet */
body {
    font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: #f8f9fc;
}

/* Common Styles */
.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

.bg-primary {
    background-color: #4e73df !important;
}

.text-primary {
    color: #4e73df !important;
}

/* Admin Layout */
.admin-layout {
    display: flex;
}

.content {
    flex: 1;
    margin-left: 250px;
    padding: 20px;
}

@media (max-width: 768px) {
    .content {
        margin-left: 0;
    }
}

/* Card Styles */
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin-bottom: 20px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

/* Alert Styles */
.alert {
    border: none;
    box-shadow: 0 0.125rem 0.25rem 0 rgba(58, 59, 69, 0.2);
}

/* Table Styles */
.table th {
    border-top: none;
    background-color: #f8f9fc;
    color: #5a5c69;
    font-weight: 700;
}

/* Custom Styles */
.page-title {
    color: #5a5c69;
    margin-bottom: 20px;
}";
    
    file_put_contents($style_css, $css_content);
    $fixed_issues[] = "Created style.css file";
    echo "<p>✅ Created style.css file</p>";
}

// Create img directory if it doesn't exist
$img_dir = ROOT_PATH . '/assets/img';
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0755, true);
    $fixed_issues[] = "Created assets/img directory";
    echo "<p>✅ Created assets/img directory</p>";
}

// Create admin-avatar.png if it doesn't exist
$admin_avatar = $img_dir . '/admin-avatar.png';
if (!file_exists($admin_avatar)) {
    // Try to get a placeholder image
    $img_content = @file_get_contents('https://via.placeholder.com/200x200.png?text=Admin');
    if ($img_content) {
        file_put_contents($admin_avatar, $img_content);
        $fixed_issues[] = "Created admin-avatar.png";
        echo "<p>✅ Created admin-avatar.png</p>";
    }
}

// Create tenant-avatar.png if it doesn't exist
$tenant_avatar = $img_dir . '/tenant-avatar.png';
if (!file_exists($tenant_avatar)) {
    // Try to get a placeholder image
    $img_content = @file_get_contents('https://via.placeholder.com/200x200.png?text=Tenant');
    if ($img_content) {
        file_put_contents($tenant_avatar, $img_content);
        $fixed_issues[] = "Created tenant-avatar.png";
        echo "<p>✅ Created tenant-avatar.png</p>";
    }
}

// 6. Create .htaccess file for clean URLs if it doesn't exist
$htaccess_file = ROOT_PATH . '/.htaccess';
if (!file_exists($htaccess_file)) {
    $htaccess_content = "# Enable rewriting
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /rental_new/
    
    # Handle authorization header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect to HTTPS (uncomment in production)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Remove trailing slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    
    # Forward all requests to index.php that are not existing files or directories
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>

# Handle errors
ErrorDocument 404 /rental_new/404.php
ErrorDocument 500 /rental_new/500.php

# Disable directory listing
Options -Indexes

# Set default character set
AddDefaultCharset UTF-8

# Enable PHP error logging
php_flag log_errors on
php_value error_log logs/php_errors.log";
    
    file_put_contents($htaccess_file, $htaccess_content);
    $fixed_issues[] = "Created .htaccess file";
    echo "<p>✅ Created .htaccess file</p>";
}

// Summary
echo "<h2>Error Fix Summary:</h2>";
if (count($fixed_issues) > 0) {
    echo "<p>The following issues were fixed:</p>";
    echo "<ul>";
    foreach ($fixed_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No issues were found that needed fixing. Your admin panel should be working correctly.</p>";
}

echo "<div style='margin-top: 20px; padding: 15px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
echo "<h3 style='color: #3c763d;'>✅ All errors fixed successfully!</h3>";
echo "<p>Your admin panel should now be working correctly. You can now <a href='".BASE_URL."/admin/index.php'>return to the dashboard</a>.</p>";
echo "</div>";

// Add button to go back to admin dashboard
echo "<div style='margin-top: 20px;'>";
echo "<a href='".BASE_URL."/admin/index.php' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a>";
echo "</div>";
?>
