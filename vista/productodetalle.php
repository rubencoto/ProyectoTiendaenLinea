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
    messageDiv.className = `message ${tipo}`;
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
        document.body.removeChild(messageDiv);
    }, 4000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<!-- Modal para eliminar producto -->
<div id="modalEliminarProducto" class="modal">
    <div class="modal-content">
        <h3>⚠️ Confirmar Eliminación</h3>
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
