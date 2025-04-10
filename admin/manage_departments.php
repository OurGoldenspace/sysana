<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle department deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $department_id = $_POST['department_id'];
    try {
        $conn->beginTransaction();
        
        // Get department name
        $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$department_id]);
        $dept_name = $stmt->fetchColumn();
        
        // Update users and courses to remove department reference
        if ($dept_name) {
            $stmt = $conn->prepare("UPDATE users SET department = NULL WHERE department = ?");
            $stmt->execute([$dept_name]);
            
            $stmt = $conn->prepare("UPDATE courses SET department = NULL WHERE department = ?");
            $stmt->execute([$dept_name]);
        }
        
        // Delete the department
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$department_id]);
        
        $conn->commit();
        $success_message = "Department deleted successfully";
    } catch(PDOException $e) {
        $conn->rollBack();
        $error_message = "Error deleting department: " . $e->getMessage();
    }
}

// Fetch all departments with their stats
try {
    $stmt = $conn->prepare("
        SELECT d.*,
            (SELECT COUNT(*) FROM users WHERE department = d.name AND role = 'student') as student_count,
            (SELECT COUNT(*) FROM users WHERE department = d.name AND role = 'faculty') as faculty_count,
            (SELECT COUNT(*) FROM courses WHERE department = d.name) as course_count
        FROM departments d
        ORDER BY d.name
    ");
    $stmt->execute();
    $departments = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching departments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Departments</h1>
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
                <a href="add_department.php" class="btn btn-primary">Add New Department</a>
            </div>

            <div class="data-table-wrapper">
                <?php if (empty($departments)): ?>
                    <p>No departments found</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Description</th>
                                <th>Students</th>
                                <th>Faculty</th>
                                <th>Courses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($department['name']); ?></td>
                                    <td><?php echo htmlspecialchars($department['description']); ?></td>
                                    <td><?php echo $department['student_count']; ?></td>
                                    <td><?php echo $department['faculty_count']; ?></td>
                                    <td><?php echo $department['course_count']; ?></td>
                                    <td class="actions">
                                        <a href="edit_department.php?id=<?php echo $department['id']; ?>" 
                                           class="btn btn-secondary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this department? This will remove department references from all associated students, faculty, and courses.');">
                                            <input type="hidden" name="department_id" value="<?php echo $department['id']; ?>">
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