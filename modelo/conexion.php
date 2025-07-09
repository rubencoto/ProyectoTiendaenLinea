<?php
// Definición de los parámetros de conexión a la base de datos
$host = "127.0.0.1";         
$usuario = "root";           
$contrasena = "";            
$base_datos = "tienda";     
$puerto = 3307;              

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
