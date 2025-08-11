<?php
require_once 'modelo/conexion.php';
require_once 'modelo/CategoriasManager.php';

// Get database connection
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();
$categoriasManager = new CategoriasManager();

echo "<h2>Asignación Automática de Categorías</h2>\n";

// Obtener todos los productos sin categoría
$stmt = $conn->prepare("
    SELECT p.id, p.nombre, p.descripcion, p.categoria as categoria_antigua
    FROM productos p
    LEFT JOIN productos_categorias pc ON p.id = pc.id_producto
    WHERE p.activo = 1 AND pc.id_producto IS NULL
");
$stmt->execute();
$productos = $stmt->fetchAll();

echo "Productos a procesar: " . count($productos) . "\n\n";

$asignaciones = 0;

foreach ($productos as $producto) {
    $nombre = strtolower($producto['nombre']);
    $descripcion = strtolower($producto['descripcion'] ?? '');
    $categoria_antigua = strtolower($producto['categoria_antigua'] ?? '');
    $texto_completo = $nombre . ' ' . $descripcion . ' ' . $categoria_antigua;
    
    $categoria_id = null;
    
    // Reglas de asignación por palabras clave
    if (preg_match('/\b(iphone|android|telefon|movil|smartphone|tablet|laptop|computador|electroni|tecnolog|auricular|cargador|cable|usb|bluetooth|wifi|smart|digital|led|oled|cpu|gpu|ram|ssd|monitor|teclado|mouse|camara|video|audio)\b/', $texto_completo)) {
        $categoria_id = 1; // Tecnología
        $categoria_nombre = 'Tecnología';
    } 
    elseif (preg_match('/\b(zapato|calzado|sandalia|tenis|bota|chancla|tacones|deportivo|running|nike|adidas|puma|converse|vans)\b/', $texto_completo)) {
        $categoria_id = 2; // Calzado
        $categoria_nombre = 'Calzado';
    }
    elseif (preg_match('/\b(camisa|pantalon|vestido|falda|blusa|camiseta|jean|short|chaqueta|abrigo|sudadera|polo|ropa|prenda|textil|algodon|polyester|talla|color|moda)\b/', $texto_completo)) {
        $categoria_id = 3; // Ropa
        $categoria_nombre = 'Ropa';
    }
    elseif (preg_match('/\b(cocina|sala|comedor|dormitorio|cama|sofa|mesa|silla|lampara|decoracion|hogar|casa|mueble|electrodomestico|licuadora|microonda|refrigerador|lavadora|aspiradora|plancha)\b/', $texto_completo)) {
        $categoria_id = 4; // Hogar
        $categoria_nombre = 'Hogar';
    }
    elseif (preg_match('/\b(bolsa|cartera|billetera|reloj|joya|collar|pulsera|anillo|aretes|lentes|gafa|cinturon|sombrero|gorra|bufanda|guante|accesorio|complemento)\b/', $texto_completo)) {
        $categoria_id = 5; // Accesorios
        $categoria_nombre = 'Accesorios';
    }
    else {
        // Si no coincide con nada, intentar usar la categoría antigua del producto si existe
        if (!empty($producto['categoria_antigua'])) {
            $cat_antigua = strtolower($producto['categoria_antigua']);
            if (strpos($cat_antigua, 'tecnolog') !== false) $categoria_id = 1;
            elseif (strpos($cat_antigua, 'calzado') !== false) $categoria_id = 2;
            elseif (strpos($cat_antigua, 'ropa') !== false) $categoria_id = 3;
            elseif (strpos($cat_antigua, 'hogar') !== false) $categoria_id = 4;
            elseif (strpos($cat_antigua, 'accesorio') !== false) $categoria_id = 5;
            else {
                // Por defecto asignar a Accesorios si no se puede determinar
                $categoria_id = 5;
            }
            $categoria_nombre = $producto['categoria_antigua'];
        } else {
            // Por defecto asignar a Accesorios
            $categoria_id = 5;
            $categoria_nombre = 'Accesorios (por defecto)';
        }
    }
    
    // Asignar la categoría
    if ($categoria_id) {
        $resultado = $categoriasManager->asignarCategoriaAProducto($producto['id'], $categoria_id);
        if ($resultado) {
            echo "✅ Producto ID {$producto['id']} '{$producto['nombre']}' → Categoría: {$categoria_nombre}\n";
            $asignaciones++;
        } else {
            echo "❌ Error asignando categoría al producto ID {$producto['id']}\n";
        }
    }
}

echo "\n<h3>Resumen:</h3>\n";
echo "Total de asignaciones realizadas: {$asignaciones}\n";

// Verificar el resultado
echo "\n<h3>Verificación post-asignación:</h3>\n";
$categorias = [1 => 'Tecnología', 2 => 'Calzado', 3 => 'Ropa', 4 => 'Hogar', 5 => 'Accesorios'];
foreach ($categorias as $id => $nombre) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM productos p
        INNER JOIN productos_categorias pc ON p.id = pc.id_producto
        WHERE pc.id_categoria = ? AND p.activo = 1
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    echo "Categoría '{$nombre}': {$result['total']} productos\n";
}

?>
