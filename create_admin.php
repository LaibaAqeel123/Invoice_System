<?php
require_once 'config.php';

// Generate password hash in PHP (no escaping issues)
$username = 'admin';
$password = '123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Creating Admin User</h2>";
echo "Username: $username<br>";
echo "Password: $password<br>";
echo "Hash generated: <code>$passwordHash</code><br><br>";

// Delete existing admin
$conn->query("DELETE FROM users WHERE username = 'admin'");
echo "Old admin deleted<br>";

// Insert new admin using prepared statement
$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $passwordHash);

if ($stmt->execute()) {
    echo "<p style='color:green; font-size:20px;'>✅ SUCCESS! Admin user created!</p>";
    echo "<p>Now try logging in with:<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>123</strong></p>";
    
    // Verify it works
    $checkStmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $user = $result->fetch_assoc();
    
    echo "<hr><h3>Verification:</h3>";
    echo "Stored hash: <code>" . $user['password'] . "</code><br>";
    $verified = password_verify($password, $user['password']);
    echo "Password verification: " . ($verified ? "✅ WORKS!" : "❌ FAILED") . "<br>";
    
} else {
    echo "<p style='color:red;'>❌ ERROR: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();

echo "<br><br><a href='login.php'>Go to Login Page</a>";
?>