<?php
/**
 * House Rental Management System
 * Admin Panel Fix Script
 * This script fixes common issues in the admin panel
 */

// Include initialization file
require_once '../includes/init.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>House Rental Management System - Admin Panel Fix</h1>";
echo "<p>This script will fix common issues in the admin panel.</p>";

// Initialize database
$db = new Database();

// Array to track fixed issues
$fixed_issues = [];

// 1. Check if IMG_PATH constant is defined
if (!defined('IMG_PATH')) {
    // Add IMG_PATH constant to config.php
    $config_file = ROOT_PATH . '/config/config.php';
    $config_content = file_get_contents($config_file);
    
    if (strpos($config_content, 'IMG_PATH') === false) {
        $new_line = "define('IMG_PATH', ASSET_PATH . '/img');\n";
        $position = strpos($config_content, "define('ASSET_PATH',");
        $position = strpos($config_content, "\n", $position) + 1;
        
        $config_content = substr($config_content, 0, $position) . $new_line . substr($config_content, $position);
        file_put_contents($config_file, $config_content);
        
        $fixed_issues[] = "Added IMG_PATH constant to config.php";
    }
}

// 2. Fix admin sidebar issues
$sidebar_file = ROOT_PATH . '/includes/admin_sidebar.php';
$sidebar_content = file_get_contents($sidebar_file);

// Fix truncated line in admin_sidebar.php
if (strpos($sidebar_content, '<a class="nav-link <?php echo (ba') !== false) {
    $fixed_content = str_replace(
        '<a class="nav-link <?php echo (ba',
        '<a class="nav-link <?php echo (basename($_SERVER[\'PHP_SELF\']) == \'houses.php\') ? \'active\' : \'\'; ?>" href="<?php echo BASE_URL; ?>/admin/houses.php">',
        $sidebar_content
    );
    file_put_contents($sidebar_file, $fixed_content);
    $fixed_issues[] = "Fixed truncated line in admin_sidebar.php";
}

// 3. Check and create admin CSS file if missing
$admin_css_file = ROOT_PATH . '/assets/css/admin.css';
if (!file_exists($admin_css_file)) {
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
    
    if (!is_dir(dirname($admin_css_file))) {
        mkdir(dirname($admin_css_file), 0755, true);
    }
    
    file_put_contents($admin_css_file, $css_content);
    $fixed_issues[] = "Created admin.css file";
}

// 4. Check and fix header.php to include admin.css
$header_file = ROOT_PATH . '/includes/header.php';
if (file_exists($header_file)) {
    $header_content = file_get_contents($header_file);
    
    if (strpos($header_content, 'admin.css') === false) {
        $admin_css_line = "<link rel=\"stylesheet\" href=\"<?php echo BASE_URL; ?>/assets/css/admin.css\">";
        $position = strpos($header_content, '<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">');
        $position = strpos($header_content, "\n", $position) + 1;
        
        $header_content = substr($header_content, 0, $position) . "    " . $admin_css_line . "\n" . substr($header_content, $position);
        file_put_contents($header_file, $header_content);
        
        $fixed_issues[] = "Added admin.css to header.php";
    }
}

// 5. Fix maintenance_requests.php if it has issues
$maintenance_file = ROOT_PATH . '/admin/maintenance_requests.php';
if (file_exists($maintenance_file)) {
    $maintenance_content = file_get_contents($maintenance_file);
    
    // Check for incomplete PHP tags or HTML structure
    $open_tags = substr_count($maintenance_content, '<?php');
    $close_tags = substr_count($maintenance_content, '?>');
    
    if ($open_tags > $close_tags) {
        $maintenance_content .= "\n?>";
        file_put_contents($maintenance_file, $maintenance_content);
        $fixed_issues[] = "Fixed unclosed PHP tags in maintenance_requests.php";
    }
    
    // Check for missing closing divs
    $open_divs = substr_count($maintenance_content, '<div');
    $close_divs = substr_count($maintenance_content, '</div>');
    
    if ($open_divs > $close_divs) {
        $missing_divs = $open_divs - $close_divs;
        $closing_divs = str_repeat("</div>\n", $missing_divs);
        $maintenance_content .= $closing_divs;
        file_put_contents($maintenance_file, $maintenance_content);
        $fixed_issues[] = "Fixed missing closing div tags in maintenance_requests.php";
    }
}

// 6. Fix reports.php SQL query issues
$reports_file = ROOT_PATH . '/admin/reports.php';
if (file_exists($reports_file)) {
    $reports_content = file_get_contents($reports_file);
    
    // Check for SQL queries without closing parenthesis
    if (strpos($reports_content, 'GROUP BY c.name') !== false && strpos($reports_content, 'GROUP BY c.name")') === false) {
        $reports_content = str_replace(
            'GROUP BY c.name',
            'GROUP BY c.name")',
            $reports_content
        );
        file_put_contents($reports_file, $reports_content);
        $fixed_issues[] = "Fixed SQL query in reports.php";
    }
}

