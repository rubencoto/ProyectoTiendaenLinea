<?php
// Diagnostic page to check what's happening
require_once '../modelo/config.php';

echo "<h2>🔍 Diagnostic Information</h2>";
echo "<h3>Environment Detection:</h3>";
echo "Is Heroku: " . (AppConfig::isHeroku() ? "✅ YES" : "❌ NO") . "<br>";
echo "Base URL: " . AppConfig::getBaseUrl() . "<br>";
echo "Current PHP file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";

echo "<h3>Server Variables:</h3>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "<br>";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "<br>";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'Not set') . "<br>";

echo "<h3>🎯 CORRECTED URLs (should work now):</h3>";
echo "<p><strong>Login Cliente:</strong> <a href='" . AppConfig::link('loginCliente.php') . "'>" . AppConfig::link('loginCliente.php') . "</a></p>";
echo "<p><strong>Login Vendedor:</strong> <a href='" . AppConfig::link('loginVendedor.php') . "'>" . AppConfig::link('loginVendedor.php') . "</a></p>";
echo "<p><strong>Main Index:</strong> <a href='" . AppConfig::link('index.php') . "'>" . AppConfig::link('index.php') . "</a></p>";
echo "<p><strong>Registro Cliente:</strong> <a href='" . AppConfig::link('registroCliente.php') . "'>" . AppConfig::link('registroCliente.php') . "</a></p>";

echo "<h3>File Check:</h3>";
$loginFile = __DIR__ . '/loginCliente.php';
echo "Login file path: " . $loginFile . "<br>";
echo "File exists: " . (file_exists($loginFile) ? "✅ YES" : "❌ NO") . "<br>";
echo "File readable: " . (is_readable($loginFile) ? "✅ YES" : "❌ NO") . "<br>";

echo "<h3>URL Generation Test:</h3>";
echo "AppConfig::link('loginCliente.php'): " . AppConfig::link('loginCliente.php') . "<br>";
echo "AppConfig::vistaUrl('loginCliente.php'): " . AppConfig::vistaUrl('loginCliente.php') . "<br>";
echo "AppConfig::controladorUrl('procesarLogin.php'): " . AppConfig::controladorUrl('procesarLogin.php') . "<br>";

echo "<h3>🚀 Quick Fix Verification:</h3>";
echo "<p style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
echo "✅ Your Heroku URL is now set to: <strong>https://proyectotiendaenlinea-9acfaa0e4138.herokuapp.com</strong><br>";
echo "✅ Links should now generate correctly without /vista/ prefix<br>";
echo "✅ Test the links above to confirm they work!";
echo "</p>";
?>
