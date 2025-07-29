<?php
// Simple test file to verify Heroku deployment
echo "Application is running successfully!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Test database connection
try {
    require_once '../modelo/conexion.php';
    echo "Database connection: SUCCESS<br>";
    $conn->close();
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

echo "<br><a href='index.php'>Go to Main Login Page</a>";
?>
