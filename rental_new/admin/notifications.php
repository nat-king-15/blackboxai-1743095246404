<?php
/**
 * House Rental Management System
 * Admin Notifications
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Notifications';

// Initialize database
$db = new Database();

// Create notifications table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS `admin_notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT '0',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$db->execute();

// Mark notifications as read if requested
if(isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $db->query("UPDATE admin_notifications SET is_read = 1");
    $db->execute();
    
    redirect(BASE_URL . '/admin/notifications.php');
}

// Mark single notification as read
if(isset($_GET['read']) && is_numeric($_GET['read'])) {
    $notification_id = intval($_GET['read']);
    
    $db->query("UPDATE admin_notifications SET is_read = 1 WHERE id = :id");
    $db->bind(':id', $notification_id);
    $db->execute();
    
    redirect(BASE_URL . '/admin/notifications.php');
}

// Delete notification if requested
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    
    $db->query("DELETE FROM admin_notifications WHERE id = :id");
    $db->bind(':id', $notification_id);
    $db->execute();
    
    $success_msg = 'Notification deleted successfully';
}

// Get all notifications
$db->query("SELECT * FROM admin_notifications ORDER BY created_at DESC");
$notifications = $db->resultSet();

// Count unread notifications
$db->query("SELECT COUNT(*) as unread_count FROM admin_notifications WHERE is_read = 0");
$unread_count = $db->single()['unread_count'];

// Include header
include '../includes/header.php';
?>

<div class="wrapper d-flex">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content flex-grow-1">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="fa fa-bars"></i>
            </button>
            
            <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
            
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['rental_user']['name']; ?></span>
                        <img class="img-profile rounded-circle" src="<?php echo BASE_URL; ?>/assets/img/admin-avatar.png" width="32">
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/profile.php">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                            Profile
                        </a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings.php">
                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/logout.php">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    Notifications
                    <?php if($unread_count > 0): ?>
                    <span class="badge badge-danger ml-2"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                </h1>
                <div>
                    <?php if($unread_count > 0): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/notifications.php?mark_read=all" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                        <i class="fas fa-check-double fa-sm text-white-50"></i> Mark All as Read
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ml-2">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            <?php if(isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Notifications -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Notifications</h6>
                </div>
                <div class="card-body">
                    <?php if(empty($notifications)): ?>
                    <div class="text-center py-4">
                        <img src="<?php echo BASE_URL; ?>/assets/img/notification-empty.png" alt="No Notifications" class="img-fluid mb-3" style="max-width: 150px;">
                        <h5 class="text-gray-500">No notifications yet</h5>
                        <p class="text-muted">You'll receive notifications about booking requests, payments, and system updates here.</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach($notifications as $notification): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h5 class="mb-1">
                                    <?php if(!$notification['is_read']): ?>
                                    <span class="badge badge-danger mr-2">New</span>
                                    <?php endif; ?>
                                    <?php echo $notification['title']; ?>
                                </h5>
                                <small class="text-muted">
                                    <?php 
                                    $date = new DateTime($notification['created_at']);
                                    $now = new DateTime();
                                    $interval = $date->diff($now);
                                    
                                    if($interval->days == 0) {
                                        if($interval->h == 0) {
                                            if($interval->i == 0) {
                                                echo "Just now";
                                            } else {
                                                echo $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
                                            }
                                        } else {
                                            echo $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
                                        }
                                    } elseif($interval->days == 1) {
                                        echo "Yesterday";
                                    } elseif($interval->days < 7) {
                                        echo $interval->days . " days ago";
                                    } else {
                                        echo date('M d, Y', strtotime($notification['created_at']));
                                    }
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo $notification['message']; ?></p>
                            <small class="text-muted">
                                <?php echo date('F d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </small>
                            
                            <div class="mt-2">
                                <?php if(!$notification['is_read']): ?>
                                <a href="<?php echo BASE_URL; ?>/admin/notifications.php?read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-check mr-1"></i>Mark as Read
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>/admin/notifications.php?delete=<?php echo $notification['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this notification?');">
                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
        
        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; <?php echo get_setting('site_name'); ?> <?php echo date('Y'); ?></span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->
    </div>
</div>

<?php include '../includes/footer.php'; ?>
