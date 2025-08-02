<?php
session_start();
require_once '../modelo/conexion.php';

// Verify vendor is logged in
if (!isset($_SESSION['id'])) {
    header('Location: loginVendedor.php');
    exit;
}

$vendedor_id = $_SESSION['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = intval($_POST['producto_id']);
    $accion = $_POST['accion'];
    
    if ($accion === 'destacar') {
        $stmt = $conn->prepare("UPDATE productos SET destacado = 1 WHERE id = ? AND id_vendedor = ?");
        $stmt->execute([$producto_id, $vendedor_id]);
        $mensaje = "Producto marcado como destacado";
    } elseif ($accion === 'quitar') {
        $stmt = $conn->prepare("UPDATE productos SET destacado = 0 WHERE id = ? AND id_vendedor = ?");
        $stmt->execute([$producto_id, $vendedor_id]);
        $mensaje = "Producto removido de destacados";
    }
}

// Get vendor's products
$stmt = $conn->prepare(
    "SELECT id, nombre, precio, destacado, imagen_principal 
    FROM productos 
    WHERE id_vendedor = ? 
    ORDER BY nombre"
);
$stmt->execute([$vendedor_id]);
$productos = [];
while ($row = $stmt->fetch()) {
    $productos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos Destacados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestionar Productos Destacados</h2>
            <a href="inicioVendedor.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>Nota:</strong> Los productos destacados aparecerán en la página principal de la tienda. 
            Solo se muestran 6 productos destacados en total.
        </div>

        <div class="row">
            <?php foreach ($productos as $producto): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <?php if ($producto['imagen_principal']): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($producto['nombre']) ?></h5>
                            <p class="card-text">Precio: ₡<?= number_format($producto['precio'], 2) ?></p>
                            
                            <?php if ($producto['destacado']): ?>
                                <span class="badge bg-success mb-2">DESTACADO</span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                                    <input type="hidden" name="accion" value="quitar">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        Quitar de Destacados
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                                    <input type="hidden" name="accion" value="destacar">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        Marcar como Destacado
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($productos)): ?>
            <div class="text-center">
                <p>No tienes productos registrados.</p>
                <a href="agregarproducto.php" class="btn btn-primary">Agregar Primer Producto</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
