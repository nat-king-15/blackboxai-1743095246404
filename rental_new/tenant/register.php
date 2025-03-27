<?php
/**
 * House Rental Management System
 * Tenant Registration
 */

// Include initialization file
require_once '../includes/init.php';

// Page title
$page_title = 'Tenant Registration';

// Check if already logged in
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] == 'tenant') {
        redirect(BASE_URL . '/tenant/dashboard.php');
    } else {
        redirect(BASE_URL . '/admin/index.php');
    }
}

// Process form submission via AJAX
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create User object
    $user = new User();
    
    // Sanitize and validate input
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $middlename = htmlspecialchars(trim($_POST['middlename'] ?? ''));
    $lastname = htmlspecialchars(trim($_POST['lastname']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $contact = htmlspecialchars(trim($_POST['contact']));
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate required fields
    if(empty($firstname) || empty($lastname) || empty($email) || empty($contact) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
        exit;
    }
    
    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    // Validate password match
    if($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        exit;
    }
    
    // Validate password strength
    if(strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long']);
        exit;
    }
    
    // Register tenant
    $result = $user->registerTenant([
        'firstname' => $firstname,
        'middlename' => $middlename,
        'lastname' => $lastname,
        'email' => $email,
        'contact' => $contact,
        'username' => $username,
        'password' => $password
    ]);
    
    // Process result
    if($result === 'success') {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Registration successful! You can now login with your credentials.',
            'redirect' => BASE_URL . '/tenant/login.php'
        ]);
    } elseif($result === 'username_exists') {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists. Please choose a different one.']);
    } elseif($result === 'email_exists') {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists. Please use a different email address.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
    
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Registration Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h2 class="mb-0">Create a Tenant Account</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> Please fill out all required fields (*) to create your account. After registration, you'll be able to browse and book available houses.
                        </div>
                        
                        <div id="alert-container"></div>
                        
                        <form id="register-form">
                            <h4 class="mb-3">Personal Information</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstname">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="middlename">Middle Name</label>
                                        <input type="text" class="form-control" id="middlename" name="middlename">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lastname">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact">Contact Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="contact" name="contact" required>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h4 class="mb-3">Account Information</h4>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="username">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="terms" name="terms" required>
                                    <label class="custom-control-label" for="terms">I agree to the <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a> <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 offset-md-3">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">
                                        <i class="fas fa-user-plus mr-2"></i>Register Account
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Already have an account? <a href="<?php echo BASE_URL; ?>/tenant/login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4>House Rental Management System - Terms of Service</h4>
                <p>Please read these terms and conditions carefully before using our service:</p>
                
                <h5>1. Account Registration</h5>
                <p>By registering an account on our platform, you agree to provide accurate and complete information. You are responsible for maintaining the confidentiality of your account credentials.</p>
                
                <h5>2. Booking Process</h5>
                <p>Submitting a booking request does not guarantee approval. All booking requests are subject to review and approval by the property administrator.</p>
                
                <h5>3. Payment Terms</h5>
                <p>Rent payments must be made on time according to the agreed schedule. Late payments may incur additional fees as specified by the property administrator.</p>
                
                <h5>4. Privacy Policy</h5>
                <p>We collect and process personal information in accordance with our Privacy Policy. By using our service, you consent to the collection and processing of your personal information.</p>
                
                <h5>5. Termination</h5>
                <p>We reserve the right to terminate or suspend your account for any violation of these terms or for any other reason at our discretion.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission
    const form = document.getElementById('register-form');
    const alertContainer = document.getElementById('alert-container');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateRegistrationForm()) return;
        
        // Collect form data
        const formData = new FormData(form);
        
        // Show loading
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
        document.body.appendChild(loadingOverlay);
        
        // Send AJAX request
        fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Remove loading overlay
            document.body.removeChild(loadingOverlay);
            
            // Display alert
            alertContainer.innerHTML = `
                <div class="alert alert-${data.status === 'success' ? 'success' : 'danger'} alert-dismissible fade show">
                    ${data.message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            
            // Scroll to alert
            alertContainer.scrollIntoView({ behavior: 'smooth' });
            
            // If successful, reset form and redirect
            if (data.status === 'success') {
                form.reset();
                
                // Redirect after delay
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                }
            }
        })
        .catch(error => {
            // Remove loading overlay
            document.body.removeChild(loadingOverlay);
            
            // Display error
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    An unexpected error occurred. Please try again.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            
            console.error('Error:', error);
        });
    });
    
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Form validation
    function validateRegistrationForm() {
        let isValid = true;
        const firstname = document.getElementById('firstname').value.trim();
        const lastname = document.getElementById('lastname').value.trim();
        const email = document.getElementById('email').value.trim();
        const contact = document.getElementById('contact').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const terms = document.getElementById('terms').checked;
        
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        
        // Required fields
        if (!firstname) {
            showError('firstname', 'First name is required');
            isValid = false;
        }
        
        if (!lastname) {
            showError('lastname', 'Last name is required');
            isValid = false;
        }
        
        if (!email) {
            showError('email', 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showError('email', 'Please enter a valid email address');
            isValid = false;
        }
        
        if (!contact) {
            showError('contact', 'Contact number is required');
            isValid = false;
        }
        
        if (!username) {
            showError('username', 'Username is required');
            isValid = false;
        }
        
        if (!password) {
            showError('password', 'Password is required');
            isValid = false;
        } else if (password.length < 6) {
            showError('password', 'Password must be at least 6 characters long');
            isValid = false;
        }
        
        if (password !== confirmPassword) {
            showError('confirm_password', 'Passwords do not match');
            isValid = false;
        }
        
        if (!terms) {
            showError('terms', 'You must agree to the Terms and Conditions');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        if (fieldId === 'terms') {
            field.parentNode.parentNode.appendChild(errorDiv);
        } else if (fieldId === 'password' || fieldId === 'confirm_password') {
            field.parentNode.parentNode.appendChild(errorDiv);
        } else {
            field.parentNode.appendChild(errorDiv);
        }
    }
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
});
</script>

<?php include '../includes/footer.php'; ?>