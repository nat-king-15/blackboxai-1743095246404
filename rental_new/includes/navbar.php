<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-home mr-2"></i>House Rental
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a>
                </li>
                <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'houses.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/houses.php">Houses</a>
                </li>
                <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/about.php">About Us</a>
                </li>
                <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/contact.php">Contact</a>
                </li>
            </ul>
            
            <ul class="navbar-nav ml-auto">
                <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'tenant'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle mr-1"></i>
                        <?php echo $_SESSION['rental_user']['firstname'] . ' ' . $_SESSION['rental_user']['lastname']; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/tenant/dashboard.php">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/tenant/profile.php">
                            <i class="fas fa-user-edit mr-2"></i>My Profile
                        </a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/tenant/bookings.php">
                            <i class="fas fa-bookmark mr-2"></i>My Bookings
                        </a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/tenant/payments.php">
                            <i class="fas fa-money-bill-wave mr-2"></i>Payments
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/tenant/logout.php">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </li>
                <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/admin/index.php">
                        <i class="fas fa-tachometer-alt mr-1"></i>Admin Dashboard
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/tenant/login.php">
                        <i class="fas fa-sign-in-alt mr-1"></i>Tenant Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm ml-2" href="<?php echo BASE_URL; ?>/tenant/register.php">
                        <i class="fas fa-user-plus mr-1"></i>Register
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 