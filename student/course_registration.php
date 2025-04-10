<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $course_id = $_POST['course_id'];
    
    try {
        // Check if already enrolled
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);
        
        if ($stmt->fetch()) {
            $error_message = "You are already enrolled in this course";
        } else {
            // Check course capacity
            $stmt = $conn->prepare("
                SELECT c.capacity, COUNT(e.id) as enrolled_count 
                FROM courses c 
                LEFT JOIN enrollments e ON c.id = e.course_id 
                WHERE c.id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();

            if ($course && $course['enrolled_count'] < $course['capacity']) {
                // Enroll the student
                $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $stmt->execute([$student_id, $course_id]);
                $success_message = "Successfully registered for the course";
            } else {
                $error_message = "Course is at full capacity";
            }
        }
    } catch(PDOException $e) {
        $error_message = "Error registering for course: " . $e->getMessage();
    }
}

// Fetch available courses
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username as instructor_name, d.name as department_name,
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_count
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        LEFT JOIN departments d ON c.department = d.name
        WHERE c.id NOT IN (
            SELECT course_id FROM enrollments WHERE student_id = ?
        )
        ORDER BY c.department, c.course_code
    ");
    $stmt->execute([$student_id]);
    $available_courses = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching courses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Course Registration</h1>
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
                <?php if (empty($available_courses)): ?>
                    <div class="no-courses">No available courses to register</div>
                <?php else: ?>
                    <?php foreach ($available_courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                            <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                            
                            <p class="description"><?php echo htmlspecialchars($course['description']); ?></p>
                            
                            <div class="course-details">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($course['department_name']); ?></p>
                                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></p>
                                <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                <p><strong>Available Seats:</strong> <?php echo $course['capacity'] - $course['enrolled_count']; ?> of <?php echo $course['capacity']; ?></p>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" name="register" class="btn btn-primary" 
                                        <?php echo ($course['enrolled_count'] >= $course['capacity']) ? 'disabled' : ''; ?>>
                                    <?php echo ($course['enrolled_count'] >= $course['capacity']) ? 'Course Full' : 'Register'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 