    </div> <!-- End of content -->
</div> <!-- End of admin-layout -->

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.6/js/dataTables.responsive.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

<!-- Admin Panel JavaScript -->
<script>
$(document).ready(function() {
    // Toggle sidebar on mobile
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('show');
    });
    
    // Initialize DataTables
    if($.fn.DataTable) {
        $('.dataTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    }
    
    // Prevent Bootstrap dropdown from closing on click inside
    $('.dropdown-menu.keep-open').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
    
    // Auto-dismiss alerts
    $('.alert-dismissible.auto-dismiss').each(function() {
        const $alert = $(this);
        setTimeout(function() {
            $alert.alert('close');
        }, 5000);
    });
    
    // Form validation styling
    $('.needs-validation').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>

<!-- Page specific scripts -->
<?php if(isset($page_scripts)): ?>
<script>
<?php echo $page_scripts; ?>
</script>
<?php endif; ?>

</body>
</html>
