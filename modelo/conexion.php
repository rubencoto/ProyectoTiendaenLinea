<?php
$host = "127.0.0.1";         // También podrías usar "localhost"
$usuario = "root";
$contrasena = "";            // Asegúrate de no haber puesto ninguna accidentalmente
$base_datos = "tienda";      // Verifica que esta base exista en phpMyAdmin
$puerto = 3307;

$conn = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// echo "✅ Conexión exitosa a la base de datos.";
?>
