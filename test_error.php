<?php
// Test file to diagnose the Internal Server Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Server Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if (file_exists('modelo/conexion.php')) {
    try {
        require_once 'modelo/conexion.php';
        echo "<p style='color: green;'>✓ Connection file loaded successfully</p>";
        
        if (class_exists('DatabaseConnection')) {
            $db = DatabaseConnection::getInstance();
            $conn = $db->getConnection();
            
            if ($conn && $conn->ping()) {
                echo "<p style='color: green;'>✓ Database connection successful</p>";
                
                // Test basic query
                $result = $conn->query("SELECT 1 as test");
                if ($result) {
                    echo "<p style='color: green;'>✓ Database query test successful</p>";
                } else {
                    echo "<p style='color: red;'>✗ Database query test failed: " . $conn->error . "</p>";
                }
                
            } else {
                echo "<p style='color: red;'>✗ Database connection failed</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ DatabaseConnection class not found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Database connection error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Connection file not found</p>";
}

// Test file system
echo "<h2>File System Test</h2>";
$currentDir = __DIR__;
echo "<p>Current directory: " . $currentDir . "</p>";
echo "<p>Files in current directory:</p>";
echo "<ul>";
$files = scandir($currentDir);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . "</li>";
    }
}
echo "</ul>";

// Test session
echo "<h2>Session Test</h2>";
session_start();
$_SESSION['test'] = 'working';
echo "<p>Session test: " . ($_SESSION['test'] === 'working' ? '✓ Working' : '✗ Failed') . "</p>";

echo "<h2>Extensions Test</h2>";
$extensions = ['mysqli', 'session', 'json', 'pdo', 'pdo_mysql'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>" . $ext . ": " . ($loaded ? '✓ Loaded' : '✗ Not loaded') . "</p>";
}

// Check available PDO drivers
if (extension_loaded('pdo')) {
    echo "<h3>Available PDO Drivers:</h3>";
    echo "<ul>";
    foreach (PDO::getAvailableDrivers() as $driver) {
        echo "<li>" . $driver . "</li>";
    }
    echo "</ul>";
}

echo "<p>Test completed successfully!</p>";
?>
