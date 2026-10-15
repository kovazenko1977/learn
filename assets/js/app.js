document.addEventListener('DOMContentLoaded', () => {
    const App = {
        state: {
            points: [],
            visits: [],
            settings: {
                interval_days: 30,
                interval_mode: 'days',
                technician_name: 'Старовойтов Алексей Михайлович',
                company_name: 'ООО «СпецПожОхрана»'
            },
            pendingSync: false,
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
            this.loadLocalDataOrFetch();
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
                        if (progress > 60) statusText.textContent = 'Загрузка телефонных данных...';
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

        // Local Storage Management
        saveToLocalStorage(markPendingSync = true) {
            localStorage.setItem('pwa_local_points', JSON.stringify(this.state.points));
            localStorage.setItem('pwa_local_visits', JSON.stringify(this.state.visits));
            localStorage.setItem('pwa_local_settings', JSON.stringify(this.state.settings));
            if (markPendingSync) {
                this.state.pendingSync = true;
                localStorage.setItem('pwa_pending_sync', 'true');
            }
            this.updateSyncButtonState();
        },

        async loadLocalDataOrFetch() {
            const localPoints = localStorage.getItem('pwa_local_points');
            const localVisits = localStorage.getItem('pwa_local_visits');
            const localSettings = localStorage.getItem('pwa_local_settings');
            const pendingSync = localStorage.getItem('pwa_pending_sync');

            this.state.pendingSync = pendingSync === 'true';

            if (localPoints && localVisits && localSettings) {
                try {
                    this.state.points = JSON.parse(localPoints);
                    this.state.visits = JSON.parse(localVisits);
                    this.state.settings = JSON.parse(localSettings);
                    this.renderAll();
                    this.updateSyncButtonState();
                    return;
                } catch (e) {
                    console.error('Error parsing local storage:', e);
                }
            }

            // Fallback: fetch initial data baseline from server
            await this.fetchDataFromServer(false);
        },

        async fetchDataFromServer(notifyUser = true) {
            try {
                const response = await fetch('api/index.php?action=get_points');
                if (!response.ok) throw new Error('Ошибка сети при загрузке данных');
                const result = await response.json();
                if (result.success) {
                    this.state.points = result.points || [];
                    this.state.visits = result.visits || [];
                    this.state.settings = result.settings || this.state.settings;
                    this.state.pendingSync = false;
                    localStorage.setItem('pwa_pending_sync', 'false');
                    this.saveToLocalStorage(false);
                    this.renderAll();
                    if (notifyUser) {
                        this.showToast('Данные успешно загружены с хостинга!', 'success');
                    }
                } else {
                    this.showToast('Ошибка загрузки данных: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                if (notifyUser) {
                    this.showToast('Ошибка обращения к API. Проверьте соединение.', 'error');
                }
            }
        },

        updateSyncButtonState() {
            const syncBtnText = document.getElementById('syncBtnText');
            const syncBtn = document.getElementById('syncServerBtn');
            if (!syncBtn || !syncBtnText) return;

            if (this.state.pendingSync) {
                syncBtnText.textContent = 'Выгрузить (есть изм.)';
                syncBtn.style.backgroundColor = 'var(--accent-orange, #ff9800)';
                syncBtn.style.borderColor = 'var(--accent-orange, #ff9800)';
                syncBtn.style.color = '#ffffff';
            } else {
                syncBtnText.textContent = 'Выгрузить на сервер';
                syncBtn.style.backgroundColor = '';
                syncBtn.style.borderColor = '';
                syncBtn.style.color = '';
            }
        },

        async handleSyncToServer() {
            try {
                const response = await fetch('api/index.php?action=sync_all', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        points: this.state.points,
                        visits: this.state.visits,
                        settings: this.state.settings
                    })
                });
                const result = await response.json();
                if (result.success) {
                    this.state.pendingSync = false;
                    localStorage.setItem('pwa_pending_sync', 'false');
                    this.updateSyncButtonState();
                    this.showToast('☁️ Все локальные данные успешно выгружены на сервер!', 'success');
                } else {
                    this.showToast('Ошибка выгрузки на сервер: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Ошибка соединения с сервером. Выгрузка выполнится при наличии сети.', 'error');
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

            // Sync to server button
            document.getElementById('syncServerBtn')?.addEventListener('click', () => this.handleSyncToServer());

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
                this.renderAll();
            });

            clearSearchBtn?.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                this.state.searchQuery = '';
                clearSearchBtn.classList.add('hidden');
                this.renderAll();
            });

            // Add Point Modal triggers
            document.getElementById('addPointBtn')?.addEventListener('click', () => this.openPointModal());
            document.getElementById('addPointHeaderBtn')?.addEventListener('click', () => this.openPointModal());
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

            // Point History Modal
            document.getElementById('closePointHistoryModalBtn')?.addEventListener('click', () => this.closePointHistoryModal());
            document.getElementById('closePointHistoryModalBottomBtn')?.addEventListener('click', () => this.closePointHistoryModal());

            // History Filter buttons
            document.querySelectorAll('#tab-history .filter-chip').forEach(chip => {
                chip.addEventListener('click', (e) => {
                    document.querySelectorAll('#tab-history .filter-chip').forEach(c => c.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    this.state.historyFilter = e.currentTarget.getAttribute('data-filter');
                    this.renderHistory();
                });
            });

            // History Date Picker
            const historyDatePicker = document.getElementById('historyDatePicker');
            historyDatePicker?.addEventListener('change', (e) => {
                this.state.historySelectedDate = e.target.value; // YYYY-MM-DD
                this.renderHistory();
            });

            document.getElementById('historyTodayBtn')?.addEventListener('click', () => {
                const todayStr = new Date().toISOString().split('T')[0];
                if (historyDatePicker) historyDatePicker.value = todayStr;
                this.state.historySelectedDate = todayStr;
                this.renderHistory();
            });

            document.getElementById('historyResetDateBtn')?.addEventListener('click', () => {
                if (historyDatePicker) historyDatePicker.value = '';
                this.state.historySelectedDate = '';
                this.renderHistory();
            });
        },

        switchTab(tab) {
            this.state.currentTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
            });

            document.querySelectorAll('.tab-panel').forEach(content => {
                const targetId = 'tab' + tab.charAt(0).toUpperCase() + tab.slice(1);
                content.classList.toggle('active', content.id === targetId);
            });

            if (tab === 'history') {
                this.renderHistory();
            } else if (tab === 'settings') {
                this.populateSettingsForm();
            }
        },

        toggleTheme() {
            const currentTheme = document.body.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('pwa_theme', newTheme);

            const btn = document.getElementById('themeToggleBtn');
            if (btn) btn.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        },

        loadTheme() {
            const savedTheme = localStorage.getItem('pwa_theme') || 'dark';
            document.body.setAttribute('data-theme', savedTheme);
            const btn = document.getElementById('themeToggleBtn');
            if (btn) btn.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        },

        toggleCompactMode() {
            const isCompact = document.body.classList.toggle('compact-mode');
            localStorage.setItem('pwa_compact_mode', isCompact ? 'true' : 'false');
            this.showToast(isCompact ? '📱 Компактный вид включен' : '📱 Стандартный вид включен', 'info');
        },

        loadCompactMode() {
            const savedCompact = localStorage.getItem('pwa_compact_mode') === 'true';
            if (savedCompact) {
                document.body.classList.add('compact-mode');
            }
        },

        // Inspection Status Logic
        isPointVisitedInInterval(point) {
            const visits = this.state.visits.filter(v => v.point_id === point.id);
            if (visits.length === 0) return false;

            // Sort visits descending
            visits.sort((a, b) => new Date(b.visited_at) - new Date(a.visited_at));
            const lastVisitDate = new Date(visits[0].visited_at);
            const now = new Date();

            if (this.state.settings.interval_mode === 'calendar_month') {
                // Return true if visited in current calendar month and year
                return lastVisitDate.getMonth() === now.getMonth() &&
                       lastVisitDate.getFullYear() === now.getFullYear();
            } else {
                // Return true if visit happened within interval_days
                const diffDays = (now - lastVisitDate) / (1000 * 60 * 60 * 24);
                return diffDays <= (this.state.settings.interval_days || 30);
            }
        },

        getLastVisit(pointId) {
            const visits = this.state.visits.filter(v => v.point_id === pointId);
            if (visits.length === 0) return null;
            visits.sort((a, b) => new Date(b.visited_at) - new Date(a.visited_at));
            return visits[0];
        },

        renderAll() {
            this.updateHeaderStats();
            this.renderPointsGrid();
            if (this.state.currentTab === 'history') {
                this.renderHistory();
            }
            this.updateSyncButtonState();
        },

        updateHeaderStats() {
            let unvisitedCount = 0;
            let visitedCount = 0;

            this.state.points.forEach(p => {
                if (this.isPointVisitedInInterval(p)) {
                    visitedCount++;
                } else {
                    unvisitedCount++;
                }
            });

            const pendingEl = document.getElementById('pendingCount');
            if (pendingEl) pendingEl.textContent = unvisitedCount;
            const completedEl = document.getElementById('completedCount');
            if (completedEl) completedEl.textContent = visitedCount;

            const modeText = this.state.settings.interval_mode === 'calendar_month'
                ? 'Месяц'
                : `${this.state.settings.interval_days || 30} дн.`;
            const intervalEl = document.getElementById('intervalDisplay');
            if (intervalEl) intervalEl.textContent = modeText;

            const countUnvisitedEl = document.getElementById('countUnvisitedBadge');
            if (countUnvisitedEl) countUnvisitedEl.textContent = unvisitedCount;
            const countVisitedEl = document.getElementById('countVisitedBadge');
            if (countVisitedEl) countVisitedEl.textContent = visitedCount;
            const countAllEl = document.getElementById('countAllBadge');
            if (countAllEl) countAllEl.textContent = this.state.points.length;

            const companyEl = document.getElementById('companyNameDisplay');
            if (companyEl) companyEl.textContent = this.state.settings.company_name || 'Пож-Охран ТО';
            const techEl = document.getElementById('technicianNameDisplay');
            if (techEl) techEl.textContent = this.state.settings.technician_name || 'Инженер ТО';
        },

        getFilteredPoints(filterType) {
            let list = [...this.state.points];

            if (this.state.searchQuery) {
                const q = this.state.searchQuery;
                list = list.filter(p =>
                    (p.name && p.name.toLowerCase().includes(q)) ||
                    (p.address && p.address.toLowerCase().includes(q)) ||
                    (p.sim_number && p.sim_number.toLowerCase().includes(q)) ||
                    (p.contract_number && p.contract_number.toLowerCase().includes(q))
                );
            }

            if (filterType === 'unvisited') {
                list = list.filter(p => !this.isPointVisitedInInterval(p));
            } else if (filterType === 'visited') {
                list = list.filter(p => this.isPointVisitedInInterval(p));
            }

            return list;
        },

        renderPointsGrid() {
            const grids = {
                unvisited: document.getElementById('unvisitedList'),
                visited: document.getElementById('visitedList'),
                all: document.getElementById('allList')
            };

            ['unvisited', 'visited', 'all'].forEach(type => {
                const gridEl = grids[type];
                if (!gridEl) return;

                const points = this.getFilteredPoints(type);

                if (points.length === 0) {
                    let msg = 'В этом разделе нет точек.';
                    if (this.state.searchQuery) msg = 'Ничего не найдено по вашему запросу.';
                    else if (type === 'unvisited') msg = '🎉 Отлично! Все точки обслужены в текущем интервале.';
                    else if (type === 'visited') msg = 'В текущем месяце/интервале еще не было посещений.';

                    gridEl.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">🔔</div>
                            <h4>Список пуст</h4>
                            <p>${msg}</p>
                        </div>
                    `;
                    return;
                }

                gridEl.innerHTML = points.map(p => this.renderPointCard(p)).join('');
            });

            this.attachCardEventListeners();
        },

        renderPointCard(point) {
            const isVisited = this.isPointVisitedInInterval(point);
            const lastVisit = this.getLastVisit(point.id);

            let lastVisitText = 'Еще не посещалась';
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
                        ${point.contract_number ? `<span class="detail-pill" title="Номер договора"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Договор: ${this.escapeHtml(point.contract_number)}</span>` : ''}
                        ${point.equipment ? `<span class="detail-pill" title="Оборудование"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> ${this.escapeHtml(point.equipment)}</span>` : ''}
                        ${point.contact_person ? `<span class="detail-pill" title="Ответственный"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> ${this.escapeHtml(point.contact_person)} ${point.contact_phone ? '(' + this.escapeHtml(point.contact_phone) + ')' : ''}</span>` : ''}
                    </div>

                    <div class="last-visit-info">
                        <span>${lastVisitText}</span>
                        <span>${isVisited ? '✓ Обслужен' : '⚠️ Требует ТО'}</span>
                    </div>

                    ${point.notes ? `<div class="point-notes-preview" style="font-size:0.78rem; color:var(--text-muted); margin-bottom:10px;">💡 ${this.escapeHtml(point.notes)}</div>` : ''}

                    <div class="card-actions">
                        <button class="btn success-btn mark-visit-btn" data-id="${point.id}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span class="btn-text-full">${isVisited ? 'Повторно отметить визит' : 'Отметить посещение объекта'}</span>
                            <span class="btn-text-short">${isVisited ? '✓ Повторно' : '✓ Отметить'}</span>
                        </button>
                        ${isVisited ? `
                            <button class="btn danger-btn cancel-visit-btn" data-id="${point.id}" title="Отменить посещение">
                                ↩️
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

        handleCancelVisit(pointId) {
            const point = this.state.points.find(p => p.id === pointId);
            if (!confirm(`Отменить последнее посещение для точки "${point ? point.name : ''}"?`)) {
                return;
            }

            // Find latest visit for this point and remove locally
            let targetIndex = -1;
            let latestTime = -1;
            this.state.visits.forEach((v, index) => {
                if (v.point_id === pointId) {
                    const t = new Date(v.visited_at).getTime();
                    if (t > latestTime) {
                        latestTime = t;
                        targetIndex = index;
                    }
                }
            });

            if (targetIndex !== -1) {
                this.state.visits.splice(targetIndex, 1);
                this.saveToLocalStorage(true);
                this.renderAll();
                this.showToast('Посещение точки отменено (сохранено в телефоне).', 'info');
            }
        },

        // Point Modal (Add / Edit)
        openPointModal(pointId = null) {
            const modal = document.getElementById('pointModal');
            const titleEl = document.getElementById('pointModalTitle');
            const deleteBtn = document.getElementById('deletePointBtn');

            document.getElementById('pointForm').reset();
            document.getElementById('pointFormId').value = '';

            if (pointId) {
                const point = this.state.points.find(p => p.id === pointId);
                if (point) {
                    titleEl.textContent = 'Редактировать объект';
                    document.getElementById('pointFormId').value = point.id;
                    document.getElementById('pointNameInput').value = point.name || '';
                    document.getElementById('pointSimInput').value = point.sim_number || '';
                    document.getElementById('pointContractInput').value = point.contract_number || '';
                    document.getElementById('pointAddressInput').value = point.address || '';
                    document.getElementById('pointEquipmentInput').value = point.equipment || '';
                    document.getElementById('pointContactPersonInput').value = point.contact_person || '';
                    document.getElementById('pointContactPhoneInput').value = point.contact_phone || '';
                    document.getElementById('pointNotesInput').value = point.notes || '';
                    if (deleteBtn) deleteBtn.classList.remove('hidden');
                }
            } else {
                titleEl.textContent = 'Добавить новый объект';
                if (deleteBtn) deleteBtn.classList.add('hidden');
            }

            if (modal) modal.classList.remove('hidden');
        },

        closePointModal() {
            const modal = document.getElementById('pointModal');
            if (modal) modal.classList.add('hidden');
        },

        handlePointSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('pointFormId').value;
            const name = document.getElementById('pointNameInput').value.trim();
            const sim = document.getElementById('pointSimInput').value.trim();
            const contract = document.getElementById('pointContractInput').value.trim();
            const address = document.getElementById('pointAddressInput').value.trim();
            const equipment = document.getElementById('pointEquipmentInput').value.trim();
            const person = document.getElementById('pointContactPersonInput').value.trim();
            const phone = document.getElementById('pointContactPhoneInput').value.trim();
            const notes = document.getElementById('pointNotesInput').value.trim();

            if (!name || !sim || !address) {
                this.showToast('Заполните обязательные поля (Название, SIM, Адрес)', 'error');
                return;
            }

            if (id) {
                // Update existing point locally
                const index = this.state.points.findIndex(p => p.id === id);
                if (index !== -1) {
                    this.state.points[index] = {
                        ...this.state.points[index],
                        name, sim_number: sim, contract_number: contract,
                        address, equipment, contact_person: person, contact_phone: phone, notes
                    };
                }
            } else {
                // Create new point locally
                const newPoint = {
                    id: 'pt_' + Date.now(),
                    name, sim_number: sim, contract_number: contract,
                    address, equipment, contact_person: person, contact_phone: phone, notes
                };
                this.state.points.unshift(newPoint);
            }

            this.saveToLocalStorage(true);
            this.closePointModal();
            this.renderAll();
            this.showToast('Объект сохранен в памяти телефона!', 'success');
        },

        handlePointDelete() {
            const id = document.getElementById('pointFormId').value;
            if (!id) return;

            if (!confirm('Вы уверены, что хотите удалить эту точку из реестра?')) {
                return;
            }

            this.state.points = this.state.points.filter(p => p.id !== id);
            this.saveToLocalStorage(true);
            this.closePointModal();
            this.renderAll();
            this.showToast('Точка удалена локально.', 'info');
        },

        // Visit Modal (Mark Visit)
        openVisitModal(pointId) {
            const point = this.state.points.find(p => p.id === pointId);
            if (!point) return;

            document.getElementById('visitPointId').value = point.id;
            const nameEl = document.getElementById('visitModalPointName');
            if (nameEl) nameEl.textContent = point.name;
            const addressEl = document.getElementById('visitModalPointAddress');
            if (addressEl) addressEl.textContent = point.address || 'Адрес не указан';
            const simEl = document.getElementById('visitModalSimNumber');
            if (simEl) simEl.textContent = `SIM: ${point.sim_number}`;

            const contractEl = document.getElementById('visitModalContractNumber');
            if (contractEl) {
                contractEl.textContent = point.contract_number ? `📄 Договор: ${point.contract_number}` : '📄 Договор: —';
            }

            document.getElementById('visitDefectsCheckbox').checked = false;
            document.getElementById('visitDefectsText').value = '';
            document.getElementById('visitNotesText').value = '';

            const defectsGroup = document.getElementById('defectsDescriptionGroup');
            if (defectsGroup) defectsGroup.classList.add('hidden');

            const modal = document.getElementById('visitModal');
            if (modal) modal.classList.remove('hidden');
        },

        closeVisitModal() {
            const modal = document.getElementById('visitModal');
            if (modal) modal.classList.add('hidden');
        },

        handleVisitSubmit(e) {
            e.preventDefault();
            const pointId = document.getElementById('visitPointId').value;
            const point = this.state.points.find(p => p.id === pointId);
            const hasDefects = document.getElementById('visitDefectsCheckbox').checked;
            const defectsDescription = document.getElementById('visitDefectsText').value;
            const notes = document.getElementById('visitNotesText').value;

            // Generate visit record with exact local timestamp YYYY-MM-DD HH:mm:ss
            const now = new Date();
            const formattedTimestamp = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');

            const newVisit = {
                id: 'v_' + Date.now(),
                point_id: pointId,
                sim_number: point ? point.sim_number : '',
                contract_number: point ? point.contract_number : '',
                point_name: point ? point.name : '',
                visited_at: formattedTimestamp,
                has_defects: hasDefects,
                defects_description: defectsDescription,
                notes: notes,
                technician: this.state.settings.technician_name || 'Инженер ТО'
            };

            this.state.visits.unshift(newVisit);
            this.saveToLocalStorage(true);

            this.closeVisitModal();
            this.renderAll();
            this.showToast('Посещение точки успешно отмечено в памяти телефона!', 'success');
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
                const dateFormatted = new Date(v.visited_at).toLocaleDateString('ru-RU', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                });
                const timeFormatted = new Date(v.visited_at).toLocaleTimeString('ru-RU', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });

                return `
                    <div class="history-item ${v.has_defects ? 'has-defects' : ''}">
                        <div class="history-header">
                            <div>
                                <span class="history-point-name">${this.escapeHtml(v.point_name)}</span>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top:2px;">
                                    SIM: <b style="color:var(--accent-blue);">${this.escapeHtml(v.sim_number)}</b>
                                    ${v.contract_number ? ` | Договор: <b>${this.escapeHtml(v.contract_number)}</b>` : ''}
                                    ${v.technician ? ` | Инженер: ${this.escapeHtml(v.technician)}` : ''}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="history-date">${dateFormatted}</div>
                                <div style="font-size:0.75rem; color:var(--accent-green); font-weight:bold;">⏱ ${timeFormatted}</div>
                            </div>
                        </div>

                        ${v.has_defects ? `
                            <div class="history-defect-box">
                                ⚠️ <b>Зафиксированы недостатки:</b> ${this.escapeHtml(v.defects_description || 'Детали не указаны')}
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

        handleSettingsSubmit(e) {
            e.preventDefault();
            this.state.settings = {
                interval_mode: document.getElementById('intervalModeSelect').value,
                interval_days: parseInt(document.getElementById('intervalDaysInput').value, 10),
                technician_name: document.getElementById('technicianNameInput').value,
                company_name: document.getElementById('companyNameInput').value
            };

            this.saveToLocalStorage(true);
            this.renderAll();
            this.showToast('Настройки ТО обновлены в телефоне!', 'success');
        },

        async handleForceRefresh() {
            if (confirm('Принудительно обновить локальные данные с хостинга? Несохраненные локальные изменения могут быть перезаписаны.')) {
                await this.fetchDataFromServer(true);
            }
        },

        handleResetIntervals() {
            if (!confirm('Вы уверены, что хотите сбросить ВСЕ зафиксированные визиты? Все точки моментально вернутся в список требующих ТО.')) {
                return;
            }

            this.state.visits = [];
            this.saveToLocalStorage(true);
            this.renderAll();
            this.showToast('Интервалы ТО сброшены. Все объекты требуют обслуживания.', 'info');
        },

        // Voice summary synthesis ("Что сегодня?")
        speakTodaySummary() {
            const todayStr = new Date().toISOString().split('T')[0];
            const todayVisits = this.state.visits.filter(v => v.visited_at && v.visited_at.startsWith(todayStr));

            let unvisitedCount = 0;
            this.state.points.forEach(p => {
                if (!this.isPointVisitedInInterval(p)) unvisitedCount++;
            });

            let reportText = '';
            if (todayVisits.length === 0) {
                reportText = `Сегодня вы еще не посетили ни одного объекта. Осталось обслужить ${unvisitedCount} ${this.pluralize(unvisitedCount, ['объект', 'объекта', 'объектов'])}.`;
            } else {
                const defectsCount = todayVisits.filter(v => v.has_defects).length;
                reportText = `За сегодня успешно обслужено ${todayVisits.length} ${this.pluralize(todayVisits.length, ['объект', 'объекта', 'объектов'])}. `;
                if (defectsCount > 0) {
                    reportText += `Из них в ${defectsCount} ${this.pluralize(defectsCount, ['объекте', 'объектах', 'объектах'])} выявлены неисправности.`;
                } else {
                    reportText += 'Все зафиксированные объекты без замечаний.';
                }
            }

            this.showToast(`📢 ${reportText}`, 'info');

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel(); // Stop active speech
                const utterance = new SpeechSynthesisUtterance(reportText);
                utterance.lang = 'ru-RU';
                utterance.rate = 1.0;
                window.speechSynthesis.speak(utterance);
            } else {
                this.showToast('Синтез речи не поддерживается браузером.', 'error');
            }
        },

        // Speech Recognition for dictation
        setupVoiceControls() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                document.querySelectorAll('.voice-input-btn').forEach(b => b.style.display = 'none');
                return;
            }

            document.querySelectorAll('.voice-input-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const targetId = e.currentTarget.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    if (!targetInput) return;

                    const recognition = new SpeechRecognition();
                    recognition.lang = 'ru-RU';
                    recognition.interimResults = false;

                    btn.classList.add('pulse');
                    this.showToast('🎙️ Говорите... идет распознавание речи', 'info');

                    recognition.onresult = (event) => {
                        const transcript = event.results[0][0].transcript;
                        if (targetInput.value) {
                            targetInput.value += ' ' + transcript;
                        } else {
                            targetInput.value = transcript;
                        }
                        this.showToast('✓ Речь распознана!', 'success');
                    };

                    recognition.onerror = () => {
                        this.showToast('Не удалось распознать речь.', 'error');
                    };

                    recognition.onend = () => {
                        btn.classList.remove('pulse');
                    };

                    recognition.start();
                });
            });
        },

        // Graphical Calendar Modal
        openCalendarModal() {
            this.state.calendarViewYear = new Date().getFullYear();
            this.state.calendarViewMonth = new Date().getMonth();
            this.renderCalendarGrid();
            const modal = document.getElementById('calendarModal');
            if (modal) modal.classList.remove('hidden');
        },

        closeCalendarModal() {
            const modal = document.getElementById('calendarModal');
            if (modal) modal.classList.add('hidden');
        },

        changeCalendarMonth(delta) {
            this.state.calendarViewMonth += delta;
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
            const monthNames = [
                'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
            ];

            const year = this.state.calendarViewYear;
            const month = this.state.calendarViewMonth;

            const calTitleEl = document.getElementById('calMonthYearTitle') || document.getElementById('calendarMonthTitle');
            if (calTitleEl) calTitleEl.textContent = `${monthNames[month]} ${year}`;

            const calendarGrid = document.getElementById('calendarDaysGrid');
            if (!calendarGrid) return;

            const firstDay = new Date(year, month, 1);
            let startingDay = firstDay.getDay() - 1; // Mon = 0, Sun = 6
            if (startingDay === -1) startingDay = 6;

            const daysInMonth = new Date(year, month + 1, 0).getDate();

            let html = '';

            for (let i = 0; i < startingDay; i++) {
                html += `<div class="calendar-day empty"></div>`;
            }

            const todayStr = new Date().toISOString().split('T')[0];

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const visitsForDay = this.state.visits.filter(v => v.visited_at && v.visited_at.startsWith(dateStr));

                const hasVisits = visitsForDay.length > 0;
                const hasDefects = visitsForDay.some(v => v.has_defects);
                const isToday = dateStr === todayStr;

                let dayClass = 'calendar-day';
                if (isToday) dayClass += ' is-today';
                if (hasVisits) dayClass += hasDefects ? ' has-defects' : ' has-visits';

                html += `
                    <div class="${dayClass}" data-date="${dateStr}">
                        <div class="day-number">${day}</div>
                        ${hasVisits ? `
                            <div class="day-badge">
                                ${visitsForDay.length} ${hasDefects ? '⚠️' : '✓'}
                            </div>
                        ` : ''}
                    </div>
                `;
            }

            calendarGrid.innerHTML = html;

            calendarGrid.querySelectorAll('.calendar-day[data-date]').forEach(dayEl => {
                dayEl.addEventListener('click', (e) => {
                    const dateSelected = e.currentTarget.getAttribute('data-date');
                    const historyDatePicker = document.getElementById('historyDatePicker');
                    if (historyDatePicker) historyDatePicker.value = dateSelected;
                    this.state.historySelectedDate = dateSelected;
                    this.closeCalendarModal();
                    this.switchTab('history');
                    this.showToast(`Выбрана дата: ${new Date(dateSelected).toLocaleDateString('ru-RU')}`, 'info');
                });
            });
        },

        // Per-Point History Modal
        openPointHistoryModal(pointId) {
            const point = this.state.points.find(p => p.id === pointId);
            if (!point) return;

            const nameEl = document.getElementById('pointHistoryModalName') || document.getElementById('pointHistoryTitle');
            if (nameEl) nameEl.textContent = point.name;

            const simEl = document.getElementById('pointHistoryModalSim') || document.getElementById('pointHistorySimBadge');
            if (simEl) simEl.textContent = `SIM: ${point.sim_number}`;

            const addrEl = document.getElementById('pointHistoryModalAddress') || document.getElementById('pointHistoryAddress');
            if (addrEl) addrEl.textContent = point.address || 'Адрес не указан';

            const contractBadge = document.getElementById('pointHistoryModalContract') || document.getElementById('pointHistoryContractBadge');
            if (contractBadge) {
                if (point.contract_number) {
                    contractBadge.textContent = `📄 Договор: ${point.contract_number}`;
                    contractBadge.classList.remove('hidden');
                } else {
                    contractBadge.textContent = `📄 Договор: —`;
                }
            }

            const visits = this.state.visits.filter(v => v.point_id === pointId);
            visits.sort((a, b) => new Date(b.visited_at) - new Date(a.visited_at));

            const totalVisitsEl = document.getElementById('pointHistoryModalTotalVisits');
            if (totalVisitsEl) totalVisitsEl.textContent = visits.length;

            const totalDefectsEl = document.getElementById('pointHistoryModalTotalDefects');
            if (totalDefectsEl) totalDefectsEl.textContent = visits.filter(v => v.has_defects).length;

            const listEl = document.getElementById('pointHistoryModalTimeline') || document.getElementById('pointHistoryList');
            if (listEl) {
                if (visits.length === 0) {
                    listEl.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📝</div>
                            <h4>История проверок пуста</h4>
                            <p>Данный объект еще не проходил регулярное ТО.</p>
                        </div>
                    `;
                } else {
                    listEl.innerHTML = visits.map(v => {
                        const dateFormatted = new Date(v.visited_at).toLocaleString('ru-RU', {
                            day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                        });
                        return `
                            <div class="timeline-item ${v.has_defects ? 'has-defects' : ''}">
                                <div class="timeline-date">
                                    📅 ${dateFormatted} ${v.technician ? `(${this.escapeHtml(v.technician)})` : ''}
                                </div>
                                <div style="font-weight:600; margin-bottom:4px; color:${v.has_defects ? 'var(--accent-red)' : 'var(--accent-green)'}">
                                    ${v.has_defects ? '⚠️ Внимание: Выявлены недостатки' : '✓ Проверка проведена успешно'}
                                </div>
                                ${v.defects_description ? `<div style="font-size:0.82rem; margin-bottom:4px;"><b>Замечания:</b> ${this.escapeHtml(v.defects_description)}</div>` : ''}
                                ${v.notes ? `<div style="font-size:0.82rem; color:var(--text-muted);"><b>Заметка:</b> ${this.escapeHtml(v.notes)}</div>` : ''}
                            </div>
                        `;
                    }).join('');
                }
            }

            const modal = document.getElementById('pointHistoryModal');
            if (modal) modal.classList.remove('hidden');
        },

        closePointHistoryModal() {
            const modal = document.getElementById('pointHistoryModal');
            if (modal) modal.classList.add('hidden');
        },

        pluralize(n, text_forms) {
            n = Math.abs(n) % 100;
            var n1 = n % 10;
            if (n > 10 && n < 20) { return text_forms[2]; }
            if (n1 > 1 && n1 < 5) { return text_forms[1]; }
            if (n1 == 1) { return text_forms[0]; }
            return text_forms[2];
        },

        setupPWA() {
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.state.deferredPrompt = e;
                const banner = document.getElementById('pwaInstallBanner');
                if (banner) banner.classList.remove('hidden');
                const btnSettings = document.getElementById('pwaInstallBtn');
                if (btnSettings) btnSettings.classList.remove('hidden');
            });

            const triggerInstall = async () => {
                const banner = document.getElementById('pwaInstallBanner');
                if (banner) banner.classList.add('hidden');
                if (this.state.deferredPrompt) {
                    this.state.deferredPrompt.prompt();
                    const { outcome } = await this.state.deferredPrompt.userChoice;
                    console.log(`PWA install choice: ${outcome}`);
                    this.state.deferredPrompt = null;
                }
            };

            document.getElementById('pwaBannerInstallBtn')?.addEventListener('click', triggerInstall);
            document.getElementById('pwaInstallBtn')?.addEventListener('click', triggerInstall);

            document.getElementById('pwaBannerCloseBtn')?.addEventListener('click', () => {
                const banner = document.getElementById('pwaInstallBanner');
                if (banner) banner.classList.add('hidden');
            });
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
