<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
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
        // Check if course code already exists
        $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $stmt->execute([$course_code]);
        
        if ($stmt->fetch()) {
            $error_message = "Course code already exists";
        } else {
            // Create new course
            $stmt = $conn->prepare("
                INSERT INTO courses (course_code, course_name, department, description, 
                                   credits, capacity, instructor_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$course_code, $course_name, $department, $description, 
                          $credits, $capacity, $instructor_id]);
            
            $success_message = "Course added successfully";
            // Clear form data on success
            $_POST = array();
        }
    } catch(PDOException $e) {
        $error_message = "Error adding course: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Add New Course</h1>
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
                           value="<?php echo htmlspecialchars($_POST['course_code'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="course_name">Course Name:</label>
                    <input type="text" id="course_name" name="course_name" required
                           value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"
                                    <?php echo (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?php 
                        echo htmlspecialchars($_POST['description'] ?? ''); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="credits">Credits:</label>
                    <input type="number" id="credits" name="credits" required min="1" max="6"
                           value="<?php echo htmlspecialchars($_POST['credits'] ?? '3'); ?>">
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity:</label>
                    <input type="number" id="capacity" name="capacity" required min="1"
                           value="<?php echo htmlspecialchars($_POST['capacity'] ?? '30'); ?>">
                </div>

                <div class="form-group">
                    <label for="instructor_id">Instructor:</label>
                    <select id="instructor_id" name="instructor_id">
                        <option value="">Select Instructor</option>
                        <?php foreach ($faculty as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>"
                                    <?php echo (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($instructor['username']); ?> 
                                (<?php echo htmlspecialchars($instructor['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Course</button>
                    <a href="manage_courses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html> 