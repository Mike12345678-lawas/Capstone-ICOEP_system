            </main>
        </div><!-- /.main-content -->
    </div><!-- /.app-layout -->
    
    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo INSTITUTION_NAME; ?>. All rights reserved.</p>
            <p class="footer-sub"><?php echo APP_FULL_NAME; ?> v<?php echo APP_VERSION; ?> &bull; 
                <a href="<?php echo BASE_URL; ?>/privacy/policy.php">Privacy Policy</a>
            </p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <?php if (isset($extraJS)): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $extraJS; ?>"></script>
    <?php endif; ?>
</body>
</html>
