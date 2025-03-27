<?php
/**
 * House Rental Management System
 * Admin Users Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Admin Users Management';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Delete user
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deleting the current user
    if($user_id == $_SESSION['rental_user']['id']) {
        $error_msg = "You cannot delete your own account.";
    } else {
        // Delete the user
        $db->query("DELETE FROM users WHERE id = :id");
        $db->bind(':id', $user_id);
        
        if($db->execute()) {
            $success_msg = "User deleted successfully.";
        } else {
            $error_msg = "Failed to delete user.";
        }
    }
}

// Add or update user
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $type = isset($_POST['type']) ? (int)$_POST['type'] : 1; // Default to admin (1)
    
    // Validate input
    if(empty($name) || empty($username)) {
        $error_msg = "Name and username are required.";
    } else {
        // Check if username already exists (for new users or when changing username)
        if($user_id == 0 || (isset($_POST['original_username']) && $_POST['original_username'] != $username)) {
            $db->query("SELECT COUNT(*) as count FROM users WHERE username = :username AND id != :id");
            $db->bind(':username', $username);
            $db->bind(':id', $user_id);
            $existing = $db->single();
            
            if($existing['count'] > 0) {
                $error_msg = "A user with this username already exists.";
            }
        }
        
        if(empty($error_msg)) {
            if($user_id > 0) {
                // Update existing user
                if(!empty($password)) {
                    // Update with new password
                    $hashed_password = md5($password); // In a real system, use password_hash
                    $db->query("UPDATE users SET name = :name, username = :username, password = :password, type = :type WHERE id = :id");
                    $db->bind(':password', $hashed_password);
                } else {
                    // Update without changing password
                    $db->query("UPDATE users SET name = :name, username = :username, type = :type WHERE id = :id");
                }
                
                $db->bind(':id', $user_id);
                $db->bind(':name', $name);
                $db->bind(':username', $username);
                $db->bind(':type', $type);
                
                if($db->execute()) {
                    $success_msg = "User updated successfully.";
                    
                    // If current user was updated, update session data
                    if($user_id == $_SESSION['rental_user']['id']) {
                        $_SESSION['rental_user']['name'] = $name;
                        $_SESSION['rental_user']['username'] = $username;
                    }
                } else {
                    $error_msg = "Failed to update user.";
                }
            } else {
                // Add new user
                if(empty($password)) {
                    $error_msg = "Password is required for new users.";
                } else {
                    $hashed_password = md5($password); // In a real system, use password_hash
                    
                    $db->query("INSERT INTO users (name, username, password, type) VALUES (:name, :username, :password, :type)");
                    $db->bind(':name', $name);
                    $db->bind(':username', $username);
                    $db->bind(':password', $hashed_password);
                    $db->bind(':type', $type);
                    
                    if($db->execute()) {
                        $success_msg = "User added successfully.";
                    } else {
                        $error_msg = "Failed to add user.";
                    }
                }
            }
        }
    }
}

// Get all users
$db->query("SELECT * FROM users ORDER BY name ASC");
$users = $db->resultSet();

// Get user for editing if ID is provided
$edit_user = null;
if(isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $db->query("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', $edit_id);
    $edit_user = $db->single();
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
                    <h2 class