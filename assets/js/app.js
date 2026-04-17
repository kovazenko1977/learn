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
                title: '', description: '', date: '', reminder: false,
                completed: false, priority: 'medium', category: 'General', mood: '😐', pinned: false
            },
            newPin: '',
            toast: null,
            recordingField: null,
            recognition: null,
            deferredPrompt: null,
            currentDate: new Date(),
            selectedDate: null,
            searchQuery: '',
            filterCategory: 'All',
            filterStatus: 'Active', // Active, Completed, All
            darkMode: localStorage.getItem('darkMode') === 'true',
            categories: ['General', 'Work', 'Personal', 'Health', 'Finance', 'Ideas'],
            moods: ['😊', '😐', '😔', '🚀', '🔥', '😴']
        };
    },
    watch: {
        darkMode(val) {
            localStorage.setItem('darkMode', val);
            this.applyTheme();
        }
    },
    computed: {
        filteredEntries() {
            let filtered = this.entries;

            // Date Filter
            if (this.selectedDate) {
                const selDate = new Date(this.selectedDate).toDateString();
                filtered = filtered.filter(e => new Date(e.date).toDateString() === selDate);
            }

            // Search
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                filtered = filtered.filter(e =>
                    e.title.toLowerCase().includes(q) || e.description.toLowerCase().includes(q)
                );
            }

            // Category
            if (this.filterCategory !== 'All') {
                filtered = filtered.filter(e => e.category === this.filterCategory);
            }

            // Status
            if (this.filterStatus === 'Active') {
                filtered = filtered.filter(e => !e.completed);
            } else if (this.filterStatus === 'Completed') {
                filtered = filtered.filter(e => e.completed);
            }

            return filtered.sort((a, b) => {
                if (a.pinned !== b.pinned) return b.pinned ? 1 : -1;
                return new Date(b.date) - new Date(a.date);
            });
        },
        calendarDays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const days = [];
            const prevMonthDays = new Date(year, month, 0).getDate();
            const startDay = firstDay === 0 ? 6 : firstDay - 1;
            for (let i = startDay - 1; i >= 0; i--) days.push({ day: prevMonthDays - i, month: month - 1, year, current: false });
            for (let i = 1; i <= daysInMonth; i++) days.push({ day: i, month, year, current: true });
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) days.push({ day: i, month: month + 1, year, current: false });
            return days;
        },
        monthName() { return this.currentDate.toLocaleString('ru-RU', { month: 'long', year: 'numeric' }); },
        stats() {
            return {
                total: this.entries.length,
                done: this.entries.filter(e => e.completed).length,
                pending: this.entries.filter(e => !e.completed).length
            }
        }
    },
    mounted() {
        this.checkAuth();
        this.initVoice();
        this.checkReminders();
        setInterval(this.checkReminders, 60000);
        this.applyTheme();
        window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); this.deferredPrompt = e; });
    },
    methods: {
        applyTheme() {
            document.body.className = this.darkMode ? 'dark-mode' : '';
        },
        async checkAuth() {
            try {
                const res = await fetch('api/auth.php?action=check');
                const data = await res.json();
                this.authenticated = data.authenticated;
                if (this.authenticated) this.fetchEntries();
            } catch (e) {}
        },
        appendPin(n) { if (this.pin.length < 6) { this.pin += n; if (this.pin.length === 6) this.login(); } },
        deletePin() { this.pin = this.pin.slice(0, -1); },
        clearPin() { this.pin = ''; this.error = ''; },
        async login() {
            try {
                const res = await fetch('api/auth.php?action=login', { method: 'POST', body: JSON.stringify({ pin: this.pin }) });
                const data = await res.json();
                if (data.success) { this.authenticated = true; this.pin = ''; this.fetchEntries(); } else { this.error = data.error; this.pin = ''; }
            } catch (e) { this.error = 'Ошибка входа'; this.pin = ''; }
        },
        async logout() { await fetch('api/auth.php?action=logout'); this.authenticated = false; this.entries = []; },
        async fetchEntries() {
            try {
                const res = await fetch('api/entries.php');
                this.entries = await res.json();
            } catch (e) { this.showToast('Не удалось загрузить записи', 'error'); }
        },
        openAddModal() {
            this.editingId = null;
            this.entryForm = {
                title: '', description: '', date: new Date().toISOString().slice(0, 16),
                reminder: false, completed: false, priority: 'medium', category: 'General', mood: '😐', pinned: false
            };
            this.showModal = true;
        },
        editEntry(entry) {
            this.editingId = entry.id;
            this.entryForm = { ...entry };
            this.showModal = true;
        },
        closeModal() { this.showModal = false; this.stopVoice(); },
        async saveEntry() {
            if (!this.entryForm.title) { this.showToast('Введите заголовок', 'error'); return; }
            try {
                const method = this.editingId ? 'PUT' : 'POST';
                const res = await fetch('api/entries.php', { method: method, body: JSON.stringify(this.entryForm) });
                if (res.ok) { this.showToast('Сохранено', 'success'); this.closeModal(); this.fetchEntries(); }
            } catch (e) { this.showToast('Ошибка сохранения', 'error'); }
        },
        async toggleComplete(entry) {
            entry.completed = !entry.completed;
            try {
                await fetch('api/entries.php', { method: 'PUT', body: JSON.stringify(entry) });
                this.showToast(entry.completed ? 'Выполнено' : 'Возвращено в работу');
                this.fetchEntries();
            } catch (e) { this.showToast('Ошибка обновления', 'error'); }
        },
        async deleteEntry(id) {
            if (!confirm('Удалить эту запись?')) return;
            try {
                const res = await fetch(`api/entries.php?id=${id}`, { method: 'DELETE' });
                if (res.ok) { this.showToast('Удалено', 'success'); this.closeModal(); this.fetchEntries(); }
            } catch (e) { this.showToast('Ошибка удаления', 'error'); }
        },
        async changePin() {
            if (!/^\d{6}$/.test(this.newPin)) { this.showToast('ПИН из 6 цифр', 'error'); return; }
            try {
                const res = await fetch('api/settings.php', { method: 'POST', body: JSON.stringify({ new_pin: this.newPin }) });
                if (res.ok) { this.showToast('ПИН изменен', 'success'); this.showSettings = false; this.newPin = ''; }
            } catch (e) { this.showToast('Ошибка изменения ПИН', 'error'); }
        },
        exportData() {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(this.entries));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "diary_export.json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        },
        initVoice() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRecognition) {
                this.recognition = new SpeechRecognition();
                this.recognition.lang = 'ru-RU';
                this.recognition.onresult = (event) => {
                    const text = event.results[0][0].transcript;
                    if (this.recordingField) this.entryForm[this.recordingField] += (this.entryForm[this.recordingField] ? ' ' : '') + text;
                };
                this.recognition.onerror = () => { this.recordingField = null; };
                this.recognition.onend = () => { this.recordingField = null; };
            }
        },
        startVoiceRecognition(field) {
            if (!this.recognition) { this.showToast('Голосовой ввод не поддерживается', 'error'); return; }
            if (this.recordingField === field) this.stopVoice(); else { this.recordingField = field; this.recognition.start(); }
        },
        stopVoice() { if (this.recognition && this.recordingField) { this.recognition.stop(); this.recordingField = null; } },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
        prevMonth() { this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1); },
        nextMonth() { this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1); },
        goToToday() { this.currentDate = new Date(); this.selectedDate = new Date(); },
        copyEntry(e) {
            const text = `${e.title}\n${e.description}\nДата: ${this.formatDate(e.date)}`;
            navigator.clipboard.writeText(text).then(() => this.showToast('Скопировано в буфер'));
        },
        async duplicateEntry(e) {
            const copy = { ...e, id: undefined, created_at: undefined, updated_at: undefined, completed: false, notified: false };
            try {
                const res = await fetch('api/entries.php', { method: 'POST', body: JSON.stringify(copy) });
                if (res.ok) { this.showToast('Дубликат создан'); this.fetchEntries(); }
            } catch (err) { this.showToast('Ошибка дублирования', 'error'); }
        },
        async clearCompleted() {
            if (!confirm('Удалить все выполненные задачи?')) return;
            const completed = this.entries.filter(e => e.completed);
            for (const e of completed) {
                await fetch(`api/entries.php?id=${e.id}`, { method: 'DELETE' });
            }
            this.showToast('Выполненные задачи удалены');
            this.fetchEntries();
        },
        selectDate(dayObj) {
            const date = new Date(dayObj.year, dayObj.month, dayObj.day);
            this.selectedDate = (this.selectedDate && this.selectedDate.toDateString() === date.toDateString()) ? null : date;
        },
        isToday(dayObj) { const t = new Date(); return t.getDate() === dayObj.day && t.getMonth() === dayObj.month && t.getFullYear() === dayObj.year; },
        isSelected(dayObj) { return this.selectedDate && this.selectedDate.getDate() === dayObj.day && this.selectedDate.getMonth() === dayObj.month && this.selectedDate.getFullYear() === dayObj.year; },
        getEntryCount(dayObj) {
            const dStr = new Date(dayObj.year, dayObj.month, dayObj.day).toDateString();
            return this.entries.filter(e => new Date(e.date).toDateString() === dStr).length;
        },
        showToast(message, type = 'success') { this.toast = { message, type }; setTimeout(() => { this.toast = null; }, 3000); },
        async requestNotificationPermission() {
            if (!("Notification" in window)) return;
            const p = await Notification.requestPermission();
            if (p === "granted") this.showToast('Уведомления включены');
        },
        checkReminders() {
            if (!this.authenticated) return;
            const now = new Date();
            let changed = false;
            this.entries.forEach(e => {
                if (e.reminder && e.date && !e.notified && !e.completed) {
                    const eTime = new Date(e.date);
                    if (now >= eTime && (now - eTime) < 300000) { this.notify(e); e.notified = true; changed = true; }
                }
            });
            if (changed) {
                this.entries.forEach(async (e) => {
                    if (e.notified && this.entries.find(orig => orig.id === e.id && !orig.notified)) {
                         await fetch('api/entries.php', {
                            method: 'PUT',
                            body: JSON.stringify(e)
                        });
                    }
                });
            }
        },
        notify(e) {
            if (Notification.permission === "granted") new Notification("Напоминание: " + e.title, { body: e.description, icon: 'assets/icon-192.png' });
        }
    }
}).mount('#app');
