    </main>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Loading indicator logic
        window.addEventListener('beforeunload', function() {
            document.getElementById('loading-overlay').style.opacity = '1';
            document.getElementById('loading-overlay').style.pointerEvents = 'all';
        });

        // Hide loading on page load
        window.addEventListener('load', function() {
            document.getElementById('loading-overlay').style.opacity = '0';
            document.getElementById('loading-overlay').style.pointerEvents = 'none';
        });

        // Form submission loading
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loading-overlay').style.opacity = '1';
                document.getElementById('loading-overlay').style.pointerEvents = 'all';
            });
        });
    </script>
</body>
</html>
