<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection singleton class using PDO
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
            // PDO connection with retry logic
            $maxRetries = 3;
            $retryDelay = 1; // seconds
            
            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $dsn = "mysql:host={$this->host};port={$this->puerto};dbname={$this->base_datos};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ];
                    
                    $this->connection = new PDO($dsn, $this->usuario, $this->contrasena, $options);
                    
                    // Connection successful, break the retry loop
                    break;
                    
                } catch (PDOException $e) {
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
            
        } catch (PDOException $e) {
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
        if (!$this->connection) {
            $this->__construct();
        }
        return $this->connection;
    }
    
    // Method to create a mysqli-like interface for compatibility
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Safe close method
    public function closeConnection() {
        $this->connection = null;
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

// Create a mysqli-compatible wrapper class
class MySQLiCompat {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function prepare($sql) {
        $stmt = $this->pdo->prepare($sql);
        return new MySQLiStmtCompat($stmt);
    }
    
    public function query($sql) {
        $stmt = $this->pdo->query($sql);
        return new MySQLiResultCompat($stmt);
    }
    
    public function ping() {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function set_charset($charset) {
        // Already handled in PDO connection
        return true;
    }
    
    public function __get($name) {
        if ($name === 'error') {
            $errorInfo = $this->pdo->errorInfo();
            return $errorInfo[2] ?? '';
        }
        if ($name === 'insert_id') {
            return $this->pdo->lastInsertId();
        }
        return null;
    }
}

class MySQLiStmtCompat {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function execute() {
        return $this->stmt->execute();
    }
    
    public function get_result() {
        return new MySQLiResultCompat($this->stmt);
    }
    
    public function bind_param($types, ...$params) {
        // For PDO, we'll use named parameters or positional
        // This is a simplified version - you might need to adjust based on usage
        foreach ($params as $index => $param) {
            $this->stmt->bindValue($index + 1, $param);
        }
        return true;
    }
}

class MySQLiResultCompat {
    private $stmt;
    private $results = [];
    private $position = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        if ($stmt) {
            $this->results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    public function fetch_assoc() {
        if ($this->position < count($this->results)) {
            return $this->results[$this->position++];
        }
        return null;
    }
    
    public function num_rows() {
        return count($this->results);
    }
}

// Get the singleton connection instance
$db = DatabaseConnection::getInstance();
$pdo = $db->getConnection();

// Create mysqli-compatible connection for legacy code
$conn = new MySQLiCompat($pdo);

?>
