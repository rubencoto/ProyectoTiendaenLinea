<?php
session_start();

// Si no hay cliente autenticado, redirige al login
if (empty($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $producto_id = intval($_POST['producto_id'] ?? 0);
    
    switch ($accion) {
        case 'agregar':
            if (!isset($_SESSION['carrito'][$producto_id])) {
                $_SESSION['carrito'][$producto_id] = 0;
            }
            $_SESSION['carrito'][$producto_id]++;
            echo json_encode(['status' => 'success', 'mensaje' => 'Producto agregado al carrito']);
            exit;
            
        case 'actualizar':
            $cantidad = intval($_POST['cantidad'] ?? 1);
            if ($cantidad > 0) {
                $_SESSION['carrito'][$producto_id] = $cantidad;
            } else {
                unset($_SESSION['carrito'][$producto_id]);
            }
            echo json_encode(['status' => 'success', 'mensaje' => 'Cantidad actualizada']);
            exit;
            
        case 'eliminar':
            unset($_SESSION['carrito'][$producto_id]);
            echo json_encode(['status' => 'success', 'mensaje' => 'Producto eliminado del carrito']);
            exit;
            
        case 'vaciar':
            $_SESSION['carrito'] = [];
            echo json_encode(['status' => 'success', 'mensaje' => 'Carrito vaciado']);
            exit;
    }
    exit;
}

// Obtener productos del carrito
$productos_carrito = [];
$total = 0;

if (!empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $conn->prepare("
        SELECT p.id, p.nombre, p.precio, p.imagen_principal, v.nombre_empresa as vendedor_nombre 
        FROM productos p 
        JOIN vendedores v ON p.id_vendedor = v.id 
        WHERE p.id IN ($placeholders)
    ");
    
    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    while ($row = $resultado->fetch_assoc()) {
        $row['cantidad'] = $_SESSION['carrito'][$row['id']];
        $row['subtotal'] = $row['precio'] * $row['cantidad'];
        $row['imagen_principal'] = base64_encode($row['imagen_principal']);
        $productos_carrito[] = $row;
        $total += $row['subtotal'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito de Compras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
            margin: -20px -20px 20px -20px;
        }
        .volver-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .volver-btn:hover {
            background-color: #5a6268;
            color: white;
            text-decoration: none;
        }
        .carrito-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .carrito-vacio {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .carrito-vacio img {
            width: 100px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        .producto-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            gap: 20px;
        }
        .producto-item:last-child {
            border-bottom: none;
        }
        .producto-imagen {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .producto-info {
            flex: 1;
        }
        .producto-nombre {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .producto-vendedor {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        .producto-precio {
            font-weight: bold;
            color: #007185;
        }
        .cantidad-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cantidad-btn {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cantidad-btn:hover {
            background-color: #e0e0e0;
        }
        .cantidad-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px;
        }
        .eliminar-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .eliminar-btn:hover {
            background-color: #c82333;
        }
        .resumen-carrito {
            background-color: #f8f9fa;
            padding: 20px;
            margin-top: 20px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .total-final {
            font-size: 1.5em;
            font-weight: bold;
            border-top: 2px solid #007185;
            padding-top: 10px;
            color: #007185;
        }
        .acciones-carrito {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background-color: #007185;
            color: white;
        }
        .btn-primary:hover {
            background-color: #005d6b;
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Mi Carrito de Compras</h1>
</div>

<a href="index.php" class="volver-btn">← Seguir Comprando</a>

<div class="carrito-container">
    <?php if (empty($productos_carrito)): ?>
        <div class="carrito-vacio">
            <div style="font-size: 4em;"></div>
            <h3>Tu carrito está vacío</h3>
            <p>¡Agrega algunos productos para empezar!</p>
            <a href="index.php" class="btn btn-primary">Ver Catálogo</a>
        </div>
    <?php else: ?>
        <?php foreach ($productos_carrito as $producto): ?>
            <div class="producto-item">
                <img src="data:image/jpeg;base64,<?= $producto['imagen_principal'] ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="producto-imagen">
                
                <div class="producto-info">
                    <div class="producto-nombre"><?= htmlspecialchars($producto['nombre']) ?></div>
                    <div class="producto-vendedor">Vendido por: <?= htmlspecialchars($producto['vendedor_nombre']) ?></div>
                    <div class="producto-precio">₡<?= number_format($producto['precio'], 0, ',', '.') ?> c/u</div>
                </div>
                
                <div class="cantidad-controls">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                        <button type="button" class="cantidad-btn" onclick="cambiarCantidad(<?= $producto['id'] ?>, -1)">-</button>
                        <input type="number" name="cantidad" value="<?= $producto['cantidad'] ?>" min="1" class="cantidad-input" id="cantidad_<?= $producto['id'] ?>" onchange="actualizarCantidad(<?= $producto['id'] ?>)">
                        <button type="button" class="cantidad-btn" onclick="cambiarCantidad(<?= $producto['id'] ?>, 1)">+</button>
                    </form>
                </div>
                
                <div style="text-align: right; min-width: 120px;">
                    <div style="font-weight: bold; color: #007185;">₡<?= number_format($producto['subtotal'], 0, ',', '.') ?></div>
                    <form method="POST" style="display: inline; margin-top: 10px;">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                        <button type="button" class="eliminar-btn" onclick="mostrarModalEliminar(<?= $producto['id'] ?>)">Eliminar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="resumen-carrito">
            <div class="total-line">
                <span>Subtotal (<?= array_sum($_SESSION['carrito']) ?> productos):</span>
                <span>₡<?= number_format($total, 0, ',', '.') ?></span>
            </div>
            <div class="total-line">
                <span>Envío:</span>
                <span>₡<?= number_format(2500, 0, ',', '.') ?></span>
            </div>
            <div class="total-line total-final">
                <span>Total:</span>
                <span>₡<?= number_format($total + 2500, 0, ',', '.') ?></span>
            </div>
            
            <div class="acciones-carrito">
                <a href="<?= AppConfig::controladorUrl('confirmarOrden.php') ?>" class="btn btn-primary">Confirmar Orden</a>
                <a href="index.php" class="btn btn-secondary">Seguir Comprando</a>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="button" class="btn btn-danger" onclick="mostrarModalVaciar()">Vaciar Carrito</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function cambiarCantidad(productoId, cambio) {
    const input = document.getElementById('cantidad_' + productoId);
    const nuevaCantidad = parseInt(input.value) + cambio;
    if (nuevaCantidad >= 1) {
        input.value = nuevaCantidad;
        actualizarCantidad(productoId);
    }
}

function actualizarCantidad(productoId) {
    const cantidad = document.getElementById('cantidad_' + productoId).value;
    
    const form = new FormData();
    form.append('accion', 'actualizar');
    form.append('producto_id', productoId);
    form.append('cantidad', cantidad);
    
    fetch('carrito.php', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartDisplay();
            mostrarToast(data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al actualizar cantidad');
    });
}

function updateCartDisplay() {
    // Reload only the cart contents
    location.reload();
}

function eliminarProducto(productoId) {
    const form = new FormData();
    form.append('accion', 'eliminar');
    form.append('producto_id', productoId);
    
    fetch('carrito.php', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartDisplay();
            mostrarToast(data.mensaje);
            cerrarModal('modalEliminar');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al eliminar producto');
    });
}

function vaciarCarrito() {
    const form = new FormData();
    form.append('accion', 'vaciar');
    
    fetch('carrito.php', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartDisplay();
            mostrarToast(data.mensaje);
            cerrarModal('modalVaciar');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al vaciar carrito');
    });
}

// Modal functions
function mostrarModalEliminar(productoId) {
    document.getElementById('modalEliminar').style.display = 'block';
    document.getElementById('eliminarProductoId').value = productoId;
}

function mostrarModalVaciar() {
    document.getElementById('modalVaciar').style.display = 'block';
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmarEliminar() {
    const productoId = document.getElementById('eliminarProductoId').value;
    eliminarProducto(productoId);
}

function confirmarVaciar() {
    vaciarCarrito();
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

function mostrarToast(msg) {
    const toast = document.createElement("div");
    toast.textContent = msg;
    Object.assign(toast.style, {
        position: "fixed",
        bottom: "20px",
        right: "20px",
        background: "#28a745",
        color: "white",
        padding: "12px 20px",
        borderRadius: "6px",
        boxShadow: "0 2px 6px rgba(0,0,0,0.2)",
        zIndex: "1000"
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<!-- Modal para eliminar producto -->
<div id="modalEliminar" class="modal">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Está seguro de que desea eliminar este producto del carrito?</p>
        <div class="modal-buttons">
            <button class="modal-btn danger" onclick="confirmarEliminar()">Sí, Eliminar</button>
            <button class="modal-btn secondary" onclick="cerrarModal('modalEliminar')">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal para vaciar carrito -->
<div id="modalVaciar" class="modal">
    <div class="modal-content">
        <h3>Vaciar Carrito</h3>
        <p>¿Está seguro de que desea eliminar todos los productos del carrito?</p>
        <div class="modal-buttons">
            <button class="modal-btn danger" onclick="confirmarVaciar()">Sí, Vaciar Todo</button>
            <button class="modal-btn secondary" onclick="cerrarModal('modalVaciar')">Cancelar</button>
        </div>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="formEliminar" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="producto_id" id="eliminarProductoId">
</form>

<form id="formVaciar" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="vaciar">
</form>

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

</body>
</html>
