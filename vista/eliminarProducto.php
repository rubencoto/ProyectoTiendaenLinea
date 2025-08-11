<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center bg-danger text-white">
                    <h4><i class="fas fa-trash me-2"></i>Eliminar Producto</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Alert Message Area -->
                    <div id="alertContainer" style="display: none;"></div>
                    
                    <!-- Delete Form -->
                    <div id="deleteForm">
                        <form id="eliminarForm">
                            <div class="mb-3">
                                <label class="form-label">ID del Producto</label>
                                <input type="number" class="form-control" name="id" id="productoId"
                                       placeholder="Ingresa el ID del producto a eliminar" min="1" required>
                            </div>
                            
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>¡Advertencia!</strong> Esta acción no se puede deshacer.
                            </div>
                            
                            <button type="submit" class="btn btn-danger w-100 mb-3" id="eliminarBtn">
                                <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="eliminarSpinner"></span>
                                <i class="fas fa-trash me-1" id="eliminarIcon"></i>
                                Eliminar Producto
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="productos.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver a Productos
                            </a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete form submission
    document.getElementById('eliminarForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const productoId = document.getElementById('productoId').value;
        
        if (!productoId || productoId <= 0) {
            showAlert('Por favor, ingresa un ID válido del producto.', 'error');
            return;
        }
        
        // Confirm deletion
        if (!confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', productoId);
        
        const eliminarBtn = document.getElementById('eliminarBtn');
        const eliminarSpinner = document.getElementById('eliminarSpinner');
        const eliminarIcon = document.getElementById('eliminarIcon');
        
        // Show loading state
        eliminarBtn.disabled = true;
        eliminarSpinner.style.display = 'inline-block';
        eliminarIcon.style.display = 'none';
        
        fetch('eliminarProductoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                // Clear form after successful deletion
                document.getElementById('productoId').value = '';
                
                // Redirect to products page after 2 seconds
                setTimeout(() => {
                    window.location.href = 'productos.php';
                }, 2000);
            } else {
                showAlert(data.message || 'Error al eliminar el producto.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
        })
        .finally(() => {
            // Hide loading state
            eliminarBtn.disabled = false;
            eliminarSpinner.style.display = 'none';
            eliminarIcon.style.display = 'inline';
        });
    });
    
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        
        alertContainer.innerHTML = `
            <div class="alert ${alertClass}" role="alert">
                <i class="${iconClass} me-2"></i>${message}
            </div>
        `;
        alertContainer.style.display = 'block';
        
        // Auto-hide success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                alertContainer.style.display = 'none';
            }, 3000);
        }
    }
});
</script>

</body>
</html>
