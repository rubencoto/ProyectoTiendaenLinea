<?php
// Database configuration for Heroku deployment
// Uses environment variables when available, falls back to default values

// Check if running on Heroku (environment variables will be available)
if (isset($_ENV['DATABASE_URL'])) {
    // Parse Heroku DATABASE_URL if available
    $url = parse_url($_ENV['DATABASE_URL']);
    $host = $url['host'];
    $usuario = $url['user'];
    $contrasena = $url['pass'];
    $base_datos = ltrim($url['path'], '/');
    $puerto = $url['port'];
} else {
    // Default configuration for local/cloud deployment
    $host = "biwezh06z1yafmlocoe7-mysql.services.clever-cloud.com";         
    $usuario = "usnfohjdasabv4el";           
    $contrasena = "vsCunVPa3JaJExZ7lIxH";            
    $base_datos = "biwezh06z1yafmlocoe7"; 
    $puerto = 3306;              
}

// Set MySQL charset to handle special characters properly
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create connection using MySQLi
    $conn = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);
    
    // Set charset to UTF-8 for proper character handling
    $conn->set_charset("utf8");
    
} catch (mysqli_sql_exception $e) {
    // Log error and show user-friendly message
    error_log("Database connection error: " . $e->getMessage());
    die("Error de conexión a la base de datos. Por favor, inténtalo más tarde.");
}

// Optional: Show connection success message for debugging (remove in production)
// echo "Conexión exitosa a la base de datos.";
?>
