    </main>

    <script>
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.opacity = '0';
                setTimeout(() => { preloader.style.display = 'none'; }, 500);
            }
        });

        // Global toast notification system
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `win-card mica-effect toast status-${type === 'success' ? 'green' : 'red'}`;
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '10000';
            toast.style.padding = '10px 20px';
            toast.innerHTML = message;
            document.body.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3000);
        }
    </script>
</body>
</html>
