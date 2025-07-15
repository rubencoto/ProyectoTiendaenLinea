<?php
include '../modelo/conexion.php';

if (!isset($_GET['id'])) {
    echo "ID no especificado";
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();
$stmt->close();
$conn->close();

if (!$producto) {
    echo "Producto no encontrado";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del producto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .producto-detalle {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }

        .galeria {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .galeria img {
            width: 180px;
            height: auto;
            border: 1px solid #ccc;
            border-radius: 4px;
            object-fit: cover;
            background-color: white;
        }

        .info-producto {
            flex: 1;
            min-width: 280px;
        }

        .info-producto p {
            margin-bottom: 8px;
        }

        .btn-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body style="background-color: #f8f8f8;">
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h2 class="mb-4"><?= htmlspecialchars($producto['nombre']) ?></h2>

        <div class="producto-detalle">
            <!-- Galería -->
            <div class="galeria">
                <?php if (!empty($producto['imagen_principal'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>">
                <?php endif; ?>
                <?php if (!empty($producto['imagen_secundaria1'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria1']) ?>">
                <?php endif; ?>
                <?php if (!empty($producto['imagen_secundaria2'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria2']) ?>">
                <?php endif; ?>
            </div>

            <!-- Información -->
            <div class="info-producto">
                <p><strong>Descripción:</strong> <?= htmlspecialchars($producto['descripcion']) ?></p>
                <p><strong>Precio:</strong> ₡<?= number_format($producto['precio'], 2) ?></p>
                <p><strong>Categoría:</strong> <?= htmlspecialchars($producto['categoria']) ?></p>
                <p><strong>Tallas:</strong> <?= htmlspecialchars($producto['tallas']) ?></p>
                <p><strong>Color:</strong> <?= htmlspecialchars($producto['color']) ?></p>
                <p><strong>Unidades:</strong> <?= htmlspecialchars($producto['unidades']) ?></p>
                <p><strong>Garantía:</strong> <?= htmlspecialchars($producto['garantia']) ?></p>
                <p><strong>Dimensiones:</strong> <?= htmlspecialchars($producto['dimensiones']) ?></p>
                <p><strong>Peso:</strong> <?= number_format($producto['peso'], 2) ?> kg</p>
                <p><strong>Tamaño del empaque:</strong> <?= htmlspecialchars($producto['tamano_empaque']) ?></p>

                <div class="btn-group">
                    <a href="editarProducto.php?id=<?= $producto['id'] ?>" class="btn btn-primary">Editar</a>
                    <button class="btn btn-danger" onclick="eliminarProducto(<?= $producto['id'] ?>)">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function eliminarProducto(id) {
    if (confirm("¿Estás seguro de que deseas eliminar este producto?")) {
        fetch("eliminarProducto.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + encodeURIComponent(id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "ok") {
                alert(data.message);
                window.location.href = "productos.php";
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(() => alert("Error al conectar con el servidor"));
    }
}
</script>

<script src="../js/app.js"></script>
</body>
</html>
