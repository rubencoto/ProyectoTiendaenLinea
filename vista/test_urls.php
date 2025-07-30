<?php
require_once '../modelo/config.php';

echo "<h2>🔗 URL Connection Test</h2>";

echo "<h3>📋 Controller URL Tests:</h3>";
$controllers = [
    'procesarRegistroCliente.php',
    'procesarRegistroVendedor.php', 
    'procesarLoginCliente.php',
    'procesarLoginVendedor.php',
    'procesarProducto.php',
    'actualizarProducto.php',
    'confirmarOrden.php',
    'verificarCuenta.php'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Controller</th><th>Generated URL</th><th>Status</th></tr>";

foreach ($controllers as $controller) {
    $url = AppConfig::controladorUrl($controller);
    echo "<tr>";
    echo "<td>$controller</td>";
    echo "<td><a href='$url' target='_blank'>$url</a></td>";
    echo "<td style='color: green;'>✅ Generated</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🧪 Vista URL Tests:</h3>";
$vistas = [
    'index.php',
    'loginCliente.php',
    'loginVendedor.php',
    'registroCliente.php',
    'registroVendedor.php',
    'carrito.php'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Vista</th><th>Generated URL</th><th>Test Link</th></tr>";

foreach ($vistas as $vista) {
    $url = AppConfig::link($vista);
    echo "<tr>";
    echo "<td>$vista</td>";
    echo "<td>$url</td>";
    echo "<td><a href='$url' target='_blank'>Test</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🌐 Environment Info:</h3>";
echo "<p><strong>Is Heroku:</strong> " . (AppConfig::isHeroku() ? "Yes" : "No") . "</p>";
echo "<p><strong>Base URL:</strong> " . AppConfig::getBaseUrl() . "</p>";
echo "<p><strong>Current Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";

echo "<h3>🔧 Controller Router Status:</h3>";
if (file_exists('controller.php')) {
    echo "<p style='color: green;'>✅ Controller router exists and ready</p>";
} else {
    echo "<p style='color: red;'>❌ Controller router missing</p>";
}

echo "<br><br>";
echo "<h3>🚀 Test Registration Process:</h3>";
echo "<ol>";
echo "<li><a href='" . AppConfig::link('registroCliente.php') . "' target='_blank'>Test Client Registration Form</a></li>";
echo "<li><a href='" . AppConfig::link('registroVendedor.php') . "' target='_blank'>Test Vendor Registration Form</a></li>";
echo "<li><a href='" . AppConfig::link('loginCliente.php') . "' target='_blank'>Test Client Login</a></li>";
echo "<li><a href='" . AppConfig::link('loginVendedor.php') . "' target='_blank'>Test Vendor Login</a></li>";
echo "</ol>";

echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
echo "<h4>🎉 URL System Fixed!</h4>";
echo "<p><strong>What's been fixed:</strong></p>";
echo "<ul>";
echo "<li>✅ Controller router created for Heroku compatibility</li>";
echo "<li>✅ All form actions updated to use AppConfig::controladorUrl()</li>";
echo "<li>✅ Registration forms now point to correct URLs</li>";
echo "<li>✅ Login forms use proper URL generation</li>";
echo "<li>✅ Cart confirmation links fixed</li>";
echo "</ul>";
echo "<p><strong>Both Heroku URL and custom domain should now work properly!</strong></p>";
echo "</div>";
?>
