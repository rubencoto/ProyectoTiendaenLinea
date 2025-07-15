<?php
require_once '../controlador/ProductoControlador.php';

if (!isset($_GET['id'])) {
    echo "<p>Error: ID no especificado</p>";
    exit;
}

$id = $_GET['id'];
$productoControlador = new ProductoControlador();
$producto = $productoControlador->obtenerProductoPorId($id);

if (!$producto) {
    echo "<p>Error: Producto no encontrado</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .preview-img {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
            border: 1px solid #ccc;
            padding: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Editar Producto</h2>
    <form action="../controlador/actualizarProducto.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($producto['id']) ?>">

        <div class="mb-3">
            <label>Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control" required><?= htmlspecialchars($producto['descripcion']) ?></textarea>
        </div>

        <div class="mb-3">
            <label>Precio</label>
            <input type="number" step="0.01" name="precio" class="form-control" value="<?= htmlspecialchars($producto['precio']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Categoría</label>
            <input type="text" name="categoria" class="form-control" value="<?= htmlspecialchars($producto['categoria']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Imagen principal actual</label><br>
            <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>" class="preview-img">
            <input type="file" name="imagen_principal" class="form-control">
        </div>

        <div class="mb-3">
            <label>Imagen secundaria 1 actual</label><br>
            <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria1']) ?>" class="preview-img">
            <input type="file" name="imagen_secundaria1" class="form-control">
        </div>

        <div class="mb-3">
            <label>Imagen secundaria 2 actual</label><br>
            <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria2']) ?>" class="preview-img">
            <input type="file" name="imagen_secundaria2" class="form-control">
        </div>

        <div class="mb-3">
            <label>Tallas</label>
            <input type="text" name="tallas" class="form-control" value="<?= htmlspecialchars($producto['tallas']) ?>">
        </div>

        <div class="mb-3">
            <label>Color</label>
            <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($producto['color']) ?>">
        </div>

        <div class="mb-3">
            <label>Unidades</label>
            <input type="number" name="unidades" class="form-control" value="<?= htmlspecialchars($producto['unidades']) ?>">
        </div>

        <div class="mb-3">
            <label>Garantía</label>
            <input type="text" name="garantia" class="form-control" value="<?= htmlspecialchars($producto['garantia']) ?>">
        </div>

        <div class="mb-3">
            <label>Dimensiones</label>
            <input type="text" name="dimensiones" class="form-control" value="<?= htmlspecialchars($producto['dimensiones']) ?>">
        </div>

        <div class="mb-3">
            <label>Peso (kg)</label>
            <input type="number" step="0.01" name="peso" class="form-control" value="<?= htmlspecialchars($producto['peso']) ?>">
        </div>

        <div class="mb-3">
            <label>Tamaño del empaque</label>
            <input type="text" name="tamano_empaque" class="form-control" value="<?= htmlspecialchars($producto['tamano_empaque']) ?>">
        </div>

        <button type="submit" class="btn btn-success">Actualizar</button>
        <a href="inicioVendedor.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script src="../js/app.js"></script>
</body>
</html>
