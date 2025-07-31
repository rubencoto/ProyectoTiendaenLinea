<?php
echo "Available PDO Drivers:\n";
foreach (PDO::getAvailableDrivers() as $driver) {
    echo "- " . $driver . "\n";
}
?>
