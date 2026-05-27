document.addEventListener('DOMContentLoaded', async () => {
    const content = document.getElementById('content');
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');

    // Toggle Sidebar
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });

    // Close sidebar on click outside on mobile
    document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !menuBtn.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.add('-translate-x-full');
        }
    });

    // Initial Load
    async function loadDevices() {
        content.innerHTML = components.renderEmptyState('Загрузка устройств...');
        try {
            const devices = await api.getDevices();
            if (devices.length === 0) {
                content.innerHTML = components.renderEmptyState('Устройства не найдены');
            } else {
                content.innerHTML = devices.map(d => components.renderDeviceCard(d)).join('');
            }
        } catch (err) {
            content.innerHTML = components.renderEmptyState('Ошибка загрузки: ' + err.message);
        }
    }

    loadDevices();
});
