
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto - Vendedor</title>
    <style>
        /* Establece el color de fondo general, la fuente, y centra vertical y horizontalmente el contenido de la página */
        body {
            background-color: #f2f2f2; /* Fondo gris claro */
            font-family: 'Arial', sans-serif; /* Fuente Arial */
            display: flex; /* Usa flexbox para centrar */
            justify-content: center; /* Centra horizontalmente */
            align-items: center; /* Centra verticalmente */
            padding: 40px 0; /* Espacio arriba y abajo */
        }

        /* Define el estilo del contenedor del formulario: fondo blanco, borde, padding, bordes redondeados, ancho máximo y sombra */
        .form-container {
            background-color: #fff; /* Fondo blanco */
            border: 1px solid #ddd; /* Borde gris claro */
            padding: 30px 40px; /* Espaciado interno */
            border-radius: 8px; /* Bordes redondeados */
            width: 100%; /* Ocupa todo el ancho disponible */
            max-width: 600px; /* Máximo 600px de ancho */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }

        /* Centra el título del formulario, agrega espacio debajo y cambia el color del texto */
        .form-container h2 {
            text-align: center; /* Centra el texto */
            margin-bottom: 25px; /* Espacio debajo del título */
            color: #111; /* Color de texto oscuro */
        }

        /* Hace que las etiquetas de los campos se muestren en bloque, con espacio y color */
        label {
            display: block; /* Ocupa toda la línea */
            margin-bottom: 5px; /* Espacio debajo de la etiqueta */
            color: #333; /* Color de texto gris oscuro */
            font-weight: bold; /* Texto en negrita */
        }

        /* Aplica estilos a los campos de texto, número, área de texto y archivos: ancho completo, padding, bordes y tamaño de fuente */
        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%; /* Ocupa todo el ancho del contenedor */
            padding: 10px; /* Espaciado interno */
            margin-bottom: 15px; /* Espacio debajo de cada campo */
            border-radius: 4px; /* Bordes redondeados */
            border: 1px solid #ccc; /* Borde gris claro */
            font-size: 14px; /* Tamaño de fuente */
        }

        /* Permite que el área de texto se pueda redimensionar solo verticalmente */
        textarea {
            resize: vertical;
        }

        /* Estiliza el botón de envío: ancho completo, padding, color de fondo amarillo, borde, texto en negrita y bordes redondeados */
        button {
            width: 100%; /* Ocupa todo el ancho */
            padding: 12px; /* Espaciado interno */
            background-color: #f0c14b; /* Fondo amarillo */
            border: 1px solid #a88734; /* Borde marrón claro */
            color: #111; /* Texto oscuro */
            font-weight: bold; /* Texto en negrita */
            font-size: 16px; /* Tamaño de fuente */
            cursor: pointer; /* Cambia el cursor al pasar el mouse */
            border-radius: 4px; /* Bordes redondeados */
        }

        /* Cambia el color de fondo del botón cuando el mouse está encima */
        button:hover {
            background-color: #e2b33d; /* Amarillo más oscuro al pasar el mouse */
        }

        /* Centra el texto de la nota informativa, reduce el tamaño de fuente y cambia el color */
        .note {
            text-align: center; /* Centra el texto */
            margin-top: 15px; /* Espacio arriba */
            font-size: 12px; /* Tamaño de fuente pequeño */
            color: #666; /* Color gris */
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Agregar producto</h2>
        <!-- Formulario para agregar un producto nuevo -->
        <form action="../controlador/procesarProducto.php" method="POST" enctype="multipart/form-data">
            <!-- Campo para el nombre del producto -->
            <label>Nombre del producto</label>
            <input type="text" name="nombre" required>

            <!-- Campo para la descripción -->
            <label>Descripción</label>
            <textarea name="descripcion" required></textarea>

            <!-- Campo para el precio -->
            <label>Precio (₡)</label>
            <input type="number" step="0.01" name="precio" required>

            <!-- Campo para la categoría -->
            <label>Categoría</label>
            <input type="text" name="categoria" required>

            <!-- Campo para la imagen principal -->
            <label>Imagen principal</label>
            <input type="file" name="imagen_principal" accept="image/*" required>

            <!-- Campos para imágenes secundarias -->
            <label>Imágenes secundarias (máximo 2)</label>
            <input type="file" name="imagen_secundaria1" accept="image/*">
            <input type="file" name="imagen_secundaria2" accept="image/*">

            <!-- Campo para tallas disponibles -->
            <label>Tallas disponibles (ej: S,M,L,XL)</label>
            <input type="text" name="tallas">

            <!-- Campo para el color -->
            <label>Color</label>
            <input type="text" name="color">

            <!-- Campo para unidades en inventario -->
            <label>Unidades en inventario</label>
            <input type="number" name="unidades">

            <!-- Campo para la garantía -->
            <label>Garantía</label>
            <input type="text" name="garantia">

            <!-- Campo para dimensiones -->
            <label>Dimensiones (Largo x Ancho x Alto)</label>
            <input type="text" name="dimensiones">

            <!-- Campo para el peso -->
            <label>Peso del producto (kg)</label>
            <input type="number" step="0.01" name="peso">

            <!-- Campo para el tamaño del empaque -->
            <label>Tamaño del empaque</label>
            <input type="text" name="tamano_empaque">

            <!-- Botón para enviar el formulario -->
            <button type="submit">Guardar producto</button>
        </form>
        <!-- Nota informativa para los usuarios -->
        <div class="note">Formulario exclusivo para vendedores autorizados</div>
    </div>
</body>
</html>
