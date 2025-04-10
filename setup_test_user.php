<?php
require_once 'config/database.php';

// Create test password and hash
$password = 'test123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Remove existing student1
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute(['student1']);

    // Create new student1
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['student1', 'student1@example.com', $hash, 'student']);

    echo "Test user created successfully!\n";
    echo "Username: student1\n";
    echo "Password: $password\n";
    echo "Hash: $hash\n";

    // Verify it works
    echo "\nTesting verification:\n";
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute(['student1']);
    $stored = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Verification result: " . (password_verify($password, $stored['password']) ? "SUCCESS" : "FAILED") . "\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 