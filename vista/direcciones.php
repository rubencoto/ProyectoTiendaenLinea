<?php
session_start();

// Verificar que el cliente esté autenticado
if (empty($_SESSION['cliente_id'])) {
    header('Location: loginCliente.php');
    exit;
}

require_once '../modelo/DireccionesManager.php';

$cliente_id = $_SESSION['cliente_id'];
$direccionesManager = new DireccionesManager();
$direcciones = $direccionesManager->obtenerDireccionesCliente($cliente_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Direcciones - Tienda en Línea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .app-header {
            background: linear-gradient(135deg, #232f3e 0%, #37475a 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container-narrow {
            max-width: 1000px;
        }
        
        .btn-eq {
            min-width: 140px;
        }
        
        .dir-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .dir-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 25px rgba(0,0,0,0.12);
        }
        
        .dir-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .badge {
            font-size: 0.7em;
            padding: 4px 8px;
        }
        
        .muted {
            color: #6c757d;
        }
        
        .btn-outline-primary {
            border-width: 1.5px;
        }
        
        .btn-outline-danger {
            border-width: 1.5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .form-control:focus {
            border-color: #007185;
            box-shadow: 0 0 0 0.2rem rgba(0, 113, 133, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007185 0%, #005d6b 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #005d6b 0%, #004a56 100%);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .spinner-border {
            width: 2rem;
            height: 2rem;
        }
    </style>
</head>
<body>

<div class="app-header">
    <div class="container container-narrow">
        <h1 class="h4 mb-0">
            <i class="fas fa-map-marker-alt me-2"></i>
            Mis Direcciones
        </h1>
    </div>
</div>

<div class="container container-narrow py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="btn btn-outline-secondary btn-eq">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
        <button id="btnNueva" class="btn btn-primary btn-eq">
            <i class="fas fa-plus me-1"></i>
            Nueva dirección
        </button>
    </div>

    <div id="listado" class="row g-3">
        <div class="col-12">
            <div class="loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2">Cargando direcciones...</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Dirección -->
<div class="modal fade" id="modalDireccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formDireccion" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Nueva dirección
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Etiqueta</label>
                        <input name="etiqueta" class="form-control" placeholder="Casa, Trabajo, etc.">
                        <div class="form-text">Opcional - para identificar fácilmente</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input name="nombre" required class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input name="apellidos" required class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input name="telefono" class="form-control" placeholder="+506 8888-8888">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Código postal</label>
                        <input name="codigo_postal" class="form-control" placeholder="Ej. 10101">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Dirección (línea 1) <span class="text-danger">*</span></label>
                        <input name="linea1" required class="form-control" 
                               placeholder="Calle, avenida, número, edificio">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Dirección (línea 2)</label>
                        <input name="linea2" class="form-control" 
                               placeholder="Apartamento, interior, piso (opcional)">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Provincia <span class="text-danger">*</span></label>
                        <select name="provincia" required class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="San José">San José</option>
                            <option value="Alajuela">Alajuela</option>
                            <option value="Cartago">Cartago</option>
                            <option value="Heredia">Heredia</option>
                            <option value="Guanacaste">Guanacaste</option>
                            <option value="Puntarenas">Puntarenas</option>
                            <option value="Limón">Limón</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantón <span class="text-danger">*</span></label>
                        <input name="canton" required class="form-control" placeholder="Ej. San José, Alajuela">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Distrito <span class="text-danger">*</span></label>
                        <input name="distrito" required class="form-control" placeholder="Ej. Centro, Merced">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Referencia</label>
                        <textarea name="referencia" class="form-control" rows="2" 
                                  placeholder="Puntos de referencia, indicaciones adicionales"></textarea>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkDefault" name="is_default">
                            <label class="form-check-label" for="chkDefault">
                                <strong>Usar como dirección principal</strong>
                                <div class="form-text">Esta será tu dirección predeterminada para envíos</div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    Guardar dirección
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const modal = new bootstrap.Modal(document.getElementById('modalDireccion'));
const form = document.getElementById('formDireccion');
const listado = document.getElementById('listado');

// Cargar direcciones al iniciar
document.addEventListener('DOMContentLoaded', () => {
    cargarDirecciones();
});

// Event listeners
document.getElementById('btnNueva').addEventListener('click', () => {
    form.reset();
    form.id.value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva dirección';
    modal.show();
});

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    await guardarDireccion();
});

// Funciones principales
async function cargarDirecciones() {
    try {
        const response = await fetch('../controlador/direccionesController.php?action=listar');
        const data = await response.json();
        
        if (data.success) {
            mostrarDirecciones(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        listado.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar las direcciones: ${error.message}
                </div>
            </div>
        `;
    }
}

function mostrarDirecciones(direcciones) {
    if (!direcciones || direcciones.length === 0) {
        listado.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-map-marker-alt"></i>
                    <h5>No tienes direcciones guardadas</h5>
                    <p>Agrega una dirección para facilitar tus compras</p>
                    <button class="btn btn-primary" onclick="document.getElementById('btnNueva').click()">
                        <i class="fas fa-plus me-1"></i>
                        Agregar primera dirección
                    </button>
                </div>
            </div>
        `;
        return;
    }
    
    const html = direcciones.map(d => {
        const fecha = new Date(d.fecha_creacion).toLocaleDateString('es-CR');
        const linea = `${d.linea1}${d.linea2 ? ', ' + d.linea2 : ''}`;
        const ubica = `${d.distrito}, ${d.canton}, ${d.provincia}`;
        const cp = d.codigo_postal ? ` ${d.codigo_postal}` : '';
        const ref = d.referencia ? `<div class="small text-muted mt-1"><i class="fas fa-info-circle me-1"></i>${d.referencia}</div>` : '';
        
        return `
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dir-card h-100">
                    <div class="dir-head">
                        <div class="fw-semibold">
                            ${d.etiqueta || 'Dirección'}
                            ${Number(d.is_default) === 1 ? '<span class="badge bg-primary ms-2">Principal</span>' : ''}
                        </div>
                        <div class="small muted">${fecha}</div>
                    </div>
                    <div class="small muted mt-1">${d.nombre} ${d.apellidos}${d.telefono ? (' · ' + d.telefono) : ''}</div>
                    <div class="mt-2">${linea}</div>
                    <div class="muted">${ubica}${cp}</div>
                    ${ref}
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="editarDireccion(${d.id})">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${Number(d.is_default) !== 1 ? `
                            <button class="btn btn-outline-success btn-sm" onclick="establecerPrincipal(${d.id})">
                                <i class="fas fa-star"></i> Principal
                            </button>
                        ` : ''}
                        <button class="btn btn-outline-danger btn-sm" onclick="eliminarDireccion(${d.id})">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    listado.innerHTML = html;
}

async function editarDireccion(id) {
    try {
        const response = await fetch(`../controlador/direccionesController.php?action=obtener&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const d = data.data;
            form.reset();
            
            // Llenar formulario
            for (const campo in d) {
                if (form[campo] && campo !== 'is_default') {
                    form[campo].value = d[campo] || '';
                }
            }
            form.is_default.checked = Number(d.is_default) === 1;
            form.id.value = d.id;
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar dirección';
            modal.show();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        mostrarAlerta('Error al cargar la dirección: ' + error.message, 'danger');
    }
}

async function eliminarDireccion(id) {
    if (!confirm('¿Estás seguro de eliminar esta dirección?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', id);
        
        const response = await fetch('../controlador/direccionesController.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarToast(data.message, 'success');
            cargarDirecciones();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        mostrarToast('Error al eliminar: ' + error.message, 'error');
    }
}

async function establecerPrincipal(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'establecer_principal');
        formData.append('id', id);
        
        const response = await fetch('../controlador/direccionesController.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarToast(data.message, 'success');
            cargarDirecciones();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        mostrarToast('Error: ' + error.message, 'error');
    }
}

async function guardarDireccion() {
    try {
        const formData = new FormData(form);
        const action = formData.get('id') ? 'actualizar' : 'crear';
        formData.append('action', action);
        
        const response = await fetch('../controlador/direccionesController.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarToast(data.message, 'success');
            modal.hide();
            cargarDirecciones();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        mostrarToast('Error al guardar: ' + error.message, 'error');
    }
}

function mostrarToast(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = `
        top: 20px; right: 20px; z-index: 9999; 
        min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    toast.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${mensaje}
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>

</body>
</html>
