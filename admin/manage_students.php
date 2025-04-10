<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle student deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $student_id = $_POST['student_id'];
    try {
        $conn->beginTransaction();
        
        // Delete enrollments first
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Then delete the student
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$student_id]);
        
        $conn->commit();
        $success_message = "Student deleted successfully";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error_message = "Error deleting student: " . $e->getMessage();
    }
}

// Fetch all students with their department and enrollment info
try {
    $stmt = $conn->prepare("
        SELECT u.*, d.name as department_name,
            (SELECT COUNT(*) FROM enrollments WHERE student_id = u.id) as total_enrollments
        FROM users u
        LEFT JOIN departments d ON u.department = d.name
        WHERE u.role = 'student'
        ORDER BY u.username
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Students</h1>
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
                <a href="add_student.php" class="btn btn-primary">Add New Student</a>
            </div>

            <div class="data-table-wrapper">
                <?php if (empty($students)): ?>
                    <p>No students found</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Enrollment Number</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Enrollments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['enrollment_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo $student['total_enrollments']; ?></td>
                                    <td class="actions">
                                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="view_student.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-secondary btn-sm">View</a>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this student?');">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
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