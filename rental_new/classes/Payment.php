<?php
/**
 * Payment Class
 * Handles rent payments and transaction management
 */
class Payment {
    private $db;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Submit tenant payment
     * @param array $data - Payment data
     * @return array
     */
    public function submitPayment($data) {
        $result = [
            'status' => 'error',
            'message' => '',
            'payment_id' => 0
        ];
        
        // Validate payment data
        if(empty($data['tenant_id']) || empty($data['amount']) || empty($data['payment_method'])) {
            $result['message'] = 'Missing required payment information';
            return $result;
        }
        
        // Process payment receipt if provided
        $receipt_path = null;
        
        if(isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $upload = $this->uploadReceipt($_FILES['receipt']);
            if($upload['status'] == 'success') {
                $receipt_path = $upload['path'];
            } else {
                $result['message'] = $upload['message'];
                return $result;
            }
        }
        
        // Generate invoice number
        $invoice = 'INV' . date('Ymd') . rand(1000, 9999);
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Insert payment
            $this->db->query("INSERT INTO payments (tenant_id, amount, invoice, payment_method, reference_number, notes, receipt_path, status, date_created) 
                             VALUES (:tenant_id, :amount, :invoice, :payment_method, :reference_number, :notes, :receipt_path, :status, NOW())");
            
            // Bind values
            $this->db->bind(':tenant_id', $data['tenant_id']);
            $this->db->bind(':amount', $data['amount']);
            $this->db->bind(':invoice', $invoice);
            $this->db->bind(':payment_method', $data['payment_method']);
            $this->db->bind(':reference_number', $data['reference_number'] ?? null);
            $this->db->bind(':notes', $data['notes'] ?? null);
            $this->db->bind(':receipt_path', $receipt_path);
            $this->db->bind(':status', 0); // Default to pending
            
            $this->db->execute();
            $payment_id = $this->db->lastInsertId();
            
            // Commit transaction
            $this->db->endTransaction();
            
            $result['status'] = 'success';
            $result['message'] = 'Payment submitted successfully';
            $result['payment_id'] = $payment_id;
            
            return $result;
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->cancelTransaction();
            
            $result['message'] = 'Error processing payment: ' . $e->getMessage();
            error_log("Payment processing error: " . $e->getMessage());
            
            return $result;
        }
    }
    
    /**
     * Upload payment receipt
     * @param array $file - Uploaded file data
     * @return array
     */
    private function uploadReceipt($file) {
        $result = [
            'status' => 'error',
            'message' => '',
            'path' => ''
        ];
        
        // Check file size
        if($file['size'] > MAX_UPLOAD_SIZE) {
            $result['message'] = 'File size exceeds limit of ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB';
            return $result;
        }
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($file_ext, ALLOWED_EXTENSIONS)) {
            $result['message'] = 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS);
            return $result;
        }
        
        // Generate unique filename
        $new_filename = uniqid() . '.' . $file_ext;
        $upload_path = UPLOAD_PATH . '/receipts/' . $new_filename;
        $db_path = 'assets/uploads/receipts/' . $new_filename;
        
        // Create directory if not exists
        if(!is_dir(UPLOAD_PATH . '/receipts/')) {
            mkdir(UPLOAD_PATH . '/receipts/', 0755, true);
        }
        
        // Move uploaded file
        if(move_uploaded_file($file['tmp_name'], $upload_path)) {
            $result['status'] = 'success';
            $result['path'] = $db_path;
        } else {
            $result['message'] = 'Failed to upload file';
        }
        
        return $result;
    }
    
    /**
     * Update payment status
     * @param int $id - Payment ID
     * @param int $status - Payment status (0=Pending, 1=Approved, 2=Rejected)
     * @param string $notes - Optional admin notes
     * @return boolean
     */
    public function updatePaymentStatus($id, $status, $notes = '') {
        $this->db->query("UPDATE payments SET status = :status, admin_notes = :notes, date_updated = NOW() WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':notes', $notes);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Get payments by status
     * @param int $status - Payment status (0=Pending, 1=Approved, 2=Rejected, null=All)
     * @return array
     */
    public function getPaymentsByStatus($status = null) {
        $sql = "SELECT p.*, 
                CONCAT(t.firstname, ' ', t.lastname) as tenant_name, 
                h.house_no, h.price as monthly_rent
                FROM payments p 
                LEFT JOIN tenants t ON p.tenant_id = t.id
                LEFT JOIN houses h ON t.house_id = h.id";
        
        if($status !== null) {
            $sql .= " WHERE p.status = :status";
        }
        
        $sql .= " ORDER BY p.date_created DESC";
        
        $this->db->query($sql);
        
        if($status !== null) {
            $this->db->bind(':status', $status);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get tenant payments
     * @param int $tenant_id - Tenant ID
     * @return array
     */
    public function getTenantPayments($tenant_id) {
        $this->db->query("SELECT * FROM payments WHERE tenant_id = :tenant_id ORDER BY date_created DESC");
        $this->db->bind(':tenant_id', $tenant_id);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get payment by ID
     * @param int $id - Payment ID
     * @return array
     */
    public function getPaymentById($id) {
        $this->db->query("SELECT p.*, 
                         CONCAT(t.firstname, ' ', t.lastname) as tenant_name, 
                         t.email as tenant_email, t.contact as tenant_contact,
                         h.house_no, h.price as monthly_rent
                         FROM payments p 
                         LEFT JOIN tenants t ON p.tenant_id = t.id
                         LEFT JOIN houses h ON t.house_id = h.id
                         WHERE p.id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Generate payment report
     * @param string $start_date - Start date (YYYY-MM-DD)
     * @param string $end_date - End date (YYYY-MM-DD)
     * @param int $status - Payment status (0=Pending, 1=Approved, 2=Rejected, null=All)
     * @return array
     */
    public function generatePaymentReport($start_date, $end_date, $status = null) {
        $sql = "SELECT p.*, 
                CONCAT(t.firstname, ' ', t.lastname) as tenant_name, 
                h.house_no, h.price as monthly_rent
                FROM payments p 
                LEFT JOIN tenants t ON p.tenant_id = t.id
                LEFT JOIN houses h ON t.house_id = h.id
                WHERE DATE(p.date_created) BETWEEN :start_date AND :end_date";
        
        if($status !== null) {
            $sql .= " AND p.status = :status";
        }
        
        $sql .= " ORDER BY p.date_created DESC";
        
        $this->db->query($sql);
        $this->db->bind(':start_date', $start_date);
        $this->db->bind(':end_date', $end_date);
        
        if($status !== null) {
            $this->db->bind(':status', $status);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get total payments
     * @param string $period - Period (today, this_week, this_month, this_year, all)
     * @param int $status - Payment status (0=Pending, 1=Approved, 2=Rejected, null=All)
     * @return float
     */
    public function getTotalPayments($period = 'all', $status = 1) {
        $sql = "SELECT SUM(amount) as total FROM payments WHERE status = :status";
        
        switch($period) {
            case 'today':
                $sql .= " AND DATE(date_created) = CURDATE()";
                break;
            case 'this_week':
                $sql .= " AND YEARWEEK(date_created) = YEARWEEK(CURDATE())";
                break;
            case 'this_month':
                $sql .= " AND MONTH(date_created) = MONTH(CURDATE()) AND YEAR(date_created) = YEAR(CURDATE())";
                break;
            case 'this_year':
                $sql .= " AND YEAR(date_created) = YEAR(CURDATE())";
                break;
        }
        
        $this->db->query($sql);
        $this->db->bind(':status', $status);
        
        $result = $this->db->single();
        
        return $result['total'] ?? 0;
    }
} 