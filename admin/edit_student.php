<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header("Location: manage_students.php");
    exit();
}

// Fetch departments for dropdown
try {
    $stmt = $conn->query("SELECT name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $error_message = "Error fetching departments: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $enrollment_number = trim($_POST['enrollment_number']);
    $department = trim($_POST['department']);
    $new_password = trim($_POST['new_password']);

    try {
        // Check if username/email/enrollment exists for other users
        $stmt = $conn->prepare("
            SELECT id FROM users 
            WHERE (username = ? OR email = ? OR enrollment_number = ?) 
            AND id != ? AND role = 'student'
        ");
        $stmt->execute([$username, $email, $enrollment_number, $student_id]);
        
        if ($stmt->fetch()) {
            $error_message = "Username, email, or enrollment number already exists";
        } else {
            // Update student info
            if ($new_password) {
                $sql = "UPDATE users SET username = ?, email = ?, enrollment_number = ?, 
                        department = ?, password = ? WHERE id = ? AND role = 'student'";
                $params = [$username, $email, $enrollment_number, $department, 
                          password_hash($new_password, PASSWORD_DEFAULT), $student_id];
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, enrollment_number = ?, 
                        department = ? WHERE id = ? AND role = 'student'";
                $params = [$username, $email, $enrollment_number, $department, $student_id];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $success_message = "Student updated successfully";
        }
    } catch(PDOException $e) {
        $error_message = "Error updating student: " . $e->getMessage();
    }
}

// Fetch student data
try {
    $stmt = $conn->prepare("
        SELECT u.*, d.name as department_name
        FROM users u
        LEFT JOIN departments d ON u.department = d.name
        WHERE u.id = ? AND u.role = 'student'
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header("Location: manage_students.php");
        exit();
    }
} catch(PDOException $e) {
    $error_message = "Error fetching student data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Student</h1>
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

            <form method="POST" class="form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($student['username']); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($student['email']); ?>">
                </div>

                <div class="form-group">
                    <label for="enrollment_number">Enrollment Number:</label>
                    <input type="text" id="enrollment_number" name="enrollment_number" required
                           value="<?php echo htmlspecialchars($student['enrollment_number']); ?>">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep current):</label>
                    <input type="password" id="new_password" name="new_password">
                </div>

                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"
                                    <?php echo ($student['department'] === $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Student</button>
                    <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <div class="enrollments-section">
                <h2>Course Enrollments</h2>
                <?php
                try {
                    $stmt = $conn->prepare("
                        SELECT e.*, c.course_code, c.course_name, c.credits
                        FROM enrollments e
                        JOIN courses c ON e.course_id = c.id
                        WHERE e.student_id = ?
                        ORDER BY e.enrollment_date DESC
                    ");
                    $stmt->execute([$student_id]);
                    $enrollments = $stmt->fetchAll();
                ?>
                    <?php if (empty($enrollments)): ?>
                        <p>No course enrollments found</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Status</th>
                                    <th>Grade</th>
                                    <th>Enrollment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                        <td><?php echo $enrollment['credits']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $enrollment['status']; ?>">
                                                <?php echo ucfirst($enrollment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $enrollment['grade'] ?? 'N/A'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php
                } catch(PDOException $e) {
                    echo "<p class='error'>Error fetching enrollments: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html> 