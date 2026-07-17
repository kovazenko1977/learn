// app.js - Full-featured SPA frontend controller for CRM. Handles auth, dynamic forms, kanban, i18n, DB settings, and visual charts.

// --- State Management ---
const state = {
    token: localStorage.getItem('crm_jwt_token') || null,
    user: JSON.parse(localStorage.getItem('crm_user_info')) || null,
    currentView: 'dashboard',
    theme: localStorage.getItem('crm_theme') || 'light',
    lang: localStorage.getItem('crm_lang') || 'ru',

    // DB Collections
    tickets: [],
    users: [],
    logs: [],
    categories: [],
    priorities: [],
    customFields: [],
    slaSettings: {},
    storageMode: 'json',

    // UI Helpers
    selectedTicketIds: new Set(),
    draggedTicketId: null
};

// --- Language Translations Dictionaries ---
const i18n = {
    ru: {
        auth_title: "Вход в CRM",
        auth_subtitle: "Управление заявками на ремонт и обслуживание",
        username_lbl: "Имя пользователя",
        password_lbl: "Пароль",
        btn_login: "Войти",
        forgot_password: "Забыли пароль?",
        no_account_yet: "Нет аккаунта?",
        register_title: "Регистрация в CRM",
        register_subtitle: "Создайте новый рабочий профиль",
        fullname_lbl: "ФИО полностью",
        email_lbl: "Email для уведомлений",
        phone_lbl: "Телефон",
        role_lbl: "Желаемая роль",
        role_creator: "Ответственный сотрудник (Заявитель)",
        role_executor: "Исполнитель (Техник/Слесарь)",
        role_manager: "Начальник отдела",
        role_admin: "Администратор",
        btn_register: "Зарегистрироваться",
        already_have_account: "Уже есть аккаунт? Войти",
        recover_title: "Восстановление доступа",
        recover_subtitle: "Введите имя пользователя или Email",
        username_or_email_lbl: "Имя пользователя или Email",
        btn_recover: "Получить ссылку",
        back_to_login: "Назад к авторизации",

        // Menu Sidebar
        menu_dashboard: "Дашборд",
        menu_tickets: "Список заявок",
        menu_kanban: "Канбан-доска",
        menu_users: "Пользователи",
        menu_settings: "Конструктор и БД",
        menu_logs: "Лог действий",
        menu_profile: "Мой профиль",
        btn_logout: "Выйти",

        // Titles & KPI
        title_dashboard: "Дашборд аналитики",
        title_tickets: "Список заявок",
        title_kanban: "Канбан-доска",
        title_users: "Пользователи",
        title_settings: "Конструктор и БД",
        title_logs: "Лог действий",
        title_profile: "Мой профиль",
        btn_new_ticket: "Создать заявку",

        kpi_total: "Всего заявок",
        kpi_active: "В работе / Назначено",
        kpi_sla_expired: "Просрочено SLA",
        kpi_avg_time: "Среднее время выполнения",
        chart_status_dist: "Распределение по статусам",
        chart_staff_load: "Нагрузка на исполнителей",

        // Tickets Filter & Table
        search_placeholder: "Поиск по теме или описанию...",
        all_categories: "Все категории",
        all_priorities: "Все приоритеты",
        all_statuses: "Все статусы",

        priority_low: "Низкий",
        priority_medium: "Средний",
        priority_high: "Высокий",

        status_new: "Новая",
        status_assigned: "Назначена",
        status_in_work: "В работе",
        status_completed: "Выполнено",
        status_rejected: "Отклонено",

        selected_count: "Выбрано заявок:",
        choose_executor: "Выбрать исполнителя...",
        btn_assign_bulk: "Назначить",

        th_title: "Тема",
        th_category: "Категория",
        th_priority: "Приоритет",
        th_status: "Статус",
        th_executor: "Исполнитель",
        th_sla: "SLA Дедлайн",
        th_actions: "Действия",
        btn_export_excel: "Экспортировать отчёт в Excel/CSV",

        // Kanban
        kanban_drag_hint: "Перетаскивайте карточки заявок между колонками для смены статуса, либо перетащите заявку на аватар исполнителя.",
        drag_to_assignee_title: "Быстрое назначение исполнителя (Перетащите карточку сюда)",

        // Users
        users_management: "Управление пользователями",
        btn_add_user: "Добавить пользователя",
        th_username: "Логин",
        th_fullname: "ФИО",
        th_role: "Роль",
        th_email: "Email",
        th_phone: "Телефон",

        // Settings & Form builder
        storage_switcher_title: "Режим хранения данных",
        storage_switch_desc: "Переключайтесь между легким JSON-файловым хранилищем и производительным MySQL. Автомиграция запустится при переключении!",
        active_mode_lbl: "Активный режим хранения:",
        change_mode_to: "Изменить режим на:",
        btn_apply_and_migrate: "Применить и перенести данные",
        sla_config_title: "Настройка сроков SLA (часы)",
        btn_save_sla: "Сохранить лимиты SLA",
        categories_config_title: "Категории заявок",
        form_builder_title: "Конструктор дополнительных полей заявок",
        form_builder_desc: "Перетащите типы полей на панель конструктора справа, чтобы настроить индивидуальную форму подачи заявок.",
        fb_toolbox: "Компоненты полей",
        fb_drop_placeholder: "Перетащите элементы сюда",
        btn_save_form: "Сохранить структуру формы",

        // Audit log
        logs_history: "История действий (Лог аудита)",
        btn_refresh: "Обновить",
        th_log_time: "Время",
        th_log_user: "Пользователь",
        th_log_action: "Действие",
        th_log_target: "Объект",
        th_log_details: "Детали",

        // Profile
        profile_details_title: "Личные данные",
        prof_new_pass_lbl: "Новый пароль (оставьте пустым, если не хотите менять)",
        btn_save_changes: "Сохранить изменения",
        notif_settings_title: "Настройки уведомлений",
        notif_freq_lbl: "Частота рассылок",
        notif_freq_immediate: "Мгновенно при изменениях",
        notif_freq_daily: "Ежедневный сводный отчет",
        notif_freq_weekly: "Еженедельный отчет",
        notify_by_email: "Отправлять Email оповещения",
        notify_by_push: "Push-уведомления на ПК/телефон",
        notify_by_tg: "Интеграция с Telegram-ботом",
        btn_save_notif: "Сохранить настройки каналов",

        // Modals
        title_new_ticket: "Создать заявку",
        ticket_theme_lbl: "Тема / Краткое описание проблемы",
        ticket_category_lbl: "Категория",
        ticket_priority_lbl: "Приоритет",
        ticket_desc_lbl: "Подробное описание заявки",
        btn_cancel: "Отмена",
        btn_save: "Сохранить",
        ticket_details_title: "Детали заявки",
        assign_executor: "Назначить исполнителя",
        change_status_lbl: "Изменить статус",
        sla_expired_msg: "СРОК ВЫПОЛНЕНИЯ ИСТЕК!",
        btn_save_status: "Обновить заявку",
        comments_title: "Комментарии и отчёты",
        comment_placeholder: "Оставьте комментарий, отчет о выполнении...",
        btn_attach_file: "Прикрепить файл/фото",
        btn_send: "Отправить",
        user_modal_add_title: "Добавить пользователя",
        user_modal_edit_title: "Редактировать пользователя"
    },
    en: {
        auth_title: "Login to CRM",
        auth_subtitle: "Application management system for repair & services",
        username_lbl: "Username",
        password_lbl: "Password",
        btn_login: "Login",
        forgot_password: "Forgot Password?",
        no_account_yet: "Don't have an account?",
        register_title: "CRM Registration",
        register_subtitle: "Create a new professional account",
        fullname_lbl: "Full Name",
        email_lbl: "Email for notifications",
        phone_lbl: "Phone",
        role_lbl: "Desired Role",
        role_creator: "Responsible Employee (Requester)",
        role_executor: "Executor (Technician/Locksmith)",
        role_manager: "Department Head",
        role_admin: "Administrator",
        btn_register: "Register",
        already_have_account: "Already have an account? Login",
        recover_title: "Password Recovery",
        recover_subtitle: "Enter your username or Email",
        username_or_email_lbl: "Username or Email",
        btn_recover: "Get recovery link",
        back_to_login: "Back to Login",

        // Menu Sidebar
        menu_dashboard: "Dashboard",
        menu_tickets: "Tickets List",
        menu_kanban: "Kanban Board",
        menu_users: "Users",
        menu_settings: "Form Builder & DB",
        menu_logs: "Audit Logs",
        menu_profile: "My Profile",
        btn_logout: "Logout",

        // Titles & KPI
        title_dashboard: "Analytics Dashboard",
        title_tickets: "Tickets List",
        title_kanban: "Kanban Board",
        title_users: "Users Management",
        title_settings: "Form Builder & DB",
        title_logs: "System Logs",
        title_profile: "My Profile",
        btn_new_ticket: "Create Ticket",

        kpi_total: "Total Tickets",
        kpi_active: "In Progress / Assigned",
        kpi_sla_expired: "SLA Expired",
        kpi_avg_time: "Average Completion Time",
        chart_status_dist: "Status Distribution",
        chart_staff_load: "Staff Workload",

        // Tickets Filter & Table
        search_placeholder: "Search theme or description...",
        all_categories: "All Categories",
        all_priorities: "All Priorities",
        all_statuses: "All Statuses",

        priority_low: "Low",
        priority_medium: "Medium",
        priority_high: "High",

        status_new: "New",
        status_assigned: "Assigned",
        status_in_work: "In Work",
        status_completed: "Completed",
        status_rejected: "Rejected",

        selected_count: "Selected tickets:",
        choose_executor: "Choose executor...",
        btn_assign_bulk: "Assign",

        th_title: "Theme",
        th_category: "Category",
        th_priority: "Priority",
        th_status: "Status",
        th_executor: "Executor",
        th_sla: "SLA Deadline",
        th_actions: "Actions",
        btn_export_excel: "Export Excel/CSV Report",

        // Kanban
        kanban_drag_hint: "Drag ticket cards between columns to change status, or drop onto executor avatar badges.",
        drag_to_assignee_title: "Quick Executor Assignment (Drag cards here)",

        // Users
        users_management: "Users Management",
        btn_add_user: "Add User",
        th_username: "Username",
        th_fullname: "Full Name",
        th_role: "Role",
        th_email: "Email",
        th_phone: "Phone",

        // Settings & Form builder
        storage_switcher_title: "Data Storage Mode",
        storage_switch_desc: "Toggle between high-performance MySQL or lightweight JSON. Data migrates automatically upon change!",
        active_mode_lbl: "Active Storage Mode:",
        change_mode_to: "Change Storage to:",
        btn_apply_and_migrate: "Apply and Migrate Data",
        sla_config_title: "SLA Threshold Config (Hours)",
        btn_save_sla: "Save SLA Thresholds",
        categories_config_title: "Ticket Categories",
        form_builder_title: "Custom Ticket Fields Builder",
        form_builder_desc: "Drag and drop input types on the canvas dropzone to customize the ticket submission questionnaire.",
        fb_toolbox: "Field Toolbox",
        fb_drop_placeholder: "Drag and drop elements here",
        btn_save_form: "Save Form Layout",

        // Audit log
        logs_history: "Action History (Audit Log)",
        btn_refresh: "Refresh",
        th_log_time: "Timestamp",
        th_log_user: "User",
        th_log_action: "Action",
        th_log_target: "Target",
        th_log_details: "Details",

        // Profile
        profile_details_title: "Personal Details",
        prof_new_pass_lbl: "New Password (leave empty to keep current)",
        btn_save_changes: "Save Details",
        notif_settings_title: "Notification Subscriptions",
        notif_freq_lbl: "Frequency",
        notif_freq_immediate: "Immediate changes",
        notif_freq_daily: "Daily summarized digest",
        notif_freq_weekly: "Weekly digest",
        notify_by_email: "Send Email alerts",
        notify_by_push: "Push notifications on Desktop/Mobile",
        notify_by_tg: "Telegram bot subscription",
        btn_save_notif: "Save Subscriptions",

        // Modals
        title_new_ticket: "Create New Ticket",
        ticket_theme_lbl: "Theme / Summary of Problem",
        ticket_category_lbl: "Category",
        ticket_priority_lbl: "Priority",
        ticket_desc_lbl: "Detailed Ticket description",
        btn_cancel: "Cancel",
        btn_save: "Save",
        ticket_details_title: "Ticket Details",
        assign_executor: "Assign Executor",
        change_status_lbl: "Change Status",
        sla_expired_msg: "SLA DEADLINE EXPIRED!",
        btn_save_status: "Update Ticket",
        comments_title: "Comments & Work Log",
        comment_placeholder: "Post a comment or executor work report...",
        btn_attach_file: "Attach Document/Photo",
        btn_send: "Send",
        user_modal_add_title: "Add User",
        user_modal_edit_title: "Edit User"
    }
};

