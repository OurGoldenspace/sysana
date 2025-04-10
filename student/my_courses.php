<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Handle course drop
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['drop'])) {
    $enrollment_id = $_POST['enrollment_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ? AND student_id = ?");
        $stmt->execute([$enrollment_id, $student_id]);
        $success_message = "Successfully dropped the course";
    } catch(PDOException $e) {
        $error_message = "Error dropping course: " . $e->getMessage();
    }
}

// Fetch enrolled courses
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username as instructor_name, d.name as department_name,
            e.id as enrollment_id, e.status, e.grade,
            e.enrollment_date
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN users u ON c.instructor_id = u.id
        LEFT JOIN departments d ON c.department = d.name
        WHERE e.student_id = ?
        ORDER BY e.status, c.department, c.course_code
    ");
    $stmt->execute([$student_id]);
    $enrolled_courses = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching enrolled courses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Courses</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="course_registration.php">Course Registration</a></li>
                    <li><a href="my_courses.php">My Courses</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="courses-grid">
                <?php if (empty($enrolled_courses)): ?>
                    <div class="no-courses">You are not enrolled in any courses</div>
                <?php else: ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="course-card">
                            <div class="status-badge <?php echo $course['status']; ?>">
                                <?php echo ucfirst($course['status']); ?>
                            </div>
                            
                            <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                            <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                            
                            <p class="description"><?php echo htmlspecialchars($course['description']); ?></p>
                            
                            <div class="course-details">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($course['department_name']); ?></p>
                                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></p>
                                <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                <p><strong>Enrolled:</strong> <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></p>
                                <?php if ($course['grade']): ?>
                                    <p><strong>Grade:</strong> <?php echo htmlspecialchars($course['grade']); ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if ($course['status'] !== 'approved'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to drop this course?');">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $course['enrollment_id']; ?>">
                                    <button type="submit" name="drop" class="btn btn-danger">Drop Course</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 