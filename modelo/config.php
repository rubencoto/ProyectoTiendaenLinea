<?php
/**
 * Application Configuration
 * Centralized configuration for URLs and environment settings
 */

class AppConfig {
    // Your Heroku app URL - UPDATE THIS WITH YOUR ACTUAL HEROKU APP URL
    const HEROKU_APP_URL = 'https://reventazon-27b010286492.herokuapp.com';
    
    // Detect if we're running on Heroku
    public static function isHeroku() {
        return !empty($_SERVER['DYNO']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'herokuapp.com') !== false;
    }
    
    // Get the base URL for the application
    public static function getBaseUrl() {
        if (self::isHeroku()) {
            // On Heroku - use the configured Heroku URL
            return self::HEROKU_APP_URL;
        } else {
            // Local development - auto-detect
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            return $protocol . $host . '/ProyectoTiendaenLinea';
        }
    }
    
    // Create a full URL for a given path
    public static function url($path) {
        return self::getBaseUrl() . $path;
    }
    
    // Create URLs for email links with parameters
    public static function emailUrl($path, $params = []) {
        // Ensure path starts with / for proper URL construction
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        
        $url = self::getBaseUrl() . $path;
        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= '?' . $query;
        }
        return $url;
    }
    
    // Helper methods for common URLs
    public static function vistaUrl($file) {
        if (self::isHeroku()) {
            // On Heroku, vista/ is document root, so files are at root level
            return self::getBaseUrl() . '/' . $file;
        } else {
            // Local development - full path needed
            return self::url('/vista/' . $file);
        }
    }
    
    public static function controladorUrl($file) {
        if (self::isHeroku()) {
            // On Heroku, use the controller router
            $controller = str_replace('.php', '', $file);
            return self::getBaseUrl() . '/controller.php?c=' . $controller;
        } else {
            // Local development - full path needed
            return self::url('/controlador/' . $file);
        }
    }
    
    // For JavaScript redirects and navigation
    public static function redirectScript($url) {
        return "<script>window.location.href = '" . htmlspecialchars($url) . "';</script>";
    }
    
    // Handle relative links - in Heroku use full URLs, locally use relative paths
    public static function link($path) {
        if (self::isHeroku()) {
            // On Heroku, vista/ is the document root, so don't add /vista/ prefix
            if (strpos($path, '/') === 0) {
                return self::getBaseUrl() . $path;
            } else {
                return self::getBaseUrl() . '/' . $path;
            }
        } else {
            // Local development - keep relative paths simple
            return $path;
        }
    }
    
    // Force Heroku mode (useful for testing)
    public static function forceHeroku() {
        return self::HEROKU_APP_URL;
    }
}

// Convenience functions
function app_url($path = '') {
    return AppConfig::url($path);
}

function heroku_url($path = '') {
    return AppConfig::HEROKU_APP_URL . $path;
}
?>
