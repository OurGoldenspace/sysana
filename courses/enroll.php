<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $student_id = $_SESSION['user_id'];
    $course_id = $_POST['course_id'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Check if already enrolled
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);
        if ($stmt->fetch()) {
            throw new Exception("You are already enrolled in this course.");
        }

        // Check available slots
        $stmt = $conn->prepare("
            SELECT c.capacity, COUNT(e.id) as enrolled
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'approved'
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        if ($course && $course['enrolled'] >= $course['capacity']) {
            throw new Exception("This course is full.");
        }

        // Create enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments (student_id, course_id, status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$student_id, $course_id]);

        $conn->commit();
        header("Location: my_courses.php?success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: available.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: available.php");
exit();
?> 