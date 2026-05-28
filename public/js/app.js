document.addEventListener('DOMContentLoaded', async () => {
    const content = document.getElementById('content');
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const demoToggleBtn = document.getElementById('demoToggleBtn');
    const startDemoBtn = document.getElementById('startDemoBtn');
    const demoBadge = document.getElementById('demoBadge');
    const demoConfig = document.getElementById('demoConfig');
    const eventLog = document.getElementById('eventLog');
    const eventCount = document.getElementById('eventCount');
    const navItems = document.querySelectorAll('.nav-item');

    let demoActive = false;
    let currentSection = 'devices';
    let events = [];

    // Navigation Logic
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            navItems.forEach(i => i.classList.remove('bg-slate-600', 'opacity-100'));
            item.classList.add('bg-slate-600', 'opacity-100');
            item.classList.remove('opacity-70');
            currentSection = item.dataset.section;
            renderContent();
            if (window.innerWidth < 768) sidebar.classList.add('-translate-x-full');
        });
    });

    menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));

    // Trigger Demo Scenario
    window.triggerScenario = async (type) => {
        await fetch(`${API_BASE}/system/demo/trigger?scenario=${type}`, { method: 'POST' });
        // Immediate UI feedback loop
        if (type === 'RESET') events = [];
        renderContent();
    };

    startDemoBtn.addEventListener('click', async () => {
        const template = document.getElementById('demoTemplate').value;
        const profile = document.getElementById('demoProfile').value;
        const status = await api.toggleDemo(true, template, profile);
        demoActive = status.active;
        updateDemoUI();
        startPolling();
    });

    demoToggleBtn.addEventListener('click', async () => {
        if (!demoActive) { demoConfig.scrollIntoView({ behavior: 'smooth' }); return; }
        const status = await api.toggleDemo(false);
        demoActive = status.active;
        updateDemoUI();
        stopPolling();
        renderContent();
    });

    function updateDemoUI() {
        if (demoActive) {
            demoToggleBtn.innerText = "Stop Demo";
            demoToggleBtn.classList.replace('bg-slate-700', 'bg-red-600');
            demoBadge.classList.remove('hidden');
            demoConfig.classList.add('hidden');
        } else {
            demoToggleBtn.innerText = "Demo Mode";
            demoToggleBtn.classList.replace('bg-red-600', 'bg-slate-700');
            demoBadge.classList.add('hidden');
            demoConfig.classList.remove('hidden');
        }
    }

    async function renderContent() {
        content.innerHTML = components.renderEmptyState('Опрос системы...');
        let html = '';

        if (demoActive) {
            html += components.renderLiveControl();
        }

        try {
            if (currentSection === 'devices') {
                const devices = await api.getDevices();
                html += devices.length ? devices.map(d => components.renderDeviceCard(d)).join('') : components.renderEmptyState('Устройства не найдены.');
            } else if (currentSection === 'scenarios') {
                const scenarios = await api.getScenarios();
                html += scenarios.length ? scenarios.map(s => components.renderScenarioCard(s)).join('') : components.renderEmptyState('Сценарии не найдены.');
            } else if (currentSection === 'diagnostics') {
                const ports = await api.getPorts();
                const hwStatus = await api.getHardwareStatus();
                html += components.renderHardwareConnect(ports, hwStatus);
                const diag = await api.getDiagnostics();
                html += components.renderDiagnostics(diag);
            }
            content.innerHTML = html;
            if (currentSection === 'diagnostics') setupHardwareListeners();
        } catch (err) {
            content.innerHTML = components.renderEmptyState('Ошибка связи: ' + err.message);
        }
    }

    function setupHardwareListeners() {
        const connectBtn = document.getElementById('connectBtn');
        if (connectBtn) {
            connectBtn.addEventListener('click', async () => {
                await api.connectPort(document.getElementById('portSelect').value);
                renderContent();
            });
        }
        const scanBtn = document.getElementById('scanBtn');
        if (scanBtn) {
            scanBtn.addEventListener('click', async () => {
                api.scanDevices();
                renderContent();
                setTimeout(renderContent, 3000);
            });
        }
    }

    function addEventToLog(event) {
        events.unshift(event);
        if (events.length > 50) events.pop();
        eventCount.innerText = events.length;
        const typeColors = { 'ALARM': 'text-red-600 font-bold', 'FIRE': 'text-orange-600 font-bold', 'RELAY_STATUS': 'text-blue-500 italic font-medium' };
        eventLog.innerHTML = events.map(e => `
            <div class="flex justify-between border-b border-slate-50 py-1">
                <span class="text-[9px] text-gray-400 w-16 uppercase">${new Date().toLocaleTimeString()}</span>
                <span class="flex-1 px-2 ${typeColors[e.type] || 'text-slate-600'}">${e.type}</span>
                <span class="text-[10px] text-slate-400 font-mono">${e.type==='RELAY_STATUS' ? `Rel: ${e.relay_id}` : `Z: ${e.zone_id}`}</span>
            </div>
        `).join('');
    }

    let pollInterval;
    function startPolling() {
        renderContent();
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            if (demoActive) {
               const demoEvent = { type: ['ALARM', 'FIRE', 'RESTORE'][Math.floor(Math.random()*3)], device_addr: 1, zone_id: Math.floor(Math.random()*5)+1 };
               addEventToLog(demoEvent);
               if (currentSection !== 'diagnostics') renderContent();
            }
        }, 5000);
    }
    function stopPolling() { clearInterval(pollInterval); }

    const status = await api.getDemoStatus();
    demoActive = status.active;
    updateDemoUI();
    if (demoActive) startPolling(); else renderContent();
});
