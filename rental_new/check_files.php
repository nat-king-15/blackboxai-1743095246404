<?php
/**
 * House Rental Management System
 * File Structure Check Script
 * This script checks for missing essential files and directories
 */

// Include initialization file
require_once 'includes/init.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>House Rental Management System - File Structure Check</h1>";
echo "<p>This script will check for missing essential files and directories.</p>";

// Define essential directories
$essential_dirs = [
    'admin',
    'admin/includes',
    'assets',
    'assets/css',
    'assets/js',
    'assets/img',
    'assets/uploads',
    'assets/uploads/houses',
    'assets/uploads/receipts',
    'assets/uploads/tenant_documents',
    'classes',
    'config',
    'includes',
    'logs',
    'tenant'
];

// Check essential directories
echo "<h2>Checking Essential Directories</h2>";
echo "<ul>";
foreach ($essential_dirs as $dir) {
    $full_path = ROOT_PATH . '/' . $dir;
    if (!is_dir($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            echo "<li style='color: green;'>✅ Directory '$dir' created successfully.</li>";
        } else {
            echo "<li style='color: red;'>❌ Failed to create directory '$dir'.</li>";
        }
    } else {
        echo "<li style='color: blue;'>✓ Directory '$dir' already exists.</li>";
    }
}
echo "</ul>";

// Define essential files
$essential_files = [
    // Core files
    'config/config.php' => '<?php
/**
 * House Rental Management System
 * Configuration File
 */

// Database configuration
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "house_rental_db");

// Base URL - Change this to your website URL
define("BASE_URL", "http://localhost/rental_new");

// Root path
define("ROOT_PATH", dirname(__DIR__));

// Error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);

// Session configuration
session_start();
',

    'includes/init.php' => '<?php
/**
 * House Rental Management System
 * Initialization File
 */

// Include configuration file
require_once dirname(__DIR__) . \'/config/config.php\';

// Include helper functions
require_once dirname(__DIR__) . \'/includes/helpers.php\';

// Include database class
require_once dirname(__DIR__) . \'/classes/Database.php\';

// Set timezone
date_default_timezone_set("Asia/Kolkata");
',

    'includes/helpers.php' => '<?php
/**
 * House Rental Management System
 * Helper Functions
 */

/**
 * Redirect to a URL
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Display flash message
 * @param string $name Message name
 * @param string $message Message text
 * @param string $class CSS class
 */
function flash($name = "", $message = "", $class = "alert alert-success") {
    if(!empty($name)) {
        if(!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION[$name."_class"] = $class;
        } else if(empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name."_class"]) ? $_SESSION[$name."_class"] : "";
            echo "<div class=\'".$class."\' id=\'msg-flash\'>".$_SESSION[$name]."</div>";
            unset($_SESSION[$name]);
            unset($_SESSION[$name."_class"]);
        }
    }
}

/**
 * Check if user is logged in
 * @return boolean
 */
