<?php
require_once '../modelo/config.php';

// Force environment detection for testing
echo "<h1>URL Testing - Environment Detection</h1>";
echo "<p><strong>Is Heroku:</strong> " . (AppConfig::isHeroku() ? 'YES' : 'NO') . "</p>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
echo "<p><strong>DYNO:</strong> " . ($_SERVER['DYNO'] ?? 'Not set') . "</p>";
echo "<p><strong>Base URL:</strong> " . AppConfig::getBaseUrl() . "</p>";

echo "<h2>Generated URLs:</h2>";
echo "<ul>";
echo "<li><strong>Vista URL (verificarCuentaCliente.php):</strong> " . AppConfig::vistaUrl('verificarCuentaCliente.php') . "</li>";
echo "<li><strong>Vista URL with params:</strong> " . AppConfig::vistaUrl('verificarCuentaCliente.php?correo=test@example.com&pendiente=1') . "</li>";
echo "<li><strong>Controller URL (procesarRegistroCliente):</strong> " . AppConfig::controladorUrl('procesarRegistroCliente.php') . "</li>";
echo "<li><strong>Email URL:</strong> " . AppConfig::emailUrl('verificarCuentaCliente.php', ['correo' => 'test@example.com', 'codigo' => '123456']) . "</li>";
echo "</ul>";

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='" . AppConfig::vistaUrl('verificarCuentaCliente.php') . "' target='_blank'>Basic Verification Page</a></li>";
echo "<li><a href='" . AppConfig::vistaUrl('verificarCuentaCliente.php?correo=test@example.com&pendiente=1') . "' target='_blank'>Pending Verification Test</a></li>";
echo "<li><a href='" . AppConfig::emailUrl('verificarCuentaCliente.php', ['correo' => 'test@example.com', 'codigo' => '123456']) . "' target='_blank'>Email Verification Link Test</a></li>";
echo "</ul>";

echo "<h2>Current Request Info:</h2>";
echo "<ul>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</li>";
echo "<li><strong>PHP_SELF:</strong> " . ($_SERVER['PHP_SELF'] ?? 'Not set') . "</li>";
echo "<li><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</li>";
echo "</ul>";

// Test the specific URL that's failing
echo "<h2>Specific Failing URL Test:</h2>";
$test_correo = 'leomoya55@yahoo.com';
$failing_url = AppConfig::vistaUrl('verificarCuentaCliente.php?correo=' . urlencode($test_correo) . '&pendiente=1');
echo "<p><strong>Generated URL:</strong> <a href='" . $failing_url . "' target='_blank'>" . $failing_url . "</a></p>";
?>
