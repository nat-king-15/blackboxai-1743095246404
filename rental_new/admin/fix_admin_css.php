<?php
/**
 * House Rental Management System
 * Admin CSS and Layout Fixer Script
 * This script fixes various issues with the admin panel CSS and layout
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config file
require_once '../config/config.php';
require_once '../classes/Database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['rental_user']) || $_SESSION['rental_user']['role'] != 'admin') {
    // Redirect to login page
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

// Set page title
$page_title = 'Fix Admin CSS';

// Function to check and create file if it doesn't exist
function checkAndCreateFile($filePath, $fileContent) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $fileContent);
        return "Created file: " . $filePath . "<br>";
    } else {
        return "File already exists: " . $filePath . "<br>";
    }
}

// Function to check and create directory if it doesn't exist
function checkAndCreateDirectory($dirPath) {
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
        return "Created directory: " . $dirPath . "<br>";
    } else {
        return "Directory already exists: " . $dirPath . "<br>";
    }
}

// Initialize messages array
$messages = [];

// Fix 1: Check and create necessary directories
$messages[] = checkAndCreateDirectory(ROOT_PATH . '/assets');
$messages[] = checkAndCreateDirectory(ROOT_PATH . '/assets/css');
$messages[] = checkAndCreateDirectory(ROOT_PATH . '/assets/js');
$messages[] = checkAndCreateDirectory(ROOT_PATH . '/assets/img');

// Fix 2: Create placeholder avatar if it doesn't exist
$avatar_path = ROOT_PATH . '/assets/img/admin-avatar.png';
if (!file_exists($avatar_path)) {
    // Create a simple placeholder image (or you can download one)
    copy('https://via.placeholder.com/150', $avatar_path);
    $messages[] = "Created admin avatar placeholder: " . $avatar_path . "<br>";
} else {
    $messages[] = "Admin avatar already exists: " . $avatar_path . "<br>";
}

// Fix 3: Create favicon if it doesn't exist
$favicon_path = ROOT_PATH . '/assets/img/favicon.ico';
if (!file_exists($favicon_path)) {
    // Create a simple placeholder favicon (or you can download one)
    copy('https://via.placeholder.com/32', $favicon_path);
    $messages[] = "Created favicon placeholder: " . $favicon_path . "<br>";
} else {
    $messages[] = "Favicon already exists: " . $favicon_path . "<br>";
}

// Fix 4: Update admin page files to include the header and footer
$admin_pages = [
    'index.php', 'tenants.php', 'houses.php', 'categories.php', 
    'booking_requests.php', 'maintenance_requests.php', 'payments.php', 
    'reports.php', 'users.php', 'settings.php', 'profile.php'
];

foreach ($admin_pages as $page) {
    $page_path = ROOT_PATH . '/admin/' . $page;
    
    if (file_exists($page_path)) {
        // Read file content
        $content = file_get_contents($page_path);
        
        // Check if header is missing and add it if needed
        if (strpos($content, "include '../includes/header.php'") === false) {
            $content = preg_replace('/<\?php/', "<?php\n// Include header\ninclude '../includes/header.php';", $content, 1);
            $messages[] = "Added header include to: " . $page . "<br>";
        }
        
        // Check if footer is missing and add it if needed
        if (strpos($content, "include '../includes/admin_footer.php'") === false) {
            // Remove regular footer if it exists
            $content = str_replace("include '../includes/footer.php';", "", $content);
            
            // Add admin footer before the closing PHP tag or at the end of the file
            if (substr(trim($content), -2) == '?>') {
                $content = substr(trim($content), 0, -2) . "\n\n// Include admin footer\ninclude '../includes/admin_footer.php';\n?>";
            } else {
                $content .= "\n\n// Include admin footer\ninclude '../includes/admin_footer.php';\n";
            }
            $messages[] = "Added admin footer include to: " . $page . "<br>";
        }
        
        // Write updated content back to file
        file_put_contents($page_path, $content);
    }
}

// Fix 5: Create or update constants.php to ensure all constants are defined
$constants_path = ROOT_PATH . '/config/constants.php';
$constants_content = <<<'EOT'
<?php
/**
 * House Rental Management System
 * Constants
 */

