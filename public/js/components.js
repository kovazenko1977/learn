const components = {
    renderDeviceCard(device) {
        return `
            <div class="device-card bg-white p-4 rounded-lg shadow hover:shadow-md cursor-pointer border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-lg">${device.name}</h3>
                        <p class="text-sm text-gray-500">${device.type}</p>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">ID: ${device.address}</span>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button class="flex-1 bg-slate-100 hover:bg-slate-200 py-2 rounded text-sm">Настроить</button>
                    <button class="flex-1 bg-slate-100 hover:bg-slate-200 py-2 rounded text-sm text-red-600">Удалить</button>
                </div>
            </div>
        `;
    },

    renderEmptyState(message) {
        return `
            <div class="col-span-full py-10 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <p>${message}</p>
            </div>
        `;
    }
};
