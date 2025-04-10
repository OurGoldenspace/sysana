<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Course Registration</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <?php if ($role === 'admin'): ?>
                <section class="admin-section">
                    <h2>Admin Dashboard</h2>
                    <div class="dashboard-actions">
                        <a href="admin/manage_users.php" class="dashboard-card">
                            <h3>Manage Users</h3>
                            <p>Add, edit, or remove users</p>
                        </a>
                        <a href="admin/manage_courses.php" class="dashboard-card">
                            <h3>Manage Courses</h3>
                            <p>Add, edit, or remove courses</p>
                        </a>
                        <a href="admin/enrollments.php" class="dashboard-card">
                            <h3>View Enrollments</h3>
                            <p>Manage student enrollments</p>
                        </a>
                    </div>
                </section>

            <?php elseif ($role === 'faculty'): ?>
                <section class="faculty-section">
                    <h2>Faculty Dashboard</h2>
                    <div class="dashboard-actions">
                        <a href="faculty/my_courses.php" class="dashboard-card">
                            <h3>My Courses</h3>
                            <p>View and manage your courses</p>
                        </a>
                        <a href="faculty/students.php" class="dashboard-card">
                            <h3>Students</h3>
                            <p>View enrolled students</p>
                        </a>
                    </div>
                </section>

            <?php else: ?>
                <section class="student-section">
                    <h2>Student Dashboard</h2>
                    <div class="dashboard-actions">
                        <a href="student/course_registration.php" class="dashboard-card">
                            <h3>Course Registration</h3>
                            <p>Browse and register for courses</p>
                        </a>
                        <a href="student/my_courses.php" class="dashboard-card">
                            <h3>My Courses</h3>
                            <p>View your enrolled courses</p>
                        </a>
                        <a href="student/schedule.php" class="dashboard-card">
                            <h3>Schedule</h3>
                            <p>View your class schedule</p>
                        </a>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 