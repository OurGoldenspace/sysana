    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="/assets/frontend/img/logo.png" alt="Logo" class="footer-logo">
                    <p>Course Registration System</p>
                </div>
                <div class="footer-links">
                    <div class="footer-section">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="/about.php">About</a></li>
                            <li><a href="/contact.php">Contact</a></li>
                            <li><a href="/help.php">Help</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="/faq.php">FAQ</a></li>
                            <li><a href="/terms.php">Terms of Service</a></li>
                            <li><a href="/privacy.php">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Course Registration System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/assets/frontend/js/main.js"></script>
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 