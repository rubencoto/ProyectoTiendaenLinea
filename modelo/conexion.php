<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection singleton class
class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    // Database parameters
    private $host = "biwezh06z1yafmlocoe7-mysql.services.clever-cloud.com";
    private $usuario = "usnfohjdasabv4el";
    private $contrasena = "vsCunVPa3JaJExZ7lIxH";
    private $base_datos = "biwezh06z1yafmlocoe7";
    private $puerto = 3306;
    
    private function __construct() {
        try {
            // Set connection timeout and other optimizations
            ini_set('mysql.connect_timeout', 10);
            ini_set('default_socket_timeout', 10);
            
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
            
            // Set charset to avoid encoding issues
            $this->connection->set_charset("utf8");
            
            // Optimize connection settings for Clever Cloud limits
            $this->connection->query("SET SESSION wait_timeout = 300");
            $this->connection->query("SET SESSION interactive_timeout = 300");
            
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
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Close connection when script ends
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Get the singleton connection instance
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Legacy compatibility - maintain the old $conn variable for existing code
// But now it uses the singleton pattern to prevent multiple connections
?>
