<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student's enrolled courses
$stmt = $conn->prepare("
    SELECT c.*, e.status, e.enrollment_date, u.username as instructor_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE e.student_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$user_id]);
$enrollments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Course Registration System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>My Courses</h1>
            <nav>
                <a href="../dashboard.php">Dashboard</a>
                <a href="available.php">Available Courses</a>
                <a href="my_courses.php">My Courses</a>
                <a href="../profile.php">Profile</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Course enrollment request submitted successfully!
            </div>
        <?php endif; ?>

        <main class="courses-grid">
            <?php foreach ($enrollments as $enrollment): ?>
                <div class="course-card">
                    <h3><?php echo htmlspecialchars($enrollment['course_code']); ?></h3>
                    <h4><?php echo htmlspecialchars($enrollment['course_name']); ?></h4>
                    <p class="description"><?php echo htmlspecialchars($enrollment['description']); ?></p>
                    <div class="course-details">
                        <p><strong>Credits:</strong> <?php echo $enrollment['credits']; ?></p>
                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($enrollment['instructor_name']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-<?php echo $enrollment['status']; ?>">
                                <?php echo ucfirst($enrollment['status']); ?>
                            </span>
                        </p>
                        <p><strong>Enrolled:</strong> 
                            <?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($enrollments)): ?>
                <p class="no-courses">You haven't enrolled in any courses yet.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 