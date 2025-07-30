<?php
/**
 * Heroku Configuration Setup Script
 * Run this script to configure your Heroku app URL and test the configuration
 */

require_once 'modelo/config.php';

echo "<h2>🚀 Heroku Configuration Setup</h2>";

// Check current configuration
echo "<h3>Current Configuration:</h3>";
echo "Heroku Detection: " . (AppConfig::isHeroku() ? "✅ Detected" : "❌ Not detected") . "<br>";
echo "Current Base URL: " . AppConfig::getBaseUrl() . "<br>";

// Instructions
echo "<h3>📋 Setup Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Update your Heroku app URL:</strong><br>";
echo "   Edit <code>modelo/config.php</code> and replace <code>https://your-app-name.herokuapp.com</code><br>";
echo "   with your actual Heroku app URL (e.g., <code>https://mi-tienda-online.herokuapp.com</code>)</li>";
echo "<li><strong>Deploy to Heroku:</strong><br>";
echo "   <code>git add .</code><br>";
echo "   <code>git commit -m \"Configure Heroku URLs\"</code><br>";
echo "   <code>git push heroku main</code></li>";
echo "<li><strong>Test the configuration:</strong><br>";
echo "   Visit your Heroku app and test email verification and password recovery</li>";
echo "</ol>";

// Test URL generation with examples
echo "<h3>🧪 URL Generation Test:</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Function</th><th>Generated URL</th></tr>";
echo "<tr><td>AppConfig::url('/vista/index.php')</td><td>" . AppConfig::url('/vista/index.php') . "</td></tr>";
echo "<tr><td>AppConfig::vistaUrl('loginCliente.php')</td><td>" . AppConfig::vistaUrl('loginCliente.php') . "</td></tr>";
echo "<tr><td>AppConfig::controladorUrl('procesarLogin.php')</td><td>" . AppConfig::controladorUrl('procesarLogin.php') . "</td></tr>";
echo "<tr><td>AppConfig::emailUrl('/vista/verificarCuentaCliente.php', ['codigo' => '123456'])</td><td>" . AppConfig::emailUrl('/vista/verificarCuentaCliente.php', ['codigo' => '123456']) . "</td></tr>";
echo "</table>";

// Environment info
echo "<h3>🔍 Environment Info:</h3>";
echo "Server Info:<br>";
echo "- HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";
echo "- DYNO: " . ($_SERVER['DYNO'] ?? 'Not set') . "<br>";
echo "- PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'Not set') . "<br>";

// Quick configuration form
echo "<h3>⚡ Quick URL Update:</h3>";
echo "<form method='POST'>";
echo "<label>Enter your Heroku app URL:</label><br>";
echo "<input type='text' name='heroku_url' placeholder='https://your-app-name.herokuapp.com' style='width: 400px; padding: 8px;'><br><br>";
echo "<input type='submit' name='update_url' value='Update Configuration' style='padding: 10px 20px; background: #007185; color: white; border: none; cursor: pointer;'>";
echo "</form>";

// Handle form submission
if (isset($_POST['update_url']) && !empty($_POST['heroku_url'])) {
    $newUrl = rtrim($_POST['heroku_url'], '/');
    
    if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
        // Read the config file
        $configPath = 'modelo/config.php';
        $configContent = file_get_contents($configPath);
        
        // Replace the URL
        $configContent = preg_replace(
            "/const HEROKU_APP_URL = '[^']*';/",
            "const HEROKU_APP_URL = '" . $newUrl . "';",
            $configContent
        );
        
        // Write back to file
        if (file_put_contents($configPath, $configContent)) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb;'>";
            echo "✅ Configuration updated successfully!<br>";
            echo "New URL: " . $newUrl . "<br>";
            echo "Please refresh this page to see the changes.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
            echo "❌ Failed to update configuration file.";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;'>";
        echo "⚠️ Please enter a valid URL (must start with http:// or https://)";
        echo "</div>";
    }
}

echo "<br><br>";
echo "<a href='vista/index.php' style='background: #007185; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Main Site</a>";
echo " ";
echo "<a href='test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Test Pages</a>";
?>
