<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>

<nav class="main-nav">
    <div class="nav-brand">
        <a href="/">
            <img src="/assets/frontend/img/logo.png" alt="Logo" class="nav-logo">
            <span>Course Registration</span>
        </a>
    </div>

    <div class="nav-links">
        <?php if ($role === 'student'): ?>
            <a href="/student/dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="/student/course_registration.php" class="<?php echo isActive('course_registration.php'); ?>">
                <i class="fas fa-book"></i>
                <span>Course Registration</span>
            </a>
            <a href="/student/my_courses.php" class="<?php echo isActive('my_courses.php'); ?>">
                <i class="fas fa-graduation-cap"></i>
                <span>My Courses</span>
            </a>
        <?php elseif ($role === 'faculty'): ?>
            <!-- Faculty navigation items -->
        <?php elseif ($role === 'admin'): ?>
            <!-- Admin navigation items -->
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="nav-user">
            <div class="user-menu">
                <button class="user-menu-button" onclick="toggleUserMenu()">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu-dropdown" id="userMenu">
                    <a href="/profile.php">
                        <i class="fas fa-user-cog"></i>
                        Profile Settings
                    </a>
                    <a href="/auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</nav> 