</div><!-- .main-content -->

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/ef9baa832e.js"></script>

<script>
    // Sidebar Toggle Script
    // Defined globally to ensure onclick attribute works
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if (sidebar) sidebar.classList.toggle('collapsed');
        if (mainContent) mainContent.classList.toggle('expanded');
        console.log('Sidebar toggled manually');
    }

    // Mobile close handler
    document.addEventListener('DOMContentLoaded', function() {
        const closeBtn = document.getElementById('sidebar-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const sidebar = document.getElementById('sidebar');
                if (sidebar) sidebar.classList.remove('active');
            });
        }
    });
</script>
</body>

</html>