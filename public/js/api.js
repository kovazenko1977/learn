const API_BASE = "http://localhost:8000";

const api = {
    async getDevices() {
        const res = await fetch(`${API_BASE}/devices/`);
        return res.json();
    },

    async getScenarios() {
        const res = await fetch(`${API_BASE}/scenarios/`); // Need to check if this route exists
        return res.status === 200 ? res.json() : [];
    },

    async checkSystem() {
        try {
            const res = await fetch(`${API_BASE}/health`);
            return res.json();
        } catch (e) {
            return { status: 'offline' };
        }
    },

    async toggleDemo(active) {
        const res = await fetch(`${API_BASE}/system/demo/toggle?active=${active}`, {
            method: 'POST'
        });
        return res.json();
    },

    async getDemoStatus() {
        const res = await fetch(`${API_BASE}/system/demo/status`);
        return res.json();
    }
};
