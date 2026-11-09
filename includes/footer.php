    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3 class="footer-heading">🔧 Car Workshop</h3>
                    <p class="footer-desc">Professional automotive diagnostics, repairs, and scheduled maintenance services by certified senior mechanics.</p>
                </div>
                <div class="footer-col">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo $base_path ?? './'; ?>index.php">Book Appointment</a></li>
                        <li><a href="<?php echo $base_path ?? './'; ?>about.php">About Our Team</a></li>
                        <li><a href="<?php echo $base_path ?? './'; ?>help.php">Help & Guidelines</a></li>
                        <li><a href="<?php echo $base_path ?? './'; ?>admin/login.php">Admin Portal</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-heading">Contact Information</h4>
                    <ul class="footer-contact">
                        <li><strong>📍 Address:</strong> 123 Workshop Avenue, Industrial Area, Dhaka</li>
                        <li><strong>📞 Phone:</strong> +880 1700-000000 / +880 1800-000000</li>
                        <li><strong>✉️ Email:</strong> service@carworkshop.local</li>
                        <li><strong>🕒 Working Hours:</strong> Sat – Thu: 8:00 AM – 6:00 PM (Friday Closed)</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Car Workshop Appointment Management System. CSE 391 Assignment.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo $base_path ?? './'; ?>assets/js/script.js"></script>
</body>
</html>