// --- Dictionary translation helper ---
function t(key) {
    return i18n[state.lang][key] || key;
}

function updateUILanguage() {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        el.innerText = t(key);
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        el.placeholder = t(key);
    });

    document.getElementById('lang-text').innerText = state.lang.toUpperCase();
}

// Play UI Audio sound alert
function playAlert(soundId) {
    try {
        const audio = document.getElementById(soundId);
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => {});
        }
    } catch(err){}
}

// --- App Navigation & Routing ---
function setupRouting() {
    window.addEventListener('hashchange', handleRouteChange);
    handleRouteChange();
}

function handleRouteChange() {
    const hash = window.location.hash || '#dashboard';
    const viewName = hash.substring(1);

    // Authorization guard: hide pages if not logged in
    if (!state.token) {
        showScreen('auth-screen');
        return;
    }

    // Check view exists
    const viewSection = document.getElementById(`view-${viewName}`);
    if (!viewSection) {
        window.location.hash = '#dashboard';
        return;
    }

    state.currentView = viewName;

    // Toggle active sidebar item
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === hash) {
            item.classList.add('active');
        }
    });

    // Toggle view visibility
    document.querySelectorAll('.app-view').forEach(view => {
        view.classList.remove('active');
    });
    viewSection.classList.add('active');

    // Update Header Title
    const titleKey = `title_${viewName}`;
    document.getElementById('current-view-title').innerText = t(titleKey);

    // Fetch fresh data for that view
    loadViewData(viewName);
}

function showScreen(screenId) {
    document.querySelectorAll('.auth-overlay').forEach(screen => {
        screen.classList.remove('active');
    });
    document.getElementById('app-container').classList.add('hidden');
    document.getElementById(screenId).classList.add('active');
}

function showAppWorkspace() {
    document.querySelectorAll('.auth-overlay').forEach(screen => {
        screen.classList.remove('active');
    });
    document.getElementById('app-container').classList.remove('hidden');
    handleRouteChange();
}

// --- API Helpers ---
async function apiRequest(endpoint, options = {}) {
    options.headers = options.headers || {};
    if (state.token) {
        options.headers['Authorization'] = `Bearer ${state.token}`;
    }

    try {
        const response = await fetch(`api.php?action=${endpoint}`, options);
        if (response.status === 401) {
            // Token expired or invalid
            logout();
            return null;
        }

        // Check if response is CSV
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('text/csv')) {
            return response;
        }

        const data = await response.json();
        if (data && data.error) {
            throw new Error(data.error);
        }
        return data;
    } catch (e) {
        console.error("API error:", e);
        throw e;
    }
}

// --- Load Views Data Controller ---
async function loadViewData(viewName) {
    if (!state.token) return;

    try {
        if (viewName === 'dashboard') {
            const data = await apiRequest('dashboard');
            const settings = await apiRequest('settings');
            if (data && settings) {
                state.categories = settings.categories || [];
                state.priorities = settings.priorities || [];
                renderDashboard(data);
            }
        } else if (viewName === 'tickets') {
            const tickets = await apiRequest('tickets');
            const settings = await apiRequest('settings');
            const users = await apiRequest('users');
            if (tickets && settings) {
                state.tickets = tickets;
                state.categories = settings.categories || [];
                state.priorities = settings.priorities || [];
                state.users = users || [];
                renderTicketsTable();
                populateFilterSelects();
                populateBulkExecutorSelect();
            }
        } else if (viewName === 'kanban') {
            const tickets = await apiRequest('tickets');
            const settings = await apiRequest('settings');
            const users = await apiRequest('users');
            if (tickets && settings) {
                state.tickets = tickets;
                state.categories = settings.categories || [];
                state.priorities = settings.priorities || [];
                state.users = users || [];
                renderKanbanBoard();
            }
        } else if (viewName === 'users') {
            const users = await apiRequest('users');
            if (users) {
                state.users = users;
                renderUsersTable();
            }
        } else if (viewName === 'settings') {
            const settings = await apiRequest('settings');
            if (settings) {
                state.categories = settings.categories || [];
                state.customFields = settings.form_fields || [];
                state.slaSettings = settings.sla || {};
                state.storageMode = settings.storage_mode || 'json';

                renderSettingsPage();
            }
        } else if (viewName === 'logs') {
            const logs = await apiRequest('logs');
            if (logs) {
                state.logs = logs;
                renderLogsTable();
            }
        } else if (viewName === 'profile') {
            const profile = await apiRequest('profile');
            if (profile) {
                renderProfileForm(profile);
            }
        }
    } catch (err) {
        alert("Ошибка загрузки данных: " + err.message);
    }
}