function is_logged_in() {
    if(isset($_SESSION["rental_user"])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get setting value
 * @param string $name Setting name
 * @return string Setting value
 */
function get_setting($name) {
    $db = new Database();
    
    // Try settings table first
    try {
        $db->query("SELECT * FROM settings WHERE id = 1");
        $settings = $db->single();
        
        if($settings && isset($settings[$name])) {
            return $settings[$name];
        }
    } catch (Exception $e) {
        // Table might not exist or column might not exist
    }
    
    // Try system_settings table
    try {
        $db->query("SELECT value FROM system_settings WHERE name = :name");
        $db->bind(":name", $name);
        $setting = $db->single();
        
        if($setting) {
            return $setting["value"];
        }
    } catch (Exception $e) {
        // Table might not exist
    }
    
    // Default values
    $defaults = [
        "site_name" => "House Rental Management System",
        "site_email" => "admin@example.com",
        "currency_symbol" => "₹",
        "date_format" => "Y-m-d",
        "about_content" => "Welcome to the House Rental Management System",
        "terms_and_conditions" => "Terms and conditions go here",
        "maintenance_mode" => "0"
    ];
    
    return isset($defaults[$name]) ? $defaults[$name] : "";
}
',

    'classes/Database.php' => '<?php
/**
 * House Rental Management System
 * PDO Database Class
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $stmt;
    private $error;
    
    public function __construct() {
        // Set DSN
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        
        // Create PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo "Database Connection Error: " . $this->error;
        }
    }
    
    // Prepare statement with query
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }
    
    // Bind values
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
    }
    
    // Execute the prepared statement
    public function execute() {
        return $this->stmt->execute();
    }
    
    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
}
',

    'includes/header.php' => '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - " . get_setting("site_name") : get_setting("site_name"); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
',

    'includes/footer.php' => '    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
',

    'includes/admin_sidebar.php' => '<div class="sidebar">
    <div class="sidebar-header">
        <h3><?php echo get_setting("site_name"); ?></h3>
    </div>
    <div class="sidebar-user">
        <img src="<?php echo BASE_URL; ?>/assets/img/admin-avatar.png" alt="Admin" class="rounded-circle">
        <div class="user-info">
            <h5><?php echo isset($_SESSION["rental_user"]["name"]) ? $_SESSION["rental_user"]["name"] : "Administrator"; ?></h5>
            <span>Admin</span>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "index.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "houses.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/houses.php"><i class="fas fa-home"></i> Houses</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "tenants.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/tenants.php"><i class="fas fa-users"></i> Tenants</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "bookings.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "payments.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "maintenance.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/maintenance.php"><i class="fas fa-tools"></i> Maintenance</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "reports.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "documents.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/documents.php"><i class="fas fa-file-alt"></i> Documents</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "notifications.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "settings.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</div>
',

    'includes/tenant_sidebar.php' => '<div class="sidebar">
    <div class="sidebar-header">
        <h3><?php echo get_setting("site_name"); ?></h3>
    </div>
    <div class="sidebar-user">
        <img src="<?php echo BASE_URL; ?>/assets/img/tenant-avatar.png" alt="Tenant" class="rounded-circle">
        <div class="user-info">
            <h5><?php echo isset($_SESSION["rental_user"]["firstname"]) ? $_SESSION["rental_user"]["firstname"] . " " . $_SESSION["rental_user"]["lastname"] : "Tenant"; ?></h5>
            <span>Tenant</span>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "dashboard.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "bookings.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "payments.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "maintenance.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/maintenance.php"><i class="fas fa-tools"></i> Maintenance Requests</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "documents.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/documents.php"><i class="fas fa-file-alt"></i> Documents</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "rental_history.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/rental_history.php"><i class="fas fa-history"></i> Rental History</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "notifications.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
        </li>
        <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "profile.php" ? "active" : ""; ?>">
            <a href="<?php echo BASE_URL; ?>/tenant/profile.php"><i class="fas fa-user"></i> My Profile</a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/tenant/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</div>
',

    'assets/css/style.css' => '/* House Rental Management System - Main Stylesheet */

/* Global Styles */
body {
    font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: #f8f9fc;
    color: #333;
}

.wrapper {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.sidebar {
    width: 250px;
    background-color: #4e73df;
    background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
    background-size: cover;
    color: #fff;
    min-height: 100vh;
    position: fixed;
    z-index: 1;
    top: 0;
    left: 0;
    overflow-y: auto;
}

.sidebar-header {
    padding: 20px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h3 {
    font-size: 1.2rem;
    margin: 0;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-user {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-user img {
    width: 60px;
    height: 60px;
    margin-bottom: 10px;
}

.user-info h5 {
    font-size: 1rem;
    margin: 0;
    font-weight: 700;
}

.user-info span {
    font-size: 0.8rem;
    opacity: 0.8;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    position: relative;
}

.sidebar-menu li a {
    display: block;
    padding: 12px 15px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s;
}

.sidebar-menu li a:hover,
.sidebar-menu li.active a {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar-menu li a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

/* Content Styles */
.content {
    flex: 1;
    margin-left: 250px;
    padding: 20px;
}

/* Card Styles */
.card {
    margin-bottom: 20px;
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    font-weight: 700;
}

/* Dashboard Stats */
.stat-card {
    border-left: 4px solid;
    border-radius: 0.35rem;
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

.stat-card .card-body {
    padding: 1.25rem;
}

.stat-card .stat-title {
    text-transform: uppercase;
    color: #4e73df;
    font-size: 0.7rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-card .stat-value {
    color: #5a5c69;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
}

/* Login/Register Forms */
.auth-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #4e73df;
    background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
}

.auth-card {
    width: 100%;
    max-width: 450px;
    padding: 2rem;
    background-color: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.auth-logo {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-logo h2 {
    color: #4e73df;
    font-weight: 700;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
    }
    
    .content {
        margin-left: 0;
    }
    
    .wrapper {
        flex-direction: column;
    }
}
',

    'assets/js/main.js' => '/**
 * House Rental Management System
 * Main JavaScript File
 */

$(document).ready(function() {
    // Initialize tooltips
    $("[data-toggle=\'tooltip\']").tooltip();
    
    // Initialize popovers
    $("[data-toggle=\'popover\']").popover();
    
    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        $("#msg-flash").fadeOut("slow");
    }, 5000);
    
    // Toggle sidebar on mobile
    $("#sidebarToggleTop").on("click", function() {
        $(".sidebar").toggleClass("show");
    });
    
    // File input display filename
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
    
    // DataTables initialization
    if($.fn.DataTable) {
        $(".datatable").DataTable({
            responsive: true
        });
    }
});
'
];

// Check essential files
echo "<h2>Checking Essential Files</h2>";
echo "<ul>";
foreach ($essential_files as $file => $content) {
    $full_path = ROOT_PATH . '/' . $file;
    if (!file_exists($full_path)) {
        // Create directory if it doesn't exist
        $dir = dirname($full_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create file
        if (file_put_contents($full_path, $content)) {
            echo "<li style='color: green;'>✅ File '$file' created successfully.</li>";
        } else {
            echo "<li style='color: red;'>❌ Failed to create file '$file'.</li>";
        }
    } else {
        echo "<li style='color: blue;'>✓ File '$file' already exists.</li>";
    }
}
echo "</ul>";

// Create default images if they don't exist
$default_images = [
    'assets/img/admin-avatar.png' => 'https://via.placeholder.com/200x200.png?text=Admin',
    'assets/img/tenant-avatar.png' => 'https://via.placeholder.com/200x200.png?text=Tenant',
    'assets/img/house-default.jpg' => 'https://via.placeholder.com/800x600.png?text=House'
];

echo "<h2>Checking Default Images</h2>";
echo "<ul>";
foreach ($default_images as $image => $placeholder) {
    $full_path = ROOT_PATH . '/' . $image;
    if (!file_exists($full_path)) {
        // Create directory if it doesn't exist
        $dir = dirname($full_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Try to get placeholder image
        $img_content = @file_get_contents($placeholder);
        if ($img_content && file_put_contents($full_path, $img_content)) {
            echo "<li style='color: green;'>✅ Image '$image' created successfully.</li>";
        } else {
            echo "<li style='color: red;'>❌ Failed to create image '$image'.</li>";
        }
    } else {
        echo "<li style='color: blue;'>✓ Image '$image' already exists.</li>";
    }
}
echo "</ul>";

// Summary
echo "<div style='margin-top: 20px; padding: 15px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
echo "<h3 style='color: #3c763d;'>File structure check completed!</h3>";
echo "<p>Your file structure has been checked and any missing essential files have been created.</p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Run the <a href='fix_database.php'>fix_database.php</a> script to ensure your database is properly set up.</li>";
echo "<li>Check for any syntax errors in your PHP files.</li>";
echo "<li>Test the system by logging in as admin (username: admin, password: admin123).</li>";
echo "</ol>";
echo "</div>";

// Add buttons to navigate
echo "<div style='margin-top: 20px;'>";
echo "<a href='fix_database.php' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Run Database Fix</a>";
echo "<a href='admin/login.php' class='btn btn-success' style='display: inline-block; padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Login</a>";
echo "</div>";
?>
