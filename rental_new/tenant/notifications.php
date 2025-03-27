<?php
/**
 * House Rental Management System
 * Tenant Notifications
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'Notifications';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];

// Get notifications
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

// Mark notifications as read if requested
if(isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND user_type = 'tenant'");
    $db->bind(':user_id', $tenant_id);
    $db->execute();
    
    redirect(BASE_URL . '/tenant/notifications.php');
}

// Get all notifications for this tenant
$db->query("SELECT * FROM notifications 
           WHERE user_id = :user_id AND user_type = 'tenant' 
           ORDER BY created_at DESC");
$db->bind(':user_id', $tenant_id);
$notifications = $db->resultSet();

// Count unread notifications
$db->query("SELECT COUNT(*) as unread_count FROM notifications 
           WHERE user_id = :user_id AND user_type = 'tenant' AND is_read = 0");
$db->bind(':user_id', $tenant_id);
$unread_count = $db->single()['unread_count'];

// Include header and navbar
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Notifications Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            Notifications
                            <?php if($unread_count > 0): ?>
                            <span class="badge badge-light ml-2"><?php echo $unread_count; ?> unread</span>
                            <?php endif; ?>
                        </h4>
                        <div>
                            <?php if($unread_count > 0): ?>
                            <a href="<?php echo BASE_URL; ?>/tenant/notifications.php?mark_read=all" class="btn btn-light btn-sm">
                                <i class="fas fa-check-double mr-1"></i>Mark All as Read
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php" class="btn btn-light btn-sm ml-2">
                                <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">View your notifications about booking requests, payments, and system updates.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(empty($notifications)): ?>
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">No Notifications</h4>
            <p>You don't have any notifications at this time. Notifications will appear here when there are updates about your bookings, payments, or other important information.</p>
        </div>
        <?php else: ?>
        <div class="list-group shadow">
            <?php foreach($notifications as $notification): ?>
            <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h5 class="mb-1">
                        <?php if(!$notification['is_read']): ?>
                        <span class="badge badge-primary mr-2">New</span>
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
                
                <?php if(!$notification['is_read']): ?>
                <div class="mt-2">
                    <a href="<?php echo BASE_URL; ?>/tenant/mark_notification.php?id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check mr-1"></i>Mark as Read
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
