const { createApp } = Vue;

createApp({
    data() {
        return {
            currentTab: 'dashboard',
            config: {
                devices: [],
                partitions: [],
                users: [],
                relays: [],
                zones: [],
                scenarios: []
            },
            defaultEvents: [
                { original: 'ВЗЯТИЕ', custom: '' },
                { original: 'СНЯТИЕ', custom: '' },
                { original: 'ТРЕВОГА', custom: '' },
                { original: 'ПОЖАР', custom: '' },
                { original: 'ВНИМАНИЕ', custom: '' },
                { original: 'НАРУШЕНИЕ ШС', custom: '' }
            ],
            showAddDeviceModal: false,
            newDevice: {
                address: 1,
                type: 'С2000-4',
                version: '2.01'
            },
            toast: {
                show: false,
                message: '',
                type: 'success'
            },
            showSidebar: false,
            isDemoMode: false,
            events: [],
            demoInterval: null,
            deferredPrompt: null
        };
    },
    mounted() {
        this.fetchConfig();
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        });
    },
    watch: {
        showSidebar(val) {
            if (val) document.body.classList.add('sidebar-open');
            else document.body.classList.remove('sidebar-open');
        }
    },
    methods: {
        async fetchConfig() {
            try {
                const response = await fetch('api.php?action=get_config');
                const data = await response.json();
                if (data && Object.keys(data).length > 0) {
                    this.config = data;
                }
            } catch (error) {
                this.showToast('Ошибка загрузки конфигурации', 'error');
            }
        },

        async readFromDevice() {
            this.showToast('Чтение конфигурации из памяти пульта...');
            await this.fetchConfig();
            setTimeout(() => this.showToast('Конфигурация успешно считана'), 1000);
        },

        async writeToDevice() {
            try {
                const response = await fetch('api.php?action=save_config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.config)
                });
                if (response.ok) {
                    this.showToast('Конфигурация успешно записана в энергонезависимую память');
                } else {
                    throw new Error();
                }
            } catch (error) {
                this.showToast('Ошибка записи в память', 'error');
            }
        },

        saveNewDevice() {
            this.config.devices.push({ ...this.newDevice, version: '1.00' });
            this.showAddDeviceModal = false;
            this.newDevice.address++;
            this.showToast('Прибор добавлен в список');
        },

        removeDevice(index) {
            this.config.devices.splice(index, 1);
        },

        addPartition() {
            this.config.partitions.push({
                name: `Новый раздел ${this.config.partitions.length + 1}`,
                loops: []
            });
        },

        removePartition(index) {
            this.config.partitions.splice(index, 1);
        },

        addUser() {
            this.config.users.push({
                name: '',
                password: '',
                role: 'user'
            });
        },

        removeUser(index) {
            this.config.users.splice(index, 1);
        },

        addRelay() {
            this.config.relays.push({
                name: `Реле ${this.config.relays.length + 1}`,
                tactic: '1',
                partition: 0
            });
        },

        exportConfig(format) {
            window.location.href = `api.php?action=export_file&format=${format}`;
        },

        async importConfig(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('config_file', file);

            try {
                const response = await fetch('api.php?action=import_file', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    this.config = result.data;
                    this.showToast('Конфигурация загружена из файла');
                } else {
                    this.showToast(result.message || 'Ошибка загрузки файла', 'error');
                }
            } catch (error) {
                this.showToast('Ошибка при чтении файла', 'error');
            }
            event.target.value = ''; // Reset input
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3000);
        },

        toggleDemoMode() {
            this.isDemoMode = !this.isDemoMode;
            if (this.isDemoMode) {
                this.showToast('Режим демо-эмуляции включен', 'success');
                this.startSimulation();
            } else {
                this.showToast('Режим эмуляции выключен', 'info');
                clearInterval(this.demoInterval);
                this.demoInterval = null;
            }
        },

        startSimulation() {
            this.addEvent('Система переведена в режим эмуляции', 'info');
            this.demoInterval = setInterval(() => {
                this.generateSimulatedEvent();
            }, 5000);
        },

        generateSimulatedEvent() {
            const types = ['ALARM', 'STATUS', 'INFO', 'WARNING'];
            const type = types[Math.floor(Math.random() * types.length)];
            const device = this.config.devices.length > 0
                ? this.config.devices[Math.floor(Math.random() * this.config.devices.length)].address
                : '1';

            const messages = {
                'ALARM': [`ТРЕВОГА: Проникновение (Шлейф ${Math.floor(Math.random()*8)+1})`, `ПОЖАР: Задымление (Шлейф ${Math.floor(Math.random()*8)+1})`],
                'STATUS': ['Прибор на связи', 'Взятие под охрану', 'Снятие с охраны'],
                'INFO': ['Проверка связи выполнена', 'Питание в норме', 'Температура корпуса +35C'],
                'WARNING': ['Корпус открыт', 'Низкий заряд АКБ', 'Ошибка шлейфа (Обрыв)']
            };

            const msgPool = messages[type];
            const msg = msgPool[Math.floor(Math.random() * msgPool.length)];

            this.addEvent(`[Адрес ${device}] ${msg}`, type.toLowerCase());
        },

        addEvent(text, type = 'info') {
            this.events.unshift({
                time: new Date().toLocaleTimeString(),
                text: text,
                type: type
            });
            if (this.events.length > 50) this.events.pop();
        },

        async installApp() {
            if (!this.deferredPrompt) return;
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                this.showToast('Приложение будет установлено');
            }
            this.deferredPrompt = null;
        }
    }
}).mount('#app');
