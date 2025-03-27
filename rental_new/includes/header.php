<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - " . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo IMG_PATH; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts - Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
    
    <!-- Admin CSS (only for admin pages) -->
    <?php if(strpos($_SERVER['PHP_SELF'], '/admin/') !== false): ?>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/admin.css">
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom global JS -->
    <script src="<?php echo JS_PATH; ?>/main.js"></script>
</head>
<body>
<?php if(strpos($_SERVER['PHP_SELF'], '/admin/') !== false): ?>
<!-- Topbar for Admin Pages -->
<div class="topbar">
    <button class="toggle-sidebar" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <h2 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
    <div class="topbar-right">
        <div class="notification-bell">
            <?php 
            // Check for unread notifications (you can replace this with actual query)
            $unread_count = 0;
            try {
                $db = new Database();
                $db->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
                $result = $db->single();
                if($result) {
                    $unread_count = $result['count'];
                }
            } catch (Exception $e) {
                // Table might not exist
            }
            ?>
            <a href="<?php echo BASE_URL; ?>/admin/notifications.php" class="text-secondary">
                <i class="fas fa-bell"></i>
                <?php if($unread_count > 0): ?>
                <span class="badge badge-danger"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="dropdown">
            <a class="user-dropdown dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img src="<?php echo IMG_PATH; ?>/admin-avatar.png" alt="Admin">
                <span class="d-none d-md-inline-block ml-1"><?php echo isset($_SESSION['rental_user']['name']) ? $_SESSION['rental_user']['name'] : 'Administrator'; ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
                </a>
                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings.php">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/logout.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
    <!-- Content will be inserted after this --> 