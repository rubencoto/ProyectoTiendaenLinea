<?php
session_start();

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    echo "ID no especificado";
    exit;
}

$id = intval($_GET['id']);

// Get product with vendor info
$stmt = $conn->prepare("
    SELECT p.*, v.nombre_empresa as vendedor_nombre 
    FROM productos p 
    JOIN vendedores v ON p.id_vendedor = v.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    echo "Producto no encontrado";
    exit;
}

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion === 'actualizar_stock') {
        $nuevo_stock = intval($_POST['nuevo_stock']);
        
        if ($nuevo_stock >= 0) {
            $stmt_update = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
            if ($stmt_update->execute([$nuevo_stock, $id])) {
                $mensaje = "Unidades actualizadas correctamente";
                $tipo_mensaje = "success";
                // Refresh product data
                $stmt = $conn->prepare("
                    SELECT p.*, v.nombre_empresa as vendedor_nombre 
                    FROM productos p 
                    JOIN vendedores v ON p.id_vendedor = v.id 
                    WHERE p.id = ?
                ");
                $stmt->execute([$id]);
                $producto = $stmt->fetch();
            } else {
                $mensaje = "Error al actualizar las unidades";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Las unidades deben ser mayor o igual a 0";
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del producto - <?= htmlspecialchars($producto['nombre']) ?></title>
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

        .stock-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 2px solid #007185;
        }

        .stock-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .stock-input {
            min-width: 120px;
        }

        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stock-indicator {
            font-size: 1.2em;
            font-weight: bold;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .stock-ok {
            background-color: #d4edda;
            color: #155724;
        }

        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-out {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body style="background-color: #f8f8f8;">
<div class="container mt-5">
    <?php if (isset($mensaje)): ?>
        <div class="mensaje <?= $tipo_mensaje ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <div class="card p-4 shadow">
        <h2 class="mb-4"><?= htmlspecialchars($producto['nombre']) ?></h2>
        <p class="text-muted">Vendedor: <?= htmlspecialchars($producto['vendedor_nombre']) ?></p>

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
                <?php if ($producto['tallas']): ?>
                <p><strong>Tallas:</strong> <?= htmlspecialchars($producto['tallas']) ?></p>
                <?php endif; ?>
                <?php if ($producto['color']): ?>
                <p><strong>Color:</strong> <?= htmlspecialchars($producto['color']) ?></p>
                <?php endif; ?>
                
                <!-- Stock Status Indicator -->
                <div class="stock-indicator <?= $producto['stock'] <= 0 ? 'stock-out' : ($producto['stock'] <= 5 ? 'stock-low' : 'stock-ok') ?>">
                    Unidades Actuales: <?= $producto['stock'] ?> unidades
                    <?php if ($producto['stock'] <= 0): ?>
                        | AGOTADO
                    <?php elseif ($producto['stock'] <= 5): ?>
                        | STOCK BAJO
                    <?php else: ?>
                        | EN STOCK
                    <?php endif; ?>
                </div>
                
                <p><strong>Unidades Disponibles:</strong> <?= htmlspecialchars($producto['stock']) ?> unidades</p>
                <?php if ($producto['garantia']): ?>
                <p><strong>Garantía:</strong> <?= htmlspecialchars($producto['garantia']) ?></p>
                <?php endif; ?>
                <?php if ($producto['dimensiones']): ?>
                <p><strong>Dimensiones:</strong> <?= htmlspecialchars($producto['dimensiones']) ?></p>
                <?php endif; ?>
                <?php if ($producto['peso']): ?>
                <p><strong>Peso:</strong> <?= number_format($producto['peso'], 2) ?> kg</p>
                <?php endif; ?>
                <?php if ($producto['tamano_empaque']): ?>
                <p><strong>Tamaño del empaque:</strong> <?= htmlspecialchars($producto['tamano_empaque']) ?></p>
                <?php endif; ?>

                <div class="btn-group">
                    <a href="editarProducto.php?id=<?= $producto['id'] ?>" class="btn btn-primary">Editar Producto</a>
                    <button class="btn btn-danger" onclick="eliminarProducto(<?= $producto['id'] ?>)">Eliminar</button>
                    <a href="productos.php" class="btn btn-secondary">Volver a Productos</a>
                </div>
            </div>
        </div>

        <!-- Stock Management Section -->
        <div class="stock-section">
            <h4>Gestión de Inventario</h4>
            <p class="text-muted">Actualiza la cantidad de unidades disponibles para la venta.</p>
            
            <form method="POST" class="stock-form">
                <input type="hidden" name="accion" value="actualizar_stock">
                
                <div class="stock-input">
                    <label for="nuevo_stock" class="form-label">Unidades Disponibles</label>
                    <input type="number" class="form-control" id="nuevo_stock" name="nuevo_stock" 
                           value="<?= $producto['stock'] ?>" min="0" required>
                    <small class="form-text text-muted">Cantidad disponible para clientes</small>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-success">Actualizar Stock</button>
                </div>
            </form>
            
            <div class="mt-3">
                <small class="text-info">
                    <strong>Tip:</strong> Este valor se usa para mostrar disponibilidad a los clientes y controlar las ventas.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-hide messages after 5 seconds
setTimeout(function() {
    const mensaje = document.querySelector('.mensaje');
    if (mensaje) {
        mensaje.style.transition = 'opacity 0.5s';
        mensaje.style.opacity = '0';
        setTimeout(() => mensaje.remove(), 500);
    }
}, 5000);

function eliminarProducto(id) {
    document.getElementById('modalEliminarProducto').style.display = 'block';
    document.getElementById('productoIdEliminar').value = id;
}

function confirmarEliminacionProducto() {
    const id = document.getElementById('productoIdEliminar').value;
    document.getElementById('modalEliminarProducto').style.display = 'none';
    
    fetch("eliminarProducto.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "ok") {
            mostrarMensaje(data.message, 'success');
            setTimeout(() => {
                window.location.href = "productos.php";
            }, 2000);
        } else {
            mostrarMensaje("Error: " + data.message, 'error');
        }
    })
    .catch(() => mostrarMensaje("Error al conectar con el servidor", 'error'));
}

function cerrarModalProducto(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function mostrarMensaje(mensaje, tipo) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `mensaje ${tipo}`;
    messageDiv.textContent = mensaje;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2000;
        padding: 15px 30px;
        border-radius: 5px;
        font-weight: bold;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        ${tipo === 'success' ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'}
    `;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        if (document.body.contains(messageDiv)) {
            document.body.removeChild(messageDiv);
        }
    }, 4000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Confirm stock update before submitting
document.querySelector('.stock-form').addEventListener('submit', function(e) {
    const nuevoStock = document.getElementById('nuevo_stock').value;
    
    if (!confirm(`¿Confirmas actualizar el stock a ${nuevoStock} unidades?`)) {
        e.preventDefault();
    }
});
</script>

<!-- Modal para eliminar producto -->
<div id="modalEliminarProducto" class="modal">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Está seguro de que desea eliminar este producto permanentemente?</p>
        <div class="modal-buttons">
            <button class="modal-btn danger" onclick="confirmarEliminacionProducto()">Sí, Eliminar</button>
            <button class="modal-btn secondary" onclick="cerrarModalProducto('modalEliminarProducto')">Cancelar</button>
        </div>
    </div>
</div>

<input type="hidden" id="productoIdEliminar">

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 30px;
    border: none;
    border-radius: 10px;
    width: 80%;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal h3 {
    color: #dc3545;
    margin-bottom: 20px;
    font-size: 24px;
}

.modal p {
    margin-bottom: 30px;
    font-size: 16px;
    color: #333;
}

.modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.modal-btn {
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

.modal-btn.danger {
    background-color: #dc3545;
    color: white;
}

.modal-btn.danger:hover {
    background-color: #c82333;
}

.modal-btn.secondary {
    background-color: #6c757d;
    color: white;
}

.modal-btn.secondary:hover {
    background-color: #5a6268;
}
</style>

<script src="../js/app.js"></script>
</body>
</html>
