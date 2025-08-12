<?php
session_start();
require_once '../modelo/conexion.php';

// Check if user is logged in
if (!isset($_SESSION['cliente_id'])) {
    die("Please log in first");
}

$cliente_id = $_SESSION['cliente_id'];

// Get database connection
$db = DatabaseConnection::getInstance();
$pdo_conn = $db->getConnection();

// Get customer info
$stmt = $pdo_conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente_data = $stmt->fetch();
$nombre_completo = $cliente_data ? $cliente_data['nombre'] . ' ' . $cliente_data['apellido'] : 'Cliente';

// Get orders - SIMPLE QUERY
$stmt_ordenes = $pdo_conn->prepare("
    SELECT id, numero_orden, total, estado, fecha_orden
    FROM pedidos 
    WHERE cliente_id = ? 
    ORDER BY fecha_orden DESC
");
$stmt_ordenes->execute([$cliente_id]);

$ordenes = [];
while ($row = $stmt_ordenes->fetch()) {
    $ordenes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Simplified</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
        }
        .order { 
            border: 1px solid #ddd; 
            margin: 10px 0; 
            padding: 15px; 
            border-radius: 8px; 
            background: #f9f9f9; 
        }
        .order.target { 
            border: 2px solid red; 
            background: #ffe6e6; 
        }
        .order-number { 
            font-weight: bold; 
            font-size: 18px; 
            color: #007185; 
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #007185;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .debug-info {
            background: #e6f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="misPedidos.php" class="back-link">← Volver a Mis Pedidos Normal</a>
        
        <h1>🔧 Mis Pedidos - Versión Simplificada</h1>
        
        <div class="debug-info">
            <h3>Información de Debug:</h3>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($nombre_completo); ?> (ID: <?php echo $cliente_id; ?>)</p>
            <p><strong>Total de órdenes encontradas:</strong> <?php echo count($ordenes); ?></p>
            <p><strong>Esta es una versión simplificada para probar si el problema persiste</strong></p>
        </div>

        <?php if (empty($ordenes)): ?>
            <p>No tienes pedidos realizados.</p>
        <?php else: ?>
            <?php 
            $target_count = 0;
            foreach ($ordenes as $index => $orden): 
                $is_target = ($orden['numero_orden'] == 'ORD-2025-945427');
                if ($is_target) $target_count++;
            ?>
                <div class="order <?php echo $is_target ? 'target' : ''; ?>">
                    <div class="order-number">
                        Orden #<?php echo htmlspecialchars($orden['numero_orden']); ?>
                        <?php if ($is_target): ?>
                            <span style="color: red; font-weight: bold;"> ← ESTA ES LA ORDEN PROBLEMÁTICA</span>
                        <?php endif; ?>
                    </div>
                    <p><strong>ID:</strong> <?php echo $orden['id']; ?></p>
                    <p><strong>Total:</strong> ₡<?php echo number_format($orden['total'], 2); ?></p>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($orden['estado']); ?></p>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></p>
                    <p><strong>Índice en Array:</strong> <?php echo $index; ?></p>
                </div>
            <?php endforeach; ?>
            
            <div class="debug-info">
                <h3>🔍 Resultado del Test:</h3>
                <p><strong>ORD-2025-945427 aparece <?php echo $target_count; ?> vez(es) en esta página simplificada</strong></p>
                <?php if ($target_count > 1): ?>
                    <p style="color: red; font-weight: bold;">❌ PROBLEMA CONFIRMADO: La orden aparece múltiples veces incluso en esta versión simplificada</p>
                    <p>Esto significa que hay un problema a nivel de base de datos o en la query PHP</p>
                <?php elseif ($target_count == 1): ?>
                    <p style="color: green; font-weight: bold;">✅ VERSIÓN SIMPLIFICADA FUNCIONA CORRECTAMENTE</p>
                    <p>La orden aparece solo una vez aquí. El problema está en la versión completa de misPedidos.php</p>
                    <p>Probablemente hay JavaScript, CSS, o lógica de display que está causando la duplicación</p>
                <?php else: ?>
                    <p style="color: blue; font-weight: bold;">ℹ️ La orden ORD-2025-945427 no se encontró</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>📋 Instrucciones para el Usuario:</h3>
        <ol>
            <li><strong>Compara esta página con misPedidos.php normal</strong></li>
            <li><strong>Si aquí aparece solo 1 vez pero en misPedidos.php aparece 2 veces:</strong>
                <ul>
                    <li>El problema está en JavaScript, CSS o display logic</li>
                    <li>Limpia caché del navegador (Ctrl+Shift+R)</li>
                    <li>Prueba en ventana incógnito</li>
                    <li>Usa las herramientas de desarrollador (F12) para buscar elementos duplicados</li>
                </ul>
            </li>
            <li><strong>Si aquí también aparece 2 veces:</strong>
                <ul>
                    <li>Hay un problema en la base de datos o en la query PHP</li>
                    <li>Contacta al desarrollador para investigar más</li>
                </ul>
            </li>
        </ol>
    </div>
</body>
</html>
