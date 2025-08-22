<?php
// Run this script to create the admin user with correct hash

$host = 'localhost';
$dbname = 'ebook_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create correct password hash
$correctPassword = 'Library.2025';
$hashedPassword = password_hash($correctPassword, PASSWORD_DEFAULT);

// Delete existing user and create new one
try {
    // Delete existing
    $pdo->prepare("DELETE FROM users WHERE username = 'LIBRARIAN-2025'")->execute();
    
    // Insert new
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute(['LIBRARIAN-2025', 'admin@library.com', $hashedPassword]);
    
    echo "✅ Admin user created successfully!<br>";
    echo "Username: LIBRARIAN-2025<br>";
    echo "Password: Library.2025<br>";
    echo "Hash: " . $hashedPassword . "<br>";
    
    // Test the login
    $testStmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
    $testStmt->execute(['LIBRARIAN-2025']);
    $user = $testStmt->fetch();
    
    if ($user && password_verify($correctPassword, $user['password'])) {
        echo "✅ Password verification test PASSED!<br>";
        echo "Login should work now.";
    } else {
        echo "❌ Something is still wrong.";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>