// 7. Check and fix session handling in admin files
$admin_files = glob(ROOT_PATH . '/admin/*.php');
foreach ($admin_files as $file) {
    if (basename($file) != 'login.php' && basename($file) != 'logout.php' && basename($file) != 'fix_admin.php' && basename($file) != 'fix_database.php') {
        $content = file_get_contents($file);
        
        // Check if session check is present
        if (strpos($content, "if(!isset(\$_SESSION['user_type']) || \$_SESSION['user_type'] != 'admin')") === false) {
            // Add session check after initialization
            $position = strpos($content, "require_once '../includes/init.php';");
            if ($position !== false) {
                $position = strpos($content, "\n", $position) + 1;
                $session_check = "\n// Check if user is logged in as admin\nif(!isset(\$_SESSION['user_type']) || \$_SESSION['user_type'] != 'admin') {\n    redirect(BASE_URL . '/admin/login.php');\n}\n";
                
                $content = substr($content, 0, $position) . $session_check . substr($content, $position);
                file_put_contents($file, $content);
                $fixed_issues[] = "Added session check to " . basename($file);
            }
        }
    }
}

// 8. Check and fix database error handling
$db_file = ROOT_PATH . '/classes/Database.php';
if (file_exists($db_file)) {
    $db_content = file_get_contents($db_file);
    
    // Improve error handling in Database class
    if (strpos($db_content, 'try {') !== false && strpos($db_content, 'catch(PDOException $e)') !== false) {
        if (strpos($db_content, 'public function execute()') !== false && strpos($db_content, 'try {') === false) {
            $execute_method = "public function execute() {
        try {
            return \$this->stmt->execute();
        } catch(PDOException \$e) {
            \$this->error = \$e->getMessage();
            error_log('Database Error: ' . \$this->error);
            return false;
        }
    }";
            
            $db_content = preg_replace('/public function execute\(\) \{.*?return \$this->stmt->execute\(\);.*?\}/s', $execute_method, $db_content);
            file_put_contents($db_file, $db_content);
            $fixed_issues[] = "Improved error handling in Database class";
        }
    }
}

// 9. Create missing admin files if needed
$required_admin_files = [
    'booking_requests.php',
    'categories.php',
    'documents.php',
    'houses.php',
    'index.php',
    'login.php',
    'logout.php',
    'maintenance_requests.php',
    'notifications.php',
    'payments.php',
    'profile.php',
    'reports.php',
    'settings.php',
    'tenants.php',
    'users.php'
];

foreach ($required_admin_files as $file) {
    $file_path = ROOT_PATH . '/admin/' . $file;
    if (!file_exists($file_path)) {
        $fixed_issues[] = "Missing admin file: $file (needs to be created)";
    }
}

// 10. Fix any issues with includes/helpers.php
$helpers_file = ROOT_PATH . '/includes/helpers.php';
if (file_exists($helpers_file)) {
    $helpers_content = file_get_contents($helpers_file);
    
    // Add get_setting function if it doesn't exist
    if (strpos($helpers_content, 'function get_setting') === false) {
        $get_setting_function = "\n/**
 * Get setting value
 * @param string \$name Setting name
 * @return string Setting value
 */
function get_setting(\$name) {
    \$db = new Database();
    
    // Try settings table first
    try {
        \$db->query(\"SELECT * FROM settings WHERE id = 1\");
        \$settings = \$db->single();
        
        if(\$settings && isset(\$settings[\$name])) {
            return \$settings[\$name];
        }
    } catch (Exception \$e) {
        // Table might not exist or column might not exist
    }
    
    // Try system_settings table
    try {
        \$db->query(\"SELECT value FROM system_settings WHERE name = :name\");
        \$db->bind(\":name\", \$name);
        \$setting = \$db->single();
        
        if(\$setting) {
            return \$setting[\"value\"];
        }
    } catch (Exception \$e) {
        // Table might not exist
    }
    
    // Default values
    \$defaults = [
        \"site_name\" => \"House Rental Management System\",
        \"site_email\" => \"admin@example.com\",
        \"currency_symbol\" => \"₹\",
        \"date_format\" => \"Y-m-d\",
        \"about_content\" => \"Welcome to the House Rental Management System\",
        \"terms_and_conditions\" => \"Terms and conditions go here\",
        \"maintenance_mode\" => \"0\"
    ];
    
    return isset(\$defaults[\$name]) ? \$defaults[\$name] : \"\";
}";
        
        $helpers_content .= $get_setting_function;
        file_put_contents($helpers_file, $helpers_content);
        $fixed_issues[] = "Added get_setting function to helpers.php";
    }
}

// Summary
echo "<h2>Admin Panel Fix Summary:</h2>";
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
echo "<h3 style='color: #3c763d;'>Admin panel fix completed!</h3>";
echo "<p>Your admin panel should now be working correctly. You can now <a href='".BASE_URL."/admin/index.php'>return to the dashboard</a>.</p>";
echo "</div>";

// Add button to go back to admin dashboard
echo "<div style='margin-top: 20px;'>";
echo "<a href='".BASE_URL."/admin/index.php' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a>";
echo "</div>";
?>
