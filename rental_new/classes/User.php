<?php
/**
 * User Class
 * Handles user authentication and management
 */
class User {
    private $db;
    private $user_data;
    private $error;
    private $is_logged_in = false;
    private $session_name = 'rental_user';
    private $user_type = ''; // 'admin', 'tenant'
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->db = new Database();
        $this->checkSession();
    }
    
    /**
     * Check if the user is logged in
     */
    private function checkSession() {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if(isset($_SESSION[$this->session_name])) {
            $this->user_data = $_SESSION[$this->session_name];
            $this->user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
            $this->is_logged_in = true;
        }
    }
    
    /**
     * Authenticate admin user
     * @param string $username - Admin username
     * @param string $password - Admin password
     * @return boolean
     */
    public function adminLogin($username, $password) {
        // Sanitize input
        $username = htmlspecialchars(strip_tags($username));
        
        // Query the database
        $this->db->query("SELECT * FROM users WHERE username = :username");
        $this->db->bind(':username', $username);
        
        $user = $this->db->single();
        
        if($user) {
            // Verify password
            $hashed_password = $user['password'];
            
            if(md5($password) === $hashed_password) {
                // Set session
                $_SESSION[$this->session_name] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'type' => $user['type']
                ];
                $_SESSION['user_type'] = 'admin';
                
                $this->user_data = $_SESSION[$this->session_name];
                $this->user_type = 'admin';
                $this->is_logged_in = true;
                
                return true;
            } else {
                $this->error = "Invalid password";
                return false;
            }
        } else {
            $this->error = "User not found";
            return false;
        }
    }
    
    /**
     * Authenticate tenant user
     * @param string $username - Tenant username
     * @param string $password - Tenant password
     * @return boolean
     */
    public function tenantLogin($username, $password) {
        // Sanitize input
        $username = htmlspecialchars(strip_tags($username));
        
        // Query the database
        $this->db->query("SELECT * FROM tenant_accounts WHERE username = :username");
        $this->db->bind(':username', $username);
        
        $user = $this->db->single();
        
        if($user) {
            // Verify password (Note: we're using MD5 here to maintain compatibility with the existing system, but in a real system we should use password_hash/password_verify)
            $hashed_password = $user['password'];
            
            if(md5($password) === $hashed_password) {
                // Set session
                $_SESSION[$this->session_name] = [
                    'id' => $user['id'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'email' => $user['email'],
                    'contact' => $user['contact'],
                    'username' => $user['username']
                ];
                $_SESSION['user_type'] = 'tenant';
                $_SESSION['login_tenant_id'] = $user['id']; // For compatibility with old code
                
                $this->user_data = $_SESSION[$this->session_name];
                $this->user_type = 'tenant';
                $this->is_logged_in = true;
                
                return true;
            } else {
                $this->error = "Invalid password";
                return false;
            }
        } else {
            $this->error = "User not found";
            return false;
        }
    }
    
    /**
     * Register new tenant
     * @param array $data - Tenant data
     * @return boolean|string
     */
    public function registerTenant($data) {
        // Check if username exists
        $this->db->query("SELECT id FROM tenant_accounts WHERE username = :username");
        $this->db->bind(':username', $data['username']);
        
        if($this->db->rowCount() > 0) {
            return "username_exists";
        }
        
        // Check if email exists
        $this->db->query("SELECT id FROM tenant_accounts WHERE email = :email");
        $this->db->bind(':email', $data['email']);
        
        if($this->db->rowCount() > 0) {
            return "email_exists";
        }
        
        // Insert new tenant
        $this->db->query("INSERT INTO tenant_accounts (firstname, middlename, lastname, email, contact, username, password) 
                         VALUES (:firstname, :middlename, :lastname, :email, :contact, :username, :password)");
        
        // Bind values
        $this->db->bind(':firstname', $data['firstname']);
        $this->db->bind(':middlename', $data['middlename'] ?? '');
        $this->db->bind(':lastname', $data['lastname']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':contact', $data['contact']);
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', md5($data['password'])); // Using MD5 for compatibility
        
        // Execute
        if($this->db->execute()) {
            return "success";
        } else {
            return "error";
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset user session
        unset($_SESSION[$this->session_name]);
        unset($_SESSION['user_type']);
        
        if($this->user_type == 'tenant') {
            unset($_SESSION['login_tenant_id']);
        }
        
        $this->is_logged_in = false;
        $this->user_data = null;
        $this->user_type = '';
        
        // Destroy session
        session_destroy();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     * @return boolean
     */
    public function isLoggedIn() {
        return $this->is_logged_in;
    }
    
    /**
     * Get user data
     * @return array
     */
    public function getUserData() {
        return $this->user_data;
    }
    
    /**
     * Get error message
     * @return string
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Get user type
     * @return string
     */
    public function getUserType() {
        return $this->user_type;
    }
} 