</div><!-- .main-content -->

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/ef9baa832e.js"></script>

<script>
    // Sidebar Toggle Script
    // Moved to footer to ensure DOM is fully loaded
    (function () {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const closeBtn = document.getElementById('sidebar-close');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent bubbling
                // Check if elements exist
                if (sidebar) sidebar.classList.toggle('collapsed');
                if (mainContent) mainContent.classList.toggle('expanded');
                console.log('Sidebar toggled');
            });
        } else {
            console.error('Sidebar toggle button not found');
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (sidebar) sidebar.classList.remove('active');
            });
        }
    })();
</script>
</body>

</html>