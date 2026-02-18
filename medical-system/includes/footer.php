        </div>
    <script>
        lucide.createIcons();

        window.addEventListener('load', () => {
            const preloader = document.getElementById('global-preloader');
            if (preloader) {
                // Delay slightly for smooth transition
                setTimeout(() => {
                    preloader.classList.add('hidden');
                }, 300);
            }
        });

        // Add preloader to all forms on submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                const preloader = document.getElementById('global-preloader');
                if (preloader) {
                    preloader.classList.remove('hidden');
                    preloader.querySelector('.loader-text').innerText = 'Обработка данных...';
                }
            });
        });
    </script>
</body>
</html>