// --- Render 1: Dashboard Analytics ---
function renderDashboard(data) {
    document.getElementById('kpi-total-tickets').innerText = data.total || 0;

    const activeCount = (data.by_status.assigned || 0) + (data.by_status.in_work || 0);
    document.getElementById('kpi-in-progress').innerText = activeCount;

    document.getElementById('kpi-sla-violations').innerText = data.sla_violations || 0;
    document.getElementById('kpi-completed-avg').innerText = (data.average_completion_hours || 0) + ' ч';

    // Render visual status distribution bar chart
    const barChartZone = document.getElementById('status-bar-chart');
    barChartZone.innerHTML = '';

    const maxVal = Math.max(...Object.values(data.by_status), 1);

    const statusTranslate = {
        new: 'status_new',
        assigned: 'status_assigned',
        in_work: 'status_in_work',
        completed: 'status_completed',
        rejected: 'status_rejected'
    };

    const statusColors = {
        new: 'var(--info-color)',
        assigned: 'var(--primary-color)',
        in_work: 'var(--warning-color)',
        completed: 'var(--success-color)',
        rejected: 'var(--danger-color)'
    };

    Object.entries(data.by_status).forEach(([status, count]) => {
        const pct = (count / maxVal) * 100;
        const labelText = t(statusTranslate[status] || status);
        const col = statusColors[status] || 'var(--primary-color)';

        const row = document.createElement('div');
        row.className = 'bar-row';
        row.innerHTML = `
            <div class="bar-label">${labelText}</div>
            <div class="bar-track">
                <div class="bar-fill" style="width: ${pct}%; background-color: ${col};"></div>
            </div>
            <div class="bar-val">${count}</div>
        `;
        barChartZone.appendChild(row);
    });

    // Render Workloads List
    const wlZone = document.getElementById('workload-container');
    wlZone.innerHTML = '';

    if (!data.workload || data.workload.length === 0) {
        wlZone.innerHTML = `<p class="text-muted" style="font-size: 0.85rem;">Исполнители отсутствуют или не имеют активных заявок.</p>`;
    } else {
        data.workload.forEach(item => {
            const initials = item.full_name ? item.full_name.charAt(0).toUpperCase() : '?';
            const itemRow = document.createElement('div');
            itemRow.className = 'workload-item';
            itemRow.innerHTML = `
                <div class="workload-user">
                    <div class="workload-user-avatar">${initials}</div>
                    <span>${item.full_name}</span>
                </div>
                <div class="workload-count">${item.active_tickets} ${t('menu_tickets').toLowerCase()}</div>
            `;
            wlZone.appendChild(itemRow);
        });
    }
}

// --- Render 2: Tickets Data Table ---
let currentSortColumn = 'id';
let currentSortDirection = 'desc';

function renderTicketsTable() {
    const searchVal = document.getElementById('search-tickets-input').value.toLowerCase();
    const catVal = document.getElementById('filter-category').value;
    const prioVal = document.getElementById('filter-priority').value;
    const statusVal = document.getElementById('filter-status').value;

    let filtered = state.tickets.filter(t => {
        // Search
        const matchSearch = t.title.toLowerCase().includes(searchVal) ||
                            (t.description && t.description.toLowerCase().includes(searchVal)) ||
                            (t.id && String(t.id) === searchVal);

        // Category
        const matchCategory = !catVal || t.category === catVal;

        // Priority
        const matchPriority = !prioVal || t.priority === prioVal;

        // Status mapping to cover EN/RU status database entries
        const statusMap = {
            'new': 'new', 'новая': 'new',
            'assigned': 'assigned', 'назначена': 'assigned',
            'in_work': 'in_work', 'в работе': 'in_work',
            'completed': 'completed', 'выполнено': 'completed',
            'rejected': 'rejected', 'отклонено': 'rejected'
        };
        const dbStatus = statusMap[t.status.toLowerCase()] || 'new';
        const matchStatus = !statusVal || dbStatus === statusVal;

        return matchSearch && matchCategory && matchPriority && matchStatus;
    });

    // Sort
    filtered.sort((a, b) => {
        let valA = a[currentSortColumn];
        let valB = b[currentSortColumn];

        // special conversion
        if (currentSortColumn === 'id') {
            valA = parseInt(valA);
            valB = parseInt(valB);
        } else {
            valA = String(valA || '').toLowerCase();
            valB = String(valB || '').toLowerCase();
        }

        if (valA < valB) return currentSortDirection === 'asc' ? -1 : 1;
        if (valA > valB) return currentSortDirection === 'asc' ? 1 : -1;
        return 0;
    });

    const tbody = document.getElementById('tickets-table-body');
    tbody.innerHTML = '';

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--text-muted);">Заявки отсутствуют.</td></tr>`;
        return;
    }

    filtered.forEach(tItem => {
        const isChecked = state.selectedTicketIds.has(tItem.id) ? 'checked' : '';

        // Localized Priority representation
        const priorityLabels = {
            low: t('priority_low'),
            medium: t('priority_medium'),
            high: t('priority_high')
        };
        const prioText = priorityLabels[tItem.priority] || tItem.priority;
        const priorityObj = state.priorities.find(p => p.key === tItem.priority);
        const prioColor = priorityObj ? priorityObj.color : '#64748b';

        // Localized Status representation
        const statusLabels = {
            new: t('status_new'),
            assigned: t('status_assigned'),
            in_work: t('status_in_work'),
            completed: t('status_completed'),
            rejected: t('status_rejected')
        };

        const rawStatus = String(tItem.status).toLowerCase();
        let normalizedStatusKey = rawStatus;
        if (rawStatus === 'новая') normalizedStatusKey = 'new';
        if (rawStatus === 'назначена') normalizedStatusKey = 'assigned';
        if (rawStatus === 'в работе' || rawStatus === 'в_работе') normalizedStatusKey = 'in_work';
        if (rawStatus === 'выполнено') normalizedStatusKey = 'completed';
        if (rawStatus === 'отклонено') normalizedStatusKey = 'rejected';

        const statusText = statusLabels[normalizedStatusKey] || tItem.status;

        // Deadline layout with safe SLA warn indicator
        let deadlineHtml = tItem.sla_deadline ? tItem.sla_deadline : '--';
        if (tItem.sla_expired) {
            deadlineHtml = `<span style="color: var(--danger-color); font-weight: bold;" title="SLA Overdue!"><i class="fa-solid fa-triangle-exclamation"></i> ${deadlineHtml}</span>`;
        }

        const isManagerAdmin = state.user.role === 'admin' || state.user.role === 'manager';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="admin-only manager-only ${isManagerAdmin ? '' : 'hidden'}">
                <input type="checkbox" class="ticket-row-check" data-id="${tItem.id}" ${isChecked}>
            </td>
            <td><b>#${tItem.id}</b></td>
            <td><a href="#" class="btn-view-ticket" data-id="${tItem.id}" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">${tItem.title}</a></td>
            <td>${tItem.category || '--'}</td>
            <td>
                <span class="priority-tag">
                    <span class="priority-dot" style="background-color: ${prioColor};"></span>
                    ${prioText}
                </span>
            </td>
            <td>
                <span class="badge badge-${normalizedStatusKey}">${statusText}</span>
            </td>
            <td>${tItem.assignee_name || '--'}</td>
            <td>${deadlineHtml}</td>
            <td>
                <div class="table-actions">
                    <button class="btn btn-secondary btn-sm btn-view-ticket" data-id="${tItem.id}"><i class="fa-solid fa-eye"></i></button>
                    ${state.user.role === 'admin' ? `<button class="btn btn-danger btn-sm btn-delete-ticket" data-id="${tItem.id}"><i class="fa-solid fa-trash"></i></button>` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    setupTableInteractions();
}

function setupTableInteractions() {
    // Row click view details
    document.querySelectorAll('.btn-view-ticket').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            const id = el.getAttribute('data-id');
            openTicketDetails(id);
        });
    });

    // Delete ticket click
    document.querySelectorAll('.btn-delete-ticket').forEach(el => {
        el.addEventListener('click', async () => {
            const id = el.getAttribute('data-id');
            if (confirm("Вы действительно хотите удалить заявку #" + id + "?")) {
                try {
                    await apiRequest('ticket_delete', {
                        method: 'POST',
                        body: JSON.stringify({ id: id })
                    });
                    playAlert('sound-success');
                    loadViewData('tickets');
                } catch (e) {
                    playAlert('sound-error');
                    alert(e.message);
                }
            }
        });
    });

    // Handle individual row checkbox clicks
    document.querySelectorAll('.ticket-row-check').forEach(chk => {
        chk.addEventListener('change', () => {
            const id = parseInt(chk.getAttribute('data-id'));
            if (chk.checked) {
                state.selectedTicketIds.add(id);
            } else {
                state.selectedTicketIds.delete(id);
            }
            toggleBulkActionsPanel();
        });
    });
}

