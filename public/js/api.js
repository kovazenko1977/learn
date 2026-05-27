const API_BASE = "http://localhost:8000"; // Fixed to Python backend

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
        try {
            const res = await fetch(`${API_BASE}/health`);
            return res.json();
        } catch (e) {
            return { status: 'offline' };
        }
    }
};
