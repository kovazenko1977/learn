document.addEventListener('DOMContentLoaded', async () => {
    const content = document.getElementById('content');
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const demoBtn = document.getElementById('demoBtn');
    const demoBadge = document.getElementById('demoBadge');
    const eventLog = document.getElementById('eventLog');
    const eventCount = document.getElementById('eventCount');

    let demoActive = false;
    let events = [];

    // Toggle Sidebar
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });

    // Toggle Demo Mode
    demoBtn.addEventListener('click', async () => {
        demoActive = !demoActive;
        await api.toggleDemo(demoActive);
        updateDemoUI();
        if (demoActive) {
            startPolling();
        } else {
            stopPolling();
            loadDevices();
        }
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

    async function loadDevices() {
        content.innerHTML = components.renderEmptyState('Загрузка устройств...');
        try {
            const devices = await api.getDevices();
            if (devices.length === 0) {
                content.innerHTML = components.renderEmptyState('Устройства не найдены. Включите Demo Mode для эмуляции.');
            } else {
                content.innerHTML = devices.map(d => components.renderDeviceCard(d)).join('');
            }
        } catch (err) {
            content.innerHTML = components.renderEmptyState('Ошибка загрузки: ' + err.message);
        }
    }

    function addEventToLog(event) {
        events.unshift(event);
        if (events.length > 50) events.pop();

        eventCount.innerText = events.length;

        const typeColors = {
            'ALARM': 'text-red-600 font-bold',
            'FIRE': 'text-orange-600 font-bold',
            'FAULT': 'text-yellow-600',
            'RESTORE': 'text-green-600',
            'TAMPER': 'text-purple-600'
        };

        const eventHtml = events.map(e => `
            <div class="flex justify-between border-b border-slate-50 py-1 animate-fadeIn">
                <span class="text-gray-400 w-16 text-[10px]">${new Date().toLocaleTimeString()}</span>
                <span class="flex-1 px-2 ${typeColors[e.type] || 'text-slate-600'}">${e.type}</span>
                <span class="text-slate-400 text-xs text-right">Устр: ${e.device_addr}, Зона: ${e.zone_id}</span>
            </div>
        `).join('');

        eventLog.innerHTML = eventHtml;
    }

    let pollInterval;
    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        loadDevices();
        pollInterval = setInterval(async () => {
            // In a real app we'd fetch actual new events
            // For demo, we simulate receipt
            const demoEvent = {
                type: ['ALARM', 'FIRE', 'RESTORE', 'FAULT'][Math.floor(Math.random()*4)],
                device_addr: Math.floor(Math.random()*5)+1,
                zone_id: Math.floor(Math.random()*10)+1
            };
            addEventToLog(demoEvent);
            if (Math.random() > 0.8) loadDevices();
        }, 3000);
    }

    function stopPolling() {
        clearInterval(pollInterval);
    }

    // Initial State
    const status = await api.getDemoStatus();
    demoActive = status.active;
    updateDemoUI();
    if (demoActive) startPolling(); else loadDevices();
});
