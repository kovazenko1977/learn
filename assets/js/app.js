document.addEventListener('DOMContentLoaded', () => {
    const App = {
        state: {
            points: [],
            visits: [],
            settings: {
                interval_days: 30,
                interval_mode: 'days',
                technician_name: 'Иванов Алексей Петрович',
                company_name: 'ООО «СпецПожОхрана»'
            },
            currentTab: 'unvisited',
            searchQuery: '',
            historyFilter: 'all',
            historySelectedDate: '', // Filter history by selected date (YYYY-MM-DD)
            calendarViewYear: new Date().getFullYear(),
            calendarViewMonth: new Date().getMonth(),
            deferredPrompt: null
        },

        init() {
            this.initPreloader();
            this.bindEvents();
            this.loadTheme();
            this.loadCompactMode();
            this.fetchData();
            this.setupPWA();
            this.setupVoiceControls();
        },

        initPreloader() {
            const progressBar = document.getElementById('preloaderProgress');
            const statusText = document.getElementById('preloaderStatusText');
            let progress = 10;

            const interval = setInterval(() => {
                progress += Math.floor(Math.random() * 25) + 10;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    if (statusText) statusText.textContent = 'Готово!';
                    setTimeout(() => this.hidePreloader(), 400);
                } else {
                    if (statusText) {
                        if (progress > 30) statusText.textContent = 'Проверка настроек интервалов...';
                        if (progress > 60) statusText.textContent = 'Расчет статусов объектов...';
                    }
                }
                if (progressBar) progressBar.style.width = `${progress}%`;
            }, 100);
        },

        hidePreloader() {
            const preloader = document.getElementById('appPreloader');
            const appContainer = document.getElementById('appContainer');
            if (appContainer) appContainer.classList.remove('hidden');
            if (preloader) preloader.style.display = 'none';
        },

        async fetchData() {
            try {
                const response = await fetch('api/index.php?action=get_points');
                if (!response.ok) throw new Error('Ошибка сети при загрузке данных');
                const result = await response.json();
                if (result.success) {
                    this.state.points = result.points || [];
                    this.state.visits = result.visits || [];
                    this.state.settings = result.settings || this.state.settings;
                    this.renderAll();
                } else {
                    this.showToast('Ошибка загрузки данных: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка обращения к API. Проверьте соединение.', 'error');
            }
        },

        bindEvents() {
            // Navigation tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const tab = e.currentTarget.getAttribute('data-tab');
                    this.switchTab(tab);
                });
            });

            // Theme toggle
            document.getElementById('themeToggleBtn')?.addEventListener('click', () => this.toggleTheme());

            // Compact view toggle
            document.getElementById('compactViewToggleBtn')?.addEventListener('click', () => this.toggleCompactMode());

            // Search input
            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearchBtn');
            searchInput?.addEventListener('input', (e) => {
                this.state.searchQuery = e.target.value.trim().toLowerCase();
                if (this.state.searchQuery.length > 0) {
                    clearSearchBtn?.classList.remove('hidden');
                } else {
                    clearSearchBtn?.classList.add('hidden');
                }
                this.renderLists();
            });

            clearSearchBtn?.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                this.state.searchQuery = '';
                clearSearchBtn.classList.add('hidden');
                this.renderLists();
            });

            // History filter pills & date picker
            document.querySelectorAll('.pill-btn').forEach(pill => {
                pill.addEventListener('click', (e) => {
                    document.querySelectorAll('.pill-btn').forEach(p => p.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    this.state.historyFilter = e.currentTarget.getAttribute('data-filter');
                    this.renderHistory();
                });
            });

            const historyDatePicker = document.getElementById('historyDatePicker');
            historyDatePicker?.addEventListener('change', (e) => {
                this.state.historySelectedDate = e.target.value;
                this.renderHistory();
            });

            document.getElementById('historyTodayBtn')?.addEventListener('click', () => {
                const today = new Date().toISOString().split('T')[0];
                if (historyDatePicker) historyDatePicker.value = today;
                this.state.historySelectedDate = today;
                this.renderHistory();
            });

            document.getElementById('historyResetDateBtn')?.addEventListener('click', () => {
                if (historyDatePicker) historyDatePicker.value = '';
                this.state.historySelectedDate = '';
                this.renderHistory();
            });

            // Add Point Modal triggers
            document.getElementById('addPointBtn')?.addEventListener('click', () => this.openPointModal());
            document.getElementById('closePointModalBtn')?.addEventListener('click', () => this.closePointModal());
            document.getElementById('cancelPointBtn')?.addEventListener('click', () => this.closePointModal());
            document.getElementById('pointForm')?.addEventListener('submit', (e) => this.handlePointSubmit(e));
            document.getElementById('deletePointBtn')?.addEventListener('click', () => this.handlePointDelete());

            // Visit Modal triggers
            document.getElementById('closeVisitModalBtn')?.addEventListener('click', () => this.closeVisitModal());
            document.getElementById('cancelVisitBtn')?.addEventListener('click', () => this.closeVisitModal());
            document.getElementById('visitForm')?.addEventListener('submit', (e) => this.handleVisitSubmit(e));

            // Visit defects checkbox toggle
            const visitDefectsCheckbox = document.getElementById('visitDefectsCheckbox');
            const defectsDescriptionGroup = document.getElementById('defectsDescriptionGroup');
            visitDefectsCheckbox?.addEventListener('change', (e) => {
                if (e.target.checked) {
                    defectsDescriptionGroup?.classList.remove('hidden');
                } else {
                    defectsDescriptionGroup?.classList.add('hidden');
                }
            });

            // Settings Form
            document.getElementById('settingsForm')?.addEventListener('submit', (e) => this.handleSettingsSubmit(e));

            // Force refresh button (bypasses local cache)
            document.getElementById('forceRefreshBtn')?.addEventListener('click', () => this.handleForceRefresh());

            // Red "Reset all intervals" button
            document.getElementById('resetIntervalsBtn')?.addEventListener('click', () => this.handleResetIntervals());

            const intervalModeSelect = document.getElementById('intervalModeSelect');
            intervalModeSelect?.addEventListener('change', (e) => {
                const daysGroup = document.getElementById('intervalDaysGroup');
                if (e.target.value === 'calendar_month') {
                    if (daysGroup) daysGroup.style.opacity = '0.5';
                } else {
                    if (daysGroup) daysGroup.style.opacity = '1';
                }
            });

            // Voice today report button
            document.getElementById('voiceTodayBtn')?.addEventListener('click', () => this.speakTodaySummary());

            // Graphical Calendar Modal
            document.getElementById('openCalendarModalBtn')?.addEventListener('click', () => this.openCalendarModal());
            document.getElementById('closeCalendarModalBtn')?.addEventListener('click', () => this.closeCalendarModal());
            document.getElementById('calPrevMonthBtn')?.addEventListener('click', () => this.changeCalendarMonth(-1));
            document.getElementById('calNextMonthBtn')?.addEventListener('click', () => this.changeCalendarMonth(1));

            // Point History Modal Triggers
            document.getElementById('closePointHistoryModalBtn')?.addEventListener('click', () => this.closePointHistoryModal());
            document.getElementById('closePointHistoryModalBottomBtn')?.addEventListener('click', () => this.closePointHistoryModal());
        },

        openPointHistoryModal(pointId) {
            const point = this.state.points.find(p => p.id === pointId);
            if (!point) return;

            document.getElementById('pointHistoryModalName').textContent = point.name;
            document.getElementById('pointHistoryModalAddress').textContent = point.address;
            document.getElementById('pointHistoryModalSim').textContent = `SIM: ${point.sim_number}`;

            const pointVisits = this.state.visits.filter(v => v.point_id === pointId);
            pointVisits.sort((a, b) => new Date(b.visited_at) - new Date(a.visited_at));

            const totalVisits = pointVisits.length;
            const totalDefects = pointVisits.filter(v => v.has_defects).length;

            document.getElementById('pointHistoryModalTotalVisits').textContent = totalVisits;
            document.getElementById('pointHistoryModalTotalDefects').textContent = totalDefects;

            const timelineEl = document.getElementById('pointHistoryModalTimeline');
            if (timelineEl) {
                if (pointVisits.length === 0) {
                    timelineEl.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <h4>История проверок пуста</h4>
                            <p>По данной организации еще не было зафиксировано ни одного визита.</p>
                        </div>
                    `;
                } else {
                    timelineEl.innerHTML = pointVisits.map(v => {
                        const visitDate = new Date(v.visited_at);
                        const dateFormatted = visitDate.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        const timeFormatted = visitDate.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                        return `
                            <div class="history-item">
                                <div class="history-header">
                                    <div>
                                        <div style="font-size:0.82rem; color:var(--text-muted);">
                                            Инженер: <b>${this.escapeHtml(v.technician || 'Инженер ТО')}</b>
                                        </div>
                                    </div>
                                    <div style="text-align:right;">
                                        <span class="history-date">${dateFormatted}</span>
                                        <div style="font-size:0.75rem; color:var(--accent-green); font-family:var(--font-mono); font-weight:700;">
                                            ⏱️ ${timeFormatted}
                                        </div>
                                    </div>
                                </div>

                                ${v.has_defects ? `
                                    <div class="history-defects-tag">
                                        ⚠️ Зафиксированы недостатки: ${this.escapeHtml(v.defects_description || 'Без описания')}
                                    </div>
                                ` : '<div style="font-size:0.8rem; color:var(--accent-green); margin-top:4px;">✓ Замечаний нет</div>'}

                                ${v.notes ? `
                                    <div class="history-notes">
                                        📝 ${this.escapeHtml(v.notes)}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                }
            }

            document.getElementById('pointHistoryModal')?.classList.remove('hidden');
        },

        closePointHistoryModal() {
            document.getElementById('pointHistoryModal')?.classList.add('hidden');
        },

        openCalendarModal() {
            document.getElementById('calendarModal')?.classList.remove('hidden');
            this.renderCalendarGrid();
        },

        closeCalendarModal() {
            document.getElementById('calendarModal')?.classList.add('hidden');
        },

        changeCalendarMonth(offset) {
            this.state.calendarViewMonth += offset;
            if (this.state.calendarViewMonth < 0) {
                this.state.calendarViewMonth = 11;
                this.state.calendarViewYear -= 1;
            } else if (this.state.calendarViewMonth > 11) {
                this.state.calendarViewMonth = 0;
                this.state.calendarViewYear += 1;
            }
            this.renderCalendarGrid();
        },

        renderCalendarGrid() {
            const gridEl = document.getElementById('calendarDaysGrid');
            const titleEl = document.getElementById('calMonthYearTitle');
            if (!gridEl || !titleEl) return;

            const year = this.state.calendarViewYear;
            const month = this.state.calendarViewMonth;

            const monthNames = [
                'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
            ];

            titleEl.textContent = `${monthNames[month]} ${year}`;

            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstDayIndex = (new Date(year, month, 1).getDay() + 6) % 7; // Monday = 0

            let cellsHtml = '';

            // Empty cells before 1st day of month
            for (let i = 0; i < firstDayIndex; i++) {
                cellsHtml += `<div class="cal-day-cell empty-day"></div>`;
            }

            const todayStr = new Date().toISOString().split('T')[0];

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayVisits = this.state.visits.filter(v => v.visited_at && v.visited_at.startsWith(dateStr));
                const count = dayVisits.length;
                const hasDefects = dayVisits.some(v => v.has_defects);

                const isToday = dateStr === todayStr;
                const isSelected = dateStr === this.state.historySelectedDate;

                let indicatorHtml = '';
                if (count > 0) {
                    indicatorHtml = `
                        <div class="cal-day-dots">
                            <span class="dot ${hasDefects ? 'dot-danger' : 'dot-success'}"></span>
                        </div>
                        <span class="cal-count-badge ${hasDefects ? 'has-defects' : 'clean-visits'}">${count}</span>
                    `;
                } else if (isToday) {
                    indicatorHtml = `<div class="cal-day-dots"><span class="dot dot-today"></span></div>`;
                }

                cellsHtml += `
                    <div class="cal-day-cell ${isToday ? 'is-today' : ''} ${isSelected ? 'is-selected' : ''}" data-date="${dateStr}">
                        <span class="cal-day-num">${day}</span>
                        ${indicatorHtml}
                    </div>
                `;
            }

            gridEl.innerHTML = cellsHtml;

            // Day click listener
            gridEl.querySelectorAll('.cal-day-cell:not(.empty-day)').forEach(cell => {
                cell.addEventListener('click', (e) => {
                    const dateStr = e.currentTarget.getAttribute('data-date');
                    this.state.historySelectedDate = dateStr;
                    const historyDatePicker = document.getElementById('historyDatePicker');
                    if (historyDatePicker) historyDatePicker.value = dateStr;
                    this.closeCalendarModal();
                    this.switchTab('history');
                });
            });
        },

        switchTab(tabName) {
            this.state.currentTab = tabName;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
            });
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            const targetPanel = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
            if (targetPanel) targetPanel.classList.add('active');

            if (tabName === 'history') {
                this.renderHistory();
            } else if (tabName === 'settings') {
                this.populateSettingsForm();
            } else {
                this.renderLists();
            }
        },

        toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('pwa_theme', newTheme);
        },

        loadTheme() {
            const savedTheme = localStorage.getItem('pwa_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        },

        toggleCompactMode() {
            const isCompact = document.body.classList.toggle('compact-mode');
            localStorage.setItem('pwa_compact_mode', isCompact ? 'true' : 'false');
            this.showToast(isCompact ? '📱 Компактный вид включен' : '📱 Стандартный вид включен', 'info');
        },

        loadCompactMode() {
            const isCompact = localStorage.getItem('pwa_compact_mode') === 'true';
            if (isCompact) {
                document.body.classList.add('compact-mode');
            } else {
                document.body.classList.remove('compact-mode');
            }
        },

        isPointVisited(pointId) {
            const visitsForPoint = this.state.visits.filter(v => v.point_id === pointId);
            if (visitsForPoint.length === 0) return false;

            visitsForPoint.sort((a, b) => new Date(b.visited_at) - new Date(a.visited_at));
            const lastVisit = visitsForPoint[0];
            const lastVisitDate = new Date(lastVisit.visited_at);
            const now = new Date();

            if (this.state.settings.interval_mode === 'calendar_month') {
                return lastVisitDate.getMonth() === now.getMonth() && lastVisitDate.getFullYear() === now.getFullYear();
            } else {
                const daysDiff = (now - lastVisitDate) / (1000 * 60 * 60 * 24);
                return daysDiff <= (this.state.settings.interval_days || 30);
            }
        },

        getLastVisit(pointId) {
            const visitsForPoint = this.state.visits.filter(v => v.point_id === pointId);
            if (visitsForPoint.length === 0) return null;
            visitsForPoint.sort((a, b) => new Date(b.visited_at) - new Date(a.visited_at));
            return visitsForPoint[0];
        },

        renderAll() {
            this.updateHeaderStats();
            this.renderLists();
            this.renderHistory();
            this.populateSettingsForm();
        },

        updateHeaderStats() {
            const technicianDisplay = document.getElementById('technicianNameDisplay');
            if (technicianDisplay) technicianDisplay.textContent = this.state.settings.technician_name || 'Инженер ТО';

            const intervalDisplay = document.getElementById('intervalDisplay');
            if (intervalDisplay) {
                if (this.state.settings.interval_mode === 'calendar_month') {
                    intervalDisplay.textContent = '1 мес.';
                } else {
                    intervalDisplay.textContent = `${this.state.settings.interval_days || 30} дн.`;
                }
            }

            let unvisitedCount = 0;
            let visitedCount = 0;

            this.state.points.forEach(point => {
                if (this.isPointVisited(point.id)) {
                    visitedCount++;
                } else {
                    unvisitedCount++;
                }
            });

            document.getElementById('pendingCount').textContent = unvisitedCount;
            document.getElementById('completedCount').textContent = visitedCount;
            document.getElementById('unvisitedBadge').textContent = unvisitedCount;
            document.getElementById('visitedBadge').textContent = visitedCount;
        },

        filterPoints(pointsList) {
            if (!this.state.searchQuery) return pointsList;
            const q = this.state.searchQuery;
            return pointsList.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.address && p.address.toLowerCase().includes(q)) ||
                (p.sim_number && p.sim_number.toLowerCase().includes(q)) ||
                (p.equipment_type && p.equipment_type.toLowerCase().includes(q))
            );
        },

        renderLists() {
            const unvisitedListEl = document.getElementById('unvisitedList');
            const visitedListEl = document.getElementById('visitedList');
            const allListEl = document.getElementById('allList');

            const unvisitedPoints = this.filterPoints(this.state.points.filter(p => !this.isPointVisited(p.id)));
            const visitedPoints = this.filterPoints(this.state.points.filter(p => this.isPointVisited(p.id)));
            const allPoints = this.filterPoints(this.state.points);

            if (unvisitedListEl) {
                if (unvisitedPoints.length === 0) {
                    unvisitedListEl.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">🎉</div>
                            <h4>Все объекты обслужены!</h4>
                            <p>В текущем интервале не осталось объектов, требующих визита.</p>
                        </div>
                    `;
                } else {
                    unvisitedListEl.innerHTML = unvisitedPoints.map(p => this.createPointCardHtml(p, false)).join('');
                }
            }

            if (visitedListEl) {
                if (visitedPoints.length === 0) {
                    visitedListEl.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <h4>Нет посещенных объектов</h4>
                            <p>В текущем интервале отметки о посещении еще не вносились.</p>
                        </div>
                    `;
                } else {
                    visitedListEl.innerHTML = visitedPoints.map(p => this.createPointCardHtml(p, true)).join('');
                }
            }

            if (allListEl) {
                if (allPoints.length === 0) {
                    allListEl.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">🔍</div>
                            <h4>Объекты не найдены</h4>
                            <p>По вашему запросу ничего не найдено или список пуст.</p>
                        </div>
                    `;
                } else {
                    allListEl.innerHTML = allPoints.map(p => this.createPointCardHtml(p, this.isPointVisited(p.id))).join('');
                }
            }

            this.attachCardEventListeners();
        },

        createPointCardHtml(point, isVisited) {
            const lastVisit = this.getLastVisit(point.id);
            let lastVisitText = 'Еще не было визитов';
            let statusBadge = isVisited
                ? `<span class="visit-time-text success-text">✓ Обслужен</span>`
                : `<span class="visit-time-text urgent-text">⚠️ Требует ТО</span>`;

            if (lastVisit) {
                const dateFormatted = new Date(lastVisit.visited_at).toLocaleString('ru-RU', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
                lastVisitText = `Последний визит: ${dateFormatted}`;
                if (lastVisit.has_defects) {
                    lastVisitText += ` <b style="color:var(--accent-red);">(Замечания)</b>`;
                }
            }

            return `
                <div class="point-card ${isVisited ? 'status-done' : 'status-urgent'}" data-id="${point.id}">
                    <div class="card-header">
                        <div class="sim-badge" title="Привязанный номер SIM-карты">
                            SIM: ${this.escapeHtml(point.sim_number)}
                        </div>
                        <div class="point-title">${this.escapeHtml(point.name)}</div>
                    </div>

                    <div class="point-address">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>${this.escapeHtml(point.address)}</span>
                    </div>

                    <div class="point-details">
                        ${point.contract_number ? `<span class="detail-tag">📄 Договор: ${this.escapeHtml(point.contract_number)}</span>` : ''}
                        ${point.equipment_type ? `<span class="detail-tag">⚙️ ${this.escapeHtml(point.equipment_type)}</span>` : ''}
                        ${point.contact_person ? `<span class="detail-tag">👤 ${this.escapeHtml(point.contact_person)} ${point.contact_phone ? '(' + this.escapeHtml(point.contact_phone) + ')' : ''}</span>` : ''}
                    </div>

                    <div class="last-visit-info">
                        <div>${lastVisitText}</div>
                        <div>${statusBadge}</div>
                    </div>

                    ${point.notes ? `<div class="point-notes-preview" style="font-size:0.78rem; color:var(--text-muted); margin-bottom:10px;">💡 ${this.escapeHtml(point.notes)}</div>` : ''}

                    <div class="card-actions">
                        <button class="btn success-btn mark-visit-btn" data-id="${point.id}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            ${isVisited ? 'Повторно отметить визит' : 'Отметить посещение объекта'}
                        </button>
                        ${isVisited ? `
                            <button class="btn danger-btn cancel-visit-btn" data-id="${point.id}" title="Отменить посещение">
                                ↩️ Отменить посещение
                            </button>
                        ` : ''}
                        <button class="btn secondary-btn edit-point-btn" data-id="${point.id}" title="Редактировать">
                            ✏️
                        </button>
                    </div>
                </div>
            `;
        },

        attachCardEventListeners() {
            document.querySelectorAll('.point-title').forEach(titleEl => {
                titleEl.addEventListener('click', (e) => {
                    const card = e.currentTarget.closest('.point-card');
                    if (card) {
                        const pointId = card.getAttribute('data-id');
                        if (pointId) this.openPointHistoryModal(pointId);
                    }
                });
            });

            document.querySelectorAll('.mark-visit-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const pointId = e.currentTarget.getAttribute('data-id');
                    this.openVisitModal(pointId);
                });
            });

            document.querySelectorAll('.cancel-visit-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const pointId = e.currentTarget.getAttribute('data-id');
                    this.handleCancelVisit(pointId);
                });
            });

            document.querySelectorAll('.edit-point-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const pointId = e.currentTarget.getAttribute('data-id');
                    this.openPointModal(pointId);
                });
            });
        },

        async handleCancelVisit(pointId) {
            const point = this.state.points.find(p => p.id === pointId);
            if (!confirm(`Отменить последнее посещение для точки "${point ? point.name : ''}"?`)) {
                return;
            }

            try {
                const response = await fetch('api/index.php?action=cancel_visit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ point_id: pointId })
                });
                const result = await response.json();
                if (result.success) {
                    this.state.points = result.points || this.state.points;
                    this.state.visits = result.visits || this.state.visits;
                    this.renderAll();
                    this.showToast('Посещение точки отменено.', 'info');
                } else {
                    this.showToast('Ошибка при отмене посещения: ' + (result.error || 'Неизвестная ошибка'), 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка сервера при отмене посещения', 'error');
            }
        },

        openVisitModal(pointId) {
            const point = this.state.points.find(p => p.id === pointId);
            if (!point) return;

            document.getElementById('visitPointId').value = point.id;
            document.getElementById('visitModalPointName').textContent = point.name;
            document.getElementById('visitModalPointAddress').textContent = point.address;
            document.getElementById('visitModalSimNumber').textContent = `SIM: ${point.sim_number}`;

            document.getElementById('visitDefectsCheckbox').checked = false;
            document.getElementById('defectsDescriptionGroup').classList.add('hidden');
            document.getElementById('visitDefectsText').value = '';
            document.getElementById('visitNotesText').value = '';

            document.getElementById('visitModal').classList.remove('hidden');
        },

        closeVisitModal() {
            document.getElementById('visitModal').classList.add('hidden');
        },

        async handleVisitSubmit(e) {
            e.preventDefault();
            const pointId = document.getElementById('visitPointId').value;
            const hasDefects = document.getElementById('visitDefectsCheckbox').checked;
            const defectsDescription = document.getElementById('visitDefectsText').value;
            const notes = document.getElementById('visitNotesText').value;

            try {
                const response = await fetch('api/index.php?action=record_visit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        point_id: pointId,
                        has_defects: hasDefects,
                        defects_description: defectsDescription,
                        notes: notes,
                        technician: this.state.settings.technician_name
                    })
                });

                const result = await response.json();
                if (result.success) {
                    this.state.points = result.points || this.state.points;
                    this.state.visits = result.visits || this.state.visits;
                    this.closeVisitModal();
                    this.renderAll();
                    this.showToast('Посещение точки успешно отмечено!', 'success');
                } else {
                    this.showToast('Ошибка при записи: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка обращения к серверу', 'error');
            }
        },

        openPointModal(pointId = null) {
            const modal = document.getElementById('pointModal');
            const title = document.getElementById('pointModalTitle');
            const deleteBtn = document.getElementById('deletePointBtn');

            if (pointId) {
                const point = this.state.points.find(p => p.id === pointId);
                if (!point) return;

                title.textContent = 'Редактирование объекта';
                deleteBtn.classList.remove('hidden');

                document.getElementById('pointFormId').value = point.id;
                document.getElementById('pointFormName').value = point.name || '';
                document.getElementById('pointFormSim').value = point.sim_number || '';
                document.getElementById('pointFormContract').value = point.contract_number || '';
                document.getElementById('pointFormAddress').value = point.address || '';
                document.getElementById('pointFormEquipment').value = point.equipment_type || '';
                document.getElementById('pointFormContactPerson').value = point.contact_person || '';
                document.getElementById('pointFormContactPhone').value = point.contact_phone || '';
                document.getElementById('pointFormNotes').value = point.notes || '';
            } else {
                title.textContent = 'Добавление нового объекта';
                deleteBtn.classList.add('hidden');

                document.getElementById('pointForm').reset();
                document.getElementById('pointFormId').value = '';
            }

            modal.classList.remove('hidden');
        },

        closePointModal() {
            document.getElementById('pointModal').classList.add('hidden');
        },

        async handlePointSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('pointFormId').value;
            const data = {
                id: id,
                name: document.getElementById('pointFormName').value,
                sim_number: document.getElementById('pointFormSim').value,
                contract_number: document.getElementById('pointFormContract').value,
                address: document.getElementById('pointFormAddress').value,
                equipment_type: document.getElementById('pointFormEquipment').value,
                contact_person: document.getElementById('pointFormContactPerson').value,
                contact_phone: document.getElementById('pointFormContactPhone').value,
                notes: document.getElementById('pointFormNotes').value
            };

            try {
                const response = await fetch('api/index.php?action=save_point', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    this.closePointModal();
                    await this.fetchData();
                    this.showToast('Объект успешно сохранен!', 'success');
                } else {
                    this.showToast('Ошибка сохранения: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка соединения с сервером', 'error');
            }
        },

        async handlePointDelete() {
            const id = document.getElementById('pointFormId').value;
            if (!id) return;

            if (!confirm('Вы уверены, что хотите удалить эту точку из реестра?')) {
                return;
            }

            try {
                const response = await fetch('api/index.php?action=delete_point', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await response.json();
                if (result.success) {
                    this.closePointModal();
                    await this.fetchData();
                    this.showToast('Точка удалена.', 'info');
                } else {
                    this.showToast('Ошибка удаления: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка сервера', 'error');
            }
        },

        renderHistory() {
            const historyListEl = document.getElementById('historyList');
            if (!historyListEl) return;

            let visits = [...this.state.visits];

            // Filter by selected date if set (YYYY-MM-DD)
            if (this.state.historySelectedDate) {
                visits = visits.filter(v => v.visited_at && v.visited_at.startsWith(this.state.historySelectedDate));
            }

            if (this.state.historyFilter === 'defects') {
                visits = visits.filter(v => v.has_defects);
            }

            if (visits.length === 0) {
                const dateMsg = this.state.historySelectedDate
                    ? `За ${new Date(this.state.historySelectedDate).toLocaleDateString('ru-RU')} посещений не зафиксировано.`
                    : 'Записи проверок не найдены.';
                historyListEl.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📅</div>
                        <h4>Нет записей за выбранную дату</h4>
                        <p>${dateMsg}</p>
                    </div>
                `;
                return;
            }

            historyListEl.innerHTML = visits.map(v => {
                const visitDate = new Date(v.visited_at);
                const dateFormatted = visitDate.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
                const timeFormatted = visitDate.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                return `
                    <div class="history-item">
                        <div class="history-header">
                            <div>
                                <span class="history-title">${this.escapeHtml(v.point_name)}</span>
                                <div style="font-size:0.8rem; color:var(--text-muted);">
                                    SIM: <b style="color:var(--accent-blue);">${this.escapeHtml(v.sim_number)}</b> | Инженер: ${this.escapeHtml(v.technician || 'Инженер ТО')}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <span class="history-date">${dateFormatted}</span>
                                <div style="font-size:0.75rem; color:var(--accent-green); font-family:var(--font-mono); font-weight:700;">
                                    ⏱️ ${timeFormatted}
                                </div>
                            </div>
                        </div>

                        ${v.has_defects ? `
                            <div class="history-defects-tag">
                                ⚠️ Зафиксированы недостатки: ${this.escapeHtml(v.defects_description || 'Без подробного описания')}
                            </div>
                        ` : ''}

                        ${v.notes ? `
                            <div class="history-notes">
                                📝 ${this.escapeHtml(v.notes)}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        },

        populateSettingsForm() {
            document.getElementById('intervalModeSelect').value = this.state.settings.interval_mode || 'days';
            document.getElementById('intervalDaysInput').value = this.state.settings.interval_days || 30;
            document.getElementById('technicianNameInput').value = this.state.settings.technician_name || '';
            document.getElementById('companyNameInput').value = this.state.settings.company_name || '';
        },

        async handleSettingsSubmit(e) {
            e.preventDefault();
            const data = {
                interval_mode: document.getElementById('intervalModeSelect').value,
                interval_days: parseInt(document.getElementById('intervalDaysInput').value, 10),
                technician_name: document.getElementById('technicianNameInput').value,
                company_name: document.getElementById('companyNameInput').value
            };

            try {
                const response = await fetch('api/index.php?action=save_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    this.state.settings = result.settings;
                    this.renderAll();
                    this.showToast('Настройки ТО успешно обновлены!', 'success');
                } else {
                    this.showToast('Ошибка сохранения настроек: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка сервера', 'error');
            }
        },

        // Force refresh button: clears browser cache and re-fetches latest server data bypassing local storage/cache
        async handleForceRefresh() {
            try {
                this.showToast('🔄 Обновление данных с сервера...', 'info');

                // Clear CacheStorage if supported
                if ('caches' in window) {
                    const cacheKeys = await caches.keys();
                    await Promise.all(cacheKeys.map(key => caches.delete(key)));
                }

                // Fetch fresh points bypassing HTTP cache with timestamp query parameter
                const cacheBuster = Date.now();
                const response = await fetch(`api/index.php?action=get_points&_t=${cacheBuster}`, {
                    cache: 'no-store'
                });

                if (!response.ok) throw new Error('Ошибка сети при обновлении');
                const result = await response.json();

                if (result.success) {
                    this.state.points = result.points || [];
                    this.state.visits = result.visits || [];
                    this.state.settings = result.settings || this.state.settings;
                    this.renderAll();
                    this.showToast('Данные успешно принудительно обновлены!', 'success');
                } else {
                    this.showToast('Ошибка при получении данных: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка принудительного обновления: ' + err.message, 'error');
            }
        },

        // Red button action: resets all intervals by clearing visit history
        async handleResetIntervals() {
            if (!confirm('Вы действительно хотите сбросить все интервалы посещений?\nВсе точки перейдут в статус "Не посещены" (Требуют ТО).')) {
                return;
            }

            try {
                const response = await fetch('api/index.php?action=reset_intervals');
                const result = await response.json();
                if (result.success) {
                    this.state.points = result.points;
                    this.state.visits = result.visits;
                    this.state.settings = result.settings;
                    this.renderAll();
                    this.showToast('Все интервалы посещений сброшены!', 'info');
                } else {
                    this.showToast('Ошибка при сбросе интервалов: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка сервера', 'error');
            }
        },

        // Voice today summary report using SpeechSynthesis
        speakTodaySummary() {
            const todayStr = new Date().toISOString().split('T')[0];
            const todayVisits = this.state.visits.filter(v => v.visited_at && v.visited_at.startsWith(todayStr));
            const totalCount = todayVisits.length;
            const defectsCount = todayVisits.filter(v => v.has_defects).length;

            let reportText = '';
            if (totalCount === 0) {
                reportText = 'Сегодня посещений точек пока не зарегистрировано.';
            } else {
                reportText = `Сегодня посетили ${totalCount} ${this.pluralize(totalCount, ['точку', 'точки', 'точек'])}. `;
                if (defectsCount > 0) {
                    reportText += `Из них в ${defectsCount} ${this.pluralize(defectsCount, ['объекте', 'объектах', 'объектах'])} выявлены неисправности.`;
                } else {
                    reportText += 'Все зафиксированные объекты без замечаний.';
                }
            }

            this.showToast(`📢 ${reportText}`, 'info');

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel(); // Stop any active speech
                const utterance = new SpeechSynthesisUtterance(reportText);
                utterance.lang = 'ru-RU';
                utterance.rate = 1.0;
                window.speechSynthesis.speak(utterance);
            } else {
                this.showToast('Синтез речи не поддерживается браузером.', 'error');
            }
        },

        // Speech Recognition for 🎤 voice dictation buttons
        setupVoiceControls() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            document.querySelectorAll('.voice-dictate-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = btn.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    if (!targetInput) return;

                    if (!SpeechRecognition) {
                        this.showToast('Голосовой ввод не поддерживается вашим браузером.', 'error');
                        return;
                    }

                    const recognition = new SpeechRecognition();
                    recognition.lang = 'ru-RU';
                    recognition.interimResults = false;

                    btn.classList.add('listening');
                    this.showToast('🎤 Говорите...', 'info');

                    recognition.onresult = (event) => {
                        const transcript = event.results[0][0].transcript;
                        if (targetInput.value) {
                            targetInput.value += ' ' + transcript;
                        } else {
                            targetInput.value = transcript;
                        }
                        targetInput.dispatchEvent(new Event('input'));
                        this.showToast(`Распознано: "${transcript}"`, 'success');
                    };

                    recognition.onerror = (event) => {
                        console.error('Speech recognition error:', event.error);
                        this.showToast('Ошибка голосового ввода.', 'error');
                    };

                    recognition.onend = () => {
                        btn.classList.remove('listening');
                    };

                    recognition.start();
                });
            });
        },

        setupPWA() {
            const pwaBanner = document.getElementById('pwaInstallBanner');
            const pwaBannerInstallBtn = document.getElementById('pwaBannerInstallBtn');
            const pwaBannerCloseBtn = document.getElementById('pwaBannerCloseBtn');

            pwaBannerCloseBtn?.addEventListener('click', () => {
                pwaBanner?.classList.add('hidden');
            });

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.state.deferredPrompt = e;

                // Show automatic banner overlay at launch if not installed
                if (pwaBanner) pwaBanner.classList.remove('hidden');

                const installBtn = document.getElementById('pwaInstallBtn');
                if (installBtn) installBtn.classList.remove('hidden');

                const handleInstall = () => {
                    pwaBanner?.classList.add('hidden');
                    if (installBtn) installBtn.classList.add('hidden');
                    this.state.deferredPrompt.prompt();
                    this.state.deferredPrompt.userChoice.then(() => {
                        this.state.deferredPrompt = null;
                    });
                };

                installBtn?.addEventListener('click', handleInstall);
                pwaBannerInstallBtn?.addEventListener('click', handleInstall);
            });

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW Registered', reg))
                    .catch(err => console.error('SW Fail', err));
            }
        },

        pluralize(number, titles) {
            const cases = [2, 0, 1, 1, 1, 2];
            return titles[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[(number % 10 < 5) ? number % 10 : 5]];
        },

        showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;

            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        },

        escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    };

    App.init();
});
