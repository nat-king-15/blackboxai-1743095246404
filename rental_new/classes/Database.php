<?php
/**
 * Database Connection Class
 * Uses PDO for secure database operations
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $stmt;
    private $error;
    
    /**
     * Constructor - Creates a PDO connection
     */
    public function __construct() {
        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        );
        
        // Create PDO instance
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Database Connection Error: ' . $this->error);
            die('Database Connection Failed: ' . $this->error);
        }
    }
    
    /**
     * Prepare statement with query
     * @param string $sql - SQL query
     */
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
    }
    
    /**
     * Bind values to prepared statement using named parameters
     * @param string $param - Parameter name
     * @param mixed $value - Parameter value
     * @param mixed $type - Parameter type if explicit binding is needed
     */
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
    }
    
    /**
     * Execute the prepared statement
     * @return boolean
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Query Execution Error: ' . $this->error);
            return false;
        }
    }
    
    /**
     * Get result set as array of objects
     * @return array
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    /**
     * Get single record as object
     * @return object
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Get row count
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Get last insert id
     * @return int
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * End a transaction with commit
     */
    public function endTransaction() {
        return $this->conn->commit();
    }
    
    /**
     * Cancel a transaction with rollback
     */
    public function cancelTransaction() {
        return $this->conn->rollBack();
    }
    
    /**
     * Debug - dumps the prepared statement
     */
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
} 