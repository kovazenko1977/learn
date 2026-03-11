            </div> <!-- .content-body -->
        </main>
    </div> <!-- .app-container -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('mobile-toggle');
            const sidebar = document.querySelector('.sidebar');

            if (toggle && sidebar) {
                toggle.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (e) => {
                if (sidebar.classList.contains('active') &&
                    !sidebar.contains(e.target) &&
                    !toggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
