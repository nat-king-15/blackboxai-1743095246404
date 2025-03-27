<?php
/**
 * House Rental Management System
 * Admin Settings
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'System Settings';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Check if settings table exists, create if not
try {
    $db->query("SHOW TABLES LIKE 'settings'");
    $result = $db->resultSet();
    if (count($result) == 0) {
        // Table doesn't exist, create it
        $db->query("CREATE TABLE `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `site_name` varchar(255) NOT NULL DEFAULT 'House Rental System',
            `site_email` varchar(255) NOT NULL DEFAULT 'admin@example.com',
            `currency_symbol` varchar(10) NOT NULL DEFAULT '$',
            `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
            `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->execute();
        
        // Insert default settings
        $db->query("INSERT INTO settings (site_name, site_email, currency_symbol, date_format, maintenance_mode) 
                   VALUES ('House Rental System', 'admin@example.com', '$', 'Y-m-d', 0)");
        $db->execute();
    }
} catch (Exception $e) {
    $error_msg = "Error checking or creating settings table: " . $e->getMessage();
}

// Get current settings
try {
    $db->query("SELECT * FROM settings WHERE id = 1");
    $settings = $db->single();

    // If settings don't exist, create default settings
    if(!$settings) {
        $db->query("INSERT INTO settings (site_name, site_email, currency_symbol, date_format, maintenance_mode) 
                   VALUES ('House Rental System', 'admin@example.com', '$', 'Y-m-d', 0)");
        $db->execute();
        
        $db->query("SELECT * FROM settings WHERE id = 1");
        $settings = $db->single();
    }
} catch (Exception $e) {
    // If there's still an error, create a default settings object
    $settings = [
        'id' => 1,
        'site_name' => 'House Rental System',
        'site_email' => 'admin@example.com',
        'currency_symbol' => '$',
        'date_format' => 'Y-m-d',
        'maintenance_mode' => 0
    ];
    $error_msg = "Error retrieving settings: " . $e->getMessage();
}

// Update settings
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = trim($_POST['site_name']);
    $site_email = trim($_POST['site_email']);
    $currency_symbol = trim($_POST['currency_symbol']);
    $date_format = trim($_POST['date_format']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // Validate input
    if(empty($site_name)) {
        $error_msg = "Site name is required.";
    } elseif(empty($site_email) || !filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "A valid site email is required.";
    } else {
        // Update settings
        $db->query("UPDATE settings SET 
                   site_name = :site_name, 
                   site_email = :site_email, 
                   currency_symbol = :currency_symbol, 
                   date_format = :date_format, 
                   maintenance_mode = :maintenance_mode 
                   WHERE id = 1");
        
        $db->bind(':site_name', $site_name);
        $db->bind(':site_email', $site_email);
        $db->bind(':currency_symbol', $currency_symbol);
        $db->bind(':date_format', $date_format);
        $db->bind(':maintenance_mode', $maintenance_mode);
        
        if($db->execute()) {
            $success_msg = "Settings updated successfully.";
            
            // Refresh settings
            $db->query("SELECT * FROM settings WHERE id = 1");
            $settings = $db->single();
        } else {
            $error_msg = "Failed to update settings.";
        }
    }
}

?>

<?php include '../includes/header.php'; ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="page-title">System Settings</h2>
                </div>
            </div>
            
            <?php if(isset($success_msg) && !empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error_msg) && !empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">General Settings</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo $settings['site_name']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_email">Site Email</label>
                                    <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo $settings['site_email']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_symbol">Currency Symbol</label>
                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo $settings['currency_symbol']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_format">Date Format</label>
                                    <select class="form-control" id="date_format" name="date_format">
                                        <option value="Y-m-d" <?php echo ($settings['date_format'] == 'Y-m-d') ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="m-d-Y" <?php echo ($settings['date_format'] == 'm-d-Y') ? 'selected' : ''; ?>>MM-DD-YYYY</option>
                                        <option value="d-m-Y" <?php echo ($settings['date_format'] == 'd-m-Y') ? 'selected' : ''; ?>>DD-MM-YYYY</option>
                                        <option value="M j, Y" <?php echo ($settings['date_format'] == 'M j, Y') ? 'selected' : ''; ?>>Month Day, Year</option>
                                        <option value="F j, Y" <?php echo ($settings['date_format'] == 'F j, Y') ? 'selected' : ''; ?>>Full Month Day, Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="maintenance_mode">Maintenance Mode</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] == 1) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="maintenance_mode">Enable Maintenance Mode</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>