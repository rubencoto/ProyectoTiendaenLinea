<?php
// Root index.php - Entry point for the application
// This file redirects to the main application in the vista directory

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to the main application
header("Location: vista/index.php");
exit;
?>
