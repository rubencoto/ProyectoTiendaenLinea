<?php
session_start();

// Add error reporting for debugging on Heroku
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modelo/conexion.php';
require_once '../modelo/config.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Check if user is logged in (optional for catalog viewing)
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

// Obtener todos los productos disponibles (excluir productos destacados)
$stmt = $conn->prepare(
    "SELECT p.id, p.nombre, p.precio, p.imagen_principal, p.descripcion, v.nombre_empresa AS vendedor_nombre 
    FROM productos p 
    JOIN vendedores v ON p.id_vendedor = v.id 
    WHERE (p.destacado = 0 OR p.destacado IS NULL) AND p.activo = 1
    ORDER BY p.id DESC"
);

$stmt->execute();
$productos = [];
while ($row = $stmt->fetch()) {
    if ($row['imagen_principal']) {
        $row['imagen_principal'] = base64_encode($row['imagen_principal']);
    }
    $productos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .login-bar {
            background-color: #37475a;
            color: white;
            padding: 10px 0;
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
        #productosContenedor {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: auto;
            padding: 0 20px;
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
        .btn-carrito:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .no-productos {
            text-align: center;
            color: #666;
            font-size: 1.1em;
            margin-top: 50px;
        }
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-options {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 5px;
            border: 1px solid #ddd;
            z-index: 1000;
            display: none;
            margin-top: 0px;
        }
        .dropdown-option {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
        }
        .dropdown-option:hover {
            background-color: #f8f9fa;
            text-decoration: none;
            color: #007185;
        }
        .dropdown-option:first-child {
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .dropdown-option:last-child {
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            border-bottom: none;
        }
        
        /* New Menu Styles */
        .menu {
            font-size: 16px;
            line-height: 1.6;
            color: #ffffff;
            width: fit-content;
            display: flex;
            list-style: none;
        }

        .menu a {
            text-decoration: none;
            color: inherit;
            font-family: inherit;
            font-size: inherit;
            line-height: inherit;
        }

        .menu .link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 12px 36px;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.48s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .menu .link::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #0a3cff;
            z-index: -1;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.48s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .menu .link svg {
            width: 14px;
            height: 14px;
            fill: #ffffff;
            transition: all 0.48s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .menu .item {
            position: relative;
        }

        .menu .item .submenu {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: absolute;
            top: 100%;
            border-radius: 0 0 16px 16px;
            left: 0;
            width: 100%;
            overflow: hidden;
            border: 1px solid #cccccc;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-12px);
            transition: all 0.48s cubic-bezier(0.23, 1, 0.32, 1);
            z-index: 1000;
            pointer-events: none;
            list-style: none;
            background: white;
        }

        .menu .item:hover .submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
            border-top: transparent;
            border-color: #0a3cff;
        }

        .menu .item:hover .link {
            color: #ffffff;
            border-radius: 16px 16px 0 0;
        }

        .menu .item:hover .link::after {
            transform: scaleX(1);
            transform-origin: right;
        }

        .menu .item:hover .link svg {
            fill: #ffffff;
            transform: rotate(-180deg);
        }

        .submenu .submenu-item {
            width: 100%;
            transition: all 0.48s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .submenu .submenu-link {
            display: block;
            padding: 12px 24px;
            width: 100%;
            position: relative;
            text-align: center;
            transition: all 0.48s cubic-bezier(0.23, 1, 0.32, 1);
            color: #333;
        }

        .submenu .submenu-item:last-child .submenu-link {
            border-bottom: none;
        }

        .submenu .submenu-link::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            transform: scaleX(0);
            width: 100%;
            height: 100%;
            background-color: #0a3cff;
            z-index: -1;
            transform-origin: left;
            transition: transform 0.48s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .submenu .submenu-link:hover:before {
            transform: scaleX(1);
            transform-origin: right;
        }

        .submenu .submenu-link:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

<script src="js/cart-utils.js"></script>

<div class="header">
    <h1>Catálogo de Productos</h1>
</div>

<div class="login-bar">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <a href="<?= AppConfig::link('index.php') ?>" class="btn btn-secondary btn-sm">← Volver al Inicio</a>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= AppConfig::link('carrito.php') ?>" class="btn btn-success btn-sm me-2">
                        Carrito
                        <span id="cart-count" class="badge bg-danger ms-1 <?= $cantidad_total > 0 ? '' : 'd-none' ?>"><?= $cantidad_total ?></span>
                    </a>
                    <div class="menu" style="display: inline-block;">
                        <div class="item">
                            <a href="#" class="link">
                                <span>Bienvenido, <?php echo htmlspecialchars($nombre_completo); ?></span>
                                <svg viewBox="0 0 360 360" xml:space="preserve">
                                    <g id="SVGRepo_iconCarrier">
                                        <path id="XMLID_225_" d="M325.607,79.393c-5.857-5.857-15.355-5.858-21.213,0.001l-139.39,139.393L25.607,79.393 c-5.857-5.857-15.355-5.858-21.213,0.001c-5.858,5.858-5.858,15.355,0,21.213l150.004,150c2.813,2.813,6.628,4.393,10.606,4.393 s7.794-1.581,10.606-4.394l149.996-150C331.465,94.749,331.465,85.251,325.607,79.393z"></path>
                                    </g>
                                </svg>
                            </a>
                            <div class="submenu">
                                <div class="submenu-item">
                                    <a href="<?= AppConfig::link('catalogo.php') ?>" class="submenu-link">Ver Catálogo</a>
                                </div>
                                <div class="submenu-item">
                                    <a href="<?= AppConfig::link('carrito.php') ?>" class="submenu-link">
                                        Mi Carrito
                                        <?php if ($cantidad_total > 0): ?>
                                            <span style="background-color: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; margin-left: 5px;"><?= $cantidad_total ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="submenu-item">
                                    <a href="<?= AppConfig::link('misPedidos.php') ?>" class="submenu-link">Mis Pedidos</a>
                                </div>
                                <div class="submenu-item">
                                    <a href="<?= AppConfig::link('perfil.php') ?>" class="submenu-link">Mi Perfil</a>
                                </div>
                                <div class="submenu-item">
                                    <a href="?logout=1" class="submenu-link" style="color: #dc3545;">Cerrar Sesión</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= AppConfig::link('loginCliente.php') ?>" class="btn btn-primary btn-sm">Clientes</a>
                    <a href="<?= AppConfig::link('loginVendedor.php') ?>" class="btn btn-outline-light btn-sm">Vendedores</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="busqueda-container">
    <input type="text" id="busqueda" placeholder="Buscar productos...">
    <select id="ordenar">
        <option value="reciente">Más recientes</option>
        <option value="asc">Nombre A-Z</option>
        <option value="desc">Nombre Z-A</option>
        <option value="precio_asc">Precio menor a mayor</option>
        <option value="precio_desc">Precio mayor a menor</option>
    </select>
    <button id="aplicarBtn">Buscar</button>
</div>

<div id="productosContenedor"></div>

<script>
const productos = <?= json_encode($productos, JSON_HEX_TAG) ?>;
const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
const baseUrl = '<?= AppConfig::link('') ?>';

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
                ${isLoggedIn ? `<button onclick="agregarAlCarrito(${p.id})" class="btn-carrito">Agregar al Carrito</button>` : `<button class="btn-carrito" disabled title="Debes iniciar sesión para agregar al carrito">Iniciar Sesión para Comprar</button>`}
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
    if (!isLoggedIn) {
        mostrarToast("Debes iniciar sesión para agregar productos al carrito", 'error');
        return;
    }
    
    const form = new FormData();
    form.append('accion', 'agregar');
    form.append('producto_id', productoId);
    
    fetch('catalogo.php', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            mostrarToast(data.mensaje);
            updateCartCount();
        } else {
            mostrarToast(data.mensaje, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast("Error al procesar la solicitud", 'error');
    });
}

function updateCartCount() {
    fetch('getCartCount.php')
    .then(response => response.json())
    .then(data => {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            if (data.count > 0) {
                cartCountElement.textContent = data.count;
                cartCountElement.classList.remove('d-none');
            } else {
                cartCountElement.classList.add('d-none');
            }
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
    });
}

function mostrarToast(msg, type = 'success') {
    const toast = document.createElement("div");
    toast.textContent = msg;
    
    const backgroundColor = type === 'success' ? '#28a745' : '#dc3545';
    
    Object.assign(toast.style, {
        position: "fixed",
        bottom: "20px",
        right: "20px",
        background: backgroundColor,
        color: "white",
        padding: "12px 20px",
        borderRadius: "6px",
        boxShadow: "0 2px 6px rgba(0,0,0,0.2)",
        zIndex: "1000"
    });
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// New menu functionality - hover handled by CSS
document.addEventListener('DOMContentLoaded', function() {
    // Menu functionality is now purely CSS-based
    // The hover effects and animations are handled by CSS transitions
    console.log('New menu loaded successfully');
});
</script>

</body>
</html>
