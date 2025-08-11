<?php
session_start();

// Include config for proper URL handling
require_once '../modelo/config.php';

// Check user type before destroying session
$is_cliente = isset($_SESSION['cliente_id']);
$is_vendedor = isset($_SESSION['vendedor_id']) || isset($_SESSION['id']); // vendedor uses both session variables

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, también hay que borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir según el tipo de usuario usando AppConfig
if ($is_cliente) {
    $redirect_url = AppConfig::vistaUrl('loginCliente.php');
} elseif ($is_vendedor) {
    $redirect_url = AppConfig::vistaUrl('loginVendedor.php');
} else {
    // Default fallback - redirect to main page
    $redirect_url = AppConfig::vistaUrl('index.php');
}

header('Location: ' . $redirect_url);
exit;
?>
