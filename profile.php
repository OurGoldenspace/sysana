<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        // Update basic info
        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $user_id]);

        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
        }

        $conn->commit();
        $success_message = "Profile updated successfully";
        
        // Update session username
        $_SESSION['username'] = $username;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT u.*, d.name as department_name
        FROM users u
        LEFT JOIN departments d ON u.department = d.name
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching user data: " . $e->getMessage();
}

$page_title = "Profile Settings";
require_once 'includes/header.php';
?>

<div class="dashboard-container">
    <header class="main-header">
        <h1><i class="fas fa-user-circle"></i> Profile Settings</h1>
        <p>Manage your account settings and preferences</p>
    </header>

    <nav class="nav-bar">
        <ul>
            <li>
                <a href="<?php echo $_SESSION['role']; ?>/dashboard.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </li>
        </ul>
    </nav>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- Profile Information -->
        <div class="dashboard-card">
            <h2><i class="fas fa-id-card"></i> Account Information</h2>
            <form method="POST" class="form" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required>
                </div>

                <?php if ($user['role'] == 'student'): ?>
                    <div class="form-group">
                        <label>Enrollment Number</label>
                        <input type="text" 
                               value="<?php echo htmlspecialchars($user['enrollment_number']); ?>" 
                               disabled>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text" 
                           value="<?php echo htmlspecialchars($user['department_name'] ?? 'Not Assigned'); ?>" 
                           disabled>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" 
                           value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" 
                           disabled>
                </div>

                <hr class="form-divider">

                <h3><i class="fas fa-lock"></i> Change Password</h3>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-help">Leave blank to keep current password</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Account Status -->
        <div class="dashboard-card">
            <h2><i class="fas fa-info-circle"></i> Account Status</h2>
            <div class="info-group">
                <label>Account Created</label>
                <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="info-group">
                <label>Last Updated</label>
                <span><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        button.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 