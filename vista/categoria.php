<?php
session_start();

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';
require_once '../modelo/config.php';
require_once '../modelo/CategoriasManager.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Instanciar el manejador de categorías
$categoriasManager = new CategoriasManager();

$isLoggedIn = !empty($_SESSION['cliente_id']);

// Get client information if logged in
$nombre_completo = 'Cliente';
if ($isLoggedIn) {
    $cliente_id = $_SESSION['cliente_id'];
    $stmt_cliente = $conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
    $stmt_cliente->execute([$cliente_id]);
    $cliente = $stmt_cliente->fetch();
    
    if ($cliente) {
        $nombre_completo = $cliente['nombre'] . ' ' . $cliente['apellido'];
    }
}

// Include persistent cart model if user is logged in
if ($isLoggedIn) {
    require_once '../modelo/carritoPersistente.php';
    $carritoPersistente = new CarritoPersistente();
    $cantidad_total = $carritoPersistente->contarProductos($cliente_id);
} else {
    $cantidad_total = 0;
}

// Handle cart operations (only for logged in users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    $accion = $_POST['accion'] ?? '';
    $producto_id = intval($_POST['producto_id'] ?? 0);
    
    switch ($accion) {
        case 'agregar':
            // Check product stock before adding
            $stmt_stock = $conn->prepare("SELECT stock, nombre FROM productos WHERE id = ? AND activo = 1");
            $stmt_stock->execute([$producto_id]);
            $product = $stmt_stock->fetch();
            
            if (!$product) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']);
                exit;
            }
            
            if ($product['stock'] <= 0) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Producto agotado. No hay unidades disponibles.']);
                exit;
            }
            
            $resultado = $carritoPersistente->agregarProducto($cliente_id, $producto_id, 1);
            if ($resultado) {
                echo json_encode(['status' => 'success', 'mensaje' => 'Producto agregado al carrito']);
            } else {
                echo json_encode(['status' => 'error', 'mensaje' => 'Stock insuficiente. Solo quedan ' . $product['stock'] . ' unidades disponibles.']);
            }
            exit;
    }
}

// Obtener parámetros de filtro
$categoria_id = intval($_GET['id'] ?? 0);
$busqueda = $_GET['busqueda'] ?? '';
$orden = $_GET['orden'] ?? 'reciente';

// Validar que se especificó una categoría
if (!$categoria_id) {
    header('Location: ' . AppConfig::vistaUrl('index.php'));
    exit;
}

// Obtener información de la categoría
$categoria_info = $categoriasManager->obtenerCategoriaPorId($categoria_id);
if (!$categoria_info) {
    header('Location: ' . AppConfig::vistaUrl('index.php'));
    exit;
}

// Obtener productos de la categoría
$productos = $categoriasManager->obtenerProductosPorCategoria($categoria_id, $busqueda, $orden);

// Procesar imágenes para base64
foreach ($productos as &$producto) {
    if ($producto['imagen_principal']) {
        $producto['imagen_principal'] = base64_encode($producto['imagen_principal']);
    }
}

// Obtener todas las categorías para el filtro
$categorias = $categoriasManager->obtenerCategoriasActivas();

