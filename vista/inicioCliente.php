<?php
session_start(); // 🔐 Iniciar sesión

// 🚫 Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

// 📋 Obtener información del cliente
require_once '../modelo/conexion.php';
$cliente_id = $_SESSION['cliente_id'];
$stmt = $conn->prepare("SELECT nombre, apellidos FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    $nombre_completo = $cliente['nombre'] . ' ' . $cliente['apellidos'];
} else {
    $nombre_completo = 'Cliente';
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Cliente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f2f2f2;
        }

        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .container {
            padding: 20px;
        }

        .opciones {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.03);
        }

        .card h3 {
            margin: 10px 0;
        }

        .card a {
            text-decoration: none;
            color: #007185;
            font-weight: bold;
        }

        .card a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Bienvenido, <?php echo htmlspecialchars($nombre_completo); ?></h1>
    </div>

    <div class="container">
        <div class="opciones">
            <div class="card">
                <h3>Ver Productos</h3>
                <p>Explora todos los productos disponibles</p>
                <a href="catalogo.php">Ver Catálogo</a>
            </div>
            <div class="card">
                <h3>Mi Carrito</h3>
                <p>Revisa los productos en tu carrito</p>
                <a href="carrito.php">Ver Carrito</a>
            </div>
            <div class="card">
                <h3>Mis Pedidos</h3>
                <p>Historial de compras realizadas</p>
                <a href="historialPedidos.php">Ver Pedidos</a>
            </div>
            <div class="card">
                <h3>Mi Perfil</h3>
                <p>Gestiona tu información personal</p>
                <a href="perfil.php">Editar Perfil</a>
            </div>
            <div class="card">
                <h3>Cerrar Sesión</h3>
                <p>Salir de tu cuenta</p>
                <a href="logout.php">Salir</a>
            </div>
        </div>
    </div>

    <!-- ✅ Script general JS -->
    <script src="../app.js"></script>
    
</body>
</html>
