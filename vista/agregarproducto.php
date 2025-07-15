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
            padding: 30px;
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

        label {
            display: block;
            margin-bottom: 5px;
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

        img.preview {
            max-width: 150px;
            margin-bottom: 15px;
            display: block;
            border: 1px solid #ddd;
            padding: 4px;
            background: #f9f9f9;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #f0c14b;
            border: 1px solid #a88734;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        .progress-container {
            margin-top: 15px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
            display: none;
        }

        .progress-bar {
            background-color: #28a745;
            height: 18px;
            width: 0%;
            color: white;
            text-align: center;
            font-size: 12px;
            line-height: 18px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Agregar producto</h2>
    <form id="formularioProducto" enctype="multipart/form-data">
        <label>Nombre</label>
        <input type="text" name="nombre" required>

        <label>Descripción</label>
        <textarea name="descripcion" required></textarea>

        <label>Precio (₡)</label>
        <input type="number" name="precio" step="0.01" required>

        <label>Categoría</label>
        <input type="text" name="categoria" required>

        <label>Imagen principal</label>
        <input type="file" name="imagen_principal" accept="image/*" onchange="preview(this, 'previewPrincipal')" required>
        <img class="preview" id="previewPrincipal">

        <label>Imagen secundaria 1</label>
        <input type="file" name="imagen_secundaria1" accept="image/*" onchange="preview(this, 'previewSec1')">
        <img class="preview" id="previewSec1">

        <label>Imagen secundaria 2</label>
        <input type="file" name="imagen_secundaria2" accept="image/*" onchange="preview(this, 'previewSec2')">
        <img class="preview" id="previewSec2">

        <label>Tallas</label>
        <input type="text" name="tallas">

        <label>Color</label>
        <input type="text" name="color">

        <label>Unidades</label>
        <input type="number" name="unidades">

        <label>Garantía</label>
        <input type="text" name="garantia">

        <label>Dimensiones</label>
        <input type="text" name="dimensiones">

        <label>Peso (kg)</label>
        <input type="number" step="0.01" name="peso">

        <label>Tamaño del empaque</label>
        <input type="text" name="tamano_empaque">

        <button type="submit">Guardar producto</button>

        <div class="progress-container" id="progressContainer">
            <div class="progress-bar" id="progressBar">0%</div>
        </div>
    </form>
</div>

<!-- Script de previsualización + barra de carga -->
<script>
function preview(input, id) {
    const img = document.getElementById(id);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => img.src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    } else {
        img.src = "";
    }
}

document.getElementById("formularioProducto").addEventListener("submit", function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const xhr = new XMLHttpRequest();

    xhr.open("POST", "../controlador/procesarProducto.php");

    xhr.upload.addEventListener("progress", function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            const bar = document.getElementById("progressBar");
            const container = document.getElementById("progressContainer");
            container.style.display = "block";
            bar.style.width = percent + "%";
            bar.textContent = percent + "%";
        }
    });

    xhr.onload = function() {
        if (xhr.status === 200) {
            document.body.innerHTML = xhr.responseText;
        } else {
            alert("Error al enviar el formulario.");
        }
    };

    xhr.send(data);
});
</script>

<!-- ✅ Script de funcionalidades JS adicionales -->
<script src="../js/app.js"></script>

</body>
</html>
