<?php
/**
 * URL Helper - Creates dynamic URLs that work for both localhost and Heroku
 */

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    if (strpos($host, 'herokuapp.com') !== false) {
        // Heroku deployment - no subdirectory needed
        return $protocol . $host;
    } else {
        // Local development - include project directory
        return $protocol . $host . '/ProyectoTiendaenLinea';
    }
}

function createUrl($path) {
    return getBaseUrl() . $path;
}

function createEmailLink($path, $params = []) {
    $url = getBaseUrl() . $path;
    if (!empty($params)) {
        $query = http_build_query($params);
        $url .= '?' . $query;
    }
    return $url;
}
?>
