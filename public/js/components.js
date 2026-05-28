const components = {
    renderDeviceCard(device) {
        // Status simulation
        const isAlarm = [1, 2, 5].includes(device.address % 12);
        const isFault = [8, 10].includes(device.address % 15);

        let borderColor = "border-blue-500";
        let statusText = "НОРМА";
        let statusColor = "text-slate-400";

        if (isAlarm) { borderColor = "border-red-500"; statusText = "ТРЕВОГА"; statusColor = "text-red-500 font-bold"; }
        else if (isFault) { borderColor = "border-yellow-500"; statusText = "НЕИСПР."; statusColor = "text-yellow-600 font-bold"; }

        return `
            <div class="device-card bg-white p-4 rounded-lg shadow hover:shadow-md cursor-pointer border-l-4 ${borderColor} animate-fadeIn transition-all duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-slate-800">${device.name}</h3>
                        <p class="text-[9px] uppercase tracking-widest text-slate-400 font-bold mt-1">${device.type}</p>
                    </div>
                    <span class="bg-slate-100 text-slate-500 text-[9px] px-2 py-0.5 rounded font-mono font-bold">ADDR: ${device.address}</span>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between">
                    <span class="text-[10px] ${statusColor}">${statusText}</span>
                    <button class="text-[10px] bg-slate-50 px-2 py-1 rounded hover:bg-slate-100 font-bold text-slate-400 uppercase tracking-tighter">Config</button>
                </div>
            </div>
        `;
    },

    renderScenarioCard(scenario) {
        return `
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500 animate-fadeIn">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-purple-50 text-purple-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div>
                    <h3 class="font-bold text-slate-800">${scenario.name}</h3>
                </div>
                <div class="mt-4 flex justify-between items-center text-[10px]">
                    <span class="text-slate-400 font-bold uppercase tracking-widest italic">Scenario Active</span>
                    <button class="text-purple-600 font-bold">Параметры</button>
                </div>
            </div>
        `;
    },

    renderHardwareConnect(ports, status) {
        return `
            <div class="col-span-full bg-white p-6 rounded-2xl shadow-sm border animate-fadeIn mb-6">
                <h2 class="text-lg font-bold mb-6 flex items-center text-slate-800"><svg class="w-5 h-5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg> Линия связи</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <select id="portSelect" class="w-full p-3 bg-slate-50 rounded-xl border border-slate-100 outline-none">
                            ${ports.map(p => `<option value="${p.id}" ${status.active_port===p.id?'selected':''}>${p.name}</option>`).join('')}
                        </select>
                        <button id="connectBtn" class="w-full mt-4 bg-slate-800 text-white font-bold py-3 rounded-xl hover:bg-slate-700 transition">
                            ${status.is_connected ? 'Отключить линию' : 'Установить соединение'}
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl border bg-slate-50 flex items-center justify-between">
                            <span class="text-sm font-medium">Статус:</span>
                            <span class="text-sm font-bold ${status.is_connected ? 'text-green-600':'text-red-400'}">${status.is_connected ? 'ONLINE' : 'OFFLINE'}</span>
                        </div>
                        <button id="scanBtn" ${!status.is_connected ? 'disabled':''} class="w-full py-4 rounded-xl font-bold border-2 border-dashed border-slate-200 text-slate-400 hover:border-blue-400 hover:text-blue-500 transition-all">
                            Поиск приборов
                        </button>
                    </div>
                </div>
            </div>
        `;
    },

    renderLiveControl() {
        return `
            <div id="liveControl" class="col-span-full bg-slate-800 p-6 rounded-2xl shadow-2xl mb-8 border border-slate-700 animate-slideDown">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-white flex items-center"><span class="w-3 h-3 bg-red-500 rounded-full mr-3 animate-ping"></span> Live Control Panel</h2>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Emulation Mode</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="window.triggerScenario('MASSIVE_FIRE')" class="group p-4 bg-red-600/10 border border-red-600/20 rounded-xl hover:bg-red-600 transition-all duration-300">
                        <p class="text-red-500 group-hover:text-white font-bold text-sm">ПОЖАР</p>
                        <p class="text-red-500/50 group-hover:text-white/50 text-[9px] uppercase mt-1">Массовая сработка</p>
                    </button>
                    <button onclick="window.triggerScenario('SYSTEM_FAULT')" class="group p-4 bg-yellow-600/10 border border-yellow-600/20 rounded-xl hover:bg-yellow-600 transition-all duration-300">
                        <p class="text-yellow-500 group-hover:text-white font-bold text-sm">АВАРИЯ</p>
                        <p class="text-yellow-500/50 group-hover:text-white/50 text-[9px] uppercase mt-1">Неисправность линии</p>
                    </button>
                    <button onclick="window.triggerScenario('RESET')" class="group p-4 bg-slate-700 border border-slate-600 rounded-xl hover:bg-slate-600 transition-all">
                        <p class="text-slate-300 group-hover:text-white font-bold text-sm">СБРОС</p>
                        <p class="text-slate-500 group-hover:text-white/50 text-[9px] uppercase mt-1">Возврат в норму</p>
                    </button>
                </div>
            </div>
        `;
    },

    renderEmptyState(message) {
        return `
            <div class="col-span-full py-20 text-center text-slate-400">
                <div class="mb-4 inline-block p-6 bg-slate-50 rounded-full animate-pulse">
                    <svg class="w-12 h-12 opacity-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <p class="text-sm font-medium opacity-50 px-10">${message}</p>
            </div>
        `;
    }
};
