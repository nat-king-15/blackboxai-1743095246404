<?php
/**
 * House Rental Management System
 * Admin Categories Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Categories Management';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Delete category
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category is in use
    $db->query("SELECT COUNT(*) as count FROM houses WHERE category_id = :category_id");
    $db->bind(':category_id', $category_id);
    $category_usage = $db->single();
    
    if($category_usage['count'] > 0) {
        $error_msg = "Cannot delete category because it is assigned to {$category_usage['count']} properties.";
    } else {
        // Delete the category
        $db->query("DELETE FROM categories WHERE id = :id");
        $db->bind(':id', $category_id);
        
        if($db->execute()) {
            $success_msg = "Category deleted successfully.";
        } else {
            $error_msg = "Failed to delete category.";
        }
    }
}

// Add or update category
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    
    // Validate input
    if(empty($category_name)) {
        $error_msg = "Category name is required.";
    } else {
        // Check if category name already exists (for new categories)
        if($category_id == 0) {
            $db->query("SELECT COUNT(*) as count FROM categories WHERE name = :name");
            $db->bind(':name', $category_name);
            $existing = $db->single();
            
            if($existing['count'] > 0) {
                $error_msg = "A category with this name already exists.";
            }
        }
        
        if(empty($error_msg)) {
            if($category_id > 0) {
                // Update existing category
                $db->query("UPDATE categories SET name = :name, description = :description, date_updated = NOW() WHERE id = :id");
                $db->bind(':id', $category_id);
                $db->bind(':name', $category_name);
                $db->bind(':description', $category_description);
                
                if($db->execute()) {
                    $success_msg = "Category updated successfully.";
                } else {
                    $error_msg = "Failed to update category.";
                }
            } else {
                // Add new category
                $db->query("INSERT INTO categories (name, description, date_created) VALUES (:name, :description, NOW())");
                $db->bind(':name', $category_name);
                $db->bind(':description', $category_description);
                
                if($db->execute()) {
                    $success_msg = "Category added successfully.";
                } else {
                    $error_msg = "Failed to add category.";
                }
            }
        }
    }
}

// Get all categories
$db->query("SELECT c.*, (SELECT COUNT(*) FROM houses WHERE category_id = c.id) as house_count FROM categories c ORDER BY c.name ASC");
$categories = $db->resultSet();

// Get category for editing if ID is provided
$edit_category = null;
if(isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $db->query("SELECT * FROM categories WHERE id = :id");
    $db->bind(':id', $edit_id);
    $edit_category = $db->single();
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
                    <h2 class="page-title">Categories Management</h2>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#categoryModal">
                        <i class="fas fa