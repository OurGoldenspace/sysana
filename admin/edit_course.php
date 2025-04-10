<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    header("Location: manage_courses.php");
    exit();
}

// Fetch departments and faculty for dropdowns
try {
    $departments = $conn->query("SELECT name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $faculty = $conn->query("
        SELECT id, username, email 
        FROM users 
        WHERE role = 'faculty' 
        ORDER BY username
    ")->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $department = trim($_POST['department']);
    $description = trim($_POST['description']);
    $credits = (int)$_POST['credits'];
    $capacity = (int)$_POST['capacity'];
    $instructor_id = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;

    try {
        // Check if course code exists for other courses
        $stmt = $conn->prepare("
            SELECT id FROM courses 
            WHERE course_code = ? AND id != ?
        ");
        $stmt->execute([$course_code, $course_id]);
        
        if ($stmt->fetch()) {
            $error_message = "Course code already exists";
        } else {
            // Update course
            $stmt = $conn->prepare("
                UPDATE courses 
                SET course_code = ?, course_name = ?, department = ?, 
                    description = ?, credits = ?, capacity = ?, instructor_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $course_code, $course_name, $department, $description,
                $credits, $capacity, $instructor_id, $course_id
            ]);
            
            $success_message = "Course updated successfully";
        }
    } catch(PDOException $e) {
        $error_message = "Error updating course: " . $e->getMessage();
    }
}

// Fetch course data
try {
    $stmt = $conn->prepare("
        SELECT c.*, d.name as department_name
        FROM courses c
        LEFT JOIN departments d ON c.department = d.name
        WHERE c.id = ?
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        header("Location: manage_courses.php");
        exit();
    }
} catch(PDOException $e) {
    $error_message = "Error fetching course data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Course</h1>
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
                    <label for="course_code">Course Code:</label>
                    <input type="text" id="course_code" name="course_code" required 
                           value="<?php echo htmlspecialchars($course['course_code']); ?>">
                </div>

                <div class="form-group">
                    <label for="course_name">Course Name:</label>
                    <input type="text" id="course_name" name="course_name" required
                           value="<?php echo htmlspecialchars($course['course_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"
                                    <?php echo ($course['department'] === $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?php 
                        echo htmlspecialchars($course['description']); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="credits">Credits:</label>
                    <input type="number" id="credits" name="credits" required min="1" max="6"
                           value="<?php echo htmlspecialchars($course['credits']); ?>">
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity:</label>
                    <input type="number" id="capacity" name="capacity" required min="1"
                           value="<?php echo htmlspecialchars($course['capacity']); ?>">
                </div>

                <div class="form-group">
                    <label for="instructor_id">Instructor:</label>
                    <select id="instructor_id" name="instructor_id">
                        <option value="">Select Instructor</option>
                        <?php foreach ($faculty as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>"
                                    <?php echo ($course['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($instructor['username']); ?> 
                                (<?php echo htmlspecialchars($instructor['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Course</button>
                    <a href="manage_courses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <div class="enrollments-section">
                <h2>Course Enrollments</h2>
                <?php
                try {
                    $stmt = $conn->prepare("
                        SELECT e.*, u.username as student_name, u.enrollment_number
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.id
                        WHERE e.course_id = ?
                        ORDER BY e.enrollment_date DESC
                    ");
                    $stmt->execute([$course_id]);
                    $enrollments = $stmt->fetchAll();
                ?>
                    <?php if (empty($enrollments)): ?>
                        <p>No enrollments found</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Enrollment Number</th>
                                    <th>Status</th>
                                    <th>Grade</th>
                                    <th>Enrollment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['enrollment_number']); ?></td>
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