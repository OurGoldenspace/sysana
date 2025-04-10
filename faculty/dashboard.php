<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

// Get faculty's courses with enrollment counts
$stmt = $conn->prepare("
    SELECT c.*, 
    (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'approved') as enrolled_count,
    (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'pending') as pending_count
    FROM courses c
    WHERE c.instructor_id = ?
    ORDER BY c.course_code
");
$stmt->execute([$faculty_id]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Course Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Faculty Dashboard</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="enrollments.php">Manage Enrollments</a>
                <a href="../profile.php">Profile</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </header>

        <main class="dashboard-content">
            <section class="courses-overview">
                <h2>My Courses</h2>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                            <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                            <p class="description"><?php echo htmlspecialchars($course['description']); ?></p>
                            <div class="course-details">
                                <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                <p><strong>Capacity:</strong> <?php echo $course['capacity']; ?></p>
                                <p><strong>Enrolled:</strong> <?php echo $course['enrolled_count']; ?></p>
                                <?php if ($course['pending_count'] > 0): ?>
                                    <p class="pending-requests">
                                        <strong>Pending Requests:</strong> 
                                        <span class="badge"><?php echo $course['pending_count']; ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="course-actions">
                                <a href="view_students.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-primary">View Students</a>
                                <a href="manage_enrollments.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-secondary">Manage Enrollments</a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($courses)): ?>
                        <p class="no-courses">You are not currently teaching any courses.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html> 