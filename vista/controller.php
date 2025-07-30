<?php
/**
 * Controller Router for Heroku
 * Routes controller requests when vista/ is document root
 */

// Get the requested controller and action
$controller = $_GET['c'] ?? '';
$action = $_GET['a'] ?? '';

if (empty($controller)) {
    http_response_code(404);
    die('Controller not specified');
}

// Map of allowed controllers to their file paths
$controllers = [
    'procesarRegistroCliente' => '../controlador/procesarRegistroCliente.php',
    'procesarRegistroVendedor' => '../controlador/procesarRegistroVendedor.php',
    'procesarLoginCliente' => '../controlador/procesarLoginCliente.php',
    'procesarLoginVendedor' => '../controlador/procesarLoginVendedor.php',
    'procesarProducto' => '../controlador/procesarProducto.php',
    'actualizarProducto' => '../controlador/actualizarProducto.php',
    'confirmarOrden' => '../controlador/confirmarOrden.php',
    'verificarCuenta' => '../controlador/verificarCuenta.php',
    'verificarCuentaCliente' => '../controlador/verificarCuentaCliente.php'
];

// Check if controller exists
if (!isset($controllers[$controller])) {
    http_response_code(404);
    die('Controller not found');
}

$controllerPath = $controllers[$controller];

// Check if file exists
if (!file_exists($controllerPath)) {
    http_response_code(404);
    die('Controller file not found');
}

// Include and execute the controller
require_once $controllerPath;
?>
