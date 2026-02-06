<?php
// Generate a fresh password hash for your system
$password = '123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Generator</h2>";
echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Generated Hash:</strong></p>";
echo "<textarea style='width:100%; height:100px; font-family:monospace;'>$hash</textarea>";

echo "<h3>Verification Test:</h3>";
if (password_verify($password, $hash)) {
    echo "<p style='color:green;'>✓ Verification works! This hash is correct.</p>";
} else {
    echo "<p style='color:red;'>✗ Verification failed!</p>";
}

echo "<h3>SQL Query to Update:</h3>";
echo "<textarea style='width:100%; height:120px; font-family:monospace;'>";
echo "UPDATE users SET password = '$hash' WHERE username = 'admin';";
echo "</textarea>";

echo "<h3>PHP Info:</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Password Algorithm: " . PASSWORD_DEFAULT . "</p>";

// Test the hash from database
echo "<h3>Test Database Hash:</h3>";
$dbHash = '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yAipmMyYp2';
if (password_verify('123', $dbHash)) {
    echo "<p style='color:green;'>✓ The database hash SHOULD work with '123'</p>";
} else {
    echo "<p style='color:red;'>✗ The database hash does NOT work with '123'</p>";
}

// Test with different passwords
echo "<h3>Test Different Passwords:</h3>";
$testPasswords = ['123', '321', 'admin', 'admin123', 'password', '12345'];
foreach ($testPasswords as $testPwd) {
    $testHash = password_hash($testPwd, PASSWORD_DEFAULT);
    $verify = password_verify($testPwd, $testHash);
    echo "<p>Password: <strong>$testPwd</strong> - Hash works: " . ($verify ? '✓ YES' : '✗ NO') . "</p>";
}
?>