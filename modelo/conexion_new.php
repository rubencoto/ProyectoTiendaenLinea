<?php
// Database connection with proper error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for required database extensions
$hasMySQL = extension_loaded('mysqli') || 
           (extension_loaded('pdo') && 
            count(PDO::getAvailableDrivers()) > 0 && 
            in_array('mysql', PDO::getAvailableDrivers()));

if (!$hasMySQL) {
    // Show user-friendly error instead of 500 Internal Server Error
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error de Configuración del Servidor</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; }
            .error { background: #ffe6e6; border: 1px solid #ff0000; padding: 20px; border-radius: 5px; }
            .solution { background: #e6f3ff; border: 1px solid #0066cc; padding: 20px; border-radius: 5px; margin-top: 20px; }
            .info { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 20px; }
            h1 { color: #d63384; }
            h2 { color: #0066cc; }
            ul, ol { padding-left: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error">
                <h1>🚫 Error de Configuración del Servidor</h1>
                <p>El servidor no tiene las extensiones de MySQL requeridas para conectar a la base de datos.</p>
                <p><strong>Estado de las extensiones:</strong></p>
                <ul>
                    <li>MySQLi: <?= extension_loaded('mysqli') ? '✅ Disponible' : '❌ No disponible' ?></li>
                    <li>PDO: <?= extension_loaded('pdo') ? '✅ Cargado' : '❌ No cargado' ?></li>
                    <li>PDO MySQL drivers: <?= extension_loaded('pdo') ? count(PDO::getAvailableDrivers()) . ' driver(s) disponible(s)' : 'PDO no disponible' ?></li>
                    <?php if (extension_loaded('pdo')): ?>
                    <li>Drivers PDO: <?= implode(', ', PDO::getAvailableDrivers()) ?: 'Ninguno' ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="solution">
                <h2>🔧 Soluciones Posibles</h2>
                
                <h3>Si estás usando Heroku:</h3>
                <ol>
                    <li>Agrega el buildpack de ClearDB MySQL: <code>heroku addons:create cleardb:ignite</code></li>
                    <li>O configura JawsDB MySQL: <code>heroku addons:create jawsdb:kitefin</code></li>
                    <li>Asegúrate de que tu <code>composer.json</code> incluya <code>ext-mysqli</code> o <code>ext-pdo_mysql</code></li>
                </ol>
                
                <h3>Si estás usando hosting compartido:</h3>
                <ol>
                    <li>Contacta a tu proveedor de hosting</li>
                    <li>Solicita que activen las extensiones PHP: <strong>mysqli</strong> y/o <strong>pdo_mysql</strong></li>
                    <li>Verifica que tu plan de hosting incluye MySQL</li>
                </ol>
                
                <h3>Si tienes un VPS o servidor dedicado:</h3>
                <ol>
                    <li><strong>Ubuntu/Debian:</strong> <code>sudo apt-get install php-mysqli php-mysql</code></li>
                    <li><strong>CentOS/RHEL:</strong> <code>sudo yum install php-mysqli php-mysqlnd</code></li>
                    <li><strong>Windows:</strong> Descomenta <code>extension=mysqli</code> en <code>php.ini</code></li>
                    <li>Reinicia el servidor web después de los cambios</li>
                </ol>
            </div>
            
            <div class="info">
                <h3>📋 Información del Sistema</h3>
                <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
                <p><strong>Sistema Operativo:</strong> <?= PHP_OS ?></p>
                <p><strong>Servidor Web:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?></p>
                <p><strong>Todas las extensiones cargadas:</strong></p>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">
                    <?= implode(', ', get_loaded_extensions()) ?>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; color: #666;">
                <p>Una vez que las extensiones MySQL estén disponibles, recarga esta página.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If we reach here, database extensions are available
// TODO: Add actual database connection code here when extensions are fixed

// For now, just indicate that the extension check passed
echo "<!-- Database extensions check passed -->";
?>
