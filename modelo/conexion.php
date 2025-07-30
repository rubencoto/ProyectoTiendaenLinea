<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection singleton class
class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    // Database parameters - AWS RDS MySQL
    private $host = "kavfu5f7pido12mr.cbetxkdyhwsb.us-east-1.rds.amazonaws.com";
    private $usuario = "kd8mm5vnhfoajcsh";
    private $contrasena = "u8im10ovr94ccsfq";
    private $base_datos = "lsj1q7iol6uhg5wu";
    private $puerto = 3306;
    
    private function __construct() {
        try {
            // Set connection timeout and other optimizations
            ini_set('mysql.connect_timeout', 5);
            ini_set('default_socket_timeout', 5);
            
            // Add retry logic for connection limits
            $maxRetries = 3;
            $retryDelay = 1; // seconds
            
            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $this->connection = new mysqli(
                        $this->host, 
                        $this->usuario, 
                        $this->contrasena, 
                        $this->base_datos, 
                        $this->puerto
                    );
                    
                    if ($this->connection->connect_error) {
                        throw new Exception("Connection failed: " . $this->connection->connect_error);
                    }
                    
                    // Connection successful, break the retry loop
                    break;
                    
                } catch (Exception $e) {
                    if ($i === $maxRetries - 1) {
                        // Last attempt failed
                        throw $e;
                    }
                    
                    // Check if it's a connection limit error
                    if (strpos($e->getMessage(), 'max_user_connections') !== false) {
                        // Wait before retrying
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                    } else {
                        // Non-connection-limit error, don't retry
                        throw $e;
                    }
                }
            }
            
            // Set charset to avoid encoding issues
            $this->connection->set_charset("utf8");
            
            // Optimize connection settings for AWS RDS
            $this->connection->query("SET SESSION wait_timeout = 28800");
            $this->connection->query("SET SESSION interactive_timeout = 28800");
            $this->connection->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Simply return the connection - no need to ping in modern PHP
        // The singleton pattern ensures we maintain one connection per script
        if (!$this->connection) {
            $this->__construct();
        }
        return $this->connection;
    }
    
    // Safe close method that can be called multiple times
    public function closeConnection() {
        if ($this->connection && $this->connection instanceof mysqli) {
            try {
                if ($this->connection->thread_id) {
                    $this->connection->close();
                }
            } catch (Error $e) {
                // Connection already closed, ignore
            }
            $this->connection = null;
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Close connection when script ends
    public function __destruct() {
        $this->closeConnection();
    }
}

// Get the singleton connection instance
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Legacy compatibility - maintain the old $conn variable for existing code
// But now it uses the singleton pattern to prevent multiple connections
?>
