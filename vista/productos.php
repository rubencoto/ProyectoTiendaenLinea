<?php
// Incluye el archivo de conexión a la base de datos
include '../modelo/conexion.php';

// Realiza la consulta para obtener los productos (id, nombre, precio e imagen principal)
$resultado = $conn->query("SELECT id, nombre, precio, imagen_principal FROM productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos del Vendedor</title>
    <style>
        /* Estilos generales de la página */
        body {
            font-family: Arial;
            background-color: #f4f4f4;
            margin: 0;
            padding: 30px;
        }

        /* Encabezado con título y botón */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h2 {
            margin: 0;
        }

        .header a button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Contenedor de la cuadrícula de productos */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        /* Tarjeta individual de producto */
        .card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Imagen del producto */
        .card img {
            max-height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
            border: 1px solid #eee;
            padding: 5px;
            background-color: #fff;
        }

        /* Nombre del producto */
        .card h3 {
            font-size: 16px;
            margin: 10px 0 5px;
        }

        /* Precio del producto */
        .card .price {
            color: #b12704;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Botón de la tarjeta */
        .card a button {
            background-color: #f0c14b;
            border: 1px solid #a88734;
            border-radius: 5px;
            padding: 8px 12px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        .card a button:hover {
            background-color: #e2b33d;
        }
    </style>
</head>
<body>

    <!-- Encabezado con título y botón para agregar producto -->
    <div class="header">
        <h2>Productos del Vendedor</h2>
        <a href="agregarProducto.php"><button>➕ Agregar producto</button></a>
    </div>

    <!-- Contenedor de la cuadrícula de productos -->
    <div class="grid-container">
        <?php while ($producto = $resultado->fetch_assoc()): ?>
            <div class="card">
                <!-- Muestra la imagen principal si existe, si no muestra un placeholder -->
                <?php if ($producto['imagen_principal']): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>" alt="Imagen del producto">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150?text=Sin+imagen" alt="Sin imagen">
                <?php endif; ?>

                <!-- Muestra el nombre del producto -->
                <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                <!-- Muestra el precio del producto -->
                <div class="price">₡<?= number_format($producto['precio'], 2) ?></div>

                <!-- Botón para ver o editar el producto -->
                <a href="productoDetalle.php?id=<?= $producto['id'] ?>">
                    <button>Ver / Editar</button>
                </a>
            </div>
        <?php endwhile; ?>
    </div>

</body>
</html>
