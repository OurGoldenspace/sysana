<?php
require_once 'config/database.php';

try {
    // First delete existing student1 if exists
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute(['student1']);

    // Create new student with fresh hash
    $username = 'student1';
    $email = 'student1@example.com';
    $password = 'student123';
    $role = 'student';

    // Generate fresh hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hash, $role]);

    echo "Student created successfully!\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Hash: $hash\n";

    // Verify the hash works
    echo "\nVerifying password works: ";
    echo password_verify($password, $hash) ? "YES" : "NO";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 