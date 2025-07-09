<?php
include '../modelo/conexion.php';

if (!isset($_GET['id'])) {
    echo "ID de producto no especificado.";
    exit;
}

$id = $_GET['id'];

// Eliminar producto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar'])) {
    $stmt = $conn->prepare("DELETE FROM productos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: productos.php");
    exit;
}

// Función para obtener imagen nueva
function getImagenSiSeSube($campo) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        return file_get_contents($_FILES[$campo]['tmp_name']);
    }
    return null;
}

// Actualizar producto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $precio = $_POST["precio"];
    $categoria = $_POST["categoria"];

    $nuevaPrincipal = getImagenSiSeSube("imagen_principal");
    $nuevaSec1 = getImagenSiSeSube("imagen_secundaria1");
    $nuevaSec2 = getImagenSiSeSube("imagen_secundaria2");

    $query = "UPDATE productos SET nombre=?, descripcion=?, precio=?, categoria=?";
    $params = [$nombre, $descripcion, $precio, $categoria];
    $types = "ssds";

    if ($nuevaPrincipal !== null) {
        $query .= ", imagen_principal=?";
        $params[] = $nuevaPrincipal;
        $types .= "s";
    }
    if ($nuevaSec1 !== null) {
        $query .= ", imagen_secundaria1=?";
        $params[] = $nuevaSec1;
        $types .= "s";
    }
    if ($nuevaSec2 !== null) {
        $query .= ", imagen_secundaria2=?";
        $params[] = $nuevaSec2;
        $types .= "s";
    }

    $query .= " WHERE id=?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo "<p>✅ Producto actualizado.</p>";
}

// Obtener producto
$stmt = $conn->prepare("SELECT * FROM productos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Producto</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        form {
            background: white;
            padding: 20px;
            max-width: 700px;
            margin: auto;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, textarea { width: 100%; padding: 10px; margin-top: 5px; }
        img { margin-top: 10px; max-width: 200px; border: 1px solid #ccc; }
        .acciones {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        button {
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
        }
        .guardar { background: #f0c14b; border: 1px solid #a88734; }
        .eliminar { background: #dc3545; color: white; border: none; }
        a { display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>

<h2>Editar producto</h2>

<form method="POST" enctype="multipart/form-data">
    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>

    <label>Descripción:</label>
    <textarea name="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></textarea>

    <label>Precio:</label>
    <input type="number" step="0.01" name="precio" value="<?= $producto['precio'] ?>" required>

    <label>Categoría:</label>
    <input type="text" name="categoria" value="<?= htmlspecialchars($producto['categoria']) ?>">

    <?php if ($producto['imagen_principal']): ?>
        <label>Imagen principal actual:</label>
        <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_principal']) ?>">
    <?php endif; ?>
    <label>Reemplazar imagen principal:</label>
    <input type="file" name="imagen_principal" accept="image/*">

    <?php if ($producto['imagen_secundaria1']): ?>
        <label>Imagen secundaria 1:</label>
        <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria1']) ?>">
    <?php endif; ?>
    <label>Reemplazar imagen secundaria 1:</label>
    <input type="file" name="imagen_secundaria1" accept="image/*">

    <?php if ($producto['imagen_secundaria2']): ?>
        <label>Imagen secundaria 2:</label>
        <img src="data:image/jpeg;base64,<?= base64_encode($producto['imagen_secundaria2']) ?>">
    <?php endif; ?>
    <label>Reemplazar imagen secundaria 2:</label>
    <input type="file" name="imagen_secundaria2" accept="image/*">

    <div class="acciones">
        <button type="submit" name="actualizar" class="guardar">💾 Actualizar</button>
        <button type="submit" name="eliminar" class="eliminar" onclick="return confirm('¿Eliminar este producto?')">🗑️ Eliminar</button>
    </div>
</form>

<a href="productos.php">⬅ Volver a la lista</a>

</body>
</html>
