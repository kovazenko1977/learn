const App = {
  user: null,
  token: localStorage.getItem('crm_token') || null,
  lang: localStorage.getItem('crm_lang') || 'ru',
  theme: localStorage.getItem('crm_theme') || 'light',
  currentView: 'dashboard',
  tickets: [],
  fields: [],
  users: [],
  settings: null,
  notifications: [],
  selectedTicketIds: new Set(),

  t(key) {
    return (I18N[this.lang] && I18N[this.lang][key]) || key;
  },

  async init() {
    this.applyTheme(this.theme);
    this.setupGlobalEvents();

    if (this.token) {
      try {
        const res = await this.apiFetch('auth/me');
        if (res.success && res.user) {
          this.user = res.user;
          await this.loadInitialData();
          this.renderApp();
          return;
        }
      } catch (e) {
        console.warn('Session expired, please login again.');
        this.logout();
      }
    }

    this.renderLogin();
  },

  applyTheme(theme) {
    this.theme = theme;
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('crm_theme', theme);
    const themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
      themeBtn.innerHTML = theme === 'dark' ? '<i class="fa fa-sun"></i>' : '<i class="fa fa-moon"></i>';
    }
  },

  toggleTheme() {
    this.applyTheme(this.theme === 'dark' ? 'light' : 'dark');
  },

  setLanguage(lang) {
    this.lang = lang;
    localStorage.setItem('crm_lang', lang);
    if (this.user) {
      this.renderApp();
    } else {
      this.renderLogin();
    }
  },

  async apiFetch(endpoint, options = {}) {
    options.headers = options.headers || {};
    if (this.token) {
      options.headers['Authorization'] = `Bearer ${this.token}`;
    }
    if (!(options.body instanceof FormData) && options.body && typeof options.body === 'object') {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }

    const response = await fetch(`api/${endpoint}`, options);
    const data = await response.json();
    return data;
  },

  async loadInitialData() {
    const [ticketsRes, fieldsRes, usersRes, settingsRes, notifRes] = await Promise.all([
      this.apiFetch('tickets'),
      this.apiFetch('fields'),
      this.apiFetch('users'),
      this.apiFetch('settings'),
      this.apiFetch('notifications')
    ]);

    if (ticketsRes.success) this.tickets = ticketsRes.tickets;
    if (fieldsRes.success) this.fields = fieldsRes.fields;
    if (usersRes.success) this.users = usersRes.users;
    if (settingsRes.success) this.settings = settingsRes.settings;
    if (notifRes.success) this.notifications = notifRes.notifications;
  },

  toast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<span>${message}</span>`;
    container.appendChild(el);

    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 250);
    }, 3500);
  },

  setupGlobalEvents() {
    window.addEventListener('popstate', () => {
      const hash = window.location.hash.replace('#', '') || 'dashboard';
      if (this.user) this.navigate(hash, false);
    });
  },

  navigate(viewName, pushState = true) {
    this.currentView = viewName;
    if (pushState) {
      window.location.hash = viewName;
    }

    document.querySelectorAll('.nav-link').forEach(link => {
      link.classList.toggle('active', link.dataset.view === viewName);
    });

    const bodyEl = document.getElementById('viewContent');
    if (!bodyEl) return;

    switch (viewName) {
      case 'dashboard':
        this.renderDashboardView(bodyEl);
        break;
      case 'tickets':
        this.renderTicketsView(bodyEl);
        break;
      case 'kanban':
        this.renderKanbanView(bodyEl);
        break;
      case 'form_builder':
        this.renderFormBuilderView(bodyEl);
        break;
      case 'analytics':
        this.renderAnalyticsView(bodyEl);
        break;
      case 'settings':
        this.renderSettingsView(bodyEl);
        break;
      case 'profile':
        this.renderProfileView(bodyEl);
        break;
      case 'notifications':
        this.renderNotificationsView(bodyEl);
        break;
      default:
        this.renderDashboardView(bodyEl);
    }
  },

  renderLogin() {
    const root = document.getElementById('appRoot');
    root.innerHTML = `
      <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #1e293b, #0f172a);">
        <div class="card" style="width: 100%; max-width: 440px; padding: 32px; border-radius: 20px; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.3);">
          <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 56px; height: 56px; margin: 0 auto 16px; background: #2563eb; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff;">
              <i class="fa fa-wrench"></i>
            </div>
            <h2 style="font-size: 22px; margin-bottom: 6px;">${this.t('app_title')}</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Вход в систему управления заявками</p>
          </div>

          <form id="loginForm" onsubmit="App.handleLogin(event)">
            <div class="form-group">
              <label class="form-label">Логин пользователя</label>
              <input type="text" id="loginUsername" class="form-control" placeholder="admin, manager, executor или requester" required autofocus />
            </div>
            <div class="form-group">
              <label class="form-label" style="display: flex; justify-content: space-between;">
                <span>Пароль</span>
                <a href="javascript:void(0)" onclick="App.openResetPasswordModal()" style="font-size: 12px;">Забыли пароль?</a>
              </label>
              <input type="password" id="loginPassword" class="form-control" placeholder="Введите пароль" required />
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px; font-weight: 600;">
              <i class="fa fa-sign-in-alt"></i> Войти в систему
            </button>
          </form>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed var(--border-color); font-size: 12px; color: var(--text-muted);">
            <div style="font-weight: 600; margin-bottom: 6px;">Быстрый вход для тестирования ролей:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <button class="btn btn-secondary btn-sm" onclick="App.fillLogin('admin','admin123')">👑 Администратор</button>
              <button class="btn btn-secondary btn-sm" onclick="App.fillLogin('manager','manager123')">👔 Начальник отдела</button>
              <button class="btn btn-secondary btn-sm" onclick="App.fillLogin('executor','executor123')">🔧 Исполнитель</button>
              <button class="btn btn-secondary btn-sm" onclick="App.fillLogin('requester','requester123')">👤 Заявитель</button>
            </div>
          </div>
        </div>
      </div>
    `;
  },

  fillLogin(u, p) {
    document.getElementById('loginUsername').value = u;
    document.getElementById('loginPassword').value = p;
  },

  async handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;

    try {
      const res = await this.apiFetch('auth/login', {
        method: 'POST',
        body: { username, password }
      });

      if (res.success && res.token) {
        this.token = res.token;
        this.user = res.user;
        localStorage.setItem('crm_token', res.token);
        this.toast(`Добро пожаловать, ${res.user.name}!`, 'success');
        await this.loadInitialData();
        this.renderApp();
      } else {
        this.toast(res.error || 'Ошибка входа', 'error');
      }
    } catch (err) {
      this.toast('Ошибка соединения с сервером', 'error');
    }
  },

  logout() {
    this.token = null;
    this.user = null;
    localStorage.removeItem('crm_token');
    this.renderLogin();
  },

  renderApp() {
    const root = document.getElementById('appRoot');
    const roleLabels = {
      admin: this.t('role_admin'),
      manager: this.t('role_manager'),
      executor: this.t('role_executor'),
      requester: this.t('role_requester')
    };

    const unreadCount = this.notifications.filter(n => !n.is_read).length;

    root.innerHTML = `
      <div class="app-container">
        <aside class="sidebar" id="appSidebar">
          <div class="sidebar-header">
            <div class="sidebar-logo-icon"><i class="fa fa-wrench"></i></div>
            <div>
              <div class="sidebar-title">${this.t('app_title')}</div>
              <div class="sidebar-subtitle">Service & Maintenance</div>
            </div>
          </div>

          <nav class="sidebar-nav">
            <div class="nav-section-title">Основное</div>
            <a class="nav-link" data-view="dashboard" onclick="App.navigate('dashboard')">
              <i class="fa fa-chart-pie"></i> <span>${this.t('nav_dashboard')}</span>
            </a>
            <a class="nav-link" data-view="tickets" onclick="App.navigate('tickets')">
              <i class="fa fa-clipboard-list"></i> <span>${this.t('nav_tickets')}</span>
              <span class="nav-badge" style="background: var(--primary);">${this.tickets.length}</span>
            </a>
            <a class="nav-link" data-view="kanban" onclick="App.navigate('kanban')">
              <i class="fa fa-columns"></i> <span>${this.t('nav_kanban')}</span>
            </a>

            ${this.user.role === 'admin' ? `
              <div class="nav-section-title">Администрирование</div>
              <a class="nav-link" data-view="form_builder" onclick="App.navigate('form_builder')">
                <i class="fa fa-cubes"></i> <span>${this.t('nav_form_builder')}</span>
              </a>
              <a class="nav-link" data-view="settings" onclick="App.navigate('settings')">
                <i class="fa fa-cog"></i> <span>${this.t('nav_settings')}</span>
              </a>
            ` : ''}

            <div class="nav-section-title">Отчётность и личное</div>
            <a class="nav-link" data-view="analytics" onclick="App.navigate('analytics')">
              <i class="fa fa-chart-line"></i> <span>${this.t('nav_analytics')}</span>
            </a>
            <a class="nav-link" data-view="notifications" onclick="App.navigate('notifications')">
              <i class="fa fa-bell"></i> <span>${this.t('nav_notifications')}</span>
              ${unreadCount > 0 ? `<span class="nav-badge">${unreadCount}</span>` : ''}
            </a>
            <a class="nav-link" data-view="profile" onclick="App.navigate('profile')">
              <i class="fa fa-user-circle"></i> <span>${this.t('nav_profile')}</span>
            </a>
          </nav>

          <div class="sidebar-footer">
            <div class="user-mini-card" onclick="App.navigate('profile')">
              <div class="user-avatar-circle">${this.user.name.charAt(0)}</div>
              <div class="user-info">
                <div class="user-name">${this.user.name}</div>
                <div class="user-role-badge">${roleLabels[this.user.role] || this.user.role}</div>
              </div>
              <button class="btn-icon" style="width: 28px; height: 28px;" onclick="event.stopPropagation(); App.logout();" title="${this.t('nav_logout')}">
                <i class="fa fa-sign-out-alt" style="font-size: 13px;"></i>
              </button>
            </div>
          </div>
        </aside>

        <div class="main-wrapper">
          <header class="topbar">
            <div class="topbar-left">
              <button class="mobile-nav-toggle" onclick="document.getElementById('appSidebar').classList.toggle('open')">
                <i class="fa fa-bars"></i>
              </button>
              <div class="page-title" id="pageTitle">${this.t('nav_dashboard')}</div>
            </div>

            <div class="topbar-right">
              ${this.user.role !== 'executor' ? `
                <button class="btn btn-primary btn-sm" onclick="App.openCreateTicketModal()">
                  <i class="fa fa-plus"></i> ${this.t('btn_create_ticket')}
                </button>
              ` : ''}

              <button class="btn-icon" onclick="App.setLanguage(App.lang === 'ru' ? 'en' : 'ru')" title="Сменить язык">
                <span style="font-size: 11px; font-weight: 700;">${this.lang.toUpperCase()}</span>
              </button>

              <button class="btn-icon" id="themeToggleBtn" onclick="App.toggleTheme()" title="Переключить тему">
                <i class="fa ${this.theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>
              </button>

              <button class="btn-icon" onclick="App.navigate('notifications')" title="${this.t('nav_notifications')}">
                <i class="fa fa-bell"></i>
                ${unreadCount > 0 ? `<span style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: var(--danger); border-radius: 50%;"></span>` : ''}
              </button>
            </div>
          </header>

          <main class="content-body" id="viewContent">
          </main>
        </div>
      </div>
    `;

    const initialView = window.location.hash.replace('#', '') || 'dashboard';
    this.navigate(initialView, false);
  },

  async renderDashboardView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_dashboard');

    const res = await this.apiFetch('analytics');
    const stats = res.success ? res : {
      total_tickets: this.tickets.length,
      by_status: { new: 0, assigned: 0, in_progress: 0, completed: 0, rejected: 0 },
      sla_breached_count: 0,
      avg_completion_hours: 0,
      sla_success_rate: 100
    };

    container.innerHTML = `
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon-wrap" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
            <i class="fa fa-clipboard-list"></i>
          </div>
          <div>
            <div class="kpi-value">${stats.total_tickets}</div>
            <div class="kpi-label">${this.t('kpi_total')}</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon-wrap" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
            <i class="fa fa-spinner"></i>
          </div>
          <div>
            <div class="kpi-value">${(stats.by_status.in_progress || 0) + (stats.by_status.assigned || 0)}</div>
            <div class="kpi-label">${this.t('kpi_in_progress')}</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon-wrap" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="fa fa-check-circle"></i>
          </div>
          <div>
            <div class="kpi-value">${stats.by_status.completed || 0}</div>
            <div class="kpi-label">${this.t('kpi_completed')}</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon-wrap" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <i class="fa fa-exclamation-triangle"></i>
          </div>
          <div>
            <div class="kpi-value">${stats.sla_breached_count || 0}</div>
            <div class="kpi-label">${this.t('kpi_sla_breach')}</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon-wrap" style="background: rgba(6, 182, 212, 0.1); color: #06b6d4;">
            <i class="fa fa-clock"></i>
          </div>
          <div>
            <div class="kpi-value">${stats.avg_completion_hours || 0} ч</div>
            <div class="kpi-label">${this.t('kpi_avg_time')}</div>
          </div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px;">Последние активные заявки</h3>
            <button class="btn btn-secondary btn-sm" onclick="App.navigate('tickets')">Все заявки →</button>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>№</th>
                  <th>Заголовок</th>
                  <th>Приоритет</th>
                  <th>Статус</th>
                  <th>Исполнитель</th>
                  <th>Действие</th>
                </tr>
              </thead>
              <tbody>
                ${this.tickets.slice(0, 5).map(t => `
                  <tr>
                    <td><strong>${t.ticket_number}</strong></td>
                    <td>${t.title}</td>
                    <td><span class="badge badge-prio-${t.priority}">${t.priority}</span></td>
                    <td><span class="badge badge-status-${t.status}">${t.status}</span></td>
                    <td>${t.executor_name || '<em style="color:var(--text-muted)">Не назначен</em>'}</td>
                    <td>
                      <button class="btn btn-secondary btn-sm" onclick="App.openTicketDetailModal(${t.id})">
                        <i class="fa fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <h3 style="font-size: 16px; margin-bottom: 16px;">SLA Контроль качества</h3>
          <div style="text-align: center; padding: 20px 0;">
            <div style="font-size: 44px; font-weight: 800; color: ${stats.sla_success_rate > 90 ? 'var(--success)' : 'var(--warning)'};">
              ${stats.sla_success_rate}%
            </div>
            <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Успешное выполнение в срок</div>
          </div>
          <div style="border-top: 1px dashed var(--border-color); padding-top: 14px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
              <span>Критический SLA (2ч):</span>
              <span class="badge badge-prio-critical">98% вовремя</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>Стандартный SLA (24ч):</span>
              <span class="badge badge-prio-medium">95% вовремя</span>
            </div>
          </div>
        </div>
      </div>
    `;
  },

  renderTicketsView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_tickets');
    this.selectedTicketIds.clear();

    const isManagerOrAdmin = ['admin', 'manager'].includes(this.user.role);

    container.innerHTML = `
      <div class="filter-bar">
        <input type="text" id="ticketSearchInput" class="input-control" placeholder="Поиск по номеру, описанию..." style="min-width: 240px;" oninput="App.applyTicketFilters()" />

        <select id="filterStatus" class="select-control" onchange="App.applyTicketFilters()">
          <option value="">Все статусы</option>
          <option value="new">Новая</option>
          <option value="assigned">Назначена</option>
          <option value="in_progress">В работе</option>
          <option value="completed">Выполнено</option>
          <option value="rejected">Отклонено</option>
        </select>

        <select id="filterPriority" class="select-control" onchange="App.applyTicketFilters()">
          <option value="">Все приоритеты</option>
          <option value="low">Низкий</option>
          <option value="medium">Средний</option>
          <option value="high">Высокий</option>
          <option value="critical">Критический</option>
        </select>

        <select id="filterArchive" class="select-control" onchange="App.applyTicketFilters()">
          <option value="0">Активные заявки</option>
          <option value="1">Архив (завершенные/отклоненные)</option>
          <option value="all">Все заявки</option>
        </select>

        <div style="margin-left: auto; display: flex; gap: 8px;">
          ${isManagerOrAdmin ? `
            <button class="btn btn-secondary btn-sm" id="massAssignBtn" onclick="App.openMassAssignModal()" style="display: none;">
              <i class="fa fa-users-cog"></i> ${this.t('btn_mass_assign')} (<span id="selectedCount">0</span>)
            </button>
          ` : ''}

          <a href="api/export?format=excel" class="btn btn-secondary btn-sm" target="_blank" download>
            <i class="fa fa-file-excel"></i> ${this.t('btn_export_excel')}
          </a>
          <button class="btn btn-secondary btn-sm" onclick="window.print()">
            <i class="fa fa-print"></i> ${this.t('btn_export_pdf')}
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table" id="ticketsTable">
          <thead>
            <tr>
              ${isManagerOrAdmin ? `<th style="width: 40px;"><input type="checkbox" onchange="App.toggleSelectAllTickets(this)" /></th>` : ''}
              <th>№ заявки</th>
              <th>Заголовок</th>
              <th>Категория</th>
              <th>Приоритет</th>
              <th>Статус</th>
              <th>Заявитель</th>
              <th>Исполнитель</th>
              <th>Дедлайн</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody id="ticketsTableBody">
          </tbody>
        </table>
      </div>
    `;

    this.applyTicketFilters();
  },

  applyTicketFilters() {
    const search = (document.getElementById('ticketSearchInput')?.value || '').toLowerCase();
    const status = document.getElementById('filterStatus')?.value || '';
    const prio = document.getElementById('filterPriority')?.value || '';
    const archive = document.getElementById('filterArchive')?.value || '0';

    const tbody = document.getElementById('ticketsTableBody');
    if (!tbody) return;

    const filtered = this.tickets.filter(t => {
      if (this.user.role === 'requester' && t.requester_id !== this.user.id) return false;
      if (this.user.role === 'executor' && t.executor_id !== this.user.id) return false;

      const isArchived = ['completed', 'rejected'].includes(t.status);
      if (archive === '0' && isArchived) return false;
      if (archive === '1' && !isArchived) return false;

      if (status && t.status !== status) return false;
      if (prio && t.priority !== prio) return false;

      if (search) {
        const full = `${t.ticket_number} ${t.title} ${t.description} ${t.requester_name || ''} ${t.executor_name || ''}`.toLowerCase();
        if (!full.includes(search)) return false;
      }

      return true;
    });

    const isManagerOrAdmin = ['admin', 'manager'].includes(this.user.role);

    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 32px; color: var(--text-muted);">Заявки не найдены</td></tr>`;
      return;
    }

    tbody.innerHTML = filtered.map(t => `
      <tr>
        ${isManagerOrAdmin ? `
          <td><input type="checkbox" value="${t.id}" onchange="App.toggleTicketSelect(${t.id}, this.checked)" ${this.selectedTicketIds.has(t.id) ? 'checked' : ''} /></td>
        ` : ''}
        <td><strong>${t.ticket_number}</strong></td>
        <td>
          <div style="font-weight: 600;">${t.title}</div>
          <div style="font-size: 12px; color: var(--text-muted);">${t.description ? t.description.substring(0, 60) + '...' : ''}</div>
        </td>
        <td><span style="font-size: 12px;">${t.category}</span></td>
        <td><span class="badge badge-prio-${t.priority}">${t.priority}</span></td>
        <td><span class="badge badge-status-${t.status}">${t.status}</span></td>
        <td>${t.requester_name || '—'}</td>
        <td>${t.executor_name ? `<span style="font-weight: 500;">${t.executor_name}</span>` : '<em style="color:var(--text-muted)">Не назначен</em>'}</td>
        <td>
          <span style="font-size: 12px; ${new Date(t.deadline) < new Date() && t.status !== 'completed' ? 'color: var(--danger); font-weight: 600;' : ''}">
            ${t.deadline ? t.deadline.replace('T', ' ').substring(0, 16) : '—'}
          </span>
        </td>
        <td>
          <button class="btn btn-secondary btn-sm" onclick="App.openTicketDetailModal(${t.id})">
            <i class="fa fa-eye"></i> Открыть
          </button>
        </td>
      </tr>
    `).join('');
  },

  toggleTicketSelect(id, checked) {
    if (checked) {
      this.selectedTicketIds.add(id);
    } else {
      this.selectedTicketIds.delete(id);
    }
    const count = this.selectedTicketIds.size;
    const btn = document.getElementById('massAssignBtn');
    const cntEl = document.getElementById('selectedCount');
    if (btn && cntEl) {
      cntEl.textContent = count;
      btn.style.display = count > 0 ? 'inline-flex' : 'none';
    }
  },

  toggleSelectAllTickets(masterCheckbox) {
    const checkboxes = document.querySelectorAll('#ticketsTableBody input[type="checkbox"]');
    checkboxes.forEach(cb => {
      cb.checked = masterCheckbox.checked;
      this.toggleTicketSelect(parseInt(cb.value), masterCheckbox.checked);
    });
  },

  renderKanbanView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_kanban');

    const statuses = [
      { id: 'new', name: 'Новая', color: '#64748b' },
      { id: 'assigned', name: 'Назначена', color: '#3b82f6' },
      { id: 'in_progress', name: 'В работе', color: '#f59e0b' },
      { id: 'completed', name: 'Выполнено', color: '#10b981' },
      { id: 'rejected', name: 'Отклонено', color: '#ef4444' }
    ];

    container.innerHTML = `
      <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
        <p style="font-size: 13px; color: var(--text-muted);">
          <i class="fa fa-info-circle"></i> Перетаскивайте карточки между колонками для быстрой смены статуса.
        </p>
      </div>

      <div class="kanban-board" id="kanbanBoard">
        ${statuses.map(st => {
          const colTickets = this.tickets.filter(t => t.status === st.id);
          return `
            <div class="kanban-col" data-status="${st.id}" ondragover="App.handleDragOver(event)" ondragleave="App.handleDragLeave(event)" ondrop="App.handleDrop(event, '${st.id}')">
              <div class="kanban-col-header">
                <div class="kanban-col-title">
                  <span style="width: 10px; height: 10px; border-radius: 50%; background: ${st.color};"></span>
                  ${st.name}
                </div>
                <span class="kanban-counter">${colTickets.length}</span>
              </div>
              <div class="kanban-cards-list">
                ${colTickets.map(t => `
                  <div class="kanban-card" draggable="true" ondragstart="App.handleDragStart(event, ${t.id})" onclick="App.openTicketDetailModal(${t.id})">
                    <div class="kanban-card-top">
                      <span class="kanban-ticket-no">${t.ticket_number}</span>
                      <span class="badge badge-prio-${t.priority}">${t.priority}</span>
                    </div>
                    <div class="kanban-card-title">${t.title}</div>
                    <div class="kanban-card-desc">${t.description || ''}</div>
                    <div class="kanban-card-footer">
                      <span><i class="fa fa-user"></i> ${t.executor_name || 'Не назначен'}</span>
                      <span style="font-size: 11px;">${t.created_at ? t.created_at.substring(5, 10) : ''}</span>
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  },

  handleDragStart(event, ticketId) {
    event.dataTransfer.setData('text/plain', ticketId);
    event.currentTarget.classList.add('dragging');
  },

  handleDragOver(event) {
    event.preventDefault();
    event.currentTarget.querySelector('.kanban-cards-list')?.classList.add('drag-over');
  },

  handleDragLeave(event) {
    event.currentTarget.querySelector('.kanban-cards-list')?.classList.remove('drag-over');
  },

  async handleDrop(event, targetStatus) {
    event.preventDefault();
    event.currentTarget.querySelector('.kanban-cards-list')?.classList.remove('drag-over');

    const ticketId = event.dataTransfer.getData('text/plain');
    if (!ticketId) return;

    try {
      const res = await this.apiFetch('tickets/status', {
        method: 'POST',
        body: {
          ticket_id: parseInt(ticketId),
          status: targetStatus,
          comment: `Статус изменен через Канбан-доску на «${targetStatus}»`
        }
      });

      if (res.success) {
        this.toast(`Заявка переведена в статус «${targetStatus}»`, 'success');
        await this.loadInitialData();
        this.renderKanbanView(document.getElementById('viewContent'));
      } else {
        this.toast(res.error || 'Ошибка изменения статуса', 'error');
      }
    } catch (e) {
      this.toast('Ошибка соединения', 'error');
    }
  },

  renderFormBuilderView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_form_builder');

    container.innerHTML = `
      <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="font-size: 16px;">Конструктор динамических полей заявок</h3>
            <p style="font-size: 13px; color: var(--text-muted);">
              Настраивайте состав и порядок дополнительных полей для формы создания заявок.
            </p>
          </div>
          <button class="btn btn-primary btn-sm" onclick="App.openAddFieldModal()">
            <i class="fa fa-plus"></i> Добавить поле
          </button>
        </div>
      </div>

      <div class="fb-container">
        <div>
          <div class="card">
            <h4 style="font-size: 14px; margin-bottom: 12px;">Типы полей</h4>
            <div class="fb-palette-item"><i class="fa fa-font"></i> Текстовое поле (text)</div>
            <div class="fb-palette-item"><i class="fa fa-hashtag"></i> Числовое значение (number)</div>
            <div class="fb-palette-item"><i class="fa fa-calendar"></i> Выбор даты (date)</div>
            <div class="fb-palette-item"><i class="fa fa-list"></i> Выпадающий список (select)</div>
            <div class="fb-palette-item"><i class="fa fa-check-square"></i> Чекбокс / Флаг (checkbox)</div>
          </div>
        </div>

        <div class="fb-canvas" id="fbCanvas">
          <div style="font-size: 13px; font-weight: 600; margin-bottom: 12px; color: var(--text-muted);">
            Активные поля в форме создания заявки (Drag & Drop порядок):
          </div>
          ${this.fields.map((f, idx) => `
            <div class="fb-canvas-item" draggable="true" data-id="${f.id}" ondragstart="App.handleFieldDragStart(event, ${idx})" ondragover="event.preventDefault()" ondrop="App.handleFieldDrop(event, ${idx})">
              <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fa fa-grip-vertical" style="color: var(--text-subtle);"></i>
                <div>
                  <div style="font-weight: 600; font-size: 14px;">${f.label}</div>
                  <div style="font-size: 12px; color: var(--text-muted);">
                    <code>${f.name}</code> | Тип: <strong>${f.type}</strong> | ${f.required ? '<span style="color:var(--danger)">Обязательное</span>' : 'Опционально'}
                  </div>
                </div>
              </div>
              <div>
                <button class="btn btn-danger btn-sm" onclick="App.deleteField('${f.id}')" title="Удалить поле">
                  <i class="fa fa-trash"></i>
                </button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  },

  handleFieldDragStart(event, index) {
    event.dataTransfer.setData('field_idx', index);
  },

  async handleFieldDrop(event, targetIndex) {
    event.preventDefault();
    const sourceIndex = parseInt(event.dataTransfer.getData('field_idx'));
    if (isNaN(sourceIndex) || sourceIndex === targetIndex) return;

    const reordered = [...this.fields];
    const [moved] = reordered.splice(sourceIndex, 1);
    reordered.splice(targetIndex, 0, moved);

    this.fields = reordered;

    await this.apiFetch('fields', {
      method: 'POST',
      body: { fields: this.fields }
    });

    this.toast('Порядок полей сохранен', 'success');
    this.renderFormBuilderView(document.getElementById('viewContent'));
  },

  async deleteField(id) {
    if (!confirm('Удалить это поле из формы?')) return;
    const res = await this.apiFetch(`fields?id=${id}`, { method: 'DELETE' });
    if (res.success) {
      this.toast('Поле удалено', 'success');
      await this.loadInitialData();
      this.renderFormBuilderView(document.getElementById('viewContent'));
    }
  },

  async renderAnalyticsView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_analytics');

    const res = await this.apiFetch('analytics');
    const data = res.success ? res : null;

    container.innerHTML = `
      <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="font-size: 16px;">Аналитика и загрузка сотрудников</h3>
            <p style="font-size: 13px; color: var(--text-muted);">
              Контроль соблюдения нормативов SLA и мониторинг продуктивности технической службы.
            </p>
          </div>
          <a href="api/export?format=excel" class="btn btn-primary btn-sm" download>
            <i class="fa fa-download"></i> Выгрузить полный отчет
          </a>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <div class="card">
          <h4 style="font-size: 15px; margin-bottom: 16px;">Заявки по статусам</h4>
          ${data ? Object.entries(data.by_status).map(([k, v]) => `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <span class="badge badge-status-${k}">${k}</span>
              <strong>${v}</strong>
            </div>
          `).join('') : ''}
        </div>

        <div class="card">
          <h4 style="font-size: 15px; margin-bottom: 16px;">Заявки по приоритетам</h4>
          ${data ? Object.entries(data.by_priority).map(([k, v]) => `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <span class="badge badge-prio-${k}">${k}</span>
              <strong>${v}</strong>
            </div>
          `).join('') : ''}
        </div>
      </div>

      <div class="card">
        <h4 style="font-size: 15px; margin-bottom: 16px;">Нагрузка на исполнителей</h4>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Исполнитель</th>
                <th>Отдел / Специализация</th>
                <th>В работе</th>
                <th>Завершено</th>
                <th>Статус нагрузки</th>
              </tr>
            </thead>
            <tbody>
              ${data && data.employee_workload ? data.employee_workload.map(emp => `
                <tr>
                  <td><strong>${emp.name}</strong></td>
                  <td>${emp.department || 'Техник'}</td>
                  <td><span class="badge badge-status-in_progress">${emp.active_tickets}</span></td>
                  <td><span class="badge badge-status-completed">${emp.completed_tickets}</span></td>
                  <td>
                    ${emp.active_tickets > 3 ? '<span class="badge badge-prio-critical">Высокая</span>' : '<span class="badge badge-prio-low">Нормальная</span>'}
                  </td>
                </tr>
              `).join('') : ''}
            </tbody>
          </table>
        </div>
      </div>
    `;
  },

  renderSettingsView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_settings');

    const s = this.settings || { system: {}, mysql_config: {}, priorities: [] };

    container.innerHTML = `
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card">
          <h3 style="font-size: 16px; margin-bottom: 16px;">Хранилище данных (JSON ↔ MySQL)</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
            CRM поддерживает мгновенное переключение между файловым JSON хранилищем и базой данных MySQL.
          </p>

          <form onsubmit="App.handleStorageSwitch(event)">
            <div class="form-group">
              <label class="form-label">Текущий режим хранилища</label>
              <select id="storageModeSelect" class="form-control" onchange="App.toggleMysqlInputs(this.value)">
                <option value="json" ${s.system.storage_mode === 'json' ? 'selected' : ''}>JSON файлы (Быстрый файловый драйвер)</option>
                <option value="mysql" ${s.system.storage_mode === 'mysql' ? 'selected' : ''}>MySQL база данных (Автоматическая схема)</option>
              </select>
            </div>

            <div id="mysqlConfigBlock" style="display: ${s.system.storage_mode === 'mysql' ? 'block' : 'none'};">
              <div class="form-group">
                <label class="form-label">Хост MySQL</label>
                <input type="text" id="mysqlHost" class="form-control" value="${s.mysql_config?.host || '127.0.0.1'}" />
              </div>
              <div class="form-group">
                <label class="form-label">База данных</label>
                <input type="text" id="mysqlDb" class="form-control" value="${s.mysql_config?.database || 'crm_maintenance'}" />
              </div>
              <div class="form-group">
                <label class="form-label">Пользователь</label>
                <input type="text" id="mysqlUser" class="form-control" value="${s.mysql_config?.username || 'root'}" />
              </div>
              <div class="form-group">
                <label class="form-label">Пароль</label>
                <input type="password" id="mysqlPass" class="form-control" value="${s.mysql_config?.password || ''}" />
              </div>
              <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                  <input type="checkbox" id="migrateJsonData" checked />
                  <span>Перенести текущие данные из JSON в MySQL автоматически</span>
                </label>
              </div>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Применить режим хранилища
            </button>
          </form>
        </div>

        <div class="card">
          <h3 style="font-size: 16px; margin-bottom: 16px;">Резервное копирование и экспорт</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
            Создавайте резервные копии базы данных или восстанавливайте данные.
          </p>

          <div style="display: flex; gap: 12px; margin-bottom: 24px;">
            <a href="api/backup" class="btn btn-secondary" download>
              <i class="fa fa-download"></i> Скачать JSON бэкап
            </a>
          </div>

          <h3 style="font-size: 16px; margin-bottom: 16px;">Управление пользователями</h3>
          <div style="max-height: 250px; overflow-y: auto;">
            <table class="data-table" style="font-size: 13px;">
              <thead>
                <tr>
                  <th>Логин</th>
                  <th>Имя</th>
                  <th>Роль</th>
                  <th>Статус</th>
                </tr>
              </thead>
              <tbody>
                ${this.users.map(u => `
                  <tr>
                    <td><strong>${u.username}</strong></td>
                    <td>${u.name}</td>
                    <td><span class="badge badge-secondary">${u.role}</span></td>
                    <td><span class="badge ${u.status === 'active' ? 'badge-status-completed' : 'badge-status-rejected'}">${u.status}</span></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
          <button class="btn btn-secondary btn-sm" style="margin-top: 12px;" onclick="App.openCreateUserModal()">
            <i class="fa fa-user-plus"></i> Добавить пользователя
          </button>
        </div>
      </div>
    `;
  },

  toggleMysqlInputs(mode) {
    document.getElementById('mysqlConfigBlock').style.display = mode === 'mysql' ? 'block' : 'none';
  },

  async handleStorageSwitch(e) {
    e.preventDefault();
    const mode = document.getElementById('storageModeSelect').value;
    const body = { mode };

    if (mode === 'mysql') {
      body.mysql_config = {
        host: document.getElementById('mysqlHost').value.trim(),
        database: document.getElementById('mysqlDb').value.trim(),
        username: document.getElementById('mysqlUser').value.trim(),
        password: document.getElementById('mysqlPass').value
      };
      body.migrate_data = document.getElementById('migrateJsonData').checked;
    }

    try {
      const res = await this.apiFetch('settings/storage-switch', {
        method: 'POST',
        body
      });

      if (res.success) {
        this.toast(res.message, 'success');
        await this.loadInitialData();
        this.renderSettingsView(document.getElementById('viewContent'));
      } else {
        this.toast(res.error || 'Ошибка смены хранилища', 'error');
      }
    } catch (err) {
      this.toast('Ошибка соединения', 'error');
    }
  },

  renderProfileView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_profile');

    container.innerHTML = `
      <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3 style="font-size: 18px; margin-bottom: 20px;">Настройки профиля</h3>
        <form onsubmit="App.handleProfileUpdate(event)">
          <div class="form-group">
            <label class="form-label">ФИО</label>
            <input type="text" id="profName" class="form-control" value="${this.user.name || ''}" required />
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" id="profEmail" class="form-control" value="${this.user.email || ''}" required />
          </div>
          <div class="form-group">
            <label class="form-label">Телефон</label>
            <input type="text" id="profPhone" class="form-control" value="${this.user.phone || ''}" />
          </div>
          <div class="form-group">
            <label class="form-label">Отдел / Должность</label>
            <input type="text" id="profDept" class="form-control" value="${this.user.department || ''}" />
          </div>
          <div class="form-group">
            <label class="form-label">Новый пароль (оставьте пустым, если не меняете)</label>
            <input type="password" id="profNewPass" class="form-control" placeholder="Минимум 6 символов" />
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> ${this.t('btn_save')}
          </button>
        </form>
      </div>
    `;
  },

  async handleProfileUpdate(e) {
    e.preventDefault();
    const name = document.getElementById('profName').value.trim();
    const email = document.getElementById('profEmail').value.trim();
    const phone = document.getElementById('profPhone').value.trim();
    const department = document.getElementById('profDept').value.trim();
    const new_password = document.getElementById('profNewPass').value;

    const res = await this.apiFetch('auth/profile', {
      method: 'POST',
      body: { name, email, phone, department, new_password }
    });

    if (res.success) {
      this.user = res.user;
      this.toast('Профиль успешно обновлен', 'success');
      this.renderApp();
    } else {
      this.toast(res.error || 'Ошибка обновления', 'error');
    }
  },

  async renderNotificationsView(container) {
    document.getElementById('pageTitle').textContent = this.t('nav_notifications');

    const res = await this.apiFetch('notifications');
    if (res.success) this.notifications = res.notifications;

    container.innerHTML = `
      <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h3 style="font-size: 18px;">Журнал уведомлений (Email, Web Push, Telegram)</h3>
          <button class="btn btn-secondary btn-sm" onclick="App.markAllNotificationsRead()">
            <i class="fa fa-check-double"></i> Отметить все прочитанными
          </button>
        </div>

        <div>
          ${this.notifications.length === 0 ? `
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
              Нет новых уведомлений
            </div>
          ` : this.notifications.map(n => `
            <div style="padding: 14px; border-bottom: 1px solid var(--border-color); display: flex; align-items: flex-start; gap: 12px; background: ${n.is_read ? 'transparent' : 'var(--bg-subtle)'}; border-radius: var(--radius-md); margin-bottom: 6px;">
              <div style="font-size: 18px; color: var(--primary); margin-top: 2px;">
                <i class="fa ${n.type === 'ticket_assigned' ? 'fa-user-check' : 'fa-bell'}"></i>
              </div>
              <div style="flex: 1;">
                <div style="font-weight: 600; font-size: 14px;">${n.title}</div>
                <div style="font-size: 13px; color: var(--text-main); margin: 4px 0;">${n.message}</div>
                <div style="font-size: 11px; color: var(--text-muted);">${n.created_at}</div>
              </div>
              ${n.ticket_id ? `
                <button class="btn btn-secondary btn-sm" onclick="App.openTicketDetailModal(${n.ticket_id})">
                  Открыть заявку
                </button>
              ` : ''}
            </div>
          `).join('')}
        </div>
      </div>
    `;
  },

  async markAllNotificationsRead() {
    await this.apiFetch('notifications', { method: 'POST', body: {} });
    await this.loadInitialData();
    this.renderNotificationsView(document.getElementById('viewContent'));
    this.toast('Уведомления отмечены как прочитанные', 'success');
  },

  openCreateTicketModal() {
    const modalEl = document.getElementById('createTicketModal');
    if (!modalEl) return;

    const dynContainer = document.getElementById('dynamicFieldsContainer');
    if (dynContainer) {
      dynContainer.innerHTML = this.fields.filter(f => f.active).map(f => {
        if (f.type === 'select') {
          return `
            <div class="form-group">
              <label class="form-label">${f.label} ${f.required ? '<span style="color:var(--danger)">*</span>' : ''}</label>
              <select name="field_${f.name}" class="form-control" ${f.required ? 'required' : ''}>
                <option value="">${f.placeholder || 'Выберите значение'}</option>
                ${(f.options || []).map(opt => `<option value="${opt}">${opt}</option>`).join('')}
              </select>
            </div>
          `;
        } else if (f.type === 'checkbox') {
          return `
            <div class="form-group">
              <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500;">
                <input type="checkbox" name="field_${f.name}" />
                <span>${f.label}</span>
              </label>
            </div>
          `;
        } else {
          return `
            <div class="form-group">
              <label class="form-label">${f.label} ${f.required ? '<span style="color:var(--danger)">*</span>' : ''}</label>
              <input type="${f.type}" name="field_${f.name}" class="form-control" placeholder="${f.placeholder || ''}" ${f.required ? 'required' : ''} />
            </div>
          `;
        }
      }).join('');
    }

    modalEl.classList.remove('hidden');
  },

  closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.add('hidden');
  },

  async handleCreateTicket(e) {
    e.preventDefault();
    const title = document.getElementById('newTicketTitle').value.trim();
    const description = document.getElementById('newTicketDesc').value.trim();
    const category = document.getElementById('newTicketCategory').value;
    const priority = document.getElementById('newTicketPriority').value;

    const custom_fields = {};
    const dynInputs = document.querySelectorAll('#dynamicFieldsContainer input, #dynamicFieldsContainer select');
    dynInputs.forEach(input => {
      const fieldName = input.name.replace('field_', '');
      if (input.type === 'checkbox') {
        custom_fields[fieldName] = input.checked;
      } else {
        custom_fields[fieldName] = input.value;
      }
    });

    const fileInput = document.getElementById('newTicketFile');
    const attachments = [];
    if (fileInput.files.length > 0) {
      const formData = new FormData();
      formData.append('file', fileInput.files[0]);
      const uploadRes = await this.apiFetch('upload', { method: 'POST', body: formData });
      if (uploadRes.success && uploadRes.file) {
        attachments.push(uploadRes.file);
      }
    }

    try {
      const res = await this.apiFetch('tickets', {
        method: 'POST',
        body: { title, description, category, priority, custom_fields, attachments }
      });

      if (res.success) {
        this.toast('Заявка успешно создана!', 'success');
        this.closeModal('createTicketModal');
        await this.loadInitialData();
        this.navigate(this.currentView, false);
      } else {
        this.toast(res.error || 'Ошибка создания заявки', 'error');
      }
    } catch (err) {
      this.toast('Ошибка сети', 'error');
    }
  },

  async openTicketDetailModal(id) {
    const res = await this.apiFetch(`tickets/detail?id=${id}`);
    if (!res.success) {
      this.toast(res.error || 'Ошибка загрузки заявки', 'error');
      return;
    }

    const { ticket, comments } = res;
    const isManagerOrAdmin = ['admin', 'manager'].includes(this.user.role);
    const isExecutor = this.user.role === 'executor';

    const modalBody = document.getElementById('ticketDetailBody');
    modalBody.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
        <div>
          <span class="kanban-ticket-no" style="font-size: 13px;">${ticket.ticket_number}</span>
          <h2 style="font-size: 18px; margin-top: 4px;">${ticket.title}</h2>
        </div>
        <div style="display: flex; gap: 8px;">
          <span class="badge badge-status-${ticket.status}">${ticket.status}</span>
          <span class="badge badge-prio-${ticket.priority}">${ticket.priority}</span>
        </div>
      </div>

      <div style="background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
        ${ticket.description || 'Без описания'}
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 13px; margin-bottom: 20px;">
        <div><strong>Заявитель:</strong> ${ticket.requester_name}</div>
        <div><strong>Исполнитель:</strong> ${ticket.executor_name || '<span style="color:var(--text-muted)">Не назначен</span>'}</div>
        <div><strong>Срок SLA:</strong> ${ticket.deadline ? ticket.deadline.replace('T', ' ') : '—'}</div>
        <div><strong>Категория:</strong> ${ticket.category}</div>
      </div>

      ${ticket.custom_fields && Object.keys(ticket.custom_fields).length > 0 ? `
        <div style="border-top: 1px dashed var(--border-color); padding-top: 14px; margin-bottom: 20px;">
          <div style="font-weight: 600; font-size: 13px; margin-bottom: 8px;">Дополнительные параметры заявки:</div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
            ${Object.entries(ticket.custom_fields).map(([k, v]) => `
              <div><span style="color:var(--text-muted);">${k}:</span> <strong>${v === true ? 'Да' : v === false ? 'Нет' : v}</strong></div>
            `).join('')}
          </div>
        </div>
      ` : ''}

      ${ticket.attachments && ticket.attachments.length > 0 ? `
        <div style="border-top: 1px dashed var(--border-color); padding-top: 14px; margin-bottom: 20px;">
          <div style="font-weight: 600; font-size: 13px; margin-bottom: 8px;">Прикрепленные файлы:</div>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            ${ticket.attachments.map(att => `
              <a href="${att.url}" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fa fa-paperclip"></i> ${att.name}
              </a>
            `).join('')}
          </div>
        </div>
      ` : ''}

      <div style="background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md); margin-bottom: 20px;">
        <div style="font-weight: 600; font-size: 13px; margin-bottom: 10px;">Действия по заявке:</div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
          ${isManagerOrAdmin ? `
            <select id="modalAssignExecutorSelect" class="select-control" style="font-size: 13px;">
              <option value="">Выбрать исполнителя...</option>
              ${this.users.filter(u => u.role === 'executor').map(u => `
                <option value="${u.id}" ${ticket.executor_id === u.id ? 'selected' : ''}>${u.name}</option>
              `).join('')}
            </select>
            <button class="btn btn-primary btn-sm" onclick="App.assignExecutorFromModal(${ticket.id})">
              ${this.t('btn_assign')}
            </button>
          ` : ''}

          ${(isExecutor && ticket.executor_id === this.user.id) || isManagerOrAdmin ? `
            ${ticket.status !== 'in_progress' && ticket.status !== 'completed' ? `
              <button class="btn btn-secondary btn-sm" onclick="App.updateTicketStatusModal(${ticket.id}, 'in_progress')">
                <i class="fa fa-play"></i> В работу
              </button>
            ` : ''}
            ${ticket.status !== 'completed' ? `
              <button class="btn btn-success btn-sm" onclick="App.openCompleteModal(${ticket.id})">
                <i class="fa fa-check"></i> ${this.t('btn_mark_complete')}
              </button>
            ` : ''}
          ` : ''}

          ${isManagerOrAdmin && ticket.status !== 'rejected' ? `
            <button class="btn btn-danger btn-sm" onclick="App.updateTicketStatusModal(${ticket.id}, 'rejected')">
              <i class="fa fa-times"></i> Отклонить
            </button>
          ` : ''}
        </div>
      </div>

      <div style="border-top: 1px solid var(--border-color); padding-top: 16px;">
        <h4 style="font-size: 15px; margin-bottom: 12px;">Комментарии и отчеты (${comments.length})</h4>
        <div style="max-height: 200px; overflow-y: auto; margin-bottom: 16px;">
          ${comments.length === 0 ? '<div style="font-size: 13px; color: var(--text-muted);">Комментариев пока нет.</div>' : ''}
          ${comments.map(c => `
            <div style="padding: 10px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 8px;">
              <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                <strong>${c.user_name} (${c.user_role})</strong>
                <span>${c.created_at}</span>
              </div>
              <div style="font-size: 13px;">${c.message}</div>
              ${c.attachments && c.attachments.length > 0 ? `
                <div style="margin-top: 6px;">
                  ${c.attachments.map(a => `<a href="${a.url}" target="_blank" style="font-size: 12px;"><i class="fa fa-image"></i> ${a.name}</a>`).join(' ')}
                </div>
              ` : ''}
            </div>
          `).join('')}
        </div>

        <form onsubmit="App.handleAddComment(event, ${ticket.id})">
          <div style="display: flex; gap: 8px;">
            <input type="text" id="commentInput" class="form-control" placeholder="Напишите комментарий или вопрос..." required />
            <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
          </div>
        </form>
      </div>
    `;

    document.getElementById('ticketDetailModal').classList.remove('hidden');
  },

  async assignExecutorFromModal(ticketId) {
    const executorId = document.getElementById('modalAssignExecutorSelect').value;
    if (!executorId) {
      this.toast('Выберите исполнителя', 'error');
      return;
    }

    const res = await this.apiFetch('tickets/assign', {
      method: 'POST',
      body: { ticket_id: ticketId, executor_id: parseInt(executorId) }
    });

    if (res.success) {
      this.toast('Исполнитель назначен', 'success');
      await this.loadInitialData();
      this.openTicketDetailModal(ticketId);
    } else {
      this.toast(res.error || 'Ошибка назначения', 'error');
    }
  },

  async updateTicketStatusModal(ticketId, status) {
    const res = await this.apiFetch('tickets/status', {
      method: 'POST',
      body: { ticket_id: ticketId, status }
    });

    if (res.success) {
      this.toast('Статус обновлен', 'success');
      await this.loadInitialData();
      this.openTicketDetailModal(ticketId);
    } else {
      this.toast(res.error || 'Ошибка', 'error');
    }
  },

  openCompleteModal(ticketId) {
    const note = prompt('Введите отчет о выполнении (какие работы были проведены):', 'Работы выполнены в полном объеме, замечаний нет.');
    if (note === null) return;

    this.apiFetch('tickets/status', {
      method: 'POST',
      body: { ticket_id: ticketId, status: 'completed', comment: note }
    }).then(res => {
      if (res.success) {
        this.toast('Заявка успешно выполнена!', 'success');
        this.loadInitialData().then(() => this.openTicketDetailModal(ticketId));
      } else {
        this.toast(res.error || 'Ошибка', 'error');
      }
    });
  },

  async handleAddComment(e, ticketId) {
    e.preventDefault();
    const input = document.getElementById('commentInput');
    const message = input.value.trim();
    if (!message) return;

    const res = await this.apiFetch('comments', {
      method: 'POST',
      body: { ticket_id: ticketId, message }
    });

    if (res.success) {
      input.value = '';
      this.toast('Комментарий добавлен', 'success');
      await this.loadInitialData();
      this.openTicketDetailModal(ticketId);
    }
  },

  openMassAssignModal() {
    const select = document.getElementById('massAssignExecutorSelect');
    if (select) {
      select.innerHTML = '<option value="">Выберите мастера...</option>' +
        this.users.filter(u => u.role === 'executor').map(u => `<option value="${u.id}">${u.name} (${u.department || 'Исполнитель'})</option>`).join('');
    }
    document.getElementById('massAssignModal').classList.remove('hidden');
  },

  async handleMassAssign(e) {
    e.preventDefault();
    const executorId = document.getElementById('massAssignExecutorSelect').value;
    if (!executorId) {
      this.toast('Выберите мастера', 'error');
      return;
    }

    const ticketIds = Array.from(this.selectedTicketIds);
    const res = await this.apiFetch('tickets/mass-assign', {
      method: 'POST',
      body: { ticket_ids: ticketIds, executor_id: parseInt(executorId) }
    });

    if (res.success) {
      this.toast(`Назначено ${res.assigned_count} заявок`, 'success');
      this.closeModal('massAssignModal');
      this.selectedTicketIds.clear();
      await this.loadInitialData();
      this.renderTicketsView(document.getElementById('viewContent'));
    } else {
      this.toast(res.error || 'Ошибка', 'error');
    }
  },

  openAddFieldModal() {
    document.getElementById('addFieldModal').classList.remove('hidden');
  },

  async handleAddField(e) {
    e.preventDefault();
    const name = document.getElementById('newFieldName').value.trim();
    const label = document.getElementById('newFieldLabel').value.trim();
    const type = document.getElementById('newFieldType').value;
    const placeholder = document.getElementById('newFieldPlaceholder').value.trim();
    const required = document.getElementById('newFieldRequired').checked;
    const rawOptions = document.getElementById('newFieldOptions').value.trim();

    const options = rawOptions ? rawOptions.split(',').map(s => s.trim()).filter(Boolean) : [];

    const res = await this.apiFetch('fields', {
      method: 'POST',
      body: { name, label, type, placeholder, required, options }
    });

    if (res.success) {
      this.toast('Новое поле добавлено в форму', 'success');
      this.closeModal('addFieldModal');
      await this.loadInitialData();
      this.renderFormBuilderView(document.getElementById('viewContent'));
    } else {
      this.toast(res.error || 'Ошибка', 'error');
    }
  },

  openCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('hidden');
  },

  async handleCreateUser(e) {
    e.preventDefault();
    const username = document.getElementById('newUserName').value.trim();
    const name = document.getElementById('newUserFio').value.trim();
    const password = document.getElementById('newUserPassword').value;
    const role = document.getElementById('newUserRole').value;
    const email = document.getElementById('newUserEmail').value.trim();
    const department = document.getElementById('newUserDept').value.trim();

    const res = await this.apiFetch('users', {
      method: 'POST',
      body: { username, name, password, role, email, department }
    });

    if (res.success) {
      this.toast('Пользователь успешно создан', 'success');
      this.closeModal('createUserModal');
      await this.loadInitialData();
      this.renderSettingsView(document.getElementById('viewContent'));
    } else {
      this.toast(res.error || 'Ошибка', 'error');
    }
  },

  openResetPasswordModal() {
    const ident = prompt('Введите ваш логин или email для сброса пароля:');
    if (!ident) return;

    this.apiFetch('auth/reset-password', {
      method: 'POST',
      body: { identity: ident }
    }).then(res => {
      if (res.success) {
        alert(res.message);
      } else {
        alert(res.error || 'Ошибка сброса пароля');
      }
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
