<?php
<<<<<<< HEAD
// Definición de los parámetros de conexión a la base de datos
$host = "biwezh06z1yafmlocoe7-mysql.services.clever-cloud.com";         
$usuario = "usnfohjdasabv4el";           
$contrasena = "vsCunVPa3JaJExZ7lIxH";            
$base_datos = "biwezh06z1yafmlocoe7"; 
$puerto = 3306;              

// Creación de la conexión usando MySQLi
$conn = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

// Verifica si hubo un error al conectar
if ($conn->connect_error) {
    // Si hay error, muestra mensaje y detiene la ejecución
    die(" Error de conexión: " . $conn->connect_error);
}

// Si la conexión es exitosa, no se muestra ningún mensaje
// echo " Conexión exitosa a la base de datos.";
=======
$host = "127.0.0.1";
$usuario = "root";
$contrasena = "";
$base_datos = "ecommerce";  // ⚠️ Usa el nombre exacto de tu base en XAMPP

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
>>>>>>> f56b1ab (Added the function to recover password)
?>
