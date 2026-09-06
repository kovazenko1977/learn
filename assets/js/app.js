/**
 * Main Application Controller
 * Handles UI interactions, API calls, state management, views switching,
 * tasks, planner/timeline, notes, habits, analytics, and voice integration.
 */

class AppController {
    constructor() {
        this.currentView = 'viewTasks';
        this.tasks = [];
        this.plannerEvents = [];
        this.habits = [];
        this.notes = [];
        this.activeCategory = 'all';
        this.activeTaskStatus = 'pending';
        this.activeNoteTag = 'all';
        this.selectedDate = new Date().toISOString().split('T')[0];
        this.recognizedCommandData = null;

        this.init();
    }

    async init() {
        this.bindEvents();
        this.registerServiceWorker();
        this.checkNotificationPermission();
        await this.loadAllData();
    }

    bindEvents() {
        // Navigation Tab Switching
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const target = tab.getAttribute('data-target');
                this.switchView(target);
            });
        });

        // Theme Toggle
        document.getElementById('btnToggleTheme').addEventListener('click', () => {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            const icon = document.querySelector('#btnToggleTheme i');
            icon.className = newTheme === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
            this.showToast(`Переключено на ${newTheme === 'dark' ? 'тёмную' : 'светлую'} тему`);
        });

        // Global Search Toggle
        const searchBar = document.getElementById('searchBar');
        document.getElementById('btnQuickSearch').addEventListener('click', () => {
            searchBar.classList.toggle('hidden');
            if (!searchBar.classList.contains('hidden')) {
                document.getElementById('globalSearchInput').focus();
            }
        });
        document.getElementById('btnCloseSearch').addEventListener('click', () => {
            searchBar.classList.add('hidden');
            document.getElementById('globalSearchInput').value = '';
            this.renderTasks();
            this.renderNotes();
        });
        document.getElementById('globalSearchInput').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            this.filterBySearch(query);
        });

        // --- TASKS MODULE BINDINGS ---
        document.getElementById('taskTitleInput').addEventListener('focus', () => {
            document.getElementById('taskOptions').classList.remove('hidden');
        });
        document.getElementById('btnAddTask').addEventListener('click', () => this.handleAddTask());

        // Task Category Pills
        document.querySelectorAll('#taskCategoryPills .pill').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#taskCategoryPills .pill').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                this.activeCategory = btn.getAttribute('data-category');
                this.renderTasks();
            });
        });

        // Task Status Filter Tabs
        document.querySelectorAll('.status-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.status-tab').forEach(s => s.classList.remove('active'));
                btn.classList.add('active');
                this.activeTaskStatus = btn.getAttribute('data-status');
                this.renderTasks();
            });
        });

        // Task Inline Voice Microphone
        document.getElementById('btnVoiceInputTask').addEventListener('click', () => {
            this.openVoiceModal('task');
        });

        // --- PLANNER MODULE BINDINGS ---
        document.getElementById('btnPrevDay').addEventListener('click', () => this.navigateDate(-1));
        document.getElementById('btnNextDay').addEventListener('click', () => this.navigateDate(1));
        document.getElementById('plannerDateDisplay').addEventListener('click', () => {
            const picker = document.getElementById('plannerDatePicker');
            picker.click();
        });
        document.getElementById('plannerDatePicker').addEventListener('change', (e) => {
            this.selectedDate = e.target.value;
            this.loadPlannerData();
        });
        document.getElementById('btnAddPlannerEvent').addEventListener('click', () => this.promptAddPlannerEvent());
        document.getElementById('btnAddHabit').addEventListener('click', () => {
            document.getElementById('habitModal').classList.remove('hidden');
        });
        document.getElementById('btnCloseHabitModal').addEventListener('click', () => {
            document.getElementById('habitModal').classList.add('hidden');
        });
        document.getElementById('btnSaveHabit').addEventListener('click', () => this.handleSaveHabit());

        // --- NOTES MODULE BINDINGS ---
        document.getElementById('btnCreateNote').addEventListener('click', () => this.openNoteModal());
        document.getElementById('btnCloseNoteModal').addEventListener('click', () => {
            document.getElementById('noteModal').classList.add('hidden');
        });
        document.getElementById('btnSaveNote').addEventListener('click', () => this.handleSaveNote());

        // Note Tags Filter
        document.querySelectorAll('#noteTagsPills .pill').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#noteTagsPills .pill').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                this.activeNoteTag = btn.getAttribute('data-tag');
                this.renderNotes();
            });
        });

        // Audio Memo Recording inside Note Modal
        let noteAudioBlob = null;
        const btnRecAudio = document.getElementById('btnRecAudioNote');
        btnRecAudio.addEventListener('click', async () => {
            if (!window.voiceEngine.isRecordingAudio) {
                try {
                    await window.voiceEngine.startAudioRecording();
                    btnRecAudio.classList.add('recording');
                    btnRecAudio.innerHTML = '<i class="fa-solid fa-square"></i> Остановить';
                    this.showToast('Идет запись аудио...');
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            } else {
                try {
                    noteAudioBlob = await window.voiceEngine.stopAudioRecording();
                    btnRecAudio.classList.remove('recording');
                    btnRecAudio.innerHTML = '<i class="fa-solid fa-circle"></i> Записать';
                    this.showToast('Аудио успешно записано!');

                    // Preview audio player
                    const audioUrl = URL.createObjectURL(noteAudioBlob);
                    const player = document.getElementById('noteAudioPlayer');
                    player.src = audioUrl;
                    document.getElementById('noteAudioPlayerContainer').classList.remove('hidden');
                    btnRecAudio._recordedBlob = noteAudioBlob;
                } catch (err) {
                    this.showToast('Ошибка остановки записи', 'error');
                }
            }
        });

        document.getElementById('btnRemoveAudioNote').addEventListener('click', () => {
            document.getElementById('noteAudioPlayer').src = '';
            document.getElementById('noteAudioPlayerContainer').classList.add('hidden');
            if (btnRecAudio) btnRecAudio._recordedBlob = null;
        });

        // --- VOICE FAB FLOATING BUTTON BINDINGS ---
        document.getElementById('fabMicBtn').addEventListener('click', () => {
            this.openVoiceModal('auto');
        });

        // Voice Modal Close & Action Handlers
        document.getElementById('btnCloseVoiceModal').addEventListener('click', () => {
            window.voiceEngine.stopDictation();
            document.getElementById('voiceModal').classList.add('hidden');
        });
        document.getElementById('btnStopVoiceRecord').addEventListener('click', () => {
            window.voiceEngine.stopDictation();
            document.getElementById('voiceModalStatus').innerText = 'Запись остановлена';
        });
        document.getElementById('btnConfirmVoiceAction').addEventListener('click', () => {
            this.executeVoiceAction();
        });

        // --- SETTINGS BINDINGS ---
        document.getElementById('voiceLangSelect').addEventListener('change', (e) => {
            window.voiceEngine.setLanguage(e.target.value);
            this.showToast(`Язык голоса изменен на ${e.target.value}`);
        });

        document.getElementById('btnEnableNotifications').addEventListener('click', () => {
            this.requestNotificationPermission();
        });

        document.getElementById('btnExportData').addEventListener('click', () => this.exportDataBackup());
        document.getElementById('importFileInput').addEventListener('change', (e) => this.importDataBackup(e));
    }

    switchView(targetId) {
        document.querySelectorAll('.view-section').forEach(sec => sec.classList.add('hidden'));
        document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));

        const targetView = document.getElementById(targetId);
        if (targetView) targetView.classList.remove('hidden');

        const activeTab = document.querySelector(`.nav-tab[data-target="${targetId}"]`);
        if (activeTab) activeTab.classList.add('active');

        this.currentView = targetId;

        // Dynamic Header titles
        const titleMap = {
            'viewTasks': 'Задачи и Напоминания',
            'viewPlanner': 'Ежедневник и Расписание',
            'viewNotes': 'Заметки и Блокнот',
            'viewStats': 'Аналитика и Обзор',
            'viewSettings': 'Настройки Приложения'
        };
        document.getElementById('headerTitle').innerText = titleMap[targetId] || 'Органайзер';

        if (targetId === 'viewStats') {
            this.loadStatsData();
        }
    }

    async loadAllData() {
        await Promise.all([
            this.loadTasksData(),
            this.loadPlannerData(),
            this.loadNotesData()
        ]);
    }

    // --- DATA LOADERS ---
    async loadTasksData() {
        try {
            const res = await fetch('api.php?action=get_tasks');
            const data = await res.json();
            if (data.success) {
                this.tasks = data.tasks || [];
                this.renderTasks();
            }
        } catch (err) {
            console.error('Error fetching tasks', err);
        }
    }

    async loadPlannerData() {
        try {
            const res = await fetch(`api.php?action=get_planner&date=${this.selectedDate}`);
            const data = await res.json();
            if (data.success) {
                this.plannerEvents = data.planner || [];
                this.habits = data.habits || [];
                this.renderPlanner();
                this.renderHabits();
            }
        } catch (err) {
            console.error('Error fetching planner', err);
        }
    }

    async loadNotesData() {
        try {
            const res = await fetch('api.php?action=get_notes');
            const data = await res.json();
            if (data.success) {
                this.notes = data.notes || [];
                this.renderNotes();
            }
        } catch (err) {
            console.error('Error fetching notes', err);
        }
    }

    async loadStatsData() {
        try {
            const res = await fetch('api.php?action=get_stats');
            const data = await res.json();
            if (data.success) {
                const s = data.summary;
                document.getElementById('statTaskProgress').innerText = `${s.task_completion_rate}%`;
                document.getElementById('statCompletedCount').innerText = `${s.completed_tasks} / ${s.total_tasks}`;
                document.getElementById('statNotesCount').innerText = s.total_notes;
                document.getElementById('statHabitsCount').innerText = s.total_habits;

                // Habit streak bars
                const container = document.getElementById('habitStreaksList');
                container.innerHTML = '';
                (data.habit_stats || []).forEach(h => {
                    const item = document.createElement('div');
                    item.className = 'habit-streak-item';
                    item.style.display = 'flex';
                    item.style.justifyContent = 'space-between';
                    item.style.padding = '8px 0';
                    item.style.borderBottom = '1px solid var(--border-color)';
                    item.innerHTML = `
                        <span>${h.icon} ${h.title}</span>
                        <span style="font-weight:700; color:var(--warning)">🔥 ${h.streak} дн. подряд</span>
                    `;
                    container.appendChild(item);
                });
            }
        } catch (err) {
            console.error('Error loading stats', err);
        }
    }

    // --- RENDER FUNCTIONS ---
    renderTasks() {
        const container = document.getElementById('taskList');
        container.innerHTML = '';

        let filtered = this.tasks.filter(t => {
            const matchesCategory = (this.activeCategory === 'all') || (t.category === this.activeCategory);
            const matchesStatus = (this.activeTaskStatus === 'completed') ? t.completed : !t.completed;
            return matchesCategory && matchesStatus;
        });

        // Counters
        const pendingCount = this.tasks.filter(t => !t.completed).length;
        const completedCount = this.tasks.filter(t => t.completed).length;
        document.getElementById('countPendingTasks').innerText = pendingCount;
        document.getElementById('countCompletedTasks').innerText = completedCount;

        if (filtered.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding: 30px; color: var(--text-muted);">
                    <i class="fa-solid fa-clipboard-check" style="font-size:36px; margin-bottom:10px;"></i>
                    <p>Задач не найдено</p>
                </div>
            `;
            return;
        }

        filtered.forEach(task => {
            const card = document.createElement('div');
            card.className = `task-card ${task.completed ? 'completed' : ''}`;

            const dueDisplay = task.due_date ? `📅 ${task.due_date} ${task.due_time || ''}` : '';

            card.innerHTML = `
                <div class="task-checkbox" data-id="${task.id}">
                    ${task.completed ? '<i class="fa-solid fa-check"></i>' : ''}
                </div>
                <div class="task-main">
                    <div class="task-title">${this.escapeHtml(task.title)}</div>
                    <div class="task-meta">
                        <span class="priority-badge ${task.priority}">${this.getPriorityLabel(task.priority)}</span>
                        <span>🏷️ ${task.category || 'Личное'}</span>
                        ${dueDisplay ? `<span>${dueDisplay}</span>` : ''}
                        ${task.voice_note_url ? `<span>🎙️ Аудио</span>` : ''}
                    </div>
                </div>
                <div class="task-actions">
                    <button class="btn-icon-danger" data-id="${task.id}" title="Удалить">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `;

            // Toggle Complete
            card.querySelector('.task-checkbox').addEventListener('click', () => this.toggleTaskComplete(task.id));
            // Delete Task
            card.querySelector('.btn-icon-danger').addEventListener('click', () => this.deleteTask(task.id));

            container.appendChild(card);
        });
    }

    renderPlanner() {
        const dateDisplay = document.getElementById('plannerDateDisplay');
        const formatted = new Date(this.selectedDate).toLocaleDateString('ru-RU', {
            weekday: 'short', day: 'numeric', month: 'long'
        });
        dateDisplay.innerText = formatted;

        const timeline = document.getElementById('plannerTimeline');
        timeline.innerHTML = '';

        // Group planner events by hour
        const eventMap = {};
        this.plannerEvents.forEach(ev => {
            const hour = ev.time ? parseInt(ev.time.split(':')[0], 10) : 9;
            if (!eventMap[hour]) eventMap[hour] = [];
            eventMap[hour].push(ev);
        });

        // Display hours 07:00 to 22:00
        for (let h = 7; h <= 22; h++) {
            const hourStr = `${String(h).padStart(2, '0')}:00`;
            const slotEvents = eventMap[h] || [];

            const slot = document.createElement('div');
            slot.className = 'time-slot-card';

            let eventsHtml = '';
            if (slotEvents.length > 0) {
                eventsHtml = slotEvents.map(e => `
                    <div class="planner-event-item" style="border-left: 3px solid ${e.color || '#4f46e5'}; padding-left: 8px; margin-bottom: 4px;">
                        <strong>${e.time}</strong> - ${this.escapeHtml(e.title)}
                        <button class="text-danger-btn" onclick="window.appController.deletePlannerEvent('${e.id}')" style="float:right; border:none; background:none; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                `).join('');
            } else {
                eventsHtml = `<span style="color:var(--text-muted); font-style:italic;">Свободное время</span>`;
            }

            slot.innerHTML = `
                <div class="slot-time">${hourStr}</div>
                <div class="slot-content">${eventsHtml}</div>
            `;

            timeline.appendChild(slot);
        }
    }

    renderHabits() {
        const container = document.getElementById('habitsList');
        container.innerHTML = '';

        if (this.habits.length === 0) {
            container.innerHTML = `<span style="color:var(--text-muted); font-size:12px;">Нет добавленных привычек</span>`;
            return;
        }

        this.habits.forEach(habit => {
            const isDoneToday = (habit.logs || []).includes(this.selectedDate);
            const chip = document.createElement('div');
            chip.className = `habit-chip ${isDoneToday ? 'done' : ''}`;
            chip.innerHTML = `
                <span>${habit.icon || '⚡'}</span>
                <span>${this.escapeHtml(habit.title)}</span>
                ${isDoneToday ? '<i class="fa-solid fa-circle-check"></i>' : ''}
            `;

            chip.addEventListener('click', () => this.toggleHabitLog(habit.id));
            container.appendChild(chip);
        });
    }

    renderNotes() {
        const grid = document.getElementById('notesGrid');
        grid.innerHTML = '';

        let filtered = this.notes.filter(n => {
            if (this.activeNoteTag === 'pinned') return n.pinned;
            if (this.activeNoteTag === 'audio') return !!n.audio_url;
            if (this.activeNoteTag !== 'all') return n.category === this.activeNoteTag;
            return true;
        });

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align:center; padding: 30px; color: var(--text-muted);">
                    <i class="fa-regular fa-note-sticky" style="font-size:36px; margin-bottom:10px;"></i>
                    <p>Заметок пока нет</p>
                </div>
            `;
            return;
        }

        filtered.forEach(note => {
            const card = document.createElement('div');
            card.className = `note-card ${note.pinned ? 'pinned' : ''}`;

            card.innerHTML = `
                ${note.pinned ? '<span class="note-pin-badge">📌</span>' : ''}
                <div>
                    <div class="note-card-title">${this.escapeHtml(note.title)}</div>
                    <div class="note-card-preview">${this.escapeHtml(note.content || 'Голосовая заметка')}</div>
                </div>
                ${note.audio_url ? `
                    <div style="margin-top:8px;">
                        <audio src="${note.audio_url}" controls style="width:100%; height:32px;"></audio>
                    </div>
                ` : ''}
                <div class="note-card-footer">
                    <span>${note.category || 'Заметки'}</span>
                    <button class="btn-icon-danger" data-id="${note.id}">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `;

            card.querySelector('.btn-icon-danger').addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteNote(note.id);
            });

            card.addEventListener('click', (e) => {
                if (e.target.closest('.btn-icon-danger') || e.target.closest('audio')) return;
                this.openNoteModal(note);
            });

            grid.appendChild(card);
        });
    }

    // --- ACTIONS & API HANDLERS ---
    async handleAddTask() {
        const titleInput = document.getElementById('taskTitleInput');
        const title = titleInput.value.trim();
        if (!title) return;

        const taskData = {
            title: title,
            due_date: document.getElementById('taskDueDateInput').value || null,
            due_time: document.getElementById('taskDueTimeInput').value || null,
            priority: document.getElementById('taskPriorityInput').value,
            category: this.activeCategory === 'all' ? 'Личное' : this.activeCategory
        };

        try {
            const res = await fetch('api.php?action=save_task', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(taskData)
            });
            const result = await res.json();
            if (result.success) {
                this.tasks.unshift(result.task);
                this.renderTasks();
                titleInput.value = '';
                document.getElementById('taskOptions').classList.add('hidden');
                this.showToast('Задача успешно добавлена!');
            }
        } catch (err) {
            this.showToast('Ошибка сохранения задачи', 'error');
        }
    }

    async toggleTaskComplete(id) {
        try {
            const res = await fetch('api.php?action=toggle_task', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) {
                const index = this.tasks.findIndex(t => t.id === id);
                if (index >= 0) this.tasks[index] = data.task;
                this.renderTasks();
            }
        } catch (err) {
            console.error('Error toggling task', err);
        }
    }

    async deleteTask(id) {
        try {
            const res = await fetch(`api.php?action=delete_task&id=${id}`);
            const data = await res.json();
            if (data.success) {
                this.tasks = this.tasks.filter(t => t.id !== id);
                this.renderTasks();
                this.showToast('Задача удалена');
            }
        } catch (err) {
            this.showToast('Ошибка удаления', 'error');
        }
    }

    async promptAddPlannerEvent() {
        const title = prompt('Введите название события:');
        if (!title) return;
        const time = prompt('Время (например, 14:00):', '12:00');

        const eventData = {
            date: this.selectedDate,
            time: time || '12:00',
            title: title
        };

        try {
            const res = await fetch('api.php?action=save_planner_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(eventData)
            });
            const data = await res.json();
            if (data.success) {
                this.plannerEvents.push(data.event);
                this.renderPlanner();
                this.showToast('Событие добавлено в расписание!');
            }
        } catch (err) {
            this.showToast('Ошибка сохранения события', 'error');
        }
    }

    async deletePlannerEvent(id) {
        try {
            const res = await fetch(`api.php?action=delete_planner_event&id=${id}`);
            const data = await res.json();
            if (data.success) {
                this.plannerEvents = this.plannerEvents.filter(e => e.id !== id);
                this.renderPlanner();
                this.showToast('Событие удалено');
            }
        } catch (err) {
            this.showToast('Ошибка удаления', 'error');
        }
    }

    async handleSaveHabit() {
        const title = document.getElementById('habitTitleInput').value.trim();
        const icon = document.getElementById('habitIconSelect').value;
        if (!title) return;

        try {
            const res = await fetch('api.php?action=save_habit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: title, icon: icon })
            });
            const data = await res.json();
            if (data.success) {
                this.habits.push(data.habit);
                this.renderHabits();
                document.getElementById('habitModal').classList.add('hidden');
                document.getElementById('habitTitleInput').value = '';
                this.showToast('Новая привычка создана!');
            }
        } catch (err) {
            this.showToast('Ошибка создания привычки', 'error');
        }
    }

    async toggleHabitLog(id) {
        try {
            const res = await fetch('api.php?action=toggle_habit_log', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, date: this.selectedDate })
            });
            const data = await res.json();
            if (data.success) {
                const index = this.habits.findIndex(h => h.id === id);
                if (index >= 0) this.habits[index] = data.habit;
                this.renderHabits();
            }
        } catch (err) {
            console.error('Error toggling habit', err);
        }
    }

    openNoteModal(note = null) {
        document.getElementById('noteIdInput').value = note ? note.id : '';
        document.getElementById('noteTitleInput').value = note ? note.title : '';
        document.getElementById('noteContentInput').value = note ? note.content : '';
        document.getElementById('noteCategorySelect').value = note ? note.category : 'Заметки';
        document.getElementById('notePinnedInput').checked = note ? !!note.pinned : false;

        const audioContainer = document.getElementById('noteAudioPlayerContainer');
        const audioPlayer = document.getElementById('noteAudioPlayer');

        if (note && note.audio_url) {
            audioPlayer.src = note.audio_url;
            audioContainer.classList.remove('hidden');
        } else {
            audioPlayer.src = '';
            audioContainer.classList.add('hidden');
        }

        document.getElementById('noteModalTitle').innerText = note ? 'Редактировать Заметку' : 'Новая Заметка';
        document.getElementById('noteModal').classList.remove('hidden');
    }

    async handleSaveNote() {
        const id = document.getElementById('noteIdInput').value;
        const title = document.getElementById('noteTitleInput').value.trim();
        const content = document.getElementById('noteContentInput').value.trim();
        const category = document.getElementById('noteCategorySelect').value;
        const pinned = document.getElementById('notePinnedInput').checked;

        let audioUrl = document.getElementById('noteAudioPlayer').src || null;
        const btnRecAudio = document.getElementById('btnRecAudioNote');

        // Check if new audio blob recorded
        if (btnRecAudio && btnRecAudio._recordedBlob) {
            const formData = new FormData();
            formData.append('audio', btnRecAudio._recordedBlob, 'memo.webm');
            try {
                const uploadRes = await fetch('api.php?action=upload_audio', {
                    method: 'POST',
                    body: formData
                });
                const uploadData = await uploadRes.json();
                if (uploadData.success) {
                    audioUrl = uploadData.url;
                }
            } catch (e) {
                console.error('Upload audio failed', e);
            }
        }

        const noteData = {
            id: id || null,
            title: title || 'Заметка без названия',
            content: content,
            category: category,
            pinned: pinned,
            audio_url: audioUrl
        };

        try {
            const res = await fetch('api.php?action=save_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(noteData)
            });
            const data = await res.json();
            if (data.success) {
                if (id) {
                    const idx = this.notes.findIndex(n => n.id === id);
                    if (idx >= 0) this.notes[idx] = data.note;
                } else {
                    this.notes.unshift(data.note);
                }
                this.renderNotes();
                document.getElementById('noteModal').classList.add('hidden');
                if (btnRecAudio) btnRecAudio._recordedBlob = null;
                this.showToast('Заметка сохранена!');
            }
        } catch (err) {
            this.showToast('Ошибка сохранения заметки', 'error');
        }
    }

    async deleteNote(id) {
        try {
            const res = await fetch(`api.php?action=delete_note&id=${id}`);
            const data = await res.json();
            if (data.success) {
                this.notes = this.notes.filter(n => n.id !== id);
                this.renderNotes();
                this.showToast('Заметка удалена');
            }
        } catch (err) {
            this.showToast('Ошибка удаления заметки', 'error');
        }
    }

    // --- VOICE RECOGNITION MODAL LOGIC ---
    openVoiceModal(mode = 'auto') {
        const modal = document.getElementById('voiceModal');
        const transcriptText = document.getElementById('voiceTranscriptText');
        const confirmBtn = document.getElementById('btnConfirmVoiceAction');
        const statusText = document.getElementById('voiceModalStatus');

        transcriptText.innerText = 'Слушаю...';
        confirmBtn.classList.add('hidden');
        statusText.innerText = 'Слушаю... Говорите!';
        modal.classList.remove('hidden');

        let fullTranscript = '';

        window.voiceEngine.startDictation(
            (res) => {
                fullTranscript = res.final || res.interim;
                transcriptText.innerText = fullTranscript;

                if (res.final) {
                    const parsed = window.voiceEngine.parseVoiceCommand(fullTranscript);
                    this.recognizedCommandData = parsed;
                    confirmBtn.classList.remove('hidden');
                    statusText.innerText = `Распознано: ${this.getActionTypeName(parsed.type)}`;
                }
            },
            (err) => {
                statusText.innerText = 'Ошибка распознавания: ' + err;
            },
            () => {
                if (!fullTranscript) {
                    statusText.innerText = 'Речь не обнаружена';
                }
            }
        );
    }

    getActionTypeName(type) {
        switch (type) {
            case 'note': return 'Создание Заметки';
            case 'planner': return 'Событие Ежедневника';
            case 'habit': return 'Привычка';
            default: return 'Создание Задачи';
        }
    }

    async executeVoiceAction() {
        if (!this.recognizedCommandData) return;
        const data = this.recognizedCommandData;

        if (data.type === 'note') {
            await fetch('api.php?action=save_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: data.title,
                    content: `Записано голосом: "${data.raw}"`,
                    category: 'Заметки'
                })
            });
            await this.loadNotesData();
            this.switchView('viewNotes');
            this.showToast('Заметка создана голосом!');
        } else if (data.type === 'planner') {
            await fetch('api.php?action=save_planner_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    date: data.date || this.selectedDate,
                    time: data.time || '12:00',
                    title: data.title
                })
            });
            await this.loadPlannerData();
            this.switchView('viewPlanner');
            this.showToast('Событие добавлено в ежедневник!');
        } else {
            // Task creation
            await fetch('api.php?action=save_task', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: data.title,
                    due_date: data.date,
                    due_time: data.time,
                    priority: data.priority,
                    category: 'Личное'
                })
            });
            await this.loadTasksData();
            this.switchView('viewTasks');
            this.showToast('Задача добавлена голосом!');
        }

        document.getElementById('voiceModal').classList.add('hidden');
    }

    // --- HELPER & UTILITY FUNCTIONS ---
    navigateDate(offset) {
        const d = new Date(this.selectedDate);
        d.setDate(d.getDate() + offset);
        this.selectedDate = d.toISOString().split('T')[0];
        this.loadPlannerData();
    }

    getPriorityLabel(priority) {
        const map = {
            'urgent': '🔥 Срочный',
            'high': 'Высокий',
            'medium': 'Средний',
            'low': 'Низкий'
        };
        return map[priority] || 'Средний';
    }

    filterBySearch(query) {
        if (!query) {
            this.renderTasks();
            this.renderNotes();
            return;
        }

        // Filter tasks
        const container = document.getElementById('taskList');
        container.innerHTML = '';
        const matchingTasks = this.tasks.filter(t => t.title.toLowerCase().includes(query));
        matchingTasks.forEach(t => {
            const div = document.createElement('div');
            div.className = 'task-card';
            div.innerHTML = `<div class="task-title">${this.escapeHtml(t.title)}</div>`;
            container.appendChild(div);
        });

        // Filter notes
        const grid = document.getElementById('notesGrid');
        grid.innerHTML = '';
        const matchingNotes = this.notes.filter(n => n.title.toLowerCase().includes(query) || n.content.toLowerCase().includes(query));
        matchingNotes.forEach(n => {
            const div = document.createElement('div');
            div.className = 'note-card';
            div.innerHTML = `<div class="note-card-title">${this.escapeHtml(n.title)}</div><div>${this.escapeHtml(n.content)}</div>`;
            grid.appendChild(div);
        });
    }

    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation text-danger' : 'fa-circle-check text-success'}"></i>
            <span>${this.escapeHtml(message)}</span>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').catch(err => {
                console.warn('SW registration failed', err);
            });
        }
    }

    checkNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'granted') {
            const btn = document.getElementById('btnEnableNotifications');
            if (btn) {
                btn.innerText = 'Включены';
                btn.disabled = true;
            }
        }
    }

    requestNotificationPermission() {
        if ('Notification' in window) {
            Notification.requestPermission().then(perm => {
                if (perm === 'granted') {
                    this.showToast('Уведомления успешно включены!');
                    this.checkNotificationPermission();
                } else {
                    this.showToast('Разрешение на уведомления отклонено', 'error');
                }
            });
        }
    }

    async exportDataBackup() {
        try {
            const res = await fetch('api.php?action=export_data');
            const data = await res.json();
            if (data.success) {
                const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `organizer_backup_${new Date().toISOString().split('T')[0]}.json`;
                a.click();
                this.showToast('Резервная копия скачана');
            }
        } catch (e) {
            this.showToast('Ошибка экспорта данных', 'error');
        }
    }

    async importDataBackup(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const importedJson = JSON.parse(e.target.result);
                const res = await fetch('api.php?action=import_data', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ data: importedJson })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast('Данные успешно импортированы!');
                    await this.loadAllData();
                }
            } catch (err) {
                this.showToast('Ошибка импорта файла', 'error');
            }
        };
        reader.readAsText(file);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.appController = new AppController();
});
