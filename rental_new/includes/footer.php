    <!-- Footer content starts here -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>House Rental Management System</h5>
                    <p>A comprehensive solution for managing rental properties efficiently.</p>
                    <p>Version: <?php echo APP_VERSION; ?></p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-white">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/houses.php" class="text-white">Available Houses</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/about.php" class="text-white">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Information</h5>
                    <address>
                        <p><i class="fas fa-envelope mr-2"></i> <?php echo APP_EMAIL; ?></p>
                        <p><i class="fas fa-phone mr-2"></i> <?php echo APP_CONTACT; ?></p>
                    </address>
                    <div class="social-links">
                        <a href="#" class="text-white mr-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white mr-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white mr-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> House Rental Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php if(isset($page_specific_js)): ?>
    <!-- Page Specific JS -->
    <script src="<?php echo JS_PATH; ?>/<?php echo $page_specific_js; ?>.js"></script>
    <?php endif; ?>
    
    <script>
        // Global JS initialization
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Initialize popovers
            $('[data-toggle="popover"]').popover();
            
            // Initialize flatpickr for date inputs
            $(".date-picker").flatpickr({
                dateFormat: "Y-m-d",
                allowInput: true
            });
            
            // Initialize DataTables
            $('.data-table').DataTable({
                responsive: true,
                "language": {
                    "lengthMenu": "Show _MENU_ entries per page",
                    "zeroRecords": "No records found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No records available",
                    "infoFiltered": "(filtered from _MAX_ total records)"
                }
            });
        });
    </script>
</body>
</html> 