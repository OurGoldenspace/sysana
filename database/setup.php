<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Create connection without database selected
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $conn->exec($sql);
    
    // Read and execute sample_data.sql
    $sql = file_get_contents(__DIR__ . '/sample_data.sql');
    $conn->exec($sql);
    
    echo "Database and tables created successfully with sample data!";
    
} catch(PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?> 