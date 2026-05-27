document.addEventListener('DOMContentLoaded', async () => {
    const content = document.getElementById('content');
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const demoBtn = document.getElementById('demoBtn');
    const demoBadge = document.getElementById('demoBadge');
    const eventLog = document.getElementById('eventLog');
    const eventCount = document.getElementById('eventCount');
    const navItems = document.querySelectorAll('.nav-item');

    let demoActive = false;
    let currentSection = 'devices';
    let events = [];

    // Navigation
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            navItems.forEach(i => i.classList.remove('bg-slate-600', 'opacity-100'));
            navItems.forEach(i => i.classList.add('opacity-70'));
            item.classList.add('bg-slate-600', 'opacity-100');
            item.classList.remove('opacity-70');
            currentSection = item.dataset.section;
            renderContent();
            if (window.innerWidth < 768) sidebar.classList.add('-translate-x-full');
        });
    });

    // Toggle Sidebar
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));

    // Toggle Demo Mode
    demoBtn.addEventListener('click', async () => {
        demoActive = !demoActive;
        await api.toggleDemo(demoActive);
        updateDemoUI();
        if (demoActive) startPolling(); else { stopPolling(); renderContent(); }
    });

    function updateDemoUI() {
        if (demoActive) {
            demoBtn.innerText = "Stop Demo";
            demoBtn.classList.replace('bg-slate-700', 'bg-red-600');
            demoBadge.classList.remove('hidden');
        } else {
            demoBtn.innerText = "Demo Mode";
            demoBtn.classList.replace('bg-red-600', 'bg-slate-700');
            demoBadge.classList.add('hidden');
        }
    }

    async function renderContent() {
        content.innerHTML = components.renderEmptyState('Загрузка...');
        try {
            if (currentSection === 'devices') {
                const devices = await api.getDevices();
                content.innerHTML = devices.length ? devices.map(d => components.renderDeviceCard(d)).join('') : components.renderEmptyState('Устройства не найдены. Включите Demo Mode.');
            } else if (currentSection === 'scenarios') {
                const scenarios = await api.getScenarios();
                content.innerHTML = scenarios.length ? scenarios.map(s => components.renderScenarioCard(s)).join('') : components.renderEmptyState('Сценарии не найдены.');
            } else {
                content.innerHTML = components.renderEmptyState(`Раздел ${currentSection} в разработке...`);
            }
        } catch (err) {
            content.innerHTML = components.renderEmptyState('Ошибка: ' + err.message);
        }
    }

    function addEventToLog(event) {
        events.unshift(event);
        if (events.length > 50) events.pop();
        eventCount.innerText = events.length;
        const typeColors = { 'ALARM': 'text-red-600 font-bold', 'FIRE': 'text-orange-600 font-bold', 'RELAY_STATUS': 'text-blue-500 italic' };
        eventLog.innerHTML = events.map(e => `
            <div class="flex justify-between border-b border-slate-50 py-1">
                <span class="text-[10px] text-gray-400 w-16">${new Date().toLocaleTimeString()}</span>
                <span class="flex-1 px-2 ${typeColors[e.type] || 'text-slate-600'}">${e.type}</span>
                <span class="text-[10px] text-slate-400">${e.type==='RELAY_STATUS' ? `Relay: ${e.relay_id} -> ${e.state}` : `Zone: ${e.zone_id}`}</span>
            </div>
        `).join('');
    }

    let pollInterval;
    function startPolling() {
        renderContent();
        pollInterval = setInterval(async () => {
            const demoEvent = { type: ['ALARM', 'FIRE', 'RESTORE'][Math.floor(Math.random()*3)], device_addr: 1, zone_id: 1 };
            addEventToLog(demoEvent);
            if (Math.random() > 0.8) renderContent();
        }, 5000);
    }
    function stopPolling() { clearInterval(pollInterval); }

    const status = await api.getDemoStatus();
    demoActive = status.active;
    updateDemoUI();
    if (demoActive) startPolling(); else renderContent();
});
