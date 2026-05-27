const API_BASE = window.location.origin;

const api = {
    async getDevices() {
        const res = await fetch(`${API_BASE}/devices/`);
        return res.json();
    },

    async createDevice(device) {
        const res = await fetch(`${API_BASE}/devices/`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(device)
        });
        return res.json();
    },

    async checkSystem() {
        const res = await fetch(`${API_BASE}/health`);
        return res.json();
    }
};
