/**
 * CRM Service Fix - SPA Frontend Engine
 */

const CRM = {
    token: localStorage.getItem('crm_token') || null,
    currentUser: null,
    categories: [],
    priorities: [],
    settings: {},
    lang: localStorage.getItem('crm_lang') || 'ru',
    theme: localStorage.getItem('crm_theme') || 'light',

    // i18n Translations
    i18n: {
        ru: {
            nav_dashboard: "Дашборд",
            nav_tickets: "Заявки",
            nav_kanban: "Канбан Борд",
            nav_analytics: "Аналитика",
            nav_form_builder: "Конструктор Форм",
            nav_users: "Сотрудники",
            nav_settings: "Настройки",
            btn_create_ticket: "Новая заявка",
            ph_search: "Поиск заявки (номер, название...)",
            menu_profile: "Мой Профиль",
            menu_notif: "Уведомления",
            menu_logout: "Выход",
            status_new: "Новая",
            status_assigned: "Назначена",
            status_in_progress: "В работе",
            status_completed: "Выполнено",
            status_rejected: "Отклонено"
        },
        en: {
            nav_dashboard: "Dashboard",
            nav_tickets: "Requests",
            nav_kanban: "Kanban Board",
            nav_analytics: "Analytics",
            nav_form_builder: "Form Builder",
            nav_users: "Employees",
            nav_settings: "Settings",
            btn_create_ticket: "New Request",
            ph_search: "Search request...",
            menu_profile: "My Profile",
            menu_notif: "Notifications",
            menu_logout: "Logout",
            status_new: "New",
            status_assigned: "Assigned",
            status_in_progress: "In Progress",
            status_completed: "Completed",
            status_rejected: "Rejected"
        }
    },

    init() {
        this.applyTheme(this.theme);
        this.setupEventListeners();

        if (this.token) {
            this.fetchCurrentUser();
        } else {
            this.renderLoginView();
        }
    },

    async api(endpoint, options = {}) {
        options.headers = options.headers || {};
        if (this.token) {
            options.headers['Authorization'] = `Bearer ${this.token}`;
        }
        if (options.body && !(options.body instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
        }

        try {
            const res = await fetch(`api/${endpoint}`, options);
            const data = await res.json();
            if (res.status === 401) {
                this.logout();
                return null;
            }
            if (!data.success) {
                this.toast(data.error || 'Произошла ошибка', 'danger');
            }
            return data;
        } catch (e) {
            this.toast('Ошибка соединения с сервером', 'danger');
            return null;
        }
    },

    toast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerText = message;
        container.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    },

    applyTheme(theme) {
        this.theme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('crm_theme', theme);
        const icon = document.querySelector('#themeToggleBtn i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    },

    setupEventListeners() {
        // Theme toggle
        document.getElementById('themeToggleBtn')?.addEventListener('click', () => {
            this.applyTheme(this.theme === 'light' ? 'dark' : 'light');
        });

        // Language select
        const langSelect = document.getElementById('langSelect');
        if (langSelect) {
            langSelect.value = this.lang;
            langSelect.addEventListener('change', (e) => {
                this.lang = e.target.value;
                localStorage.setItem('crm_lang', this.lang);
                this.updateTranslations();
            });
        }

        // Profile Menu Toggle
        document.getElementById('profileMenuBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('profileDropdown')?.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            document.getElementById('profileDropdown')?.classList.remove('show');
        });

        // Logout
        document.getElementById('logoutBtn')?.addEventListener('click', () => this.logout());

        // Quick New Ticket
        document.getElementById('quickNewTicketBtn')?.addEventListener('click', () => this.openNewTicketModal());

        // Global Search
        document.getElementById('globalSearchInput')?.addEventListener('input', (e) => {
            const val = e.target.value;
            if (window.location.hash === '#tickets') {
                this.renderTicketsView(val);
            }
        });

        // Hash Routing
        window.addEventListener('hashchange', () => this.handleRoute());
    },

    updateTranslations() {
        const dict = this.i18n[this.lang] || this.i18n.ru;
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (dict[key]) el.innerText = dict[key];
        });
        document.querySelectorAll('[data-i18n-ph]').forEach(el => {
            const key = el.getAttribute('data-i18n-ph');
            if (dict[key]) el.placeholder = dict[key];
        });
    },

    async fetchCurrentUser() {
        const res = await this.api('auth.php?action=me');
        if (res && res.success) {
            this.currentUser = res.user;
            this.updateUserUI();
            await this.loadInitialMetadata();
            this.handleRoute();
        } else {
            this.renderLoginView();
        }
    },

    async loadInitialMetadata() {
        const res = await this.api('settings.php');
        if (res && res.success) {
            this.categories = res.categories || [];
            this.priorities = res.priorities || [];
            this.settings = res.settings || {};
        }
    },

    updateUserUI() {
        if (!this.currentUser) return;

        document.getElementById('userName').innerText = this.currentUser.full_name;
        document.getElementById('userRole').innerText = this.currentUser.role.toUpperCase();
        document.getElementById('userAvatar').innerText = this.currentUser.full_name.charAt(0);

        // Role visibility toggles
        document.querySelectorAll('.admin-only').forEach(el => {
            el.style.display = this.currentUser.role === 'admin' ? 'flex' : 'none';
        });
        document.querySelectorAll('.admin-manager-only').forEach(el => {
            el.style.display = (this.currentUser.role === 'admin' || this.currentUser.role === 'manager') ? 'flex' : 'none';
        });

        this.updateTranslations();
    },

    logout() {
        this.token = null;
        this.currentUser = null;
        localStorage.removeItem('crm_token');
        this.renderLoginView();
    },

    handleRoute() {
        if (!this.currentUser) {
            this.renderLoginView();
            return;
        }

        const hash = window.location.hash || '#dashboard';
        const page = hash.replace('#', '');

        // Update nav active link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-page') === page);
        });

        switch (page) {
            case 'dashboard': this.renderDashboardView(); break;
            case 'tickets': this.renderTicketsView(); break;
            case 'kanban': this.renderKanbanView(); break;
            case 'analytics': this.renderAnalyticsView(); break;
            case 'form-builder': this.renderFormBuilderView(); break;
            case 'users': this.renderUsersView(); break;
            case 'settings': this.renderSettingsView(); break;
            case 'profile': this.renderProfileView(); break;
            case 'notifications': this.renderNotificationsView(); break;
            default: this.renderDashboardView(); break;
        }
    },

    // ==========================================
    // LOGIN & RESET PASSWORD VIEW
    // ==========================================
    renderLoginView() {
        const container = document.getElementById('pageContainer');
        document.getElementById('sidebar').style.display = 'none';

        container.innerHTML = `
            <div style="max-width: 400px; margin: 80px auto;" class="card-table">
                <div style="padding: 28px;">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <i class="fa-solid fa-screwdriver-wrench" style="font-size: 36px; color: var(--primary);"></i>
                        <h2 style="font-size: 20px; font-weight: 700; margin-top: 8px;">CRM Service Fix</h2>
                        <p style="font-size: 13px; color: var(--text-muted);">Система управления заявками</p>
                    </div>

                    <form id="loginForm">
                        <div class="form-group">
                            <label class="form-label">Логин</label>
                            <input type="text" id="loginUsername" class="form-control" placeholder="admin / user / tech1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Пароль</label>
                            <input type="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 12px;">Войти в систему</button>
                    </form>

                    <div style="text-align: center; margin-top: 16px;">
                        <a href="#" id="resetPasswordLink" style="font-size: 12px;">Забыли пароль?</a>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;

            const res = await this.api('auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ username, password })
            });

            if (res && res.success) {
                this.token = res.token;
                this.currentUser = res.user;
                localStorage.setItem('crm_token', res.token);
                document.getElementById('sidebar').style.display = 'flex';
                this.updateUserUI();
                await this.loadInitialMetadata();
                window.location.hash = '#dashboard';
            }
        });

        document.getElementById('resetPasswordLink')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.openResetPasswordModal();
        });
    },

    openResetPasswordModal() {
        this.modal(`
            <h3 style="font-size: 18px; margin-bottom: 16px;">Восстановление пароля</h3>
            <form id="resetPassForm">
                <div class="form-group">
                    <label class="form-label">Ваш Логин</label>
                    <input type="text" id="resetUsername" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ваш Email</label>
                    <input type="email" id="resetEmail" class="form-control" required>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="CRM.closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сбросить пароль</button>
                </div>
            </form>
        `);

        document.getElementById('resetPassForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('resetUsername').value;
            const email = document.getElementById('resetEmail').value;

            const res = await this.api('auth.php?action=reset-password', {
                method: 'POST',
                body: JSON.stringify({ username, email })
            });

            if (res && res.success) {
                this.toast(res.message, 'success');
                this.closeModal();
            }
        });
    },

    // ==========================================
    // DASHBOARD VIEW
    // ==========================================
    async renderDashboardView() {
        const container = document.getElementById('pageContainer');
        const data = await this.api('analytics.php?action=dashboard');
        const m = data?.metrics || { status_counts: {}, sla_stats: {} };

        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Дашборд состояния заявок</h1>
                <button class="btn btn-primary" onclick="CRM.openNewTicketModal()">
                    <i class="fa-solid fa-plus"></i> Подать заявку
                </button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-layer-group"></i></div>
                    <div>
                        <div class="stat-value">${m.status_counts.total || 0}</div>
                        <div class="stat-label">Всего заявок</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="stat-value">${m.status_counts.in_progress || 0}</div>
                        <div class="stat-label">В работе</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="stat-value">${m.status_counts.completed || 0}</div>
                        <div class="stat-label">Выполнено</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <div class="stat-value">${m.sla_stats.breached || 0}</div>
                        <div class="stat-label">SLA Просрочено</div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="card-table" style="padding: 20px;">
                    <h3 style="font-size: 16px; margin-bottom: 16px;">Последние активные заявки</h3>
                    <div id="dashboardTicketsList">Загрузка...</div>
                </div>

                <div class="card-table" style="padding: 20px;">
                    <h3 style="font-size: 16px; margin-bottom: 16px;">KPI Эффективности</h3>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="padding: 12px; background: var(--bg-surface-subtle); border-radius: var(--radius-md);">
                            <div style="font-size: 12px; color: var(--text-muted);">Среднее время ремонта (MTTR)</div>
                            <div style="font-size: 22px; font-weight: 700; color: var(--primary);">${m.avg_resolution_hours} ч.</div>
                        </div>
                        <div style="padding: 12px; background: var(--bg-surface-subtle); border-radius: var(--radius-md);">
                            <div style="font-size: 12px; color: var(--text-muted);">Выполнено успешно</div>
                            <div style="font-size: 22px; font-weight: 700; color: var(--success);">${m.completed_count} заявок</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.loadDashboardTicketsList();
    },

    async loadDashboardTicketsList() {
        const res = await this.api('tickets.php');
        const listEl = document.getElementById('dashboardTicketsList');
        if (!res || !res.tickets) return;

        document.getElementById('sidebarTicketCount').innerText = res.tickets.length;

        if (res.tickets.length === 0) {
            listEl.innerHTML = '<p style="color: var(--text-muted); font-size: 13px;">Заявок пока нет.</p>';
            return;
        }

        const rows = res.tickets.slice(0, 5).map(t => `
            <tr style="cursor: pointer;" onclick="CRM.openTicketDetailModal(${t.id})">
                <td style="font-weight: 700;">${t.number}</td>
                <td>${t.title}</td>
                <td><span class="badge badge-status-${t.status}">${this.getStatusLabel(t.status)}</span></td>
                <td>${t.created_at}</td>
            </tr>
        `).join('');

        listEl.innerHTML = `
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Тема</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    },

    // ==========================================
    // TICKETS LIST & ARCHIVE VIEW
    // ==========================================
    async renderTicketsView(searchQuery = '') {
        const container = document.getElementById('pageContainer');
        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Список заявок</h1>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-secondary" onclick="CRM.toggleArchiveView(this)">
                        <i class="fa-solid fa-box-archive"></i> Архив
                    </button>
                    <button class="btn btn-primary" onclick="CRM.openNewTicketModal()">
                        <i class="fa-solid fa-plus"></i> Новая заявка
                    </button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="card-table" style="padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <select id="filterStatus" class="form-select select-sm" style="width: 160px;" onchange="CRM.fetchFilteredTickets()">
                        <option value="">Все статусы</option>
                        <option value="new">Новая</option>
                        <option value="assigned">Назначена</option>
                        <option value="in_progress">В работе</option>
                        <option value="completed">Выполнено</option>
                        <option value="rejected">Отклонено</option>
                    </select>

                    <select id="filterCategory" class="form-select select-sm" style="width: 180px;" onchange="CRM.fetchFilteredTickets()">
                        <option value="">Все категории</option>
                        ${this.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                    </select>

                    <select id="filterPriority" class="form-select select-sm" style="width: 160px;" onchange="CRM.fetchFilteredTickets()">
                        <option value="">Все приоритеты</option>
                        ${this.priorities.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                    </select>
                </div>
            </div>

            <div class="card-table">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>№ Заявки</th>
                                <th>Тема</th>
                                <th>Категория</th>
                                <th>Приоритет</th>
                                <th>Статус</th>
                                <th>SLA Контроль</th>
                                <th>Исполнитель</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody id="ticketsTableBody">
                            <tr><td colspan="8" style="text-align: center;">Загрузка данных...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        this.fetchFilteredTickets(searchQuery);
    },

    isArchiveView: false,
    toggleArchiveView(btn) {
        this.isArchiveView = !this.isArchiveView;
        btn.classList.toggle('btn-primary', this.isArchiveView);
        this.fetchFilteredTickets();
    },

    async fetchFilteredTickets(searchQuery = '') {
        const status = document.getElementById('filterStatus')?.value || '';
        const cat = document.getElementById('filterCategory')?.value || '';
        const prio = document.getElementById('filterPriority')?.value || '';
        const archived = this.isArchiveView ? '1' : '0';

        const params = new URLSearchParams({
            status, category_id: cat, priority_id: prio, archived, search: searchQuery
        });

        const res = await this.api(`tickets.php?${params.toString()}`);
        const tbody = document.getElementById('ticketsTableBody');
        if (!tbody || !res) return;

        if (!res.tickets || res.tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted);">Заявки не найдены</td></tr>';
            return;
        }

        tbody.innerHTML = res.tickets.map(t => `
            <tr style="cursor: pointer;" onclick="CRM.openTicketDetailModal(${t.id})">
                <td style="font-weight: 700; color: var(--primary);">${t.number}</td>
                <td style="font-weight: 600;">${t.title}</td>
                <td>${t.category?.name || '—'}</td>
                <td><span style="color: ${t.priority?.color}; font-weight: 600;">● ${t.priority?.name || 'Обычный'}</span></td>
                <td><span class="badge badge-status-${t.status}">${this.getStatusLabel(t.status)}</span></td>
                <td><span class="badge badge-sla-${t.sla_status}">${this.getSlaLabel(t.sla_status)}</span></td>
                <td>${t.assignee?.full_name || '<span style="color: var(--text-muted)">Не назначен</span>'}</td>
                <td style="font-size: 12px;">${t.created_at}</td>
            </tr>
        `).join('');
    },

    getStatusLabel(st) {
        const labels = {
            new: "Новая",
            assigned: "Назначена",
            in_progress: "В работе",
            completed: "Выполнено",
            rejected: "Отклонено"
        };
        return labels[st] || st;
    },

    getSlaLabel(sla) {
        if (sla === 'ok') return 'SLA В норме';
        if (sla === 'warning') return 'SLA Внимание';
        if (sla === 'breached') return 'SLA Просрочено';
        return 'Выполнено';
    },

    // ==========================================
    // INTERACTIVE KANBAN BOARD WITH DRAG & DROP
    // ==========================================
    async renderKanbanView() {
        const container = document.getElementById('pageContainer');
        const res = await this.api('tickets.php');
        const tickets = res?.tickets || [];

        const columns = [
            { id: 'new', title: 'Новые' },
            { id: 'assigned', title: 'Назначены' },
            { id: 'in_progress', title: 'В работе' },
            { id: 'completed', title: 'Выполнено' }
        ];

        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Канбан Борд управления заявками</h1>
                <p style="font-size: 13px; color: var(--text-muted);">Перетаскивайте карточки для смены статуса</p>
            </div>

            <div class="kanban-board">
                ${columns.map(col => `
                    <div class="kanban-column" data-status="${col.id}">
                        <div class="kanban-column-header">
                            <span class="kanban-column-title">${col.title}</span>
                            <span class="badge badge-primary">${tickets.filter(t => t.status === col.id).length}</span>
                        </div>
                        <div class="kanban-cards" ondragover="CRM.handleDragOver(event)" ondragleave="CRM.handleDragLeave(event)" ondrop="CRM.handleDrop(event, '${col.id}')">
                            ${tickets.filter(t => t.status === col.id).map(t => `
                                <div class="kanban-card" draggable="true" ondragstart="CRM.handleDragStart(event, ${t.id})" onclick="CRM.openTicketDetailModal(${t.id})">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-weight: 700; font-size: 12px; color: var(--primary);">${t.number}</span>
                                        <span class="badge badge-sla-${t.sla_status}" style="font-size: 10px;">${this.getSlaLabel(t.sla_status)}</span>
                                    </div>
                                    <div style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">${t.title}</div>
                                    <div style="font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between;">
                                        <span><i class="fa-solid fa-user"></i> ${t.assignee?.full_name || 'Не назначен'}</span>
                                        <span>${t.category?.name || ''}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    draggedTicketId: null,
    handleDragStart(e, id) {
        this.draggedTicketId = id;
        e.dataTransfer.setData('text/plain', id);
        e.target.classList.add('dragging');
    },

    handleDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    },

    handleDragLeave(e) {
        e.currentTarget.classList.remove('drag-over');
    },

    async handleDrop(e, targetStatus) {
        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');
        if (!this.draggedTicketId) return;

        const res = await this.api('tickets.php?action=update-status', {
            method: 'POST',
            body: JSON.stringify({ id: this.draggedTicketId, status: targetStatus })
        });

        if (res && res.success) {
            this.toast('Статус заявки обновлён', 'success');
            this.renderKanbanView();
        }
    },

    // ==========================================
    // TICKET DETAILS MODAL & COMMENTS
    // ==========================================
    async openTicketDetailModal(id) {
        const res = await this.api(`tickets.php`);
        const ticket = res?.tickets?.find(t => t.id == id);
        if (!ticket) return;

        const commentsRes = await this.api(`comments.php?ticket_id=${id}`);
        const comments = commentsRes?.comments || [];

        const usersRes = await this.api('users.php');
        const executors = (usersRes?.users || []).filter(u => u.role === 'executor');

        this.modal(`
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <span style="font-weight: 700; color: var(--primary); font-size: 14px;">${ticket.number}</span>
                    <h2 style="font-size: 18px; font-weight: 700;">${ticket.title}</h2>
                </div>
                <button class="btn-icon" onclick="CRM.closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <p style="font-size: 14px; margin-bottom: 16px;">${ticket.description}</p>

                    <!-- Custom Dynamic Form Fields -->
                    ${ticket.custom_fields && Object.keys(ticket.custom_fields).length > 0 ? `
                        <div style="background: var(--bg-surface-subtle); padding: 12px; border-radius: var(--radius-md); margin-bottom: 16px;">
                            <h4 style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Дополнительные параметры</h4>
                            <div style="font-size: 13px;">
                                ${Object.entries(ticket.custom_fields).map(([k, v]) => `<div><strong>${k}:</strong> ${v}</div>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div style="background: var(--bg-surface-subtle); padding: 12px; border-radius: var(--radius-md); font-size: 13px;">
                    <div style="margin-bottom: 8px;"><strong>Статус:</strong> <span class="badge badge-status-${ticket.status}">${this.getStatusLabel(ticket.status)}</span></div>
                    <div style="margin-bottom: 8px;"><strong>Приоритет:</strong> ${ticket.priority?.name}</div>
                    <div style="margin-bottom: 8px;"><strong>Заявитель:</strong> ${ticket.creator?.full_name || '—'}</div>
                    <div style="margin-bottom: 12px;"><strong>Исполнитель:</strong> ${ticket.assignee?.full_name || 'Не назначен'}</div>

                    <!-- Assign Executor Selector for Manager/Admin -->
                    ${(this.currentUser.role === 'admin' || this.currentUser.role === 'manager') ? `
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Назначить исполнителя</label>
                            <select id="modalAssigneeSelect" class="form-select select-sm" onchange="CRM.assignExecutorFromModal(${ticket.id}, this.value)">
                                <option value="">Выберите технтика</option>
                                ${executors.map(e => `<option value="${e.id}" ${ticket.assigned_to == e.id ? 'selected' : ''}>${e.full_name}</option>`).join('')}
                            </select>
                        </div>
                    ` : ''}

                    <button class="btn btn-secondary" style="width: 100%; font-size: 12px; margin-top: 8px;" onclick="CRM.toggleArchiveTicket(${ticket.id})">
                        <i class="fa-solid fa-box-archive"></i> ${ticket.archived ? 'Извлечь из архива' : 'В архив'}
                    </button>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 16px 0;">

            <!-- Comments Timeline -->
            <h3 style="font-size: 15px; margin-bottom: 12px;">Обсуждение и отчётность</h3>
            <div class="comments-timeline" id="commentsTimeline">
                ${comments.map(c => `
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-author">${c.user_name} (${c.role.toUpperCase()})</span>
                            <span style="color: var(--text-muted);">${c.created_at}</span>
                        </div>
                        <div class="comment-body">${c.comment}</div>
                    </div>
                `).join('')}
            </div>

            <form id="commentAddForm" style="margin-top: 16px;">
                <div class="form-group">
                    <textarea id="commentText" class="form-control" placeholder="Напишите комментарий или фото-отчёт..." rows="2" required></textarea>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="font-size: 12px; display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" id="commentCompletionCheck"> Отметка о завершении работы
                    </label>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Отправить</button>
                </div>
            </form>
        `);

        document.getElementById('commentAddForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const comment = document.getElementById('commentText').value;
            const is_completion_report = document.getElementById('commentCompletionCheck').checked;

            const res = await this.api('comments.php', {
                method: 'POST',
                body: JSON.stringify({ ticket_id: id, comment, is_completion_report })
            });

            if (res && res.success) {
                this.toast('Комментарий добавлен', 'success');
                this.openTicketDetailModal(id);
            }
        });
    },

    async assignExecutorFromModal(ticketId, executorId) {
        if (!executorId) return;
        const res = await this.api('assignments.php?action=assign', {
            method: 'POST',
            body: JSON.stringify({ ticket_id: ticketId, executor_id: executorId })
        });
        if (res && res.success) {
            this.toast(res.message, 'success');
            this.openTicketDetailModal(ticketId);
        }
    },

    async toggleArchiveTicket(id) {
        const res = await this.api('tickets.php?action=toggle-archive', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        if (res && res.success) {
            this.toast(res.message, 'success');
            this.closeModal();
            this.handleRoute();
        }
    },

    // ==========================================
    // CREATE NEW TICKET MODAL WITH FORM BUILDER
    // ==========================================
    async openNewTicketModal() {
        const fieldsRes = await this.api('form-fields.php');
        const customFields = fieldsRes?.fields || [];

        this.modal(`
            <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">Подача новой заявки</h2>
            <form id="newTicketForm">
                <div class="form-group">
                    <label class="form-label">Тема заявки</label>
                    <input type="text" id="ticketTitle" class="form-control" placeholder="Например: Не работает розетка" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Категория</label>
                        <select id="ticketCategory" class="form-select" required>
                            ${this.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Приоритет</label>
                        <select id="ticketPriority" class="form-select" required>
                            ${this.priorities.map(p => `<option value="${p.id}">${p.name} (SLA: ${p.sla_hours}ч)</option>`).join('')}
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Подробное описание проблемы</label>
                    <textarea id="ticketDescription" class="form-control" rows="3" required></textarea>
                </div>

                <!-- Custom Form Builder Rendered Fields -->
                ${customFields.map(f => `
                    <div class="form-group">
                        <label class="form-label">${f.label} ${f.required ? '*' : ''}</label>
                        <input type="${f.field_type}" data-custom-field="${f.label}" class="form-control custom-field-input" placeholder="${f.placeholder || ''}" ${f.required ? 'required' : ''}>
                    </div>
                `).join('')}

                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="CRM.closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Отправить заявку</button>
                </div>
            </form>
        `);

        document.getElementById('newTicketForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = document.getElementById('ticketTitle').value;
            const category_id = document.getElementById('ticketCategory').value;
            const priority_id = document.getElementById('ticketPriority').value;
            const description = document.getElementById('ticketDescription').value;

            const custom_fields = {};
            document.querySelectorAll('.custom-field-input').forEach(inp => {
                const label = inp.getAttribute('data-custom-field');
                if (label && inp.value) {
                    custom_fields[label] = inp.value;
                }
            });

            const res = await this.api('tickets.php?action=create', {
                method: 'POST',
                body: JSON.stringify({ title, category_id, priority_id, description, custom_fields })
            });

            if (res && res.success) {
                this.toast('Заявка успешно создана!', 'success');
                this.closeModal();
                this.handleRoute();
            }
        });
    },

    // ==========================================
    // FORM BUILDER DRAG-AND-DROP VIEW
    // ==========================================
    async renderFormBuilderView() {
        const container = document.getElementById('pageContainer');
        const res = await this.api('form-fields.php');
        const fields = res?.fields || [];

        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Конструктор настраиваемых полей формы</h1>
                <button class="btn btn-primary" onclick="CRM.openAddFieldModal()">
                    <i class="fa-solid fa-plus"></i> Добавить поле
                </button>
            </div>

            <div class="card-table" style="padding: 20px;">
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                    Перетаскивайте поля для изменения порядка отображения при создании заявки.
                </p>

                <div id="fieldsDragList" style="display: flex; flex-direction: column; gap: 8px;">
                    ${fields.map(f => `
                        <div class="field-drag-item" data-id="${f.id}" draggable="true" ondragstart="CRM.handleFieldDragStart(event)" ondragover="CRM.handleFieldDragOver(event)" ondrop="CRM.handleFieldDrop(event)" style="padding: 12px; background: var(--bg-surface-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center; cursor: grab;">
                            <div>
                                <i class="fa-solid fa-grip-vertical" style="color: var(--text-muted); margin-right: 12px;"></i>
                                <strong>${f.label}</strong>
                                <span class="badge badge-primary" style="margin-left: 8px;">${f.field_type}</span>
                                ${f.required ? '<span class="badge badge-status-rejected">Обязательное</span>' : ''}
                            </div>
                            <button class="btn-icon" style="color: var(--danger);" onclick="CRM.deleteFormField(${f.id})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    },

    draggedFieldEl: null,
    handleFieldDragStart(e) {
        this.draggedFieldEl = e.currentTarget;
        e.dataTransfer.effectAllowed = 'move';
    },

    handleFieldDragOver(e) {
        e.preventDefault();
    },

    async handleFieldDrop(e) {
        e.preventDefault();
        const target = e.currentTarget;
        if (this.draggedFieldEl && target !== this.draggedFieldEl) {
            const container = document.getElementById('fieldsDragList');
            container.insertBefore(this.draggedFieldEl, target);

            // Send new order to server
            const orderedIds = Array.from(container.querySelectorAll('.field-drag-item')).map(el => el.getAttribute('data-id'));
            await this.api('form-fields.php?action=reorder', {
                method: 'POST',
                body: JSON.stringify({ order: orderedIds })
            });
            this.toast('Порядок полей сохранён', 'success');
        }
    },

    openAddFieldModal() {
        this.modal(`
            <h3 style="font-size: 18px; margin-bottom: 16px;">Добавить поле в конструктор</h3>
            <form id="addFieldForm">
                <div class="form-group">
                    <label class="form-label">Заголовок поля (Label)</label>
                    <input type="text" id="fieldLabel" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Тип поля</label>
                    <select id="fieldType" class="form-select">
                        <option value="text">Текст (Text)</option>
                        <option value="number">Число (Number)</option>
                        <option value="date">Дата (Date)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Подсказка (Placeholder)</label>
                    <input type="text" id="fieldPlaceholder" class="form-control">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="fieldRequired"> Обязательное поле
                    </label>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="CRM.closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        `);

        document.getElementById('addFieldForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const label = document.getElementById('fieldLabel').value;
            const field_type = document.getElementById('fieldType').value;
            const placeholder = document.getElementById('fieldPlaceholder').value;
            const required = document.getElementById('fieldRequired').checked;

            const res = await this.api('form-fields.php', {
                method: 'POST',
                body: JSON.stringify({ label, field_type, placeholder, required })
            });

            if (res && res.success) {
                this.toast('Поле добавлено', 'success');
                this.closeModal();
                this.renderFormBuilderView();
            }
        });
    },

    async deleteFormField(id) {
        if (!confirm('Удалить это поле?')) return;
        const res = await this.api(`form-fields.php?id=${id}`, { method: 'DELETE' });
        if (res && res.success) {
            this.toast('Поле удалено', 'success');
            this.renderFormBuilderView();
        }
    },

    // ==========================================
    // ANALYTICS & EXPORT VIEW
    // ==========================================
    async renderAnalyticsView() {
        const container = document.getElementById('pageContainer');
        const res = await this.api('analytics.php?action=dashboard');
        const m = res?.metrics || { status_counts: {}, executor_workload: [] };

        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Аналитика и Отчётность</h1>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> Печать / PDF
                    </button>
                    <a href="api/analytics.php?action=export-excel&token=${this.token}" class="btn btn-primary" target="_blank">
                        <i class="fa-solid fa-file-excel"></i> Экспорт в Excel (CSV)
                    </a>
                </div>
            </div>

            <div class="card-table" style="padding: 20px; margin-bottom: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Нагрузка и производительность сотрудников</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ФИО Сотрудника</th>
                                <th>Отдел</th>
                                <th>Назначено</th>
                                <th>В работе</th>
                                <th>Выполнено</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${m.executor_workload.map(w => `
                                <tr>
                                    <td style="font-weight: 600;">${w.full_name}</td>
                                    <td>${w.department}</td>
                                    <td><span class="badge badge-status-assigned">${w.assigned}</span></td>
                                    <td><span class="badge badge-status-in_progress">${w.in_progress}</span></td>
                                    <td><span class="badge badge-status-completed">${w.completed}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    // ==========================================
    // ADMIN SETTINGS VIEW & STORAGE SWITCHER
    // ==========================================
    async renderSettingsView() {
        const container = document.getElementById('pageContainer');
        const res = await this.api('settings.php');
        const s = res?.settings || {};

        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Системные Настройки</h1>
            </div>

            <div class="card-table" style="padding: 24px; max-width: 700px;">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Хранилище данных (JSON ↔ MySQL)</h3>
                <div style="padding: 16px; background: var(--bg-surface-subtle); border-radius: var(--radius-md); margin-bottom: 20px;">
                    <p style="font-size: 13px; margin-bottom: 12px;">Текущий режим: <strong>${(s.storage_mode || 'json').toUpperCase()}</strong></p>

                    <form id="storageSwitchForm">
                        <div class="form-group">
                            <label class="form-label">Выберите режим хранения</label>
                            <select id="storageModeSelect" class="form-select" onchange="CRM.toggleMySQLConfigVisibility(this.value)">
                                <option value="json" ${s.storage_mode === 'json' ? 'selected' : ''}>JSON файлы (локальное атомарное хранилище)</option>
                                <option value="mysql" ${s.storage_mode === 'mysql' ? 'selected' : ''}>MySQL Сервер БД (авто-миграция и таблицы)</option>
                            </select>
                        </div>

                        <div id="mysqlConfigBlock" style="display: ${s.storage_mode === 'mysql' ? 'block' : 'none'}; border-top: 1px solid var(--border-color); pt-3; mt-3;">
                            <div class="form-group"><label class="form-label">Host</label><input type="text" id="dbHost" class="form-control" value="${s.mysql?.host || 'localhost'}"></div>
                            <div class="form-group"><label class="form-label">Database Name</label><input type="text" id="dbName" class="form-control" value="${s.mysql?.dbname || 'crm_maintenance'}"></div>
                            <div class="form-group"><label class="form-label">Username</label><input type="text" id="dbUser" class="form-control" value="${s.mysql?.username || 'root'}"></div>
                            <div class="form-group"><label class="form-label">Password</label><input type="password" id="dbPass" class="form-control" value="${s.mysql?.password || ''}"></div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Применить изменения хранилища</button>
                    </form>
                </div>
            </div>
        `;

        document.getElementById('storageSwitchForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const mode = document.getElementById('storageModeSelect').value;
            const mysql = {
                host: document.getElementById('dbHost')?.value || 'localhost',
                dbname: document.getElementById('dbName')?.value || 'crm_maintenance',
                username: document.getElementById('dbUser')?.value || 'root',
                password: document.getElementById('dbPass')?.value || ''
            };

            const res = await this.api('settings.php?action=switch-storage', {
                method: 'POST',
                body: JSON.stringify({ storage_mode: mode, mysql })
            });

            if (res && res.success) {
                this.toast(res.message, 'success');
                this.renderSettingsView();
            }
        });
    },

    toggleMySQLConfigVisibility(val) {
        const block = document.getElementById('mysqlConfigBlock');
        if (block) block.style.display = val === 'mysql' ? 'block' : 'none';
    },

    // ==========================================
    // USERS MANAGEMENT VIEW
    // ==========================================
    async renderUsersView() {
        const container = document.getElementById('pageContainer');
        const res = await this.api('users.php');
        const users = res?.users || [];

        container.innerHTML = `
            <div class="page-title-row">
                <h1 class="page-title">Сотрудники и Права доступа</h1>
                <button class="btn btn-primary" onclick="CRM.openCreateUserModal()">
                    <i class="fa-solid fa-user-plus"></i> Добавить сотрудника
                </button>
            </div>

            <div class="card-table">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ФИО</th>
                                <th>Логин</th>
                                <th>Роль</th>
                                <th>Отдел</th>
                                <th>Контакты</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${users.map(u => `
                                <tr>
                                    <td style="font-weight: 600;">${u.full_name}</td>
                                    <td>${u.username}</td>
                                    <td><span class="badge badge-primary">${u.role.toUpperCase()}</span></td>
                                    <td>${u.department || '—'}</td>
                                    <td style="font-size: 12px;">${u.email}<br>${u.phone}</td>
                                    <td>${u.active ? '<span class="badge badge-sla-ok">Активен</span>' : '<span class="badge badge-sla-breached">Заблокирован</span>'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    openCreateUserModal() {
        this.modal(`
            <h3 style="font-size: 18px; margin-bottom: 16px;">Добавление сотрудника</h3>
            <form id="createUserForm">
                <div class="form-group"><label class="form-label">ФИО</label><input type="text" id="uFullName" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Логин</label><input type="text" id="uUsername" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Пароль</label><input type="password" id="uPassword" class="form-control" required></div>
                <div class="form-group">
                    <label class="form-label">Роль</label>
                    <select id="uRole" class="form-select">
                        <option value="responsible">Ответственный сотрудник</option>
                        <option value="manager">Начальник отдела</option>
                        <option value="executor">Исполнитель (Техник)</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Отдел</label><input type="text" id="uDepartment" class="form-control"></div>
                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="CRM.closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        `);

        document.getElementById('createUserForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const full_name = document.getElementById('uFullName').value;
            const username = document.getElementById('uUsername').value;
            const password = document.getElementById('uPassword').value;
            const role = document.getElementById('uRole').value;
            const department = document.getElementById('uDepartment').value;

            const res = await this.api('users.php', {
                method: 'POST',
                body: JSON.stringify({ full_name, username, password, role, department })
            });

            if (res && res.success) {
                this.toast('Сотрудник создан', 'success');
                this.closeModal();
                this.renderUsersView();
            }
        });
    },

    // Helper Modal Windows
    modal(htmlContent) {
        const overlay = document.getElementById('modalOverlay');
        const box = document.getElementById('modalContent');
        box.innerHTML = htmlContent;
        overlay.classList.remove('hidden');
    },

    closeModal() {
        document.getElementById('modalOverlay').classList.add('hidden');
    }
};

// Start application
document.addEventListener('DOMContentLoaded', () => CRM.init());
