<?php
/**
 * House Class
 * Manages property listings and bookings
 */
class House {
    private $db;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get all available houses
     * @param int $category_id - Optional category filter
     * @return array
     */
    public function getAvailableHouses($category_id = null) {
        $sql = "SELECT h.*, c.name as category 
                FROM houses h 
                LEFT JOIN categories c ON h.category_id = c.id 
                WHERE h.status = 0";
        
        if($category_id) {
            $sql .= " AND h.category_id = :category_id";
        }
        
        $this->db->query($sql);
        
        if($category_id) {
            $this->db->bind(':category_id', $category_id);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get all houses
     * @param int $category_id - Optional category filter
     * @return array
     */
    public function getAllHouses($category_id = null) {
        $sql = "SELECT h.*, c.name as category 
                FROM houses h 
                LEFT JOIN categories c ON h.category_id = c.id";
        
        if($category_id) {
            $sql .= " WHERE h.category_id = :category_id";
        }
        
        $this->db->query($sql);
        
        if($category_id) {
            $this->db->bind(':category_id', $category_id);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get house by ID
     * @param int $id - House ID
     * @return array
     */
    public function getHouseById($id) {
        $this->db->query("SELECT h.*, c.name as category 
                         FROM houses h 
                         LEFT JOIN categories c ON h.category_id = c.id 
                         WHERE h.id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Add new house
     * @param array $data - House data
     * @return boolean
     */
    public function addHouse($data) {
        // Check if house number exists
        $this->db->query("SELECT id FROM houses WHERE house_no = :house_no");
        $this->db->bind(':house_no', $data['house_no']);
        
        if($this->db->rowCount() > 0) {
            return false;
        }
        
        // Process image upload if provided
        $image_path = 'assets/img/house-default.jpg'; // Default image
        
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload = $this->uploadImage($_FILES['image']);
            if($upload['status'] == 'success') {
                $image_path = $upload['path'];
            }
        }
        
        // Insert new house
        $this->db->query("INSERT INTO houses (house_no, category_id, description, price, status, image_path) 
                         VALUES (:house_no, :category_id, :description, :price, :status, :image_path)");
        
        // Bind values
        $this->db->bind(':house_no', $data['house_no']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':status', 0); // Default to available
        $this->db->bind(':image_path', $image_path);
        
        // Execute
        return $this->db->execute();
    }
    
    /**
     * Update house
     * @param array $data - House data
     * @return boolean
     */
    public function updateHouse($data) {
        // Check if house number exists for other houses
        $this->db->query("SELECT id FROM houses WHERE house_no = :house_no AND id != :id");
        $this->db->bind(':house_no', $data['house_no']);
        $this->db->bind(':id', $data['id']);
        
        if($this->db->rowCount() > 0) {
            return false;
        }
        
        // Process image upload if provided
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload = $this->uploadImage($_FILES['image']);
            if($upload['status'] == 'success') {
                // Update image path
                $this->db->query("UPDATE houses SET image_path = :image_path WHERE id = :id");
                $this->db->bind(':image_path', $upload['path']);
                $this->db->bind(':id', $data['id']);
                $this->db->execute();
            }
        }
        
        // Update house
        $this->db->query("UPDATE houses SET 
                         house_no = :house_no, 
                         category_id = :category_id, 
                         description = :description, 
                         price = :price, 
                         status = :status 
                         WHERE id = :id");
        
        // Bind values
        $this->db->bind(':house_no', $data['house_no']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':id', $data['id']);
        
        // Execute
        return $this->db->execute();
    }
    
    /**
     * Delete house
     * @param int $id - House ID
     * @return boolean
     */
    public function deleteHouse($id) {
        // Check if house is occupied
        $this->db->query("SELECT status FROM houses WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $house = $this->db->single();
        
        if($house['status'] == 1) {
            return false; // Cannot delete occupied house
        }
        
        // Delete house
        $this->db->query("DELETE FROM houses WHERE id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Upload house image
     * @param array $file - Uploaded file data
     * @return array
     */
    private function uploadImage($file) {
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
        $upload_path = UPLOAD_PATH . '/houses/' . $new_filename;
        $db_path = 'assets/uploads/houses/' . $new_filename;
        
        // Create directory if not exists
        if(!is_dir(UPLOAD_PATH . '/houses/')) {
            mkdir(UPLOAD_PATH . '/houses/', 0755, true);
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
     * Submit booking request
     * @param array $data - Booking data
     * @return boolean
     */
    public function submitBookingRequest($data) {
        // Check if house is available
        $this->db->query("SELECT status FROM houses WHERE id = :house_id");
        $this->db->bind(':house_id', $data['house_id']);
        
        $house = $this->db->single();
        
        if($house['status'] != 0) {
            return false; // House is not available
        }
        
        // Check if tenant already has pending requests for this house
        $this->db->query("SELECT id FROM booking_requests 
                         WHERE tenant_id = :tenant_id 
                         AND house_id = :house_id 
                         AND status = 0");
        $this->db->bind(':tenant_id', $data['tenant_id']);
        $this->db->bind(':house_id', $data['house_id']);
        
        if($this->db->rowCount() > 0) {
            return false; // Already has pending request
        }
        
        // Insert booking request
        $this->db->query("INSERT INTO booking_requests (tenant_id, house_id, move_in_date, message, status) 
                         VALUES (:tenant_id, :house_id, :move_in_date, :message, 0)");
        
        // Bind values
        $this->db->bind(':tenant_id', $data['tenant_id']);
        $this->db->bind(':house_id', $data['house_id']);
        $this->db->bind(':move_in_date', $data['move_in_date']);
        $this->db->bind(':message', $data['message']);
        
        // Execute
        return $this->db->execute();
    }
    
    /**
     * Approve booking request
     * @param int $request_id - Request ID
     * @param string $notes - Optional notes
     * @return boolean
     */
    public function approveBookingRequest($request_id, $notes = '') {
        // Get booking request details
        $this->db->query("SELECT * FROM booking_requests WHERE id = :id");
        $this->db->bind(':id', $request_id);
        
        $request = $this->db->single();
        
        if(!$request) {
            return false;
        }
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Update booking request status
            $this->db->query("UPDATE booking_requests SET status = 1, notes = :notes, date_updated = NOW() WHERE id = :id");
            $this->db->bind(':notes', $notes);
            $this->db->bind(':id', $request_id);
            $this->db->execute();
            
            // Update house status to occupied
            $this->db->query("UPDATE houses SET status = 1 WHERE id = :house_id");
            $this->db->bind(':house_id', $request['house_id']);
            $this->db->execute();
            
            // Get tenant details
            $this->db->query("SELECT * FROM tenant_accounts WHERE id = :id");
            $this->db->bind(':id', $request['tenant_id']);
            $tenant = $this->db->single();
            
            // Create tenant record
            $this->db->query("INSERT INTO tenants (firstname, middlename, lastname, email, contact, house_id, status, date_in, booking_request_id) 
                             VALUES (:firstname, :middlename, :lastname, :email, :contact, :house_id, 1, :date_in, :booking_request_id)");
            
            $this->db->bind(':firstname', $tenant['firstname']);
            $this->db->bind(':middlename', $tenant['middlename']);
            $this->db->bind(':lastname', $tenant['lastname']);
            $this->db->bind(':email', $tenant['email']);
            $this->db->bind(':contact', $tenant['contact']);
            $this->db->bind(':house_id', $request['house_id']);
            $this->db->bind(':date_in', $request['move_in_date']);
            $this->db->bind(':booking_request_id', $request_id);
            $this->db->execute();
            
            // Commit transaction
            $this->db->endTransaction();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->cancelTransaction();
            error_log("Error approving booking request: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reject booking request
     * @param int $request_id - Request ID
     * @param string $notes - Optional notes
     * @return boolean
     */
    public function rejectBookingRequest($request_id, $notes = '') {
        // Update booking request status
        $this->db->query("UPDATE booking_requests SET status = 2, notes = :notes, date_updated = NOW() WHERE id = :id");
        $this->db->bind(':notes', $notes);
        $this->db->bind(':id', $request_id);
        
        return $this->db->execute();
    }
    
    /**
     * Get booking requests by status
     * @param int $status - Request status (0=Pending, 1=Approved, 2=Rejected)
     * @return array
     */
    public function getBookingRequestsByStatus($status) {
        $this->db->query("SELECT br.*, 
                         CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, 
                         ta.email as tenant_email, 
                         ta.contact as tenant_contact,
                         h.house_no, h.description, h.price, 
                         c.name as category
                         FROM booking_requests br 
                         INNER JOIN tenant_accounts ta ON br.tenant_id = ta.id
                         INNER JOIN houses h ON br.house_id = h.id 
                         LEFT JOIN categories c ON h.category_id = c.id 
                         WHERE br.status = :status
                         ORDER BY br.date_created DESC");
        
        $this->db->bind(':status', $status);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get tenant's booking requests
     * @param int $tenant_id - Tenant ID
     * @return array
     */
    public function getTenantBookingRequests($tenant_id) {
        $this->db->query("SELECT br.*, 
                         h.house_no, h.description, h.price, h.image_path,
                         c.name as category
                         FROM booking_requests br 
                         INNER JOIN houses h ON br.house_id = h.id 
                         LEFT JOIN categories c ON h.category_id = c.id 
                         WHERE br.tenant_id = :tenant_id
                         ORDER BY br.date_created DESC");
        
        $this->db->bind(':tenant_id', $tenant_id);
        
        return $this->db->resultSet();
    }
} 