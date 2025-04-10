<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle course deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $course_id = $_POST['course_id'];
    try {
        $conn->beginTransaction();
        
        // Delete enrollments first
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE course_id = ?");
        $stmt->execute([$course_id]);
        
        // Then delete the course
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        
        $conn->commit();
        $success_message = "Course deleted successfully";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error_message = "Error deleting course: " . $e->getMessage();
    }
}

// Fetch all courses with their department and enrollment info
try {
    $stmt = $conn->prepare("
        SELECT c.*, d.name as department_name, u.username as instructor_name,
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_students
        FROM courses c
        LEFT JOIN departments d ON c.department = d.name
        LEFT JOIN users u ON c.instructor_id = u.id
        ORDER BY c.department, c.course_code
    ");
    $stmt->execute();
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
    <title>Manage Courses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Courses</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_students.php">Manage Students</a></li>
                    <li><a href="manage_courses.php">Manage Courses</a></li>
                    <li><a href="manage_departments.php">Manage Departments</a></li>
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

            <div class="actions-bar">
                <a href="add_course.php" class="btn btn-primary">Add New Course</a>
            </div>

            <div class="data-table-wrapper">
                <?php if (empty($courses)): ?>
                    <p>No courses found</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Instructor</th>
                                <th>Credits</th>
                                <th>Capacity</th>
                                <th>Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['department_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo $course['credits']; ?></td>
                                    <td><?php echo $course['capacity']; ?></td>
                                    <td><?php echo $course['enrolled_students']; ?></td>
                                    <td class="actions">
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="view_course.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-secondary btn-sm">View</a>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this course?');">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 