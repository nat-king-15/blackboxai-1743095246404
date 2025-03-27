<!-- Admin Sidebar Navigation -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="text-white text-decoration-none">
            <h3><i class="fas fa-home mr-2"></i>Rental Admin</h3>
        </a>
    </div>
    
    <div class="sidebar-user">
        <img src="<?php echo IMG_PATH; ?>/admin-avatar.png" alt="Admin" class="img-fluid">
        <div class="user-info mt-2">
            <h5><?php echo isset($_SESSION['rental_user']['name']) ? $_SESSION['rental_user']['name'] : 'Administrator'; ?></h5>
            <span class="badge badge-primary">Administrator</span>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-header">PROPERTY MANAGEMENT</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'houses.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/houses.php">
                    <i class="fas fa-building"></i>
                    <span>Houses</span>
                </a>
            </li>
            
            <li class="nav-header">TENANT MANAGEMENT</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tenants.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/tenants.php">
                    <i class="fas fa-users"></i>
                    <span>Tenants</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'booking_requests.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/booking_requests.php">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Booking Requests</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'maintenance_requests.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/maintenance_requests.php">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </a>
            </li>
            
            <li class="nav-header">FINANCIAL</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/payments.php">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payments</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="nav-header">SYSTEM</li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/users.php">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin Users</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/notifications.php">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/admin/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>