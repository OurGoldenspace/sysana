<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;

if (!$course_id) {
    header("Location: my_courses.php");
    exit();
}

// Verify this course belongs to the faculty
try {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $faculty_id]);
    $course = $stmt->fetch();

    if (!$course) {
        header("Location: my_courses.php");
        exit();
    }
} catch(PDOException $e) {
    $error_message = "Error verifying course: " . $e->getMessage();
}

// Handle enrollment status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    try {
        $stmt = $conn->prepare("UPDATE enrollments SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['enrollment_id']]);
        $success_message = "Enrollment status updated successfully";
    } catch(PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Fetch enrolled students
try {
    $stmt = $conn->prepare("
        SELECT u.username, u.email, e.id as enrollment_id, e.status, e.enrollment_date
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        WHERE e.course_id = ?
        ORDER BY u.username
    ");
    $stmt->execute([$course_id]);
    $enrollments = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching enrollments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - <?php echo htmlspecialchars($course['course_code']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Students in <?php echo htmlspecialchars($course['course_code']); ?></h1>
            <nav>
                <ul>
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <li><a href="my_courses.php">Back to Courses</a></li>
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

            <div class="admin-grid">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enrollment['username']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['enrollment_date']); ?></td>
                            <td><span class="status-<?php echo $enrollment['status']; ?>"><?php echo ucfirst($enrollment['status']); ?></span></td>
                            <td>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $enrollment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $enrollment['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $enrollment['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html> 