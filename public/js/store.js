const store = {
    state: {
        devices: [],
        zones: [],
        activeSection: 'devices'
    },

    listeners: [],

    subscribe(fn) {
        this.listeners.push(fn);
    },

    notify() {
        this.listeners.forEach(fn => fn(this.state));
    },

    setDevices(devices) {
        this.state.devices = devices;
        this.notify();
    }
};
