<?php
$password = "student123";
$new_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Use these SQL commands:\n\n";
echo "-- First delete existing student1\n";
echo "DELETE FROM users WHERE username = 'student1';\n\n";
echo "-- Then create new student1 with fresh hash\n";
echo "INSERT INTO users (username, email, password, role) VALUES (\n";
echo "    'student1',\n";
echo "    'student1@example.com',\n";
echo "    '" . $new_hash . "',\n";
echo "    'student'\n";
echo ");\n";

// Verify the hash works
echo "\nVerifying hash works:\n";
echo "Password: $password\n";
echo "Hash: $new_hash\n";
echo "Verification result: " . (password_verify($password, $new_hash) ? "matched" : "not matched") . "\n";
?> 