function toggleBulkActionsPanel() {
    const panel = document.getElementById('bulk-actions-bar');
    if (state.selectedTicketIds.size > 0) {
        panel.classList.remove('hidden');
        document.getElementById('bulk-selected-count').innerText = state.selectedTicketIds.size;
    } else {
        panel.classList.add('hidden');
    }
}

// Populate Search Filter Selectors
function populateFilterSelects() {
    const catSelect = document.getElementById('filter-category');
    const savedVal = catSelect.value;
    catSelect.innerHTML = `<option value="">${t('all_categories')}</option>`;
    state.categories.forEach(cat => {
        catSelect.innerHTML += `<option value="${cat}" ${cat === savedVal ? 'selected' : ''}>${cat}</option>`;
    });
}

function populateBulkExecutorSelect() {
    const bulkSelect = document.getElementById('bulk-assignee-select');
    bulkSelect.innerHTML = `<option value="">${t('choose_executor')}</option>`;
    state.users.forEach(u => {
        if (u.role === 'executor') {
            bulkSelect.innerHTML += `<option value="${u.id}">${u.full_name || u.username}</option>`;
        }
    });
}

// --- Render 3: Kanban Board (Drag and Drop Integration) ---
function renderKanbanBoard() {
    const containers = {
        new: document.getElementById('kb-column-new'),
        assigned: document.getElementById('kb-column-assigned'),
        in_work: document.getElementById('kb-column-inwork'),
        completed: document.getElementById('kb-column-completed'),
        rejected: document.getElementById('kb-column-rejected')
    };

    // Clear containers and counters
    Object.keys(containers).forEach(key => {
        containers[key].innerHTML = '';
        document.getElementById(`count-kb-${key === 'in_work' ? 'inwork' : key}`).innerText = '0';
    });

    const statusCounts = { new: 0, assigned: 0, in_work: 0, completed: 0, rejected: 0 };

    const statusMap = {
        'new': 'new', 'новая': 'new',
        'assigned': 'assigned', 'назначена': 'assigned',
        'in_work': 'in_work', 'в работе': 'in_work', 'в_работе': 'in_work',
        'completed': 'completed', 'выполнено': 'completed',
        'rejected': 'rejected', 'отклонено': 'rejected'
    };

    state.tickets.forEach(ticket => {
        const canonicalStatus = statusMap[ticket.status.toLowerCase()] || 'new';

        statusCounts[canonicalStatus]++;

        // Priority config
        const priorityObj = state.priorities.find(p => p.key === ticket.priority);
        const col = priorityObj ? priorityObj.color : '#94a3b8';

        const card = document.createElement('div');
        card.className = 'kanban-card';
        card.setAttribute('draggable', 'true');
        card.setAttribute('data-id', ticket.id);

        card.innerHTML = `
            <div style="width: 40px; height: 4px; border-radius: 2px; background-color: ${col}; margin-bottom: 8px;"></div>
            <h4>#${ticket.id}: ${ticket.title}</h4>
            <div class="kanban-card-meta">
                <span><i class="fa-solid fa-user-pen"></i> ${ticket.creator_name}</span>
                <span><i class="fa-solid fa-user-gear"></i> ${ticket.assignee_name || '--'}</span>
                ${ticket.sla_expired ? `<span style="color: var(--danger-color); font-weight: 700;"><i class="fa-solid fa-triangle-exclamation"></i> Overdue!</span>` : ''}
            </div>
        `;

        // Click to view ticket details
        card.addEventListener('click', () => openTicketDetails(ticket.id));

        // Drag events on ticket cards
        card.addEventListener('dragstart', (e) => {
            state.draggedTicketId = ticket.id;
            e.dataTransfer.setData('text/plain', ticket.id);
            card.style.opacity = '0.5';
        });

        card.addEventListener('dragend', () => {
            card.style.opacity = '1';
            state.draggedTicketId = null;
        });

        if (containers[canonicalStatus]) {
            containers[canonicalStatus].appendChild(card);
        }
    });

    // Update Kanban Counters
    Object.keys(statusCounts).forEach(key => {
        const countId = `count-kb-${key === 'in_work' ? 'inwork' : key}`;
        document.getElementById(countId).innerText = statusCounts[key];
    });

    // Wire-up Column Drops
    Object.keys(containers).forEach(key => {
        const colContainer = containers[key];
        colContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        colContainer.addEventListener('drop', async (e) => {
            e.preventDefault();
            const ticketId = state.draggedTicketId || e.dataTransfer.getData('text/plain');
            if (ticketId) {
                // Update ticket status dynamically
                try {
                    await apiRequest('ticket_save', {
                        method: 'POST',
                        body: JSON.stringify({ id: ticketId, status: key })
                    });
                    playAlert('sound-success');
                    loadViewData('kanban');
                } catch(err) {
                    playAlert('sound-error');
                    alert(err.message);
                }
            }
        });
    });

    // Render Drag-to-assignee row badges
    const dropListZone = document.getElementById('kanban-execs-drop-list');
    dropListZone.innerHTML = '';

    state.users.forEach(u => {
        if (u.role === 'executor') {
            const initials = u.full_name ? u.full_name.split(' ').map(n=>n[0]).join('') : u.username[0].toUpperCase();

            const badge = document.createElement('div');
            badge.className = 'executor-drop-badge';
            badge.innerHTML = `
                <div class="workload-user-avatar" style="background-color: var(--primary-color);">${initials}</div>
                <span>${u.full_name || u.username}</span>
            `;

            badge.addEventListener('dragover', (e) => {
                e.preventDefault();
                badge.classList.add('dragover');
            });

            badge.addEventListener('dragleave', () => {
                badge.classList.remove('dragover');
            });

            badge.addEventListener('drop', async (e) => {
                e.preventDefault();
                badge.classList.remove('dragover');
                const ticketId = state.draggedTicketId || e.dataTransfer.getData('text/plain');
                if (ticketId) {
                    try {
                        await apiRequest('ticket_save', {
                            method: 'POST',
                            body: JSON.stringify({ id: ticketId, assignee_id: u.id })
                        });
                        playAlert('sound-success');
                        loadViewData('kanban');
                    } catch(err) {
                        playAlert('sound-error');
                        alert(err.message);
                    }
                }
            });

            dropListZone.appendChild(badge);
        }
    });
}

// --- Render 4: User Directory (Admin Only) ---
function renderUsersTable() {
    const tbody = document.getElementById('users-table-body');
    tbody.innerHTML = '';

    state.users.forEach(u => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>#${u.id}</td>
            <td><b>${u.username}</b></td>
            <td>${u.full_name || '--'}</td>
            <td><span class="badge" style="background-color: var(--border-color); color: var(--text-main);">${u.role.toUpperCase()}</span></td>
            <td>${u.email || '--'}</td>
            <td>${u.phone || '--'}</td>
            <td class="admin-only">
                <div class="table-actions">
                    <button class="btn btn-secondary btn-sm btn-edit-user" data-id="${u.id}"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-danger btn-sm btn-delete-user" data-id="${u.id}"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Edit & delete listeners
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const uObj = state.users.find(x => String(x.id) === String(id));
            if (uObj) {
                openUserFormModal(uObj);
            }
        });
    });

    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (confirm("Вы уверены, что хотите удалить этого пользователя?")) {
                try {
                    await apiRequest('user_delete', {
                        method: 'POST',
                        body: JSON.stringify({ id: id })
                    });
                    playAlert('sound-success');
                    loadViewData('users');
                } catch(e) {
                    playAlert('sound-error');
                    alert(e.message);
                }
            }
        });
    });
}

