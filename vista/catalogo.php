<?php
session_start();

// Si no hay cliente autenticado, redirige al login
if (empty($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/conexion.php';

// 🔍 Búsqueda AJAX
if (isset($_GET['busqueda_ajax'])) {
    header('Content-Type: application/json');
    
    $termino = $_GET['q'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    $orden = $_GET['orden'] ?? 'reciente';
    
    $sql = "SELECT p.id, p.nombre, p.precio, p.imagen_principal, p.descripcion, v.nombre_empresa AS vendedor_nombre 
            FROM productos p 
            JOIN vendedores v ON p.id_vendedor = v.id 
            WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($termino) {
        $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR v.nombre_empresa LIKE ?)";
        $termino_like = "%$termino%";
        $params = array_merge($params, [$termino_like, $termino_like, $termino_like]);
        $types .= 'sss';
    }
    
    if ($categoria) {
        $sql .= " AND p.categoria = ?";
        $params[] = $categoria;
        $types .= 's';
    }
    
    // Ordenamiento
    switch($orden) {
        case 'precio_asc':
            $sql .= " ORDER BY p.precio ASC";
            break;
        case 'precio_desc':
            $sql .= " ORDER BY p.precio DESC";
            break;
        case 'nombre_asc':
            $sql .= " ORDER BY p.nombre ASC";
            break;
        case 'nombre_desc':
            $sql .= " ORDER BY p.nombre DESC";
            break;
        default:
            $sql .= " ORDER BY p.id DESC";
    }
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $productos = [];
    while ($row = $resultado->fetch_assoc()) {
        $row['imagen_principal'] = base64_encode($row['imagen_principal']);
        $productos[] = $row;
    }
    
    echo json_encode($productos);
    $stmt->close();
    $conn->close();
    exit;
}

// Obtener todos los productos disponibles (comportamiento normal)
$stmt = $conn->prepare(
    "SELECT p.id, p.nombre, p.precio, p.imagen_principal, p.descripcion, v.nombre_empresa AS vendedor_nombre 
    FROM productos p 
    JOIN vendedores v ON p.id_vendedor = v.id 
    ORDER BY p.id DESC"
);

$stmt->execute();
$resultado = $stmt->get_result();

$productos = [];
while ($row = $resultado->fetch_assoc()) {
    $row['imagen_principal'] = base64_encode($row['imagen_principal']);
    $productos[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Productos</title>
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
        .busqueda-container {
            display: flex;
            gap: 10px;
            max-width: 1200px;
            margin: 0 auto 20px auto;
            flex-wrap: wrap;
        }
        #busqueda, #ordenar, #categoria {
            flex: 1;
            min-width: 200px;
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
        #aplicarBtn:hover {
            background-color: #005d6b;
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
        #productosContenedor {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: auto;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .card img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .card h3 {
            margin: 10px 0;
            color: #333;
        }
        .card .precio {
            font-size: 1.2em;
            font-weight: bold;
            color: #007185;
            margin: 10px 0;
        }
        .card .vendedor {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }
        .card .descripcion {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 15px;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card .botones {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .card a, .card button {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-detalle {
            background-color: #f0c14b;
            color: #111;
        }
        .btn-detalle:hover {
            background-color: #e2b33d;
            color: #111;
            text-decoration: none;
        }
        .btn-carrito {
            background-color: #28a745;
            color: white;
        }
        .btn-carrito:hover {
            background-color: #218838;
        }
        .no-productos {
            text-align: center;
            color: #666;
            font-size: 1.1em;
            margin-top: 50px;
        }
        
        /* 🔄 Animación de carga */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .busqueda-container {
            display: flex;
            gap: 10px;
            max-width: 1000px;
            margin: 0 auto 20px auto;
            flex-wrap: wrap;
        }
        
        .busqueda-container input,
        .busqueda-container select {
            flex: 1;
            min-width: 150px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Catálogo de Productos</h1>
</div>

<a href="inicioCliente.php" class="volver-btn">← Volver al Inicio</a>

<div style="text-align: right; margin-bottom: 10px;">
    <a href="carrito.php" style="background-color: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold;">
        🛒 Ver Carrito
        <?php 
        $cantidad_total = 0;
        if (isset($_SESSION['carrito'])) {
            $cantidad_total = array_sum($_SESSION['carrito']);
        }
        if ($cantidad_total > 0): ?>
            <span style="background-color: #dc3545; border-radius: 50%; padding: 2px 6px; font-size: 0.8em; margin-left: 5px;"><?= $cantidad_total ?></span>
        <?php endif; ?>
    </a>
</div>

<div class="busqueda-container">
    <input type="text" id="busqueda" placeholder="Buscar productos...">
    <select id="categoria">
        <option value="">Todas las categorías</option>
        <option value="Electrónicos">Electrónicos</option>
        <option value="Ropa">Ropa</option>
        <option value="Hogar">Hogar</option>
        <option value="Deportes">Deportes</option>
    </select>
    <select id="ordenar">
        <option value="reciente">Más recientes</option>
        <option value="nombre_asc">Nombre A-Z</option>
        <option value="nombre_desc">Nombre Z-A</option>
        <option value="precio_asc">Precio menor a mayor</option>
        <option value="precio_desc">Precio mayor a menor</option>
    </select>
    <button id="aplicarBtn">Buscar</button>
</div>

<div id="productosContenedor"></div>

<script>
const productos = <?= json_encode($productos, JSON_HEX_TAG) ?>;
let busquedaTimeout;

// 🚀 Búsqueda en tiempo real con debounce
document.getElementById('busqueda').addEventListener('input', function() {
    clearTimeout(busquedaTimeout);
    busquedaTimeout = setTimeout(() => {
        buscarProductosAjax();
    }, 500); // Esperar 500ms después de que el usuario deje de escribir
});

document.getElementById('categoria').addEventListener('change', buscarProductosAjax);
document.getElementById('ordenar').addEventListener('change', buscarProductosAjax);

// 🔍 Función de búsqueda AJAX
async function buscarProductosAjax() {
    const busqueda = document.getElementById('busqueda').value;
    const categoria = document.getElementById('categoria').value;
    const orden = document.getElementById('ordenar').value;
    
    // Mostrar indicador de carga
    document.getElementById('productosContenedor').innerHTML = 
        '<div style="text-align: center; padding: 50px;"><div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #007185; border-radius: 50%; animation: spin 1s linear infinite;"></div> Buscando...</div>';
    
    try {
        const params = new URLSearchParams({
            busqueda_ajax: '1',
            q: busqueda,
            categoria: categoria,
            orden: orden
        });
        
        const response = await fetch(`catalogo.php?${params}`);
        const productosEncontrados = await response.json();
        
        renderizarProductos(productosEncontrados);
        
    } catch (error) {
        console.error('Error en búsqueda:', error);
        document.getElementById('productosContenedor').innerHTML = 
            '<div style="text-align: center; color: red;">Error al buscar productos</div>';
    }
}

function renderizarProductos(lista) {
    const contenedor = document.getElementById("productosContenedor");
    contenedor.innerHTML = "";
    
    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="no-productos">No se encontraron productos</div>';
        return;
    }
    
    lista.forEach(p => {
        const card = document.createElement("div");
        card.className = "card";
        card.innerHTML = `
            <img src="data:image/jpeg;base64,${p.imagen_principal}" alt="${p.nombre}">
            <h3>${p.nombre}</h3>
            <div class="precio">₡${parseFloat(p.precio).toLocaleString()}</div>
            <div class="vendedor">Vendido por: ${p.vendedor_nombre}</div>
            <div class="descripcion">${p.descripcion || 'Sin descripción disponible'}</div>
            <div class="botones">
                <a href="productoDetalleCliente.php?id=${p.id}" class="btn-detalle">Ver Detalle</a>
                <button onclick="agregarAlCarrito(${p.id})" class="btn-carrito">🛒 Agregar</button>
            </div>
        `;
        contenedor.appendChild(card);
    });
}

function aplicarCambios() {
    const busqueda = document.getElementById("busqueda").value.toLowerCase();
    const orden = document.getElementById("ordenar").value;
    
    let filtrados = productos.filter(p =>
        p.nombre.toLowerCase().includes(busqueda) ||
        (p.descripcion && p.descripcion.toLowerCase().includes(busqueda)) ||
        p.vendedor_nombre.toLowerCase().includes(busqueda)
    );
    
    filtrados.sort((a, b) => {
        switch(orden) {
            case "asc":
                return a.nombre.toLowerCase().localeCompare(b.nombre.toLowerCase());
            case "desc":
                return b.nombre.toLowerCase().localeCompare(a.nombre.toLowerCase());
            case "precio_asc":
                return parseFloat(a.precio) - parseFloat(b.precio);
            case "precio_desc":
                return parseFloat(b.precio) - parseFloat(a.precio);
            case "reciente":
            default:
                return 0; // Mantener orden original (más recientes primero)
        }
    });
    
    renderizarProductos(filtrados);
}

document.getElementById("aplicarBtn").addEventListener("click", aplicarCambios);
document.getElementById("busqueda").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        aplicarCambios();
    }
});

// Cargar productos al inicio
renderizarProductos(productos);

function agregarAlCarrito(productoId) {
    const form = new FormData();
    form.append('accion', 'agregar');
    form.append('producto_id', productoId);
    
    fetch('carrito.php', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            mostrarToast("🛒 " + data.mensaje);
        } else {
            mostrarToast("❌ Error al agregar al carrito");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast("❌ Error al agregar al carrito");
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
        padding: "12px 20px",
        borderRadius: "6px",
        boxShadow: "0 2px 6px rgba(0,0,0,0.2)",
        zIndex: "1000"
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

</body>
</html>
