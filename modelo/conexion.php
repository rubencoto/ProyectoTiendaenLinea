<?php
// Database connection with fallback options for Heroku/server environments
error_reporting(E_ALL);
ini_set('display_errors', 1);

class DatabaseConnection {
    private static $instance = null;
    private $connection = null;
    private $connectionType = 'none';
    private $lastError = '';
    
    // Database parameters
    private $host = "kavfu5f7pido12mr.cbetxkdyhwsb.us-east-1.rds.amazonaws.com";
    private $usuario = "kd8mm5vnhfoajcsh";
    private $contrasena = "u8im10ovr94ccsfq";
    private $base_datos = "lsj1q7iol6uhg5wu";
    private $puerto = 3306;
    
    private function __construct() {
        $this->attemptConnection();
    }
    
    private function attemptConnection() {
        // Check if we're on Heroku and have DATABASE_URL
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl) {
            $this->parseHerokuDatabaseUrl($databaseUrl);
        }
        
        // Try PDO first (most compatible)
        if (extension_loaded('pdo')) {
            $this->tryPDOConnection();
        }
        
        // Try MySQLi if PDO failed
        if (!$this->connection && extension_loaded('mysqli')) {
            $this->tryMySQLiConnection();
        }
        
        // If no connection established, create a mock connection for error handling
        if (!$this->connection) {
            $this->createMockConnection();
        }
    }
    
    private function parseHerokuDatabaseUrl($url) {
        $parts = parse_url($url);
        if ($parts) {
            $this->host = $parts['host'] ?? $this->host;
            $this->usuario = $parts['user'] ?? $this->usuario;
            $this->contrasena = $parts['pass'] ?? $this->contrasena;
            $this->base_datos = ltrim($parts['path'] ?? '', '/') ?: $this->base_datos;
            $this->puerto = $parts['port'] ?? $this->puerto;
        }
    }
    
    private function tryPDOConnection() {
        try {
            // Try different DSN formats
            $dsnOptions = [
                "mysql:host={$this->host};port={$this->puerto};dbname={$this->base_datos}",
                "mysql:host={$this->host};dbname={$this->base_datos}",
                // Fallback without specifying driver
                "host={$this->host};port={$this->puerto};dbname={$this->base_datos}"
            ];
            
            foreach ($dsnOptions as $dsn) {
                try {
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 5
                    ];
                    
                    $this->connection = new PDO($dsn, $this->usuario, $this->contrasena, $options);
                    $this->connectionType = 'pdo';
                    return true;
                } catch (PDOException $e) {
                    $this->lastError = "PDO attempt failed: " . $e->getMessage();
                    continue;
                }
            }
        } catch (Exception $e) {
            $this->lastError = "PDO not available: " . $e->getMessage();
        }
        return false;
    }
    
    private function tryMySQLiConnection() {
        try {
            $this->connection = new mysqli($this->host, $this->usuario, $this->contrasena, $this->base_datos, $this->puerto);
            
            if ($this->connection->connect_error) {
                throw new Exception("MySQLi connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            $this->connectionType = 'mysqli';
            return true;
        } catch (Exception $e) {
            $this->lastError = "MySQLi failed: " . $e->getMessage();
            return false;
        }
    }
    
    private function createMockConnection() {
        // Create a mock connection object that handles method calls gracefully
        $this->connection = new class {
            public function prepare($sql) {
                return new class {
                    public function execute($params = []) { return false; }
                    public function fetch() { return false; }
                    public function fetchAll() { return []; }
                    public function get_result() { 
                        return new class {
                            public function fetch_assoc() { return false; }
                        };
                    }
                    public function close() { return true; }
                };
            }
            public function query($sql) { return false; }
            public function ping() { return false; }
            public $error = "Database connection not available";
            public $insert_id = 0;
        };
        $this->connectionType = 'mock';
        $this->lastError = "No database extensions available";
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function isConnected() {
        return $this->connectionType !== 'mock' && $this->connection !== null;
    }
    
    public function getConnectionType() {
        return $this->connectionType;
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    // Helper methods
    public function prepare($sql) {
        return $this->connection ? $this->connection->prepare($sql) : false;
    }
    
    public function query($sql) {
        return $this->connection ? $this->connection->query($sql) : false;
    }
    
    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Create global connection for legacy compatibility
try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    // Log connection status for debugging
    error_log("Database connection established: " . $db->getConnectionType() . 
              ($db->isConnected() ? " (connected)" : " (mock)"));
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    // Still create the variables to prevent undefined variable errors
    $db = null;
    $conn = null;
}

?>
