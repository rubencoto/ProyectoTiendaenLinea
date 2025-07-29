<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
?>
