<?php
/**
 * House Rental Management System
 * Tenant Forgot Password
 */

// Include initialization file
require_once '../includes/init.php';

// Page title
$page_title = 'Forgot Password';

// Check if already logged in
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] == 'tenant') {
        redirect(BASE_URL . '/tenant/dashboard.php');
    } else {
        redirect(BASE_URL . '/admin/index.php');
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $email = htmlspecialchars(trim($_POST['email']));
    
    // Validate required fields
    if(empty($email)) {
        $error_msg = 'Please enter your email address';
    } else {
        // Check if email exists
        $db->query("SELECT id, firstname, lastname, username FROM tenant_accounts WHERE email = :email");
        $db->bind(':email', $email);
        $tenant = $db->single();
        
        if($tenant) {
            // Generate reset token
            $token = md5(uniqid(rand(), true));
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Store token in database
            $db->query("UPDATE tenant_accounts SET reset_token = :token, reset_expiry = :expiry WHERE id = :id");
            $db->bind(':token', $token);
            $db->bind(':expiry', $expiry);
            $db->bind(':id', $tenant['id']);
            
            if($db->execute()) {
                // Create reset link
                $reset_link = BASE_URL . '/tenant/reset_password.php?token=' . $token;
                
                // Email content
                $to = $email;
                $subject = "Password Reset - House Rental Management System";
                $message = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Dear {$tenant['firstname']} {$tenant['lastname']},</p>
                    <p>We received a request to reset your password for the House Rental Management System. If you did not make this request, please ignore this email.</p>
                    <p>To reset your password, click on the link below:</p>
                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    <p>This link will expire in 24 hours.</p>
                    <p>If you're having trouble clicking the link, copy and paste the URL into your web browser.</p>
                    <p>Thank you,<br>House Rental Management System</p>
                </body>
                </html>
                ";
                
                // Set content-type header for sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: House Rental <' . get_setting('site_email') . '>' . "\r\n";
                
                // Send email
                if(mail($to, $subject, $message, $headers)) {
                    $success_msg = "Password reset instructions have been sent to your email address.";
                } else {
                    // If email sending fails, still show success message but log the error
                    error_log("Failed to send password reset email to: " . $email);
                    $success_msg = "Password reset instructions have been sent to your email address.";
                }
            } else {
                $error_msg = "An error occurred. Please try again later.";
            }
        } else {
            // Don't reveal that the email doesn't exist for security reasons
            $success_msg = "If your email address exists in our database, you will receive a password recovery link at your email address.";
        }
    }
}

// Include header and navbar
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Forgot Password Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h3 class="mb-0">Forgot Password</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if(isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-4">Enter your email address and we'll send you instructions to reset your password.</p>
                        
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p><a href="<?php echo BASE_URL; ?>/tenant/login.php"><i class="fas fa-arrow-left mr-1"></i>Back to Login</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
