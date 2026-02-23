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

        // Reports Period Logic
        document.addEventListener('DOMContentLoaded', function() {
            const daysAheadInput = document.getElementById('days_ahead');
            const savePeriodCheckbox = document.getElementById('save_period');
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');
            const form = daysAheadInput?.closest('form');

            if (!daysAheadInput) return;

            const savedDays = localStorage.getItem('reports_days_ahead');
            if (savedDays) {
                daysAheadInput.value = savedDays;
                savePeriodCheckbox.checked = true;

                const urlParams = new URLSearchParams(window.location.search);
                if (!urlParams.has('start_date')) {
                     const start = new Date();
                     const end = new Date();
                     end.setDate(start.getDate() + parseInt(savedDays));
                     const startStr = start.toISOString().split('T')[0];
                     const endStr = end.toISOString().split('T')[0];

                     const url = new URL(window.location);
                     url.searchParams.set('start_date', startStr);
                     url.searchParams.set('end_date', endStr);
                     window.location.href = url.toString();
                }
            }

            daysAheadInput.addEventListener('input', function() {
                if (this.value >= 0 && this.value !== '') {
                    const start = new Date();
                    const end = new Date();
                    end.setDate(start.getDate() + parseInt(this.value));
                    if (startDateInput) startDateInput.value = start.toISOString().split('T')[0];
                    if (endDateInput) endDateInput.value = end.toISOString().split('T')[0];
                }
            });

            if (form) {
                form.addEventListener('submit', function() {
                    if (savePeriodCheckbox.checked) {
                        localStorage.setItem('reports_days_ahead', daysAheadInput.value);
                    } else {
                        localStorage.removeItem('reports_days_ahead');
                    }
                });
            }
        });
    </script>
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--win-border); text-align: center;">
        <p style="font-size: 0.75rem; color: var(--win-text-secondary); opacity: 0.7;">разработчик wes.by</p>
    </div>
</body>
</html>
