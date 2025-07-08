<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto - Vendedor</title>
    <style>
        body {
            background-color: #f2f2f2;
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 0;
        }

        .form-container {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 30px 40px;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #111;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #f0c14b;
            border: 1px solid #a88734;
            color: #111;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #e2b33d;
        }

        .note {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Agregar producto</h2>
        <form action="../controlador/procesarProducto.php" method="POST" enctype="multipart/form-data">
            <label>Nombre del producto</label>
            <input type="text" name="nombre" required>

            <label>Descripción</label>
            <textarea name="descripcion" required></textarea>

            <label>Precio (₡)</label>
            <input type="number" step="0.01" name="precio" required>

            <label>Categoría</label>
            <input type="text" name="categoria" required>

            <label>Imagen principal</label>
            <input type="file" name="imagen_principal" accept="image/*" required>

            <label>Imágenes secundarias (máximo 2)</label>
            <input type="file" name="imagen_secundaria1" accept="image/*">
            <input type="file" name="imagen_secundaria2" accept="image/*">

            <label>Tallas disponibles (ej: S,M,L,XL)</label>
            <input type="text" name="tallas">

            <label>Color</label>
            <input type="text" name="color">

            <label>Unidades en inventario</label>
            <input type="number" name="unidades">

            <label>Garantía</label>
            <input type="text" name="garantia">

            <label>Dimensiones (Largo x Ancho x Alto)</label>
            <input type="text" name="dimensiones">

            <label>Peso del producto (kg)</label>
            <input type="number" step="0.01" name="peso">

            <label>Tamaño del empaque</label>
            <input type="text" name="tamano_empaque">

            <button type="submit">Guardar producto</button>
        </form>
        <div class="note">Formulario exclusivo para vendedores autorizados</div>
    </div>
</body>
</html>
