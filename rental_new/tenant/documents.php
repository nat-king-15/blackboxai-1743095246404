<?php
/**
 * House Rental Management System
 * Tenant Documents
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'My Documents';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant_account = $db->single();

// Create documents table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS `tenant_documents` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text,
    `file_path` varchar(255) NOT NULL,
    `file_type` varchar(50) NOT NULL,
    `file_size` int(11) NOT NULL,
    `is_verified` tinyint(1) NOT NULL DEFAULT '0',
    `admin_notes` text,
    `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `tenant_id` (`tenant_id`),
    CONSTRAINT `tenant_documents_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenant_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$db->execute();

// Process document upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])) {
    // Sanitize input
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    
    // Validate required fields
    if(empty($title)) {
        $error_msg = 'Please enter a document title';
    } elseif(!isset($_FILES['document']) || $_FILES['document']['error'] != 0) {
        $error_msg = 'Please select a valid document to upload';
    } else {
        // Get file details
        $file = $_FILES['document'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file extensions
        $allowed_ext = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
        
        // Check file extension
        if(!in_array($file_ext, $allowed_ext)) {
            $error_msg = 'Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG';
        } elseif($file_size > 5242880) { // 5MB max
            $error_msg = 'File size exceeds 5MB limit';
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = ROOT_PATH . '/uploads/tenant_documents/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $new_file_name = 'tenant_' . $tenant_id . '_' . time() . '.' . $file_ext;
            $file_destination = $upload_dir . $new_file_name;
            $db_file_path = 'uploads/tenant_documents/' . $new_file_name;
            
            // Upload file
            if(move_uploaded_file($file_tmp, $file_destination)) {
                // Insert document record
                $db->query("INSERT INTO tenant_documents (tenant_id, title, description, file_path, file_type, file_size) 
                           VALUES (:tenant_id, :title, :description, :file_path, :file_type, :file_size)");
                
                // Bind values
                $db->bind(':tenant_id', $tenant_id);
                $db->bind(':title', $title);
                $db->bind(':description', $description);
                $db->bind(':file_path', $db_file_path);
                $db->bind(':file_type', $file_ext);
                $db->bind(':file_size', $file_size);
                
                // Execute
                if($db->execute()) {
                    $success_msg = 'Document uploaded successfully';
                } else {
                    $error_msg = 'Failed to save document record';
                    // Remove uploaded file if database insert fails
                    if(file_exists($file_destination)) {
                        unlink($file_destination);
                    }
                }
            } else {
                $error_msg = 'Failed to upload document. Please try again.';
            }
        }
    }
}

// Delete document
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $document_id = intval($_GET['delete']);
    
    // Get document details
    $db->query("SELECT * FROM tenant_documents WHERE id = :id AND tenant_id = :tenant_id");
    $db->bind(':id', $document_id);
    $db->bind(':tenant_id', $tenant_id);
    $document = $db->single();
    
    if($document) {
        // Delete file
        $file_path = ROOT_PATH . '/' . $document['file_path'];
        if(file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete record
        $db->query("DELETE FROM tenant_documents WHERE id = :id");
        $db->bind(':id', $document_id);
        
        if($db->execute()) {
            $success_msg = 'Document deleted successfully';
        } else {
            $error_msg = 'Failed to delete document';
        }
    } else {
        $error_msg = 'Document not found or you do not have permission to delete it';
    }
}

// Get all documents for this tenant
$db->query("SELECT * FROM tenant_documents WHERE tenant_id = :tenant_id ORDER BY uploaded_at DESC");
$db->bind(':tenant_id', $tenant_id);
$documents = $db->resultSet();

// Include header and navbar
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Documents Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">My Documents</h4>
                        <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Upload and manage your important documents such as ID proofs, employment verification, etc.</p>
                        
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadDocumentModal">
                            <i class="fas fa-upload mr-2"></i>Upload New Document
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
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
        
        <?php if(empty($documents)): ?>
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">No Documents</h4>
            <p>You haven't uploaded any documents yet. Use the "Upload New Document" button to add your first document.</p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach($documents as $document): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-truncate" title="<?php echo $document['title']; ?>">
                            <?php echo $document['title']; ?>
                        </h5>
                        <span class="badge badge-<?php echo $document['is_verified'] ? 'success' : 'warning'; ?> px-3 py-2">
                            <?php echo $document['is_verified'] ? 'Verified' : 'Pending'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($document['description'])): ?>
                        <p class="card-text"><?php echo $document['description']; ?></p>
                        <?php endif; ?>
                        
                        <div class="document-info mb-3">
                            <p class="mb-1">
                                <strong>File Type:</strong> 
                                <span class="text-uppercase"><?php echo $document['file_type']; ?></span>
                            </p>
                            <p class="mb-1">
                                <strong>Size:</strong> 
                                <?php echo round($document['file_size'] / 1024, 2); ?> KB
                            </p>
                            <p class="mb-1">
                                <strong>Uploaded:</strong> 
                                <?php echo date('M d, Y h:i A', strtotime($document['uploaded_at'])); ?>
                            </p>
                        </div>
                        
                        <?php if($document['is_verified']): ?>
                        <div class="alert alert-success py-2 mb-3">
                            <small><i class="fas fa-check-circle mr-1"></i>This document has been verified</small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($document['admin_notes'])): ?>
                        <div class="alert alert-info py-2 mb-3">
                            <small><i class="fas fa-info-circle mr-1"></i><strong>Admin Note:</strong> <?php echo $document['admin_notes']; ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="btn-group w-100">
                            <a href="<?php echo BASE_URL . '/' . $document['file_path']; ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            <a href="<?php echo BASE_URL . '/' . $document['file_path']; ?>" class="btn btn-success" download>
                                <i class="fas fa-download mr-1"></i>Download
                            </a>
                            <a href="<?php echo BASE_URL; ?>/tenant/documents.php?delete=<?php echo $document['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this document?');">
                                <i class="fas fa-trash-alt mr-1"></i>Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" role="dialog" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="uploadDocumentModalLabel">Upload New Document</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title">Document Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <small class="text-muted">E.g., ID Proof, Employment Verification, etc.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <small class="text-muted">Optional description of the document</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="document">Select Document <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="document" name="document" required>
                            <label class="custom-file-label" for="document">Choose file</label>
                        </div>
                        <small class="text-muted">Allowed formats: PDF, DOC, DOCX, JPG, JPEG, PNG (Max 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_document" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i>Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Display filename when file is selected
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
});
</script>

<?php include '../includes/footer.php'; ?>
