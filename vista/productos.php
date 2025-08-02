<?php
session_start();
require_once '../modelo/config.php';

// Si no hay vendedor autenticado, redirige al login
if (empty($_SESSION['id'])) {
    header('Location: ' . AppConfig::vistaUrl('loginVendedor.php'));
    exit;
}

require_once '../modelo/conexion.php';

// Prepara y ejecuta la consulta filtrando por id_vendedor
$idV = $_SESSION['id'];
$stmt = $conn->prepare(
    "SELECT id, nombre, precio, imagen_principal 
     FROM productos 
     WHERE id_vendedor = ?"
);
$stmt->bind_param("i", $idV);
$stmt->execute();
$resultado = $stmt->get_result();

$productos = [];
while ($row = $resultado->fetch_assoc()) {
    $row['imagen_principal'] = base64_encode($row['imagen_principal']);
    $productos[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Productos</title>
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
        .busqueda-container {
            display: flex;
            gap: 10px;
            max-width: 1000px;
            margin: 0 auto 20px auto;
        }
        #busqueda, #ordenar {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        #aplicarBtn {
            padding: 10px 20px;
            background-color: #007185;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #productosContenedor {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: auto;
        }
        .card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }
        .card img {
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .card h3 {
            margin: 10px 0;
        }
        .card a {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #f0c14b;
            color: #111;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        .card a:hover {
            background-color: #e2b33d;
        }
        .card button {
            margin-top: 10px;
            padding: 8px;
            background-color: #dc3545;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        .card button:hover {
            background-color: #c82333;
        }
        .desaparecer {
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.4s ease;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Gestión de Mis Productos</h1>
</div>

<a href="inicioVendedor.php" class="volver-btn">← Volver al Panel</a>

<div class="busqueda-container">
    <input type="text" id="busqueda" placeholder="Buscar por nombre...">
    <select id="ordenar">
        <option value="asc">Orden A-Z</option>
        <option value="desc">Orden Z-A</option>
    </select>
    <button id="aplicarBtn">Aplicar cambios</button>
</div>

<div id="productosContenedor"></div>

<script>
const productos = <?= json_encode($productos, JSON_HEX_TAG) ?>;

function renderizarProductos(lista) {
    const contenedor = document.getElementById("productosContenedor");
    contenedor.innerHTML = "";
    lista.forEach(p => {
        const card = document.createElement("div");
        card.className = "card";
        card.innerHTML = `
            <img src="data:image/jpeg;base64,${p.imagen_principal}">
            <h3>${p.nombre}</h3>
            <p><strong>₡${parseFloat(p.precio).toLocaleString()}</strong></p>
            <a href="productodetalle.php?id=${p.id}">Ver Detalle</a>
            <button onclick="eliminarProducto(${p.id}, this)">Eliminar</button>
        `;
        contenedor.appendChild(card);
    });
}

function aplicarCambios() {
    const busqueda = document.getElementById("busqueda").value.toLowerCase();
    const orden = document.getElementById("ordenar").value;
    let filtrados = productos.filter(p =>
        p.nombre.toLowerCase().includes(busqueda)
    );
    filtrados.sort((a, b) => {
        const nombreA = a.nombre.toLowerCase();
        const nombreB = b.nombre.toLowerCase();
        return orden === "asc"
            ? nombreA.localeCompare(nombreB)
            : nombreB.localeCompare(nombreA);
    });
    renderizarProductos(filtrados);
}

document.getElementById("aplicarBtn").addEventListener("click", aplicarCambios);
renderizarProductos(productos);

function eliminarProducto(id, boton) {
    // Store the button and id for later use
    window.eliminarData = { id: id, boton: boton };
    document.getElementById('modalEliminarProductoLista').style.display = 'block';
}

function confirmarEliminacionLista() {
    const { id, boton } = window.eliminarData;
    document.getElementById('modalEliminarProductoLista').style.display = 'none';
    
    boton.disabled = true;
    boton.textContent = "Eliminando...";
    fetch("eliminarProducto.php", {
        method: "POST",
        headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === "ok") {
            const card = boton.closest(".card");
            card.classList.add("desaparecer");
            setTimeout(() => card.remove(), 500);
            mostrarToast("Producto eliminado.");
        } else {
            mostrarToast("Error al eliminar.");
            boton.disabled = false;
            boton.textContent = "Eliminar";
        }
    });
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
        padding: "10px 20px",
        borderRadius: "6px",
        boxShadow: "0 2px 6px rgba(0,0,0,0.2)"
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function cerrarModalLista(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<!-- Modal para eliminar producto de la lista -->
<div id="modalEliminarProductoLista" class="modal">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Está seguro de que desea eliminar este producto permanentemente de su inventario?</p>
        <div class="modal-buttons">
            <button class="modal-btn danger" onclick="confirmarEliminacionLista()">Sí, Eliminar</button>
            <button class="modal-btn secondary" onclick="cerrarModalLista('modalEliminarProductoLista')">Cancelar</button>
        </div>
    </div>
</div>

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
