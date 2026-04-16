const { createApp } = Vue;

createApp({
    data() {
        return {
            authenticated: false,
            pin: '',
            error: '',
            entries: [],
            showModal: false,
            showSettings: false,
            editingId: null,
            entryForm: {
                title: '',
                description: '',
                date: '',
                reminder: false
            },
            newPin: '',
            toast: null,
            recordingField: null,
            recognition: null,
            deferredPrompt: null
        };
    },
    computed: {
        sortedEntries() {
            return [...this.entries].sort((a, b) => new Date(b.date) - new Date(a.date));
        }
    },
    mounted() {
        this.checkAuth();
        this.initVoice();
        this.checkReminders();
        setInterval(this.checkReminders, 60000); // Check every minute

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        });
    },
    methods: {
        async checkAuth() {
            try {
                const res = await fetch('api/auth.php?action=check');
                const data = await res.json();
                this.authenticated = data.authenticated;
                if (this.authenticated) {
                    this.fetchEntries();
                }
            } catch (e) {
                console.error('Auth check failed', e);
            }
        },
        appendPin(n) {
            if (this.pin.length < 6) {
                this.pin += n;
                if (this.pin.length === 6) {
                    this.login();
                }
            }
        },
        deletePin() {
            this.pin = this.pin.slice(0, -1);
        },
        clearPin() {
            this.pin = '';
            this.error = '';
        },
        async login() {
            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    body: JSON.stringify({ pin: this.pin })
                });
                const data = await res.json();
                if (data.success) {
                    this.authenticated = true;
                    this.pin = '';
                    this.fetchEntries();
                } else {
                    this.error = data.error;
                    this.pin = '';
                }
            } catch (e) {
                this.error = 'Ошибка входа';
                this.pin = '';
            }
        },
        async logout() {
            await fetch('api/auth.php?action=logout');
            this.authenticated = false;
            this.entries = [];
        },
        async installApp() {
            if (!this.deferredPrompt) return;
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                this.deferredPrompt = null;
            }
        },
        async fetchEntries() {
            try {
                const res = await fetch('api/entries.php');
                this.entries = await res.json();
            } catch (e) {
                this.showToast('Не удалось загрузить записи', 'error');
            }
        },
        openAddModal() {
            this.editingId = null;
            this.entryForm = {
                title: '',
                description: '',
                date: new Date().toISOString().slice(0, 16),
                reminder: false
            };
            this.showModal = true;
        },
        editEntry(entry) {
            this.editingId = entry.id;
            this.entryForm = { ...entry };
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
            this.stopVoice();
        },
        async saveEntry() {
            if (!this.entryForm.title) {
                this.showToast('Введите заголовок', 'error');
                return;
            }
            try {
                const method = this.editingId ? 'PUT' : 'POST';
                const res = await fetch('api/entries.php', {
                    method: method,
                    body: JSON.stringify(this.entryForm)
                });
                if (res.ok) {
                    this.showToast('Сохранено', 'success');
                    this.closeModal();
                    this.fetchEntries();
                }
            } catch (e) {
                this.showToast('Ошибка сохранения', 'error');
            }
        },
        async deleteEntry(id) {
            if (!confirm('Удалить эту запись?')) return;
            try {
                const res = await fetch(`api/entries.php?id=${id}`, { method: 'DELETE' });
                if (res.ok) {
                    this.showToast('Удалено', 'success');
                    this.closeModal();
                    this.fetchEntries();
                }
            } catch (e) {
                this.showToast('Ошибка удаления', 'error');
            }
        },
        async changePin() {
            if (!/^\d{6}$/.test(this.newPin)) {
                this.showToast('ПИН должен быть из 6 цифр', 'error');
                return;
            }
            try {
                const res = await fetch('api/settings.php', {
                    method: 'POST',
                    body: JSON.stringify({ new_pin: this.newPin })
                });
                if (res.ok) {
                    this.showToast('ПИН-код изменен', 'success');
                    this.showSettings = false;
                    this.newPin = '';
                }
            } catch (e) {
                this.showToast('Ошибка изменения ПИН-кода', 'error');
            }
        },
        initVoice() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRecognition) {
                this.recognition = new SpeechRecognition();
                this.recognition.lang = 'ru-RU';
                this.recognition.interimResults = false;
                this.recognition.continuous = false;

                this.recognition.onstart = () => {
                    console.log('Voice recognition started');
                };

                this.recognition.onresult = (event) => {
                    const text = event.results[0][0].transcript;
                    if (this.recordingField) {
                        this.entryForm[this.recordingField] += (this.entryForm[this.recordingField] ? ' ' : '') + text;
                    }
                };

                this.recognition.onerror = (event) => {
                    console.error('Speech recognition error', event.error);
                    this.recordingField = null;
                    this.showToast('Ошибка: ' + event.error, 'error');
                };

                this.recognition.onend = () => {
                    this.recordingField = null;
                };
            }
        },
        startVoiceRecognition(field) {
            if (!this.recognition) {
                this.showToast('Голосовой ввод не поддерживается', 'error');
                return;
            }
            if (this.recordingField === field) {
                this.stopVoice();
            } else {
                this.recordingField = field;
                this.recognition.start();
            }
        },
        stopVoice() {
            if (this.recognition && this.recordingField) {
                this.recognition.stop();
                this.recordingField = null;
            }
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },
        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 3000);
        },
        async requestNotificationPermission() {
            if (!("Notification" in window)) return;
            const permission = await Notification.requestPermission();
            if (permission === "granted") {
                this.showToast('Уведомления включены', 'success');
            }
        },
        checkReminders() {
            if (!this.authenticated) return;
            const now = new Date();
            let changed = false;
            this.entries.forEach(entry => {
                if (entry.reminder && entry.date && !entry.notified) {
                    const entryTime = new Date(entry.date);
                    if (now >= entryTime && (now - entryTime) < 300000) { // Within 5 minutes
                        this.notify(entry);
                        entry.notified = true;
                        changed = true;
                    }
                }
            });
            if (changed) {
                this.entries.forEach(async (e) => {
                    if (e.notified) {
                         await fetch('api/entries.php', {
                            method: 'PUT',
                            body: JSON.stringify(e)
                        });
                    }
                });
            }
        },
        notify(entry) {
            if (!("Notification" in window)) return;

            if (Notification.permission === "granted") {
                new Notification("Напоминание: " + entry.title, {
                    body: entry.description,
                    icon: 'assets/icon-192.png'
                });
            }
        }
    }
}).mount('#app');
