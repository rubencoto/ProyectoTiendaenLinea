<?php
function obtenerURLNgrok() {
    $json = @file_get_contents("http://127.0.0.1:4040/api/tunnels");

    if (!$json) return null;

    $data = json_decode($json, true);
    
    foreach ($data['tunnels'] as $tunnel) {
        if (strpos($tunnel['public_url'], 'https://') === 0) {
            return $tunnel['public_url'];
        }
    }

    return null;
}
?>
