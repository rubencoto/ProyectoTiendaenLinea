<?php
require_once '../modelo/conexion.php';

echo "<h2>🗑️ Quick Database Cleanup Tool</h2>";

// Delete all unverified accounts
if (isset($_GET['delete_all_unverified'])) {
    $stmt = $conn->prepare("DELETE FROM clientes WHERE verificado = 0");
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #28a745;'>";
        echo "✅ <strong>Success!</strong> Deleted $deleted_count unverified accounts.";
        echo "</div>";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #dc3545;'>";
        echo "❌ <strong>Error!</strong> Could not delete accounts: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
}

// Delete by specific email
if (isset($_POST['delete_email']) && !empty($_POST['delete_email'])) {
    $email_to_delete = $_POST['delete_email'];
    $stmt = $conn->prepare("DELETE FROM clientes WHERE correo = ?");
    $stmt->bind_param("s", $email_to_delete);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        if ($deleted_count > 0) {
            echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #28a745;'>";
            echo "✅ <strong>Success!</strong> Deleted account with email: " . htmlspecialchars($email_to_delete);
            echo "</div>";
        } else {
            echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #ffc107;'>";
            echo "⚠️ <strong>Warning!</strong> No account found with email: " . htmlspecialchars($email_to_delete);
            echo "</div>";
        }
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #dc3545;'>";
        echo "❌ <strong>Error!</strong> Could not delete account: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
}

// Show current unverified count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM clientes WHERE verificado = 0");
$stmt->execute();
$result = $stmt->get_result();
$unverified_count = $result->fetch_assoc()['count'];
$stmt->close();

// Show total count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM clientes");
$stmt->execute();
$result = $stmt->get_result();
$total_count = $result->fetch_assoc()['count'];
$stmt->close();

echo "<div style='background-color: #e7f3ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #007bff;'>";
echo "📊 <strong>Database Stats:</strong><br>";
echo "• Total clients: $total_count<br>";
echo "• Unverified clients: $unverified_count<br>";
echo "• Verified clients: " . ($total_count - $unverified_count);
echo "</div>";

?>

<div style="max-width: 600px; margin: 20px 0;">
    <h3>🧹 Cleanup Options</h3>
    
    <!-- Delete all unverified -->
    <div style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
        <h4 style="color: #dc3545;">⚠️ Delete All Unverified Accounts</h4>
        <p>This will permanently delete all accounts that haven't been verified yet.</p>
        <a href="?delete_all_unverified=1" 
           style="background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;"
           onclick="return confirm('⚠️ WARNING: This will permanently delete ALL unverified accounts (<?= $unverified_count ?> accounts). This cannot be undone. Are you sure?')">
           🗑️ Delete All Unverified (<?= $unverified_count ?> accounts)
        </a>
    </div>
    
    <!-- Delete specific email -->
    <div style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
        <h4 style="color: #fd7e14;">🎯 Delete Specific Account</h4>
        <p>Enter an email address to delete that specific account (verified or unverified).</p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete the account with this email?')">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="email" name="delete_email" placeholder="user@example.com" 
                       style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 3px;" required>
                <button type="submit" 
                        style="background-color: #fd7e14; color: white; padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer;">
                    🗑️ Delete Account
                </button>
            </div>
        </form>
    </div>
    
    <!-- Navigation -->
    <div style="margin: 20px 0; padding: 15px; border: 1px solid #28a745; border-radius: 5px; background-color: #f8fff9;">
        <h4 style="color: #28a745;">🔗 Quick Links</h4>
        <a href="manage_database.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">📋 View All Accounts</a>
        <a href="registroCliente.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">➕ Test Registration</a>
        <a href="loginCliente.php" style="color: #007bff; text-decoration: none;">🔑 Client Login</a>
    </div>
</div>

<style>
body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
}
h2, h3, h4 { margin-top: 0; }
</style>
