<?php
$host = 'localhost';
$dbname = 'course_registration';
$username = 'root';  // default XAMPP username
$password = '';      // default XAMPP password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Remove or comment out this debug line
    // echo "Database connected successfully<br>";
} catch(PDOException $e) {
    if ($e->getCode() == 1049) { // Database doesn't exist
        header("Location: ../database/setup.php");
        exit();
    }
    die("Connection failed: " . $e->getMessage());
}
?> 