<?php
/**
 * House Rental Management System
 * Admin Document Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Document Management';

// Initialize database
$db = new Database();

// Create tenant_documents table if it doesn't exist
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

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Process document verification
if(isset($_POST['verify_document']) && isset($_POST['document_id'])) {
    $document_id = intval($_POST['document_id']);
    $admin_notes = htmlspecialchars(trim($_POST['admin_notes']));
    
    $db->query("UPDATE tenant_documents SET is_verified = 1, admin_notes = :admin_notes WHERE id = :id");
    $db->bind(':admin_notes', $admin_notes);
    $db->bind(':id', $document_id);
    
    if($db->execute()) {
        $success_msg = 'Document verified successfully';
        
        // Get document details for notification
        $db->query("SELECT td.*, ta.firstname, ta.lastname, ta.id as account_id 
                   FROM tenant_documents td 
                   INNER JOIN tenant_accounts ta ON td.tenant_id = ta.id 
                   WHERE td.id = :id");
        $db->bind(':id', $document_id);
        $document = $db->single();
        
        if($document) {
            // Create notification for tenant
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
            
            $db->query("INSERT INTO notifications (user_id, user_type, title, message) 
                       VALUES (:user_id, 'tenant', :title, :message)");
            $db->bind(':user_id', $document['account_id']);
            $db->bind(':title', 'Document Verified');
            $db->bind(':message', 'Your document "' . $document['title'] . '" has been verified by the administrator.');
            $db->execute();
        }
    } else {
        $error_msg = 'Failed to verify document';
    }
}

// Process document rejection
if(isset($_POST['reject_document']) && isset($_POST['document_id'])) {
    $document_id = intval($_POST['document_id']);
    $admin_notes = htmlspecialchars(trim($_POST['admin_notes']));
    
    $db->query("UPDATE tenant_documents SET is_verified = 0, admin_notes = :admin_notes WHERE id = :id");
    $db->bind(':admin_notes', $admin_notes);
    $db->bind(':id', $document_id);
    
    if($db->execute()) {
        $success_msg = 'Document rejected successfully';
        
        // Get document details for notification
        $db->query("SELECT td.*, ta.firstname, ta.lastname, ta.id as account_id 
                   FROM tenant_documents td 
                   INNER JOIN tenant_accounts ta ON td.tenant_id = ta.id 
                   WHERE td.id = :id");
        $db->bind(':id', $document_id);
        $document = $db->single();
        
        if($document) {
            // Create notification for tenant
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
            
            $db->query("INSERT INTO notifications (user_id, user_type, title, message) 
                       VALUES (:user_id, 'tenant', :title, :message)");
            $db->bind(':user_id', $document['account_id']);
            $db->bind(':title', 'Document Needs Attention');
            $db->bind(':message', 'Your document "' . $document['title'] . '" requires attention. Please check the admin notes and resubmit if necessary.');
            $db->execute();
        }
    } else {
        $error_msg = 'Failed to reject document';
    }
}

// Delete document if requested
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $document_id = intval($_GET['delete']);
    
    // Get document details
    $db->query("SELECT * FROM tenant_documents WHERE id = :id");
    $db->bind(':id', $document_id);
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
        $error_msg = 'Document not found';
    }
}

// Get tenant filter if provided
$tenant_filter = isset($_GET['tenant']) ? intval($_GET['tenant']) : 0;

// Get all tenants for the filter dropdown
$db->query("SELECT ta.id, CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name 
           FROM tenant_accounts ta 
           ORDER BY ta.firstname, ta.lastname");
$tenants = $db->resultSet();

// Get all documents
if($tenant_filter > 0) {
    $db->query("SELECT td.*, CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name 
               FROM tenant_documents td 
               INNER JOIN tenant_accounts ta ON td.tenant_id = ta.id 
               WHERE td.tenant_id = :tenant_id
               ORDER BY td.uploaded_at DESC");
    $db->bind(':tenant_id', $tenant_filter);
} else {
    $db->query("SELECT td.*, CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name 
               FROM tenant_documents td 
               INNER JOIN tenant_accounts ta ON td.tenant_id = ta.id 
               ORDER BY td.uploaded_at DESC");
}
$documents = $db->resultSet();

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
            
            <h1 class="h3 mb-0 text-gray-800">Document Management</h1>
            
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
                <h1 class="h3 mb-0 text-gray-800">Tenant Documents</h1>
                <a href="<?php echo BASE_URL; ?>/admin/index.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                </a>
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
            
            <!-- Filter Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Documents</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="tenant" class="mr-2">Tenant:</label>
                            <select class="form-control" id="tenant" name="tenant">
                                <option value="0">All Tenants</option>
                                <?php foreach($tenants as $tenant): ?>
                                <option value="<?php echo $tenant['id']; ?>" <?php echo ($tenant_filter == $tenant['id']) ? 'selected' : ''; ?>>
                                    <?php echo $tenant['tenant_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        <?php if($tenant_filter > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/documents.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-times mr-1"></i> Clear Filter
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Documents Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $tenant_filter > 0 ? 'Documents for Selected Tenant' : 'All Tenant Documents'; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if(empty($documents)): ?>
                    <div class="text-center py-4">
                        <img src="<?php echo BASE_URL; ?>/assets/img/document-empty.png" alt="No Documents" class="img-fluid mb-3" style="max-width: 150px;">
                        <h5 class="text-gray-500">No documents found</h5>
                        <p class="text-muted">
                            <?php echo $tenant_filter > 0 ? 'This tenant has not uploaded any documents yet.' : 'No tenants have uploaded any documents yet.'; ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="documentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tenant</th>
                                    <th>Document Title</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($documents as $document): ?>
                                <tr>
                                    <td><?php echo $document['id']; ?></td>
                                    <td><?php echo $document['tenant_name']; ?></td>
                                    <td><?php echo $document['title']; ?></td>
                                    <td>
                                        <span class="badge badge-info text-uppercase">
                                            <?php echo $document['file_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo round($document['file_size'] / 1024, 2); ?> KB</td>
                                    <td>
                                        <?php if($document['is_verified']): ?>
                                        <span class="badge badge-success">Verified</span>
                                        <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($document['uploaded_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo BASE_URL . '/' . $document['file_path']; ?>" class="btn btn-sm btn-info" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#verifyModal<?php echo $document['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>/admin/documents.php?delete=<?php echo $document['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                        
                                        <!-- Verify Modal -->
                                        <div class="modal fade" id="verifyModal<?php echo $document['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="verifyModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="verifyModalLabel<?php echo $document['id']; ?>">
                                                            <?php echo $document['is_verified'] ? 'Update Document Verification' : 'Verify Document'; ?>
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                                            <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                                                            
                                                            <div class="form-group">
                                                                <label for="admin_notes<?php echo $document['id']; ?>">Admin Notes:</label>
                                                                <textarea class="form-control" id="admin_notes<?php echo $document['id']; ?>" name="admin_notes" rows="3"><?php echo $document['admin_notes']; ?></textarea>
                                                                <small class="form-text text-muted">Add any notes or comments about this document.</small>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label>Document Information:</label>
                                                                <ul class="list-group">
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        Title
                                                                        <span><?php echo $document['title']; ?></span>
                                                                    </li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        Tenant
                                                                        <span><?php echo $document['tenant_name']; ?></span>
                                                                    </li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        Uploaded
                                                                        <span><?php echo date('M d, Y h:i A', strtotime($document['uploaded_at'])); ?></span>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <div class="d-flex justify-content-between">
                                                                    <button type="submit" name="verify_document" class="btn btn-success">
                                                                        <i class="fas fa-check mr-1"></i> Verify Document
                                                                    </button>
                                                                    <button type="submit" name="reject_document" class="btn btn-warning">
                                                                        <i class="fas fa-times mr-1"></i> Reject Document
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#documentsTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "lengthMenu": "Show _MENU_ documents per page",
            "zeroRecords": "No documents found",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No documents available",
            "infoFiltered": "(filtered from _MAX_ total documents)"
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