// --- Render 5: Settings, custom Form builder, DB Mode ---
function renderSettingsPage() {
    // DB mode badges
    document.getElementById('badge-mode-json').classList.remove('active');
    document.getElementById('badge-mode-mysql').classList.remove('active');

    if (state.storageMode === 'mysql') {
        document.getElementById('badge-mode-mysql').classList.add('active');
        document.getElementById('settings-storage-select').value = 'mysql';
        document.getElementById('mysql-credentials-box').classList.remove('hidden');
    } else {
        document.getElementById('badge-mode-json').classList.add('active');
        document.getElementById('settings-storage-select').value = 'json';
        document.getElementById('mysql-credentials-box').classList.add('hidden');
    }

    // SLA Inputs
    document.getElementById('sla-low').value = state.slaSettings.low || 72;
    document.getElementById('sla-medium').value = state.slaSettings.medium || 24;
    document.getElementById('sla-high').value = state.slaSettings.high || 4;

    // Categories List
    renderSettingsCategories();

    // Custom Fields Canvas
    renderFormBuilderCanvas();
}

function renderSettingsCategories() {
    const list = document.getElementById('settings-categories-list');
    list.innerHTML = '';
    state.categories.forEach((cat, index) => {
        const item = document.createElement('div');
        item.className = 'category-config-item';
        item.innerHTML = `
            <span>${cat}</span>
            <button class="btn btn-danger btn-sm btn-delete-cat" data-index="${index}">&times;</button>
        `;
        list.appendChild(item);
    });

    document.querySelectorAll('.btn-delete-cat').forEach(btn => {
        btn.addEventListener('click', async () => {
            const index = parseInt(btn.getAttribute('data-index'));
            state.categories.splice(index, 1);
            await saveSettings();
            renderSettingsPage();
        });
    });
}

// Custom Form builder drag events
function renderFormBuilderCanvas() {
    const wrapper = document.getElementById('builder-fields-wrapper');
    wrapper.innerHTML = '';

    const placeholder = document.getElementById('canvas-placeholder-msg');
    if (state.customFields.length > 0) {
        placeholder.classList.add('hidden');
    } else {
        placeholder.classList.remove('hidden');
    }

    state.customFields.forEach((f, idx) => {
        const row = document.createElement('div');
        row.className = 'canvas-field-row';
        row.innerHTML = `
            <div class="canvas-field-row-left">
                <i class="fa-solid fa-grip-vertical text-muted"></i>
                <span class="badge" style="background-color: var(--primary-color); color: white;">${f.type.toUpperCase()}</span>
                <input type="text" class="form-control form-control-sm field-label-editor" data-index="${idx}" value="${f.label}">
                <div class="form-checkbox-group">
                    <input type="checkbox" class="field-req-editor" data-index="${idx}" ${f.required ? 'checked' : ''} id="req-${idx}">
                    <label for="req-${idx}" style="font-size:0.75rem; margin-bottom:0; font-weight:normal;">Required</label>
                </div>
            </div>
            <button class="btn btn-danger btn-sm btn-builder-field-del" data-index="${idx}"><i class="fa-solid fa-trash"></i></button>
        `;
        wrapper.appendChild(row);
    });

    // Attach event listeners to builder fields
    document.querySelectorAll('.field-label-editor').forEach(inp => {
        inp.addEventListener('input', (e) => {
            const idx = parseInt(inp.getAttribute('data-index'));
            state.customFields[idx].label = e.target.value;
        });
    });

    document.querySelectorAll('.field-req-editor').forEach(chk => {
        chk.addEventListener('change', (e) => {
            const idx = parseInt(chk.getAttribute('data-index'));
            state.customFields[idx].required = e.target.checked;
        });
    });

    document.querySelectorAll('.btn-builder-field-del').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = parseInt(btn.getAttribute('data-index'));
            state.customFields.splice(idx, 1);
            renderFormBuilderCanvas();
        });
    });
}

async function saveSettings() {
    try {
        await apiRequest('settings', {
            method: 'POST',
            body: JSON.stringify({
                categories: state.categories,
                form_fields: state.customFields,
                sla: state.slaSettings
            })
        });
        playAlert('sound-success');
    } catch (e) {
        playAlert('sound-error');
        alert("Ошибка сохранения: " + e.message);
    }
}

// Setup custom drag & drop form builder event listners
function setupFormBuilderDragEvents() {
    const canvas = document.getElementById('builder-canvas-zone');

    document.querySelectorAll('.draggable-field-item').forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('field-type', item.getAttribute('data-type'));
        });
    });

    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
    });

    canvas.addEventListener('drop', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('field-type');
        if (type) {
            const defaultLabel = type === 'text' ? 'Текстовое поле' : type === 'number' ? 'Числовое поле' : 'Выпадающий список';
            const newId = 'custom_' + Date.now();
            state.customFields.push({
                id: newId,
                label: defaultLabel,
                type: type,
                required: false
            });
            renderFormBuilderCanvas();
        }
    });
}

