<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch summary statistics
try {
    // Get total counts
    $stats = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
            (SELECT COUNT(*) FROM users WHERE role = 'faculty') as total_faculty,
            (SELECT COUNT(*) FROM courses) as total_courses,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'pending') as pending_enrollments,
            (SELECT COUNT(*) FROM departments) as total_departments
    ")->fetch(PDO::FETCH_ASSOC);

    // Get recent enrollments
    $recent_enrollments = $conn->query("
        SELECT e.*, u.username as student_name, c.course_code, c.course_name
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN courses c ON e.course_id = c.id
        WHERE e.status = 'pending'
        ORDER BY e.enrollment_date DESC
        LIMIT 5
    ")->fetchAll();

} catch(PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
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
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Students</h3>
                    <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                    <a href="manage_students.php" class="btn btn-secondary">Manage Students</a>
                </div>

                <div class="stat-card">
                    <h3>Faculty</h3>
                    <div class="stat-number"><?php echo $stats['total_faculty']; ?></div>
                    <a href="manage_faculty.php" class="btn btn-secondary">Manage Faculty</a>
                </div>

                <div class="stat-card">
                    <h3>Courses</h3>
                    <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                    <a href="manage_courses.php" class="btn btn-secondary">Manage Courses</a>
                </div>

                <div class="stat-card">
                    <h3>Departments</h3>
                    <div class="stat-number"><?php echo $stats['total_departments']; ?></div>
                    <a href="manage_departments.php" class="btn btn-secondary">Manage Departments</a>
                </div>
            </div>

            <div class="dashboard-actions">
                <div class="action-card">
                    <h3>Quick Actions</h3>
                    <div class="button-group">
                        <a href="add_student.php" class="btn btn-primary">Add New Student</a>
                        <a href="add_course.php" class="btn btn-primary">Add New Course</a>
                        <a href="add_department.php" class="btn btn-primary">Add New Department</a>
                    </div>
                </div>

                <div class="action-card">
                    <h3>Pending Enrollments (<?php echo $stats['pending_enrollments']; ?>)</h3>
                    <?php if (empty($recent_enrollments)): ?>
                        <p>No pending enrollments</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($enrollment['course_code']); ?> - 
                                            <?php echo htmlspecialchars($enrollment['course_name']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td>
                                            <a href="manage_enrollment.php?id=<?php echo $enrollment['id']; ?>" 
                                               class="btn btn-secondary btn-sm">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="manage_enrollments.php" class="btn btn-link">View All Enrollments</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 