const API_BASE = "http://localhost:8000";

const api = {
    // ... previous methods ...
    async getDevices() { const res = await fetch(`${API_BASE}/devices/`); return res.json(); },
    async getZones() { const res = await fetch(`${API_BASE}/zones/`); return res.json(); },
    async getScenarios() { const res = await fetch(`${API_BASE}/scenarios/`); return res.status === 200 ? res.json() : []; },
    async checkSystem() { try { const res = await fetch(`${API_BASE}/health`); return res.json(); } catch (e) { return { status: 'offline' }; } },
    async toggleDemo(active, template = "Apartment", profile = "Random") {
        const res = await fetch(`${API_BASE}/system/demo/toggle?active=${active}&template=${template}&profile=${profile}`, { method: 'POST' });
        return res.json();
    },
    async getDemoStatus() { const res = await fetch(`${API_BASE}/system/demo/status`); return res.json(); },
    async fireEvent(event) {
        const res = await fetch(`${API_BASE}/system/fire-event`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(event) });
        return res.json();
    },
    async getDiagnostics() { const res = await fetch(`${API_BASE}/diagnostics/`); return res.json(); },
    async checkUpdates() { const res = await fetch(`${API_BASE}/updates/check`); return res.json(); },

    // Hardware API
    async getPorts() {
        const res = await fetch(`${API_BASE}/hardware/ports`);
        return res.json();
    },
    async connectPort(portId) {
        const res = await fetch(`${API_BASE}/hardware/connect?port_id=${portId}`, { method: 'POST' });
        return res.json();
    },
    async scanDevices() {
        const res = await fetch(`${API_BASE}/hardware/scan`, { method: 'POST' });
        return res.json();
    },
    async syncConfig(direction) {
        const res = await fetch(`${API_BASE}/hardware/sync?direction=${direction}`, { method: 'POST' });
        return res.json();
    },
    async getHardwareStatus() {
        const res = await fetch(`${API_BASE}/hardware/status`);
        return res.json();
    }
};