// Función para obtener imagen representativa de una categoría
function obtenerImagenCategoria($conn, $categoria_id) {
    // Primero intentar obtener un producto destacado de la categoría
    $stmt = $conn->prepare("
        SELECT p.imagen_principal 
        FROM productos p 
        INNER JOIN productos_categorias pc ON p.id = pc.id_producto 
        WHERE pc.id_categoria = ? AND p.activo = 1 AND p.imagen_principal IS NOT NULL AND p.destacado = 1
        ORDER BY p.id DESC
        LIMIT 1
    ");
    $stmt->execute([$categoria_id]);
    $producto = $stmt->fetch();
    
    // Si no hay productos destacados, obtener cualquier producto de la misma categoría
    if (!$producto || !$producto['imagen_principal']) {
        $stmt = $conn->prepare("
            SELECT p.imagen_principal 
            FROM productos p 
            INNER JOIN productos_categorias pc ON p.id = pc.id_producto 
            WHERE pc.id_categoria = ? AND p.activo = 1 AND p.imagen_principal IS NOT NULL 
            ORDER BY p.id DESC
            LIMIT 1
        ");
        $stmt->execute([$categoria_id]);
        $producto = $stmt->fetch();
    }
    
    if ($producto && $producto['imagen_principal']) {
        return base64_encode($producto['imagen_principal']);
    }
    
    // Si no hay productos con imagen en esta categoría, retornar null
    return null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($categoria_info['nombre_categoria']) ?> - Tienda en Línea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .header {
            background: linear-gradient(135deg, #232f3e 0%, #37475a 100%);
            color: white;
            padding: 15px;
            text-align: center;
        }
        .categoria-header {
            background-color: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .categoria-titulo {
            font-size: 2rem;
            font-weight: bold;
            color: #232f3e;
            margin-bottom: 10px;
        }
        .categoria-descripcion {
            color: #666;
            font-size: 1.1rem;
        }
        .busqueda-container {
            display: flex;
            gap: 10px;
            max-width: 1200px;
            margin: 20px auto;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        #busqueda, #ordenar {
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
        .btn-volver {
            background-color: #f0c14b;
            color: #111;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-volver:hover {
            background-color: #e2b33d;
            color: #111;
            text-decoration: none;
        }
        #productosContenedor {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
            cursor: pointer;
            position: relative;
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
            font-size: 1.1rem;
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
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .card .botones {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-carrito {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            background-color: #28a745;
            color: white;
            transition: background-color 0.2s;
        }
        .btn-carrito:hover {
            background-color: #218838;
        }
        .btn-carrito:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .no-productos {
            text-align: center;
            color: #666;
            font-size: 1.1em;
            margin: 50px 0;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            grid-column: 1 / -1;
        }
        .contador-productos {
            color: #666;
            margin: 20px 0;
            text-align: center;
            font-size: 1.1rem;
        }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateX(300px);
            transition: all 0.3s ease;
        }
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast.error {
            background: #dc3545;
        }
        
        /* Estilos para recuadros de categorías */
        .categorias-section {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .categorias-titulo {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }
        
        .categorias-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .categoria-card {
            position: relative;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .categoria-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .categoria-card.active {
            border: 3px solid #007185;
            box-shadow: 0 4px 15px rgba(0, 113, 133, 0.3);
        }
        
        .categoria-imagen {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .categoria-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        
        .categoria-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 10px;
            text-align: center;
        }
        
        .categoria-nombre {
            font-weight: bold;
            font-size: 14px;
            margin: 0;
        }
        
        /* Responsive design para categorías */
        @media (max-width: 992px) {
            .categorias-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .categorias-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .categoria-card {
                height: 100px;
            }
            .categoria-nombre {
                font-size: 12px;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .busqueda-container {
                flex-direction: column;
            }
            .categoria-titulo {
                font-size: 1.5rem;
            }
            #productosContenedor {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Tienda en Línea - <?= htmlspecialchars($categoria_info['nombre_categoria']) ?></h1>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="categoria-header">
                <a href="<?= AppConfig::vistaUrl('index.php') ?>" class="btn-volver">← Volver al Inicio</a>
                <h1 class="categoria-titulo"><?= htmlspecialchars($categoria_info['nombre_categoria']) ?></h1>
                <p class="categoria-descripcion">Explora nuestra selección de productos en la categoría <?= htmlspecialchars($categoria_info['nombre_categoria']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="busqueda-container">
    <input type="text" id="busqueda" placeholder="Buscar en <?= htmlspecialchars($categoria_info['nombre_categoria']) ?>..." value="<?= htmlspecialchars($busqueda) ?>">
    <select id="ordenar">
        <option value="reciente" <?= $orden === 'reciente' ? 'selected' : '' ?>>Más recientes</option>
        <option value="asc" <?= $orden === 'asc' ? 'selected' : '' ?>>Nombre A-Z</option>
        <option value="desc" <?= $orden === 'desc' ? 'selected' : '' ?>>Nombre Z-A</option>
        <option value="precio_asc" <?= $orden === 'precio_asc' ? 'selected' : '' ?>>Precio menor a mayor</option>
        <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Precio mayor a menor</option>
    </select>
    <button id="aplicarBtn">Buscar</button>
</div>

<!-- Sección de Categorías -->
<div class="categorias-section">
    <h3 class="categorias-titulo">Otras Categorías</h3>
    <div class="categorias-grid">
        <?php foreach ($categorias as $categoria): ?>
            <div class="categoria-card <?= ($categoria['id_categoria'] == $categoria_id) ? 'active' : '' ?>" onclick="navegarACategoria(<?= $categoria['id_categoria'] ?>)">
                <?php 
                $imagen_categoria = obtenerImagenCategoria($conn, $categoria['id_categoria']);
                if ($imagen_categoria): 
                ?>
                    <img src="data:image/jpeg;base64,<?= $imagen_categoria ?>" alt="<?= htmlspecialchars($categoria['nombre_categoria']) ?>" class="categoria-imagen">
                <?php else: ?>
                    <div class="categoria-placeholder">
                        <div style="color: white; text-align: center;">
                            <div style="font-size: 24px; margin-bottom: 5px;">📂</div>
                            <div style="font-size: 10px;">Sin productos disponibles</div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="categoria-overlay">
                    <h4 class="categoria-nombre"><?= htmlspecialchars($categoria['nombre_categoria']) ?></h4>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="container-fluid">
    <div class="contador-productos">
        <strong><?= count($productos) ?></strong> producto(s) encontrado(s) en <?= htmlspecialchars($categoria_info['nombre_categoria']) ?>
    </div>
</div>

<div id="productosContenedor"></div>

<div class="toast-container" id="toastContainer"></div>

<script>
const productos = <?= json_encode($productos, JSON_HEX_TAG) ?>;
const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
const categoriaId = <?= $categoria_id ?>;

// Función para navegar a otra categoría
function navegarACategoria(nuevaCategoriaId) {
    if (nuevaCategoriaId !== categoriaId) {
        window.location.href = 'categoria.php?id=' + nuevaCategoriaId;
    }
}

function renderizarProductos(lista) {
    const contenedor = document.getElementById("productosContenedor");
    contenedor.innerHTML = "";
    
    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="no-productos">No se encontraron productos en esta categoría con los criterios de búsqueda especificados.</div>';
        return;
    }
    
    lista.forEach(p => {
        const card = document.createElement("div");
        card.className = "card";
        card.addEventListener('click', function(e) {
            // Only navigate if the click wasn't on a button
            if (!e.target.closest('.btn-carrito')) {
                window.location.href = `productoDetalleCliente.php?id=${p.id}`;
            }
        });
        
        card.innerHTML = `
            <img src="data:image/jpeg;base64,${p.imagen_principal}" alt="${p.nombre}" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5Ij5TaW4gaW1hZ2VuPC90ZXh0Pjwvc3ZnPg=='">
            <h3>${p.nombre}</h3>
            <div class="precio">₡${parseFloat(p.precio).toLocaleString()}</div>
            <div class="vendedor">Vendido por: ${p.vendedor_nombre}</div>
            <div class="descripcion">${p.descripcion || 'Sin descripción disponible'}</div>
            <div class="botones">
                ${isLoggedIn ? 
                    (p.stock > 0 ? 
                        `<button onclick="event.stopPropagation(); agregarAlCarrito(${p.id})" class="btn-carrito">Agregar al Carrito</button>` : 
                        `<button class="btn-carrito" disabled>Agotado</button>`
                    ) : 
                    `<button onclick="event.stopPropagation(); window.location.href='loginCliente.php'" class="btn-carrito">Iniciar Sesión para Comprar</button>`
                }
            </div>
        `;
        contenedor.appendChild(card);
    });
}

function aplicarFiltros() {
    const busqueda = document.getElementById("busqueda").value;
    const orden = document.getElementById("ordenar").value;
    
    // Redirigir con parámetros de búsqueda
    const url = new URL(window.location);
    url.searchParams.set('busqueda', busqueda);
    url.searchParams.set('orden', orden);
    window.location.href = url.toString();
}

document.getElementById("aplicarBtn").addEventListener("click", aplicarFiltros);
document.getElementById("busqueda").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        aplicarFiltros();
    }
});

// Load products on page load
document.addEventListener('DOMContentLoaded', function() {
    renderizarProductos(productos);
});

function agregarAlCarrito(productoId) {
    if (!isLoggedIn) {
        mostrarToast("Debes iniciar sesión para agregar productos al carrito", 'error');
        return;
    }
    
    const form = new FormData();
    form.append('accion', 'agregar');
    form.append('producto_id', productoId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            mostrarToast(data.mensaje, 'success');
            // Update cart count if available
            updateCartCount();
        } else {
            mostrarToast(data.mensaje, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al agregar producto al carrito', 'error');
    });
}

function updateCartCount() {
    // Implementation for updating cart count
    // This would need to be implemented based on your cart system
}

function mostrarToast(mensaje, tipo = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${tipo}`;
    toast.textContent = mensaje;
    
    container.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Hide and remove toast
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => container.removeChild(toast), 300);
    }, 3000);
}
</script>

</body>
</html>
