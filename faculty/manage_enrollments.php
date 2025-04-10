<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'];

// Verify this course belongs to the faculty
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $faculty_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: dashboard.php");
    exit();
}

// Handle enrollment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enrollment_id'], $_POST['action'])) {
    try {
        $enrollment_id = $_POST['enrollment_id'];
        $action = $_POST['action'];
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $conn->prepare("UPDATE enrollments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $enrollment_id]);

        header("Location: manage_enrollments.php?course_id=" . $course_id . "&success=Enrollment updated");
        exit();
    } catch (Exception $e) {
        $error = "Error updating enrollment status";
    }
}

// Get enrollment requests
$stmt = $conn->prepare("
    SELECT e.*, u.username, u.email
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    WHERE e.course_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$course_id]);
$enrollments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrollments - <?php echo htmlspecialchars($course['course_code']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Manage Enrollments: <?php echo htmlspecialchars($course['course_name']); ?></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="enrollments.php">Manage Enrollments</a>
                <a href="../profile.php">Profile</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <main class="admin-grid">
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
                            <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                            <td>
                                <span class="status-<?php echo $enrollment['status']; ?>">
                                    <?php echo ucfirst($enrollment['status']); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if ($enrollment['status'] === 'pending'): ?>
                                    <form action="manage_enrollments.php?course_id=<?php echo $course_id; ?>" 
                                          method="POST" class="inline-form">
                                        <input type="hidden" name="enrollment_id" 
                                               value="<?php echo $enrollment['id']; ?>">
                                        <button type="submit" name="action" value="approve" 
                                                class="btn btn-small btn-success">Approve</button>
                                        <button type="submit" name="action" value="reject" 
                                                class="btn btn-small btn-delete">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No enrollment requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html> 