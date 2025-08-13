<?php
session_start();

// Verificar que el usuario esté autenticado
if (empty($_SESSION['cliente_id'])) {
    header('Location: ../vista/index.php');
    exit;
}

require_once '../modelo/conexion.php';
require_once '../modelo/carritoPersistente.php';
require_once '../modelo/DireccionesManager.php';

$cliente_id = $_SESSION['cliente_id'];
$carritoPersistente = new CarritoPersistente();
$direccionesManager = new DireccionesManager();

// Verificar que hay productos en el carrito
$productos_carrito = $carritoPersistente->obtenerCarrito($cliente_id);
if (empty($productos_carrito)) {
    header('Location: carrito.php');
    exit;
}

// Obtener información del cliente
$stmt = $conn->prepare("SELECT nombre, apellido, correo, direccion, telefono FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

// Obtener todas las direcciones del cliente
$direcciones = $direccionesManager->obtenerDireccionesCliente($cliente_id);
$direccion_principal = $direccionesManager->obtenerDireccionPrincipalCliente($cliente_id);

// Si no hay dirección principal, usar la del perfil principal
$direccion_envio = $direccion_principal ? $direccion_principal : [
    'direccion_completa' => $cliente['direccion'],
    'is_default' => 0,
    'id' => 'perfil'
];

// Calcular totales
$total = 0;
foreach ($productos_carrito as $producto) {
    $producto['subtotal'] = $producto['precio'] * $producto['cantidad'];
    $total += $producto['subtotal'];
}

$envio = 2500;
$total_final = $total + $envio;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Orden - Tienda en Línea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #007185, #005d6b);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007185, #005d6b);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #005d6b, #004d57);
        }
        .producto-item {
            border-left: 4px solid #007185;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .direccion-card {
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .direccion-card:hover {
            border-color: #007185;
            transform: translateY(-2px);
        }
        .direccion-card.selected {
            border-color: #007185;
            background-color: #f0f9ff;
        }
        .total-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
        }
        .nueva-direccion-form {
            display: none;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <!-- Resumen del pedido -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Resumen del Pedido</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($productos_carrito as $producto): ?>
                        <div class="producto-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php if (!empty($producto['imagen_principal'])): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen_principal']); ?>"
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             style="max-height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 60px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                    <small class="text-muted">Vendedor: <?php echo htmlspecialchars($producto['vendedor_nombre']); ?></small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="fw-bold"><?php echo $producto['cantidad']; ?></span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <span class="fw-bold">₡<?php echo number_format($producto['precio'] * $producto['cantidad'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dirección de envío -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Dirección de Envío</h4>
                    </div>
                    <div class="card-body">
                        <form id="direccionForm">
                            <!-- Dirección del perfil principal -->
                            <div class="direccion-card p-3 rounded mb-3 <?php echo !$direccion_principal ? 'selected' : ''; ?>"
                                 onclick="seleccionarDireccion('perfil')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="direccion_envio"
                                           id="direccion_perfil" value="perfil"
                                           <?php echo !$direccion_principal ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="direccion_perfil">
                                        <i class="fas fa-user me-2"></i>Dirección del perfil
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <p class="mb-1"><?php echo htmlspecialchars($cliente['direccion']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?><br>
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($cliente['telefono']); ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Direcciones guardadas -->
                            <?php if (!empty($direcciones)): ?>
                                <?php foreach ($direcciones as $direccion): ?>
                                <div class="direccion-card p-3 rounded mb-3 <?php echo $direccion['is_default'] ? 'selected' : ''; ?>"
                                     onclick="seleccionarDireccion(<?php echo $direccion['id']; ?>)">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="direccion_envio"
                                               id="direccion_<?php echo $direccion['id']; ?>" value="<?php echo $direccion['id']; ?>"
                                               <?php echo $direccion['is_default'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="direccion_<?php echo $direccion['id']; ?>">
                                            <i class="fas fa-home me-2"></i><?php echo htmlspecialchars($direccion['etiqueta'] ?? 'Dirección guardada'); ?>
                                            <?php if ($direccion['is_default']): ?>
                                                <span class="badge bg-primary ms-2">Principal</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <p class="mb-1"><?php echo htmlspecialchars($direccion['linea1']); ?></p>
                                        <?php if (!empty($direccion['linea2'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($direccion['linea2']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($direccion['provincia'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($direccion['provincia']); ?>
                                            <?php if (!empty($direccion['canton'])): ?>, <?php echo htmlspecialchars($direccion['canton']); ?><?php endif; ?>
                                            <?php if (!empty($direccion['distrito'])): ?>, <?php echo htmlspecialchars($direccion['distrito']); ?><?php endif; ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Opción para nueva dirección -->
                            <div class="direccion-card p-3 rounded mb-3" onclick="seleccionarDireccion('nueva')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="direccion_envio"
                                           id="direccion_nueva" value="nueva">
                                    <label class="form-check-label fw-bold" for="direccion_nueva">
                                        <i class="fas fa-plus me-2"></i>Usar nueva dirección
                                    </label>
                                </div>
                            </div>

                            <!-- Formulario para nueva dirección -->
                            <div id="nueva-direccion-form" class="nueva-direccion-form">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nueva_provincia" class="form-label">Provincia</label>
                                        <input type="text" class="form-control" id="nueva_provincia" name="nueva_provincia">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nueva_canton" class="form-label">Cantón</label>
                                        <input type="text" class="form-control" id="nueva_canton" name="nueva_canton">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nueva_distrito" class="form-label">Distrito</label>
                                        <input type="text" class="form-control" id="nueva_distrito" name="nueva_distrito">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nueva_alias" class="form-label">Alias (opcional)</label>
                                        <input type="text" class="form-control" id="nueva_alias" name="nueva_alias" placeholder="Casa, Oficina, etc.">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="nueva_direccion_completa" class="form-label">Dirección completa</label>
                                    <textarea class="form-control" id="nueva_direccion_completa" name="nueva_direccion_completa" rows="3" placeholder="Dirección exacta con señas"></textarea>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="guardar_direccion" name="guardar_direccion" value="1">
                                    <label class="form-check-label" for="guardar_direccion">
                                        Guardar esta dirección para futuras compras
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Resumen de pago -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-calculator me-2"></i>Resumen de Pago</h4>
                    </div>
                    <div class="card-body">
                        <div class="total-section">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (<?php echo count($productos_carrito); ?> productos):</span>
                                <span>₡<?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span>₡<?php echo number_format($envio, 0, ',', '.'); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-primary">₡<?php echo number_format($total_final, 0, ',', '.'); ?></strong>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6><i class="fas fa-credit-card me-2"></i>Método de Pago</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Pago contra entrega disponible
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-primary btn-lg" onclick="confirmarOrden()">
                                <i class="fas fa-check me-2"></i>Confirmar Pedido
                            </button>
                            <a href="carrito.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Carrito
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function seleccionarDireccion(direccionId) {
            // Remover selección anterior
            document.querySelectorAll('.direccion-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Seleccionar nueva dirección
            const selectedCard = event.currentTarget;
            selectedCard.classList.add('selected');

            // Marcar el radio button correspondiente
            const radioButton = selectedCard.querySelector('input[type="radio"]');
            radioButton.checked = true;

            // Mostrar/ocultar formulario de nueva dirección
            const nuevaDireccionForm = document.getElementById('nueva-direccion-form');
            if (direccionId === 'nueva') {
                nuevaDireccionForm.style.display = 'block';
                // Hacer campos obligatorios
                nuevaDireccionForm.querySelectorAll('input[required], textarea[required]').forEach(field => {
                    field.required = true;
                });
            } else {
                nuevaDireccionForm.style.display = 'none';
                // Remover obligatoriedad
                nuevaDireccionForm.querySelectorAll('input[required], textarea[required]').forEach(field => {
                    field.required = false;
                });
            }
        }

        function confirmarOrden() {
            // Verificar que se haya seleccionado una dirección
            const direccionSeleccionada = document.querySelector('input[name="direccion_envio"]:checked');
            if (!direccionSeleccionada) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dirección requerida',
                    text: 'Por favor selecciona una dirección de envío'
                });
                return;
            }

            // Si se seleccionó nueva dirección, validar campos
            if (direccionSeleccionada.value === 'nueva') {
                const direccionCompleta = document.getElementById('nueva_direccion_completa').value.trim();
                const provincia = document.getElementById('nueva_provincia').value.trim();

                if (!direccionCompleta || !provincia) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Datos incompletos',
                        text: 'Por favor completa al menos la dirección completa y la provincia'
                    });
                    return;
                }
            }

            // Mostrar confirmación
            Swal.fire({
                title: '¿Confirmar pedido?',
                text: 'Una vez confirmado, tu pedido será procesado y no podrá ser cancelado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007185',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    procesarOrden();
                }
            });
        }

        function procesarOrden() {
            const direccionSeleccionada = document.querySelector('input[name="direccion_envio"]:checked').value;

            // Mostrar loading
            Swal.fire({
                title: 'Procesando pedido...',
                text: 'Por favor espera mientras procesamos tu orden',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Preparar datos para enviar
            const formData = new FormData();
            formData.append('direccion_seleccionada', direccionSeleccionada);

            if (direccionSeleccionada === 'nueva') {
                formData.append('nueva_direccion_completa', document.getElementById('nueva_direccion_completa').value);
                formData.append('nueva_provincia', document.getElementById('nueva_provincia').value);
                formData.append('nueva_canton', document.getElementById('nueva_canton').value);
                formData.append('nueva_distrito', document.getElementById('nueva_distrito').value);
                formData.append('nueva_alias', document.getElementById('nueva_alias').value);
                formData.append('guardar_direccion', document.getElementById('guardar_direccion').checked ? '1' : '0');
            }

            // Enviar orden
            fetch('../controlador/confirmarOrden.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // La respuesta del controlador ya incluye HTML completo
                document.open();
                document.write(data);
                document.close();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ha ocurrido un error al procesar el pedido. Por favor, inténtalo de nuevo.'
                });
            });
        }

        // Inicializar comportamiento de clicks
        document.addEventListener('DOMContentLoaded', function() {
            // Hacer que las cards de dirección sean clickeables
            document.querySelectorAll('.direccion-card').forEach(card => {
                card.addEventListener('click', function(event) {
                    // Prevenir doble click en radio button
                    if (event.target.type !== 'radio') {
                        const radioButton = this.querySelector('input[type="radio"]');
                        radioButton.click();
                    }
                });
            });
        });
    </script>
</body>
</html>