// --- Render 6: System Audit Logs ---
function renderLogsTable() {
    const tbody = document.getElementById('logs-table-body');
    tbody.innerHTML = '';

    if (state.logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">Логи отсутствуют.</td></tr>`;
        return;
    }

    state.logs.forEach(l => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>#${l.id}</td>
            <td><small>${l.created_at}</small></td>
            <td><b>${l.user_id}</b></td>
            <td><span class="badge" style="background-color: var(--border-color); color: var(--text-main);">${l.action}</span></td>
            <td><code>${l.target_type} / ${l.target_id}</code></td>
            <td><small>${l.details}</small></td>
        `;
        tbody.appendChild(row);
    });
}

// --- Render 7: User Profile Settings ---
function renderProfileForm(profile) {
    document.getElementById('prof-fullname').value = profile.full_name || '';
    document.getElementById('prof-email').value = profile.email || '';
    document.getElementById('prof-phone').value = profile.phone || '';
    document.getElementById('prof-notif-freq').value = profile.notifications_freq || 'immediate';

    document.getElementById('prof-notify-email').checked = profile.notify_email == 1;
    document.getElementById('prof-notify-push').checked = profile.notify_push == 1;
    document.getElementById('prof-notify-tg').checked = profile.notify_tg == 1;
}

// --- Action Modals & Dialog Controllers ---

// 1. Create / Edit Ticket Modal
function openTicketFormModal(ticketObj = null) {
    const modal = document.getElementById('ticket-modal');
    const form = document.getElementById('ticket-form');
    form.reset();

    const catSelect = document.getElementById('t-category');
    catSelect.innerHTML = '';
    state.categories.forEach(cat => {
        catSelect.innerHTML += `<option value="${cat}">${cat}</option>`;
    });

    const dynamicContainer = document.getElementById('dynamic-fields-container');
    dynamicContainer.innerHTML = '';

    if (!ticketObj) {
        // Create Mode
        document.getElementById('ticket-modal-title').innerText = t('title_new_ticket');
        document.getElementById('ticket-id-field').value = '';

        // Render custom form questions dynamically
        state.customFields.forEach(f => {
            const grp = document.createElement('div');
            grp.className = 'form-group';
            grp.innerHTML = `
                <label for="dyn-${f.id}">${f.label} ${f.required ? '<span style="color:var(--danger-color);">*</span>' : ''}</label>
                ${f.type === 'select' ?
                    `<select id="dyn-${f.id}" class="dynamic-input-field" data-id="${f.id}" ${f.required ? 'required' : ''}>
                        <option value="">-- Выбрать --</option>
                        <option value="Да">Да</option>
                        <option value="Нет">Нет</option>
                        <option value="Не применимо">Не применимо</option>
                    </select>` :
                    `<input type="${f.type}" id="dyn-${f.id}" class="dynamic-input-field" data-id="${f.id}" ${f.required ? 'required' : ''} placeholder="${f.label}">`
                }
            `;
            dynamicContainer.appendChild(grp);
        });
    } else {
        // Edit Mode
        document.getElementById('ticket-modal-title').innerText = "Редактировать заявку #" + ticketObj.id;
        document.getElementById('ticket-id-field').value = ticketObj.id;

        document.getElementById('t-title').value = ticketObj.title;
        document.getElementById('t-category').value = ticketObj.category || '';
        document.getElementById('t-priority').value = ticketObj.priority || 'medium';
        document.getElementById('t-description').value = ticketObj.description || '';

        // Render with existing answers
        state.customFields.forEach(f => {
            const ans = (ticketObj.custom_fields && ticketObj.custom_fields[f.id]) ? ticketObj.custom_fields[f.id] : '';
            const grp = document.createElement('div');
            grp.className = 'form-group';
            grp.innerHTML = `
                <label for="dyn-${f.id}">${f.label} ${f.required ? '<span style="color:var(--danger-color);">*</span>' : ''}</label>
                ${f.type === 'select' ?
                    `<select id="dyn-${f.id}" class="dynamic-input-field" data-id="${f.id}" ${f.required ? 'required' : ''}>
                        <option value="">-- Выбрать --</option>
                        <option value="Да" ${ans === 'Да' ? 'selected' : ''}>Да</option>
                        <option value="Нет" ${ans === 'Нет' ? 'selected' : ''}>Нет</option>
                        <option value="Не применимо" ${ans === 'Не применимо' ? 'selected' : ''}>Не применимо</option>
                    </select>` :
                    `<input type="${f.type}" id="dyn-${f.id}" class="dynamic-input-field" data-id="${f.id}" ${f.required ? 'required' : ''} value="${ans}">`
                }
            `;
            dynamicContainer.appendChild(grp);
        });
    }

    modal.classList.add('active');
}

// 2. Full Ticket Details View (Comments, SLA, execution logs)
async function openTicketDetails(ticketId) {
    const modal = document.getElementById('ticket-details-modal');

    try {
        // Fetch detailed ticket metadata and related comments
        const tickets = await apiRequest('tickets');
        const ticket = tickets.find(t => String(t.id) === String(ticketId));
        if (!ticket) {
            alert("Заявка не найдена.");
            return;
        }

        document.getElementById('det-ticket-id').innerText = ticket.id;
        document.getElementById('det-title').innerText = ticket.title;
        document.getElementById('det-creator').innerText = ticket.creator_name;
        document.getElementById('det-created-at').innerText = ticket.created_at;
        document.getElementById('det-description').innerText = ticket.description || '--';

        // Render custom questions answers list
        const qaBox = document.getElementById('det-custom-fields-container');
        qaBox.innerHTML = '';

        // Find custom layout fields configurations
        const settings = await apiRequest('settings');
        const customFieldsSchema = settings.form_fields || [];

        if (customFieldsSchema.length === 0) {
            qaBox.innerHTML = `<p class="text-muted" style="font-size:0.8rem;">Дополнительные поля не настроены.</p>`;
        } else {
            customFieldsSchema.forEach(f => {
                const answerVal = (ticket.custom_fields && ticket.custom_fields[f.id]) ? ticket.custom_fields[f.id] : '--';
                const row = document.createElement('div');
                row.className = 'detail-custom-answer';
                row.innerHTML = `<span>${f.label}:</span> <span>${answerVal}</span>`;
                qaBox.appendChild(row);
            });
        }

        // SLA compliance alert warning display trigger
        document.getElementById('det-sla-deadline').innerText = ticket.sla_deadline || '--';
        if (ticket.sla_expired) {
            document.getElementById('det-sla-status-warning').classList.remove('hidden');
        } else {
            document.getElementById('det-sla-status-warning').classList.add('hidden');
        }

        // Status indicator class badges layout updates
        const statusBadgesMap = {
            'new': 'badge-new', 'новая': 'badge-new',
            'assigned': 'badge-assigned', 'назначена': 'badge-assigned',
            'in_work': 'badge-in_work', 'в работе': 'badge-in_work', 'в_работе': 'badge-in_work',
            'completed': 'badge-completed', 'выполнено': 'badge-completed',
            'rejected': 'badge-rejected', 'отклонено': 'badge-rejected'
        };
        const stKey = String(ticket.status).toLowerCase();
        const normalizedKey = stKey === 'новая' ? 'new' : stKey === 'назначена' ? 'assigned' : (stKey === 'в работе' || stKey === 'в_работе') ? 'in_work' : stKey === 'выполнено' ? 'completed' : stKey === 'отклонено' ? 'rejected' : stKey;

        const badgeClass = statusBadgesMap[stKey] || 'badge-new';
        const stLabel = i18n[state.lang][`status_${normalizedKey}`] || ticket.status;

        const badgeEl = document.getElementById('det-badge-status');
        badgeEl.className = `badge ${badgeClass}`;
        badgeEl.innerText = stLabel;

        // Populate Assignee dropdown
        const assignSelect = document.getElementById('det-assignee-select');
        assignSelect.innerHTML = '<option value="">-- Не назначен --</option>';
        state.users.forEach(u => {
            if (u.role === 'executor') {
                assignSelect.innerHTML += `<option value="${u.id}" ${String(u.id) === String(ticket.assignee_id) ? 'selected' : ''}>${u.full_name || u.username}</option>`;
            }
        });

        // Populate Status selector
        document.getElementById('det-status-select').value = normalizedKey;

        // Store ticket ID in global dataset reference for updating controls easily
        modal.setAttribute('data-loaded-id', ticket.id);

        // Render Nested Comments List
        await loadTicketComments(ticket.id);

        modal.classList.add('active');
    } catch(err) {
        alert(err.message);
    }
}

async function loadTicketComments(ticketId) {
    const commentsListZone = document.getElementById('det-comments-container');
    commentsListZone.innerHTML = '';

    try {
        const comments = await apiRequest(`comments&ticket_id=${ticketId}`);
        if (!comments || comments.length === 0) {
            commentsListZone.innerHTML = `<p class="text-muted" style="text-align: center; padding: 20px 0; font-size: 0.85rem;">Комментарии отсутствуют.</p>`;
            return;
        }

        comments.forEach(c => {
            const card = document.createElement('div');
            card.className = 'comment-card';

            // Render beautiful layout with attachment anchor downloads links safely
            let attachmentHtml = '';
            if (c.attachment_path) {
                const fileExt = c.attachment_path.split('.').pop().toLowerCase();
                const isImg = ['png', 'jpg', 'jpeg', 'gif'].includes(fileExt);

                if (isImg) {
                    attachmentHtml = `
                        <div style="margin-top: 8px;">
                            <img src="${c.attachment_path}" style="max-width: 100%; max-height: 150px; border-radius:6px; border: 1px solid var(--border-color); display: block;" alt="Attachment">
                            <a href="${c.attachment_path}" target="_blank" class="comment-attachment-preview">
                                <i class="fa-solid fa-expand"></i> ${c.attachment_name || 'Просмотреть'}
                            </a>
                        </div>
                    `;
                } else {
                    attachmentHtml = `
                        <a href="${c.attachment_path}" target="_blank" class="comment-attachment-preview">
                            <i class="fa-solid fa-file-arrow-down"></i> ${c.attachment_name || 'Скачать файл'}
                        </a>
                    `;
                }
            }

            card.innerHTML = `
                <div class="comment-meta">
                    <span class="comment-author">${c.user_name}</span>
                    <span class="comment-time">${c.created_at}</span>
                </div>
                <div class="comment-text">${c.comment_text || ''}</div>
                ${attachmentHtml}
            `;
            commentsListZone.appendChild(card);
        });

        // Scroll to bottom
        commentsListZone.scrollTop = commentsListZone.scrollHeight;
    } catch(e) {
        console.error("Comments error: ", e);
    }
}

// 3. User modal adding / editing controller
function openUserFormModal(userObj = null) {
    const modal = document.getElementById('user-modal');
    const form = document.getElementById('user-form');
    form.reset();

    if (!userObj) {
        // Add User mode
        document.getElementById('user-modal-title').innerText = t('user_modal_add_title');
        document.getElementById('user-id-field').value = '';
        document.getElementById('u-password').setAttribute('required', 'true');
        document.getElementById('u-password-label').innerHTML = t('password_lbl') + ' <span style="color:var(--danger-color);">*</span>';
    } else {
        // Edit User mode
        document.getElementById('user-modal-title').innerText = t('user_modal_edit_title');
        document.getElementById('user-id-field').value = userObj.id;
        document.getElementById('u-password').removeAttribute('required');
        document.getElementById('u-password-label').innerHTML = t('password_lbl') + ' <span style="font-size:0.75rem; color:var(--text-muted);">(оставьте пустым для сохранения)</span>';

        document.getElementById('u-username').value = userObj.username;
        document.getElementById('u-role').value = userObj.role;
        document.getElementById('u-fullname').value = userObj.full_name || '';
        document.getElementById('u-email').value = userObj.email || '';
        document.getElementById('u-phone').value = userObj.phone || '';
    }

    modal.classList.add('active');
}

// --- Auth Systems & Logins/Logout ---
function checkAuth() {
    if (state.token && state.user) {
        // Setup initial sidebar details
        document.getElementById('sidebar-fullname').innerText = state.user.full_name || state.user.username;

        // Translate roles
        const rolesTranslate = {
            admin: t('role_admin'),
            manager: t('role_manager'),
            creator: t('role_creator'),
            executor: t('role_executor')
        };
        document.getElementById('sidebar-role').innerText = rolesTranslate[state.user.role] || state.user.role;
        document.getElementById('sidebar-role').className = `badge badge-${state.user.role}`;

        // Render initials on avatar bubble
        document.getElementById('avatar-circle').innerText = state.user.full_name ? state.user.full_name.charAt(0).toUpperCase() : state.user.username.charAt(0).toUpperCase();

        // Toggle view sidebar tabs based on permission roles
        document.querySelectorAll('.admin-only').forEach(el => {
            if (state.user.role === 'admin') el.classList.remove('hidden');
            else el.classList.add('hidden');
        });
        document.querySelectorAll('.manager-only').forEach(el => {
            if (state.user.role === 'manager' || state.user.role === 'admin') el.classList.remove('hidden');
            else el.classList.add('hidden');
        });
        document.querySelectorAll('.creator-only').forEach(el => {
            if (state.user.role === 'creator' || state.user.role === 'admin' || state.user.role === 'manager') el.classList.remove('hidden');
            else el.classList.add('hidden');
        });
        document.querySelectorAll('.executor-only').forEach(el => {
            if (state.user.role === 'executor' || state.user.role === 'admin' || state.user.role === 'manager') el.classList.remove('hidden');
            else el.classList.add('hidden');
        });

        showAppWorkspace();
    } else {
        showScreen('auth-screen');
    }
}

function logout() {
    localStorage.removeItem('crm_jwt_token');
    localStorage.removeItem('crm_user_info');
    state.token = null;
    state.user = null;
    checkAuth();
}

// --- Theme Switchers ---
function toggleTheme() {
    state.theme = state.theme === 'light' ? 'dark' : 'light';
    localStorage.setItem('crm_theme', state.theme);
    applyTheme();
}

function applyTheme() {
    if (state.theme === 'dark') {
        document.body.classList.remove('light-mode');
        document.body.classList.add('dark-mode');
        document.getElementById('btn-theme-toggle').innerHTML = `<i class="fa-solid fa-sun" style="color:#f59e0b;"></i>`;
    } else {
        document.body.classList.remove('dark-mode');
        document.body.classList.add('light-mode');
        document.getElementById('btn-theme-toggle').innerHTML = `<i class="fa-solid fa-moon"></i>`;
    }
}

// --- Wire Event Handlers ---
function setupEvents() {
    // 1. Language switcher toggle click
    document.getElementById('btn-lang-toggle').addEventListener('click', () => {
        state.lang = state.lang === 'ru' ? 'en' : 'ru';
        localStorage.setItem('crm_lang', state.lang);
        updateUILanguage();
        handleRouteChange(); // reload language strings in charts and lists
    });

    // 2. Dark/Light Theme toggle click
    document.getElementById('btn-theme-toggle').addEventListener('click', toggleTheme);

    // 3. Login submit action
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const u = document.getElementById('login-username').value.trim();
        const p = document.getElementById('login-password').value.trim();

        try {
            const data = await apiRequest('login', {
                method: 'POST',
                body: JSON.stringify({ username: u, password: p })
            });
            if (data && data.success) {
                state.token = data.token;
                state.user = data.user;
                localStorage.setItem('crm_jwt_token', data.token);
                localStorage.setItem('crm_user_info', JSON.stringify(data.user));

                playAlert('sound-success');
                checkAuth();
            }
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 4. Register switches UI & submits actions
    document.getElementById('link-show-register').addEventListener('click', (e) => {
        e.preventDefault();
        showScreen('register-screen');
    });

    document.getElementById('link-back-login').addEventListener('click', (e) => {
        e.preventDefault();
        showScreen('auth-screen');
    });

    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const u = document.getElementById('reg-username').value.trim();
        const p = document.getElementById('reg-password').value.trim();
        const fn = document.getElementById('reg-fullname').value.trim();
        const em = document.getElementById('reg-email').value.trim();
        const ph = document.getElementById('reg-phone').value.trim();
        const rl = document.getElementById('reg-role').value;

        try {
            const data = await apiRequest('register', {
                method: 'POST',
                body: JSON.stringify({ username: u, password: p, full_name: fn, email: em, phone: ph, role: rl })
            });
            if (data && data.success) {
                state.token = data.token;
                state.user = data.user;
                localStorage.setItem('crm_jwt_token', data.token);
                localStorage.setItem('crm_user_info', JSON.stringify(data.user));

                playAlert('sound-success');
                checkAuth();
            }
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 5. Recovery password workflow
    document.getElementById('link-recover-pwd').addEventListener('click', (e) => {
        e.preventDefault();
        showScreen('recover-screen');
    });

    document.getElementById('link-recover-back').addEventListener('click', (e) => {
        e.preventDefault();
        showScreen('auth-screen');
    });

    document.getElementById('recover-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const identity = document.getElementById('recover-identity').value.trim();

        try {
            const data = await apiRequest('recover', {
                method: 'POST',
                body: JSON.stringify({ identity: identity })
            });
            if (data && data.success) {
                playAlert('sound-success');
                alert(data.message);
                showScreen('auth-screen');
            }
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 6. Logout action
    document.getElementById('btn-logout').addEventListener('click', logout);

    // 7. Modals close click actions
    document.querySelectorAll('.modal-close-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('active');
        });
    });

    // 8. Open quick create ticket form modal
    document.getElementById('btn-quick-create').addEventListener('click', () => {
        openTicketFormModal();
    });

    // 9. Submit ticket form (create or edit)
    document.getElementById('ticket-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('ticket-id-field').value;
        const title = document.getElementById('t-title').value.trim();
        const category = document.getElementById('t-category').value;
        const priority = document.getElementById('t-priority').value;
        const desc = document.getElementById('t-description').value.trim();

        // Parse dynamic form builder questionnaire field values
        const customFieldsAnswers = {};
        document.querySelectorAll('.dynamic-input-field').forEach(el => {
            const fId = el.getAttribute('data-id');
            customFieldsAnswers[fId] = el.value;
        });

        try {
            await apiRequest('ticket_save', {
                method: 'POST',
                body: JSON.stringify({
                    id: id || null,
                    title: title,
                    category: category,
                    priority: priority,
                    description: desc,
                    custom_fields: customFieldsAnswers
                })
            });

            playAlert('sound-success');
            document.getElementById('ticket-modal').classList.remove('active');

            // reload active views list
            loadViewData(state.currentView);
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 10. Save Ticket details controls status update
    document.getElementById('btn-save-details-controls').addEventListener('click', async () => {
        const modal = document.getElementById('ticket-details-modal');
        const id = modal.getAttribute('data-loaded-id');
        const assigneeId = document.getElementById('det-assignee-select').value;
        const status = document.getElementById('det-status-select').value;

        try {
            await apiRequest('ticket_save', {
                method: 'POST',
                body: JSON.stringify({
                    id: id,
                    assignee_id: assigneeId || null,
                    status: status
                })
            });
            playAlert('sound-success');
            modal.classList.remove('active');
            loadViewData(state.currentView);
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 11. Comment composición form attachments styling label updates
    document.getElementById('comment-file-input').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('comment-attached-file-name').innerText = file.name;
        } else {
            document.getElementById('comment-attached-file-name').innerText = '';
        }
    });

    // 12. Submit comment composición details
    document.getElementById('comment-composition-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const modal = document.getElementById('ticket-details-modal');
        const id = modal.getAttribute('data-loaded-id');
        const txt = document.getElementById('comment-text-field').value.trim();
        const fileInp = document.getElementById('comment-file-input');

        if (txt === '' && !fileInp.files[0]) return;

        const fd = new FormData();
        fd.append('ticket_id', id);
        fd.append('comment_text', txt);
        if (fileInp.files[0]) {
            fd.append('attachment', fileInp.files[0]);
        }

        try {
            // Note: FormData requires custom call because header content-type must not be forced to json
            const headers = { 'Authorization': `Bearer ${state.token}` };
            const response = await fetch('api.php?action=comment_save', {
                method: 'POST',
                headers: headers,
                body: fd
            });
            const resData = await response.json();
            if (resData && resData.error) {
                throw new Error(resData.error);
            }

            playAlert('sound-success');
            document.getElementById('comment-text-field').value = '';
            fileInp.value = '';
            document.getElementById('comment-attached-file-name').innerText = '';

            // Reload comments
            await loadTicketComments(id);
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 13. Search Tickets filtering text listeners
    document.getElementById('search-tickets-input').addEventListener('input', renderTicketsTable);
    document.getElementById('filter-category').addEventListener('change', renderTicketsTable);
    document.getElementById('filter-priority').addEventListener('change', renderTicketsTable);
    document.getElementById('filter-status').addEventListener('change', renderTicketsTable);

    // 14. Check all rows in list table
    const checkAll = document.getElementById('check-all-tickets');
    if (checkAll) {
        checkAll.addEventListener('change', () => {
            document.querySelectorAll('.ticket-row-check').forEach(chk => {
                chk.checked = checkAll.checked;
                const id = parseInt(chk.getAttribute('data-id'));
                if (checkAll.checked) {
                    state.selectedTicketIds.add(id);
                } else {
                    state.selectedTicketIds.delete(id);
                }
            });
            toggleBulkActionsPanel();
        });
    }

    // 15. Submit Bulk ticket assignment executor
    document.getElementById('btn-bulk-assign-submit').addEventListener('click', async () => {
        const executorId = document.getElementById('bulk-assignee-select').value;
        if (!executorId) {
            alert("Пожалуйста, выберите исполнителя.");
            return;
        }

        try {
            await apiRequest('ticket_bulk_assign', {
                method: 'POST',
                body: JSON.stringify({
                    ticket_ids: Array.from(state.selectedTicketIds),
                    assignee_id: executorId
                })
            });

            playAlert('sound-success');
            state.selectedTicketIds.clear();
            if (checkAll) checkAll.checked = false;
            toggleBulkActionsPanel();
            loadViewData('tickets');
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 16. Excel Report CSV Downloads trigger
    document.getElementById('btn-export-csv').addEventListener('click', () => {
        window.open(`api.php?action=export_csv&token=${state.token}`, '_blank');
    });

    // 17. Audit Logs Refresh Click trigger
    document.getElementById('btn-refresh-logs').addEventListener('click', () => {
        loadViewData('logs');
    });

    // 18. Admin user management addition popup clicks
    document.getElementById('btn-add-user').addEventListener('click', () => {
        openUserFormModal();
    });

    // 19. User edit/add form submit trigger handler
    document.getElementById('user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('user-id-field').value;
        const u = document.getElementById('u-username').value.trim();
        const p = document.getElementById('u-password').value.trim();
        const r = document.getElementById('u-role').value;
        const fn = document.getElementById('u-fullname').value.trim();
        const em = document.getElementById('u-email').value.trim();
        const ph = document.getElementById('u-phone').value.trim();

        try {
            await apiRequest('user_save', {
                method: 'POST',
                body: JSON.stringify({
                    id: id || null,
                    username: u,
                    password: p || null,
                    role: r,
                    full_name: fn,
                    email: em,
                    phone: ph
                })
            });
            playAlert('sound-success');
            document.getElementById('user-modal').classList.remove('active');
            loadViewData('users');
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 20. Profile details update submissions
    document.getElementById('profile-details-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fn = document.getElementById('prof-fullname').value.trim();
        const em = document.getElementById('prof-email').value.trim();
        const ph = document.getElementById('prof-phone').value.trim();
        const pass = document.getElementById('prof-password').value.trim();

        try {
            await apiRequest('profile', {
                method: 'POST',
                body: JSON.stringify({
                    full_name: fn,
                    email: em,
                    phone: ph,
                    password: pass || null
                })
            });

            // update local representation values too
            state.user.full_name = fn;
            state.user.email = em;
            state.user.phone = ph;
            localStorage.setItem('crm_user_info', JSON.stringify(state.user));

            playAlert('sound-success');
            alert("Личные данные сохранены.");
            checkAuth();
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 21. Profile notification settings updates
    document.getElementById('profile-notifications-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const freq = document.getElementById('prof-notif-freq').value;
        const emailCheck = document.getElementById('prof-notify-email').checked ? 1 : 0;
        const pushCheck = document.getElementById('prof-notify-push').checked ? 1 : 0;
        const tgCheck = document.getElementById('prof-notify-tg').checked ? 1 : 0;

        try {
            await apiRequest('profile', {
                method: 'POST',
                body: JSON.stringify({
                    notifications_freq: freq,
                    notify_email: emailCheck,
                    notify_push: pushCheck,
                    notify_tg: tgCheck
                })
            });
            playAlert('sound-success');
            alert("Настройки уведомлений сохранены.");
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 22. Toggle MySQL Creds block visual in settings dropdown change
    document.getElementById('settings-storage-select').addEventListener('change', (e) => {
        const val = e.target.value;
        const box = document.getElementById('mysql-credentials-box');
        if (val === 'mysql') {
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    });

    // 23. DB switch storage mode settings application sync
    document.getElementById('btn-save-storage-mode').addEventListener('click', async () => {
        const mode = document.getElementById('settings-storage-select').value;

        let mysqlConf = null;
        if (mode === 'mysql') {
            mysqlConf = {
                host: document.getElementById('db-host').value.trim(),
                port: document.getElementById('db-port').value.trim(),
                database: document.getElementById('db-name').value.trim(),
                username: document.getElementById('db-user').value.trim(),
                password: document.getElementById('db-pass').value.trim()
            };
        }

        try {
            const data = await apiRequest('switch_storage', {
                method: 'POST',
                body: JSON.stringify({
                    storage_mode: mode,
                    mysql: mysqlConf
                })
            });
            if (data && data.success) {
                playAlert('sound-success');
                alert(data.message);
                state.storageMode = mode;
                loadViewData('settings');
            }
        } catch(err) {
            playAlert('sound-error');
            alert(err.message);
        }
    });

    // 24. SLA config limits thresholds submit
    document.getElementById('sla-settings-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        state.slaSettings.low = parseInt(document.getElementById('sla-low').value);
        state.slaSettings.medium = parseInt(document.getElementById('sla-medium').value);
        state.slaSettings.high = parseInt(document.getElementById('sla-high').value);

        await saveSettings();
        alert("Лимиты SLA обновлены.");
    });

    // 25. Add Custom Category element
    document.getElementById('btn-add-category-item').addEventListener('click', async () => {
        const catVal = document.getElementById('new-category-input').value.trim();
        if (catVal === '') return;

        state.categories.push(catVal);
        document.getElementById('new-category-input').value = '';
        await saveSettings();
        renderSettingsCategories();
    });

    // 26. Custom Fields builder save config layout
    document.getElementById('btn-save-custom-form').addEventListener('click', async () => {
        await saveSettings();
        alert("Конструктор полей сохранен.");
    });

    // 27. Dynamic interactive sorting of list data table columns
    document.querySelectorAll('.data-table th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.getAttribute('data-sort');
            if (currentSortColumn === col) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = col;
                currentSortDirection = 'asc';
            }

            // update sort icon visuals on headers
            document.querySelectorAll('.data-table th i').forEach(ico => {
                ico.className = 'fa-solid fa-sort';
            });
            const currentIcon = th.querySelector('i');
            if (currentIcon) {
                currentIcon.className = currentSortDirection === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';
            }

            renderTicketsTable();
        });
    });
}

// --- App Bootstrap / Initialization ---
document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    updateUILanguage();
    checkAuth();
    setupRouting();
    setupEvents();
    setupFormBuilderDragEvents();
});
