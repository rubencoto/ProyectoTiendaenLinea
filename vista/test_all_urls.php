<?php
require_once '../modelo/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>URL Test - Project Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .list-group-item { border: none; margin-bottom: 5px; border-radius: 8px; background: #f8f9fa; }
        .badge { font-size: 0.75em; }
        .url-test { font-family: monospace; font-size: 0.85em; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="fas fa-globe"></i> Project URL Status Test</h2>
                    <small>Environment: <?= AppConfig::isHeroku() ? 'Heroku Production' : 'Local Development' ?></small>
                </div>
                <div class="card-body">
                    
                    <h5 class="text-primary">🌐 Base Configuration</h5>
                    <div class="row mb-4">
                        <div class="col-12">
                            <p><strong>Base URL:</strong> <code><?= AppConfig::getBaseUrl() ?></code></p>
                            <p><strong>Environment:</strong> <span class="badge <?= AppConfig::isHeroku() ? 'bg-success' : 'bg-info' ?>"><?= AppConfig::isHeroku() ? 'Heroku' : 'Local' ?></span></p>
                        </div>
                    </div>

                    <h5 class="text-primary">🔐 Authentication Pages</h5>
                    <div class="list-group mb-4">
                        <?php
                        $auth_pages = [
                            'Client Login' => 'loginCliente.php',
                            'Vendor Login' => 'loginVendedor.php',
                            'Client Registration' => 'registroCliente.php',
                            'Vendor Registration' => 'registroVendedor.php',
                            'Verify Account' => 'verificarCuentaCliente.php',
                            'Password Recovery' => 'recuperarContrasena.php'
                        ];
                        
                        foreach ($auth_pages as $name => $file) {
                            $url = AppConfig::vistaUrl($file);
                            echo "<div class='list-group-item d-flex justify-content-between align-items-center'>";
                            echo "<div>";
                            echo "<strong>$name</strong><br>";
                            echo "<small class='url-test text-muted'>$url</small>";
                            echo "</div>";
                            echo "<a href='$url' target='_blank' class='btn btn-sm btn-outline-primary'>Test</a>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <h5 class="text-primary">🏪 Main Application Pages</h5>
                    <div class="list-group mb-4">
                        <?php
                        $main_pages = [
                            'Home Page' => 'index.php',
                            'Products Catalog' => 'productos.php',
                            'Vendor Dashboard' => 'inicioVendedor.php',
                            'Client Profile' => 'perfil.php',
                            'Add Product' => 'agregarproducto.php',
                            'Change Password' => 'cambiarPassword.php'
                        ];
                        
                        foreach ($main_pages as $name => $file) {
                            $url = AppConfig::vistaUrl($file);
                            echo "<div class='list-group-item d-flex justify-content-between align-items-center'>";
                            echo "<div>";
                            echo "<strong>$name</strong><br>";
                            echo "<small class='url-test text-muted'>$url</small>";
                            echo "</div>";
                            echo "<a href='$url' target='_blank' class='btn btn-sm btn-outline-primary'>Test</a>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <h5 class="text-primary">⚙️ Controller Actions</h5>
                    <div class="list-group mb-4">
                        <?php
                        $controllers = [
                            'Process Client Login' => 'procesarLoginCliente.php',
                            'Process Vendor Login' => 'procesarLoginVendedor.php',
                            'Process Client Registration' => 'procesarRegistroCliente.php',
                            'Process Vendor Registration' => 'procesarRegistroVendedor.php',
                            'Process Product' => 'procesarProducto.php'
                        ];
                        
                        foreach ($controllers as $name => $file) {
                            $url = AppConfig::controladorUrl($file);
                            echo "<div class='list-group-item d-flex justify-content-between align-items-center'>";
                            echo "<div>";
                            echo "<strong>$name</strong><br>";
                            echo "<small class='url-test text-muted'>$url</small>";
                            echo "</div>";
                            echo "<span class='badge bg-secondary'>POST Form Target</span>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <p class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                All URLs are generated using AppConfig for cross-environment compatibility
                            </p>
                            <a href="<?= AppConfig::vistaUrl('index.php') ?>" class="btn btn-primary me-2">Go to Home</a>
                            <a href="<?= AppConfig::vistaUrl('debug.php') ?>" class="btn btn-outline-secondary">Debug Info</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
</body>
</html>
