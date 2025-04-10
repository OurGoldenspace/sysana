<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch student information
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
            (SELECT COUNT(*) FROM enrollments WHERE student_id = u.id) as enrolled_courses,
            (SELECT SUM(c.credits) 
             FROM enrollments e 
             JOIN courses c ON e.course_id = c.id 
             WHERE e.student_id = u.id) as total_credits,
            (SELECT COUNT(*) FROM enrollments 
             WHERE student_id = u.id AND status = 'pending') as pending_enrollments
        FROM users u
        WHERE u.id = ? AND u.role = 'student'
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    // Fetch recent enrollments
    $stmt = $conn->prepare("
        SELECT e.*, c.course_name, c.course_code
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.student_id = ?
        ORDER BY e.enrollment_date DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_enrollments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="dashboard-body">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" class="sidebar-logo">
                <h3>Student Portal</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="course_registration.php">
                    <i class="fas fa-book"></i> Course Registration
                </a>
                <a href="my_courses.php">
                    <i class="fas fa-graduation-cap"></i> My Courses
                </a>
                <a href="../profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="../auth/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-welcome">
                    <h1>Welcome, <?php echo htmlspecialchars($student['username']); ?>!</h1>
                    <p class="text-muted"><?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="header-actions">
                    <a href="course_registration.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Register New Course
                    </a>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Enrolled Courses</h3>
                        <p class="stat-number"><?php echo $student['enrolled_courses']; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Credits</h3>
                        <p class="stat-number"><?php echo $student['total_credits'] ?? 0; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Pending Enrollments</h3>
                        <p class="stat-number"><?php echo $student['pending_enrollments']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <section class="dashboard-section">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <div class="activity-list">
                    <?php if (empty($recent_enrollments)): ?>
                        <p class="no-data">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recent_enrollments as $enrollment): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="activity-details">
                                    <h4><?php echo htmlspecialchars($enrollment['course_code']); ?></h4>
                                    <p><?php echo htmlspecialchars($enrollment['course_name']); ?></p>
                                    <span class="activity-date">
                                        <?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?>
                                    </span>
                                </div>
                                <div class="activity-status">
                                    <span class="status-badge <?php echo $enrollment['status']; ?>">
                                        <?php echo ucfirst($enrollment['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>

</html>