// Application Constants
if (!defined('APP_NAME')) define('APP_NAME', 'House Rental Management System');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');

// Image Constants
if (!defined('IMG_PATH')) define('IMG_PATH', ASSET_PATH . '/img');

// Status Constants
if (!defined('STATUS_PENDING')) define('STATUS_PENDING', 0);
if (!defined('STATUS_APPROVED')) define('STATUS_APPROVED', 1);
if (!defined('STATUS_REJECTED')) define('STATUS_REJECTED', 2);
if (!defined('STATUS_COMPLETED')) define('STATUS_COMPLETED', 3);

// User Roles
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_TENANT')) define('ROLE_TENANT', 'tenant');

// Notification Types
if (!defined('NOTIFICATION_BOOKING')) define('NOTIFICATION_BOOKING', 1);
if (!defined('NOTIFICATION_PAYMENT')) define('NOTIFICATION_PAYMENT', 2);
if (!defined('NOTIFICATION_MAINTENANCE')) define('NOTIFICATION_MAINTENANCE', 3);
if (!defined('NOTIFICATION_SYSTEM')) define('NOTIFICATION_SYSTEM', 4);
EOT;

$messages[] = checkAndCreateFile($constants_path, $constants_content);

// Initialize database connection for testing
$db = new Database();

// Include header
include '../includes/header.php';
?>

<div class="admin-layout">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Fix Admin CSS and Layout</h1>
                <div class="action-buttons">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-primary">
                        <i class="fas fa-home fa-sm"></i> Return to Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Results Card -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="font-weight-bold text-primary mb-0">Fix Results</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($messages)): ?>
                        <ul class="list-group">
                            <?php foreach ($messages as $message): ?>
                                <li class="list-group-item"><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="alert alert-success mt-4">
                            <h5><i class="fas fa-check-circle"></i> All fixes completed!</h5>
                            <p>Your admin panel CSS and layout should now be working correctly. Please check the dashboard and other pages to confirm everything is displaying properly.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> No issues found</h5>
                            <p>No CSS or layout issues were detected in your admin panel.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <h4>CSS Test Results</h4>
                        <p>If you can see these elements styled properly, the CSS is working correctly:</p>
                        
                        <!-- Test Elements -->
                        <div class="row">
                            <div class="col-md-3 mb-4">
                                <div class="card stat-card primary h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stat-title text-primary">TEST CARD</div>
                                                <div class="stat-value">1</div>
                                            </div>
                                            <div class="stat-icon text-primary">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="card stat-card success h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stat-title text-success">TEST CARD</div>
                                                <div class="stat-value">2</div>
                                            </div>
                                            <div class="stat-icon text-success">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="card stat-card info h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stat-title text-info">TEST CARD</div>
                                                <div class="stat-value">3</div>
                                            </div>
                                            <div class="stat-icon text-info">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="card stat-card warning h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stat-title text-warning">TEST CARD</div>
                                                <div class="stat-value">4</div>
                                            </div>
                                            <div class="stat-icon text-warning">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buttons Test -->
                        <div class="my-4">
                            <button class="btn btn-primary mx-1">Primary Button</button>
                            <button class="btn btn-success mx-1">Success Button</button>
                            <button class="btn btn-info mx-1">Info Button</button>
                            <button class="btn btn-warning mx-1">Warning Button</button>
                            <button class="btn btn-danger mx-1">Danger Button</button>
                        </div>
                        
                        <!-- Badges Test -->
                        <div class="my-4">
                            <span class="badge badge-primary mx-1">Primary Badge</span>
                            <span class="badge badge-success mx-1">Success Badge</span>
                            <span class="badge badge-info mx-1">Info Badge</span>
                            <span class="badge badge-warning mx-1">Warning Badge</span>
                            <span class="badge badge-danger mx-1">Danger Badge</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-text">
                <span>Copyright &copy; <?php echo APP_NAME; ?> <?php echo date('Y'); ?></span>
            </div>
            <div class="footer-version">
                <span>Version 1.0</span>
            </div>
        </footer>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
