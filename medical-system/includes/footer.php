        </div>
    <script>
        lucide.createIcons();

        const hidePreloader = () => {
            const preloader = document.getElementById('global-preloader');
            if (preloader && !preloader.classList.contains('hidden')) {
                preloader.classList.add('hidden');
            }
        };

        // Try hiding on DOMContentLoaded (faster)
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(hidePreloader, 800); // Small buffer for Lucide icons
        });

        // Definitely hide on window load
        window.addEventListener('load', () => {
            setTimeout(hidePreloader, 100);
        });

        // Extreme Failsafe: hide preloader after 3 seconds regardless of load state
        setTimeout(hidePreloader, 3000);

        // Add preloader to all forms on submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                // Ignore if it's a simple search form or something that shouldn't block
                if (form.classList.contains('no-preloader')) return;

                // Form validation check
                if (form.checkValidity && !form.checkValidity()) return;

                const preloader = document.getElementById('global-preloader');
                if (preloader) {
                    preloader.classList.remove('hidden');
                    preloader.querySelector('.loader-text').innerText = 'Обработка данных...';

                    // Safety timeout for forms (if it stays on page)
                    setTimeout(hidePreloader, 10000);
                }
            });
        });

        // Hide preloader if user navigates back/forward (Safari fix)
        window.onpageshow = function(event) {
            if (event.persisted) hidePreloader();
        };

        // Unified Period Persistence Logic
        document.addEventListener('DOMContentLoaded', function() {
            const daysAheadInput = document.getElementById('days_ahead');
            const savePeriodCheckbox = document.getElementById('save_period');
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');
            const form = daysAheadInput?.closest('form');

            if (!daysAheadInput) return;

            // Key includes user ID and page path for "separately for each" and "depends on profile"
            const pageId = window.location.pathname.split('/').pop() || 'index.php';
            const storageKey = `period_${window.WES_USER_ID || 'guest'}_${pageId}`;

            const savedDays = localStorage.getItem(storageKey);
            if (savedDays !== null) {
                daysAheadInput.value = savedDays;
                savePeriodCheckbox.checked = true;

                const urlParams = new URLSearchParams(window.location.search);
                // Auto-apply only if URL doesn't have explicit dates
                if (!urlParams.has('start_date') && !urlParams.has('end_date')) {
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

            // Real-time date adjustment when typing number of days
            daysAheadInput.addEventListener('input', function() {
                const val = parseInt(this.value);
                if (!isNaN(val) && val >= 0) {
                    const start = new Date();
                    const end = new Date();
                    end.setDate(start.getDate() + val);
                    if (startDateInput) startDateInput.value = start.toISOString().split('T')[0];
                    if (endDateInput) endDateInput.value = end.toISOString().split('T')[0];
                }
            });

            if (form) {
                form.addEventListener('submit', function() {
                    if (savePeriodCheckbox.checked) {
                        localStorage.setItem(storageKey, daysAheadInput.value);
                    } else {
                        localStorage.removeItem(storageKey);
                    }
                });
            }
        });
    </script>
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--win-border); text-align: center;">
        <p style="font-size: 0.75rem; color: var(--win-text-secondary); opacity: 0.7;">© WES.BY — Коваженко С.Б., 2024</p>
    </div>

    <!-- Global Toast System -->
    <div id="win-toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 10002; display: flex; flex-direction: column; gap: 10px;"></div>

    <style>
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>

    <script>
        window.showToast = function(message, type = 'info') {
            const container = document.getElementById('win-toast-container');
            const toast = document.createElement('div');
            toast.className = 'mica-effect';
            toast.style.cssText = `
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 0.9rem;
                min-width: 250px;
                animation: slideInRight 0.3s cubic-bezier(0.1, 0.9, 0.2, 1);
                border-left: 4px solid ${type === 'success' ? '#107c10' : (type === 'error' ? '#d13438' : '#0078d4')};
            `;

            const icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-circle' : 'info');
            toast.innerHTML = `
                <i data-lucide="${icon}" style="width:18px; height:18px; color: ${type === 'success' ? '#107c10' : (type === 'error' ? '#d13438' : '#0078d4')}"></i>
                <div style="flex-grow:1;">${message}</div>
                <button onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; opacity:0.5;"><i data-lucide="x" style="width:14px; height:14px;"></i></button>
            `;

            container.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                toast.style.transition = 'all 0.5s ease';
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        };

    </script>
</body>
</html>
