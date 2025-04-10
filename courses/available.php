<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all available courses that the student hasn't enrolled in yet
$stmt = $conn->prepare("
    SELECT c.*, u.username as instructor_name,
    (c.capacity - (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'approved')) as available_slots
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE c.id NOT IN (
        SELECT course_id FROM enrollments WHERE student_id = ?
    )
    HAVING available_slots > 0
");
$stmt->execute([$user_id]);
$available_courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses - Course Registration System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Available Courses</h1>
            <nav>
                <a href="../dashboard.php">Dashboard</a>
                <a href="available.php">Available Courses</a>
                <a href="my_courses.php">My Courses</a>
                <a href="../profile.php">Profile</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </header>

        <main class="courses-grid">
            <?php foreach ($available_courses as $course): ?>
                <div class="course-card">
                    <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                    <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                    <p class="description"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="course-details">
                        <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                        <p><strong>Available Slots:</strong> <?php echo $course['available_slots']; ?></p>
                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                    </div>
                    <form action="enroll.php" method="POST">
                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <button type="submit" class="enroll-btn">Enroll</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (empty($available_courses)): ?>
                <p class="no-courses">No available courses found.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 