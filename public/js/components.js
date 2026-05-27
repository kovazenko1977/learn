const components = {
    // ... previous methods ...
    renderDeviceCard(device) {
        return `
            <div class="device-card bg-white p-4 rounded-lg shadow hover:shadow-md cursor-pointer border-l-4 border-blue-500 animate-fadeIn">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-slate-800">${device.name}</h3>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mt-1">${device.type}</p>
                    </div>
                    <span class="bg-slate-100 text-slate-600 text-[10px] px-2 py-0.5 rounded font-mono">ADDR: ${device.address}</span>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-50 flex space-x-2">
                    <button class="flex-1 bg-slate-50 hover:bg-slate-100 py-2 rounded text-xs font-medium transition">Настроить</button>
                    <button class="p-2 text-red-400 hover:bg-red-50 rounded transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
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
                    <span class="text-slate-400 font-bold uppercase tracking-widest">Active</span>
                    <button class="text-purple-600 font-bold">Изменить</button>
                </div>
            </div>
        `;
    },

    renderDiagnostics(data) {
        return `
            <div class="col-span-full bg-white p-6 rounded-xl shadow-sm border animate-fadeIn">
                <h2 class="text-lg font-bold mb-4 flex items-center"><svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 21.48V22"></path></svg> Диагностика системы</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-lg border">
                        <p class="text-xs text-slate-400 font-bold uppercase mb-1">База данных</p>
                        <p class="text-lg font-medium ${data.checks.database==='connected'?'text-green-600':'text-red-600'}">${data.checks.database}</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-lg border">
                        <p class="text-xs text-slate-400 font-bold uppercase mb-1">Аптайм сервера</p>
                        <p class="text-lg font-medium text-slate-700">${Math.round(data.checks.uptime)} сек.</p>
                    </div>
                </div>
                <div class="mt-6">
                    <h3 class="font-bold text-sm mb-3">Ручное управление (Эмуляция)</h3>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="window.fireDemoEvent('ALARM')" class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-red-200 transition">FIRE ALARM</button>
                        <button onclick="window.fireDemoEvent('FIRE')" class="bg-orange-100 text-orange-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-orange-200 transition">FIRE FIRE</button>
                        <button onclick="window.fireDemoEvent('RESTORE')" class="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-green-200 transition">RESTORE</button>
                    </div>
                </div>
            </div>
        `;
    },

    renderEmptyState(message) {
        return `
            <div class="col-span-full py-16 text-center text-slate-400">
                <div class="mb-4 inline-block p-4 bg-slate-50 rounded-full">
                    <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <p class="text-sm font-medium px-10">${message}</p>
            </div>
        `;
    }
};
