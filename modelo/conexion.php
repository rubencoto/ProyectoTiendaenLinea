<?php
// Unified database connection with MySQLi/PDO compatibility layer
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Unified database statement wrapper that provides MySQLi-like interface for both PDO and MySQLi
class UnifiedStatement {
    private $statement;
    private $connection;
    private $connectionType;
    private $boundParams = [];
    
    public function __construct($statement, $connection, $connectionType) {
        $this->statement = $statement;
        $this->connection = $connection;
        $this->connectionType = $connectionType;
    }
    
    public function bind_param($types, ...$params) {
        if ($this->connectionType === 'pdo') {
            // For PDO, store the parameters to use in execute()
            $this->boundParams = $params;
            return true;
        } else {
            // For MySQLi, use native bind_param
            return $this->statement->bind_param($types, ...$params);
        }
    }
    
    public function execute($params = null) {
        if ($this->connectionType === 'pdo') {
            // Use provided params or stored bound params
            $executeParams = $params ?? $this->boundParams;
            return $this->statement->execute($executeParams);
        } else {
            return $this->statement->execute();
        }
    }
    
    public function get_result() {
        if ($this->connectionType === 'pdo') {
            return new UnifiedResult($this->statement, 'pdo');
        } else {
            return new UnifiedResult($this->statement->get_result(), 'mysqli');
        }
    }
    
    public function store_result() {
        if ($this->connectionType === 'mysqli') {
            return $this->statement->store_result();
        }
        // PDO doesn't need store_result
        return true;
    }
    
    public function bind_result(...$vars) {
        if ($this->connectionType === 'mysqli') {
            return $this->statement->bind_result(...$vars);
        }
        // For PDO, this will be handled differently
        return true;
    }
    
    public function fetch() {
        if ($this->connectionType === 'pdo') {
            return $this->statement->fetch();
        } else {
            return $this->statement->fetch();
        }
    }
    
    public function fetchColumn($column = 0) {
        if ($this->connectionType === 'pdo') {
            return $this->statement->fetchColumn($column);
        } else {
            // For MySQLi, we need to fetch the row and return the specific column
            $result = $this->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row) {
                    $values = array_values($row);
                    return isset($values[$column]) ? $values[$column] : null;
                }
            }
            return null;
        }
    }
    
    public function close() {
        if ($this->connectionType === 'mysqli') {
            return $this->statement->close();
        }
        // PDO statements are automatically cleaned up
        return true;
    }
    
    public function __get($name) {
        if ($name === 'num_rows') {
            if ($this->connectionType === 'pdo') {
                return $this->statement->rowCount();
            } else {
                return $this->statement->num_rows;
            }
        }
        return $this->statement->$name ?? null;
    }
}

// Unified result wrapper
class UnifiedResult {
    private $result;
    private $connectionType;
    
    public function __construct($result, $connectionType) {
        $this->result = $result;
        $this->connectionType = $connectionType;
    }
    
    public function fetch_assoc() {
        if ($this->connectionType === 'pdo') {
            return $this->result->fetch();
        } else {
            return $this->result->fetch_assoc();
        }
    }
    
    public function __get($name) {
        if ($name === 'num_rows') {
            if ($this->connectionType === 'pdo') {
                return $this->result->rowCount();
            } else {
                return $this->result->num_rows;
            }
        }
        return $this->result->$name ?? null;
    }
}

class DatabaseConnection {
    private static $instance = null;
    private $connection = null;
    private $connectionType = 'none';
    private $lastError = '';
    
    // Database parameters
    private $host = "gtizpe105piw2gfq.cbetxkdyhwsb.us-east-1.rds.amazonaws.com";
    private $usuario = "mkmse4xl3pihhaxh";
    private $contrasena = "j0jf79neridfkfo2";
    private $base_datos = "ddpuklcmtttnqqkl";
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
        
        // If no connection established, create a mock connection
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
            $dsn = "mysql:host={$this->host};port={$this->puerto};dbname={$this->base_datos}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ];
            
            $this->connection = new PDO($dsn, $this->usuario, $this->contrasena, $options);
            $this->connectionType = 'pdo';
            return true;
        } catch (PDOException $e) {
            $this->lastError = "PDO failed: " . $e->getMessage();
            return false;
        }
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
        $this->connection = new class {
            public function prepare($sql) {
                return new class {
                    public function bind_param($types, ...$params) { return true; }
                    public function execute($params = []) { return false; }
                    public function get_result() { 
                        return new class {
                            public function fetch_assoc() { return false; }
                            public $num_rows = 0;
                        };
                    }
                    public function store_result() { return true; }
                    public function bind_result(...$vars) { return true; }
                    public function fetch() { return false; }
                    public function close() { return true; }
                    public $num_rows = 0;
                };
            }
            public function query($sql) { return false; }
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
    
    public function prepare($sql) {
        if ($this->connectionType === 'mock') {
            return $this->connection->prepare($sql);
        }
        
        $stmt = $this->connection->prepare($sql);
        return new UnifiedStatement($stmt, $this->connection, $this->connectionType);
    }
    
    public function query($sql) {
        return $this->connection ? $this->connection->query($sql) : false;
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
    
    // MySQLi compatibility properties
    public function __get($name) {
        if ($name === 'error') {
            return $this->lastError;
        } elseif ($name === 'insert_id') {
            if ($this->connectionType === 'pdo') {
                return $this->connection->lastInsertId();
            } elseif ($this->connectionType === 'mysqli') {
                return $this->connection->insert_id;
            }
        }
        return $this->connection->$name ?? null;
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
    $conn = $db; // Make $conn point to the DatabaseConnection instance for compatibility
    
    // Log connection status for debugging
    error_log("Database connection established: " . $db->getConnectionType() . 
              ($db->isConnected() ? " (connected)" : " (mock)"));
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    $db = null;
    $conn = null;
}

?>
