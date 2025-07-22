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

        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            font-size: 18px;
            text-align: center;
            animation: pulse 2s ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .back-btn:hover {
            background-color: #5a6268;
            color: white;
            text-decoration: none;
        }

        /* Custom Modal Styles */
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
            color: #155724;
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

        .modal-btn.primary {
            background-color: #28a745;
            color: white;
        }

        .modal-btn.primary:hover {
            background-color: #218838;
        }

        .modal-btn.secondary {
            background-color: #6c757d;
            color: white;
        }

        .modal-btn.secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Agregar producto</h2>
    <a href="inicioVendedor.php" class="back-btn">← Volver al Panel</a>
    
    <div id="messageContainer"></div>
    
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

<!-- Custom Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <h3>🎉 ¡Producto Agregado Exitosamente!</h3>
        <p>Su producto ha sido registrado correctamente en el sistema.</p>
        <div class="modal-buttons">
            <button class="modal-btn primary" onclick="addAnotherProduct()">Agregar Otro Producto</button>
            <button class="modal-btn secondary" onclick="goToDashboard()">Volver al Panel</button>
        </div>
    </div>
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
    
    // IMMEDIATELY show a processing message
    showMessage("🔄 Procesando producto... Por favor espera.", "success");
    
    try {
        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = "Procesando...";
        
        const form = e.target;
        const data = new FormData(form);
        const xhr = new XMLHttpRequest();

        xhr.open("POST", "../controlador/procesarProducto.php");
        
        // Set timeout to 30 seconds
        xhr.timeout = 30000;
        
        xhr.ontimeout = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            document.getElementById("progressContainer").style.display = "none";
            showMessage("❌ Tiempo de espera agotado. El servidor tardó demasiado en responder.", "error");
        };

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
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            const progressContainer = document.getElementById("progressContainer");
            
            // Hide progress bar
            progressContainer.style.display = "none";
            
            console.log("XHR Status:", xhr.status);
            console.log("Server Response Length:", xhr.responseText.length);
            console.log("Server Response:", xhr.responseText);
            
            // ALWAYS show a result - no matter what
            if (xhr.status === 200) {
                
                // Check if response contains success indicator or SweetAlert2
                if (xhr.responseText.includes("Producto agregado con éxito") || 
                    xhr.responseText.includes("SweetAlert2") ||
                    xhr.responseText.includes("Swal.fire")) {
                    
                    // DON'T replace the body - show our own success dialog instead
                    showMessage("✅ ¡Producto agregado exitosamente! 🎉", "success");
                    
                    // Show custom modal after 2 seconds
                    setTimeout(() => {
                        document.getElementById("successModal").style.display = "block";
                    }, 2000);
                    
                } else if (xhr.responseText.includes("Error al guardar producto")) {
                    // Show error message from server
                    showMessage("❌ " + xhr.responseText, "error");
                    
                } else if (xhr.responseText.trim() === "") {
                    // Empty response - likely a PHP error
                    showMessage("❌ El servidor devolvió una respuesta vacía. Puede que el producto se haya guardado. Revisa el panel de productos.", "error");
                    
                } else {
                    // Unknown response - but let's assume it might have worked
                    showMessage("⚠️ Respuesta del servidor no reconocida. El producto puede haberse guardado. Respuesta: " + xhr.responseText.replace(/</g, "&lt;").substring(0, 200) + "...", "error");
                }
            } else {
                showMessage("❌ Error HTTP " + xhr.status + ": " + xhr.statusText, "error");
            }
        };

        xhr.onerror = function() {
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            document.getElementById("progressContainer").style.display = "none";
            showMessage("❌ Error de conexión. Verifica tu internet e inténtalo de nuevo.", "error");
        };

        xhr.send(data);
        
    } catch (error) {
        console.error("JavaScript Error:", error);
        showMessage("❌ Error de JavaScript: " + error.message, "error");
    }
});

function showMessage(message, type) {
    const messageContainer = document.getElementById("messageContainer");
    messageContainer.innerHTML = `<div class="message ${type}">${message}</div>`;
    
    // Scroll to top to show the message
    window.scrollTo(0, 0);
    
    // Auto-hide error messages after 8 seconds (increased from 5)
    if (type === 'error') {
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 8000);
    }
    // Keep success messages visible longer
    else if (type === 'success') {
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 10000);
    }
}

// Modal functions
function addAnotherProduct() {
    // Hide modal
    document.getElementById("successModal").style.display = "none";
    
    // Reset form
    document.getElementById("formularioProducto").reset();
    
    // Clear image previews
    document.getElementById("previewPrincipal").src = "";
    document.getElementById("previewSec1").src = "";
    document.getElementById("previewSec2").src = "";
    
    // Clear message
    document.getElementById("messageContainer").innerHTML = "";
    
    // Scroll to top
    window.scrollTo(0, 0);
}

function goToDashboard() {
    window.location.href = "inicioVendedor.php";
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById("successModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<!-- ✅ Script de funcionalidades JS adicionales -->
<script src="../js/app.js"></script>

</body>
</html>
