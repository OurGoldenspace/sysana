<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

// Fetch assigned courses
try {
    $stmt = $conn->prepare("
        SELECT c.*, 
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_count
        FROM courses c
        WHERE c.instructor_id = ?
        ORDER BY c.course_code
    ");
    $stmt->execute([$faculty_id]);
    $courses = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching courses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Faculty Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Courses</h1>
            <nav>
                <ul>
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <li><a href="enrollments.php">Manage Enrollments</a></li>
                    <li><a href="../auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="courses-grid">
                <?php if (empty($courses)): ?>
                    <div class="no-courses">No courses assigned to you</div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                            <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                            
                            <p class="description"><?php echo htmlspecialchars($course['description']); ?></p>
                            
                            <div class="course-details">
                                <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                <p><strong>Enrolled Students:</strong> <?php echo $course['enrolled_count']; ?> of <?php echo $course['capacity']; ?></p>
                            </div>

                            <div class="course-actions">
                                <a href="view_students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">View Students</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 