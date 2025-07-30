<?php
session_start(); // Iniciar sesión

// 🚫 Verificar si hay sesión activa del cliente
if (!isset($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

// Obtener información del cliente
require_once '../modelo/conexion.php';
$cliente_id = $_SESSION['cliente_id'];
$stmt = $conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    $nombre_completo = $cliente['nombre'] . ' ' . $cliente['apellido'];
} else {
    $nombre_completo = 'Cliente';
}

$stmt->close();
// Connection managed by singleton, no need to close explicitly
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
                <a href="index.php">Ver Catálogo</a>
            </div>
            <div class="card">
                <h3>Mi Carrito
                    <?php 
                    $cantidad_total = 0;
                    if (isset($_SESSION['carrito'])) {
                        $cantidad_total = array_sum($_SESSION['carrito']);
                    }
                    if ($cantidad_total > 0): ?>
                        <span id="cart-count-home" style="background-color: #dc3545; border-radius: 50%; padding: 2px 6px; font-size: 0.8em; margin-left: 5px; color: white;"><?= $cantidad_total ?></span>
                    <?php endif; ?>
                </h3>
                <p>Revisa los productos en tu carrito</p>
                <a href="carrito.php">Ver Carrito</a>
            </div>
            <div class="card">
                <h3>Mis Pedidos</h3>
                <p>Historial de compras realizadas</p>
                <a href="misPedidos.php">Ver Pedidos</a>
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

    <!-- Script general JS -->
    <script src="../app.js"></script>
    
</body>
</html>
