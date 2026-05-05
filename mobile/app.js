const API_BASE = '../api';
let token = localStorage.getItem('token');
let currentUser = null;
let workTypes = [];
let users = [];
let systemSettings = {};

const screens = {
    auth: document.getElementById('auth-screen'),
    app: document.getElementById('app-screen'),
    main: document.getElementById('main-content'),
    modal: document.getElementById('modal-overlay'),
    modalBody: document.getElementById('modal-body')
};

async function initApp() {
    applyTheme(localStorage.getItem('mobile-theme') || 'default');

    // Safety timeout: hide splash anyway after 5 seconds
    const safetyTimeout = setTimeout(hideSplashScreen, 5000);

    try {
        try {
            const cfgRes = await fetch(`${API_BASE}/auth.php?action=config`);
            if (cfgRes.ok) systemSettings = await cfgRes.json();
        } catch (e) { console.error('Config fetch failed', e); }

        if (systemSettings && !systemSettings.auth_required && !token) {
            try {
                const res = await fetch(`${API_BASE}/auth.php?action=login`, { method: 'POST' });
                if (res.ok) {
                    const data = await res.json();
                    token = data.token;
                    currentUser = data.user;
                    localStorage.setItem('token', token);
                }
            } catch (e) { console.error('Auto-login failed', e); }
        }

        if (token) {
            const success = await fetchUser();
            if (success) {
                // Decision made: we show the app. Hide splash as soon as we start loading app data
                hideSplashScreen();
                clearTimeout(safetyTimeout);

                await loadLookups();
                await showApp();
            } else {
                showAuth();
                hideSplashScreen();
                clearTimeout(safetyTimeout);
            }
        } else {
            showAuth();
            hideSplashScreen();
            clearTimeout(safetyTimeout);
        }
    } catch (err) {
        console.error('Initialization error:', err);
        showAuth();
        hideSplashScreen();
        clearTimeout(safetyTimeout);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

function applyTheme(theme) {
    document.body.className = theme === 'default' ? '' : `theme-${theme}`;
    localStorage.setItem('mobile-theme', theme);
    document.querySelectorAll('.theme-opt').forEach(opt => {
        opt.classList.toggle('active', opt.dataset.theme === theme);
    });
}

function createAbortSignal(ms) {
    if (typeof AbortSignal !== 'undefined' && typeof AbortSignal.timeout === 'function') {
        return AbortSignal.timeout(ms);
    }
    if (typeof AbortController !== 'undefined') {
        const controller = new AbortController();
        setTimeout(() => controller.abort(), ms);
        return controller.signal;
    }
    return null;
}

async function fetchUser() {
    if (!token) return false;
    try {
        const res = await fetch(`${API_BASE}/auth.php?action=me`, {
            headers: { 'Authorization': `Bearer ${token}` },
            signal: createAbortSignal(5000) // Don't hang forever
        });
        if (res.ok) {
            currentUser = await res.json();
            return true;
        }
        return false;
    } catch (e) {
        console.error('Fetch user failed', e);
        return false;
    }
}

async function loadLookups() {
    try {
        const [wtRes, uRes] = await Promise.all([
            apiFetch('/admin.php?action=worktypes'),
            apiFetch('/admin.php?action=users')
        ]);
        if (wtRes.ok) workTypes = await wtRes.json();
        if (uRes.ok) users = await uRes.json();
    } catch (e) {}
}

function hideSplashScreen() {
    const splash = document.getElementById('splash-screen');
    if (splash) {
        splash.classList.add('hidden');
        setTimeout(() => splash.remove(), 500);
    }
}

async function apiFetch(url, options = {}) {
    options.headers = { ...options.headers, 'Authorization': `Bearer ${token}` };
    if (!options.signal) options.signal = createAbortSignal(10000); // Default timeout
    const res = await fetch(`${API_BASE}${url}`, options);
    if (res.status === 401) {
        localStorage.removeItem('token');
        location.reload();
    }
    return res;
}

function showAuth() {
    screens.auth.classList.remove('hidden');
    screens.app.classList.add('hidden');
}

async function showApp() {
    screens.auth.classList.add('hidden');
    screens.app.classList.remove('hidden');

    // Safety check if splash is still there
    hideSplashScreen();

    const perms = currentUser.permissions || {};

    const navTasks = document.getElementById('nav-tasks-btn');
    const isWorker = ['admin', 'executor', 'manager'].includes(currentUser.role);
    if (navTasks && !isWorker) {
        navTasks.classList.add('hidden');
    }

    const navRep = document.getElementById('nav-rep-btn');
    const canRep = currentUser.role === 'admin' || currentUser.role === 'manager' || (perms.can_view_reports);
    if (navRep && !canRep) {
        navRep.classList.add('hidden');
    }

    const navChat = document.getElementById('nav-chat-btn');
    const canChat = currentUser.role === 'admin' || (perms.can_view_chat ?? true);
    if (navChat && !canChat) {
        navChat.classList.add('hidden');
    }

    const navDept = document.querySelector('.nav-btn[data-view="department"]');
    const canDept = currentUser.role === 'admin' || (perms.can_view_department ?? (currentUser.role !== 'user')) || (perms.can_view_all_tasks);
    if (navDept && !canDept) {
        navDept.classList.add('hidden');
    }

    if (systemSettings.org_name) {
        document.querySelectorAll('.org-name-header').forEach(h => h.innerText = systemSettings.org_name);
    }

    await renderDashboard();
}

// LOGIN
document.getElementById('login-form').onsubmit = async (e) => {
    e.preventDefault();
    const login = document.getElementById('login').value;
    const password = document.getElementById('password').value;
    const errorEl = document.getElementById('login-error');

    try {
        const res = await fetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login, password })
        });
        if (res.ok) {
            const data = await res.json();
            token = data.token;
            currentUser = data.user;
            localStorage.setItem('token', token);
            await loadLookups();
            showApp();
        } else {
            errorEl.classList.remove('hidden');
        }
    } catch (e) { errorEl.classList.remove('hidden'); }
};

document.getElementById('logout-btn').onclick = () => {
    localStorage.removeItem('token');
    location.reload();
};

// NAV
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.onclick = async () => {
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const view = btn.dataset.view;
        switch(view) {
            case 'dashboard': await renderDashboard(); break;
            case 'tasks': await renderTasks(); break;
            case 'create': renderCreate(); break;
            case 'department': await renderDepartment(); break;
            case 'reports': await renderReports(); break;
            case 'profile': renderProfile(); break;
            case 'chat': await renderGlobalChat(); break;
        }
    };
});

function getStatusLabel(status) {
    const labels = {
        'new': 'НОВАЯ',
        'assigned': 'НАЗНАЧЕНА',
        'in_progress': 'В РАБОТЕ',
        'completed': 'ВЫПОЛНЕНА',
        'closed': 'ЗАКРЫТА',
        'rejected': 'ОТКЛОНЕНА'
    };
    return labels[status] || status;
}

function getWorkTypeName(id) {
    const wt = workTypes.find(w => w.id == id);
    return wt ? wt.name : id;
}

function getUserName(id) {
    if (!id) return '---';
    const u = users.find(user => user.id == id);
    return u ? u.full_name : id;
}

async function renderTasks() {
    document.getElementById('view-title').innerText = 'ЗАДАЧИ';
    screens.main.innerHTML = '<div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch('/requests.php?action=my_tasks');
        const requests = await res.json();
        screens.main.innerHTML = '';
        requests.forEach(r => {
            const card = document.createElement('div');
            card.className = 'req-card';
            card.innerHTML = `
                <div class="req-header">
                    <span class="req-number">${r.number}</span>
                    <span class="status-badge status-${r.status}">${getStatusLabel(r.status)}</span>
                </div>
                <div class="req-desc">${r.description}</div>
                <div class="req-meta">
                    <span><i class="bi bi-calendar3"></i> ${new Date(r.created_at).toLocaleDateString()}</span>
                    <span><i class="bi bi-tag"></i> ${getWorkTypeName(r.work_type_id)}</span>
                </div>
            `;
            card.onclick = () => showDetails(r.id);
            screens.main.appendChild(card);
        });
        if (requests.length === 0) screens.main.innerHTML = '<div style="text-align:center; padding:20px;">НЕТ ЗАДАЧ</div>';
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

async function renderDashboard() {
    document.getElementById('view-title').innerText = 'ЗАЯВКИ';
    screens.main.innerHTML = '<div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch('/requests.php?action=my');
        const requests = await res.json();
        screens.main.innerHTML = '';
        requests.forEach(r => {
            const card = document.createElement('div');
            card.className = 'req-card';
            card.innerHTML = `
                <div class="req-header">
                    <span class="req-number">${r.number}</span>
                    <span class="status-badge status-${r.status}">${getStatusLabel(r.status)}</span>
                </div>
                <div class="req-desc">${r.description}</div>
                <div class="req-meta">
                    <span><i class="bi bi-calendar3"></i> ${new Date(r.created_at).toLocaleDateString()}</span>
                    <span><i class="bi bi-tag"></i> ${getWorkTypeName(r.work_type_id)}</span>
                </div>
            `;
            card.onclick = () => showDetails(r.id);
            screens.main.appendChild(card);
        });
        if (requests.length === 0) screens.main.innerHTML = '<div style="text-align:center; padding:20px;">НЕТ ЗАЯВОК</div>';
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

async function renderDepartment(viewMode = 'table') {
    document.getElementById('view-title').innerText = 'ОТДЕЛ';
    screens.main.innerHTML = `
        <div class="view-mode-toggle">
            <button class="view-mode-btn ${viewMode === 'table' ? 'active' : ''}" onclick="renderDepartment('table')">СПИСОК</button>
            <button class="view-mode-btn ${viewMode === 'kanban' ? 'active' : ''}" onclick="renderDepartment('kanban')">ДОСКА</button>
        </div>
        <div id="dept-container"><div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div></div>
    `;
    try {
        const res = await apiFetch('/requests.php?action=department');
        const requests = await res.json();
        const container = document.getElementById('dept-container');
        container.innerHTML = '';

        if (viewMode === 'kanban') {
            renderKanban(requests, 'dept-container');
        } else {
            requests.forEach(r => {
                const card = document.createElement('div');
                card.className = 'req-card';
                card.innerHTML = `
                    <div class="req-header">
                        <span class="req-number">${r.number}</span>
                        <span class="status-badge status-${r.status}">${getStatusLabel(r.status)}</span>
                    </div>
                    <div class="req-desc">${r.description}</div>
                    <div class="req-meta">
                        <span><i class="bi bi-person"></i> ${getUserName(r.requester_id)}</span>
                        <span>→ ${getUserName(r.assigned_to)}</span>
                    </div>
                `;
                card.onclick = () => showDetails(r.id);
                container.appendChild(card);
            });
            if (requests.length === 0) container.innerHTML = '<div style="text-align:center; padding:20px;">НЕТ ЗАЯВОК</div>';
        }
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

function renderCreate() {
    document.getElementById('view-title').innerText = 'НОВАЯ ЗАЯВКА';
    screens.main.innerHTML = `
        <form id="create-form">
            <span class="label">ТИП РАБОТ</span>
            <select name="work_type_id" required>
                ${workTypes.map(wt => `<option value="${wt.id}">${wt.name}</option>`).join('')}
            </select>
            <span class="label">ПРИОРИТЕТ</span>
            <select name="priority">
                <option value="normal">ОБЫЧНЫЙ</option>
                <option value="high">ВЫСОКИЙ</option>
                <option value="low">НИЗКИЙ</option>
            </select>
            <span class="label">МЕСТО</span>
            <input type="text" name="location" placeholder="Корпус, этаж, кабинет" required>
            <span class="label">ОПИСАНИЕ</span>
            <textarea name="description" rows="5" placeholder="Опишите проблему..." required></textarea>
            <span class="label">ФОТО / ФАЙЛ</span>
            <input type="file" name="file">
            <button type="submit"><i class="bi bi-send-fill"></i> ОТПРАВИТЬ</button>
        </form>
    `;

    document.getElementById('create-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await apiFetch('/requests.php?action=create', { method: 'POST', body: formData });
        if (res.ok) {
            document.querySelector('[data-view="dashboard"]').click();
        } else {
            alert('ОШИБКА ПРИ СОЗДАНИИ');
        }
    };
}

async function renderReports(viewMode = 'stats') {
    document.getElementById('view-title').innerText = 'АНАЛИТИКА';
    screens.main.innerHTML = `
        <div class="view-mode-toggle">
            <button class="view-mode-btn ${viewMode === 'stats' ? 'active' : ''}" onclick="renderReports('stats')">ОТЧЕТЫ</button>
            <button class="view-mode-btn ${viewMode === 'kanban' ? 'active' : ''}" onclick="renderReports('kanban')">ДОСКА</button>
        </div>
        <div id="reports-container"><div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div></div>
    `;
    try {
        const container = document.getElementById('reports-container');
        if (viewMode === 'kanban') {
            const res = await apiFetch('/requests.php?action=all');
            const requests = await res.json();
            renderKanban(requests, 'reports-container');
            return;
        }

        const res = await apiFetch('/reports.php?action=summary');
        const s = await res.json();
        container.innerHTML = `
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-lbl">ЗАЯВОК</div>
                    <div class="stat-val">${s.total}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-lbl">РЕЙТИНГ</div>
                    <div class="stat-val">${s.avg_rating}</div>
                </div>
                <div class="stat-box" style="border-color: var(--error);">
                    <div class="stat-lbl" style="color:var(--error);">ПРОСРОЧЕНО</div>
                    <div class="stat-val" style="color:var(--error);">${s.overdue}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-lbl">В РАБОТЕ</div>
                    <div class="stat-val">${s.status_dist.in_progress}</div>
                </div>
            </div>
            <div class="req-card">
                <span class="label">СТАТИСТИКА:</span>
                <div style="font-size: 0.85rem; line-height: 2;">
                    <div>• НОВЫЕ: <b>${s.status_dist.new}</b></div>
                    <div>• В РАБОТЕ: <b>${s.status_dist.in_progress}</b></div>
                    <div>• ВЫПОЛНЕНЫ: <b>${s.status_dist.completed + s.status_dist.closed}</b></div>
                </div>
            </div>

            <div class="req-card">
                <span class="label">ЗАГРУЗКА ИСПОЛНИТЕЛЕЙ:</span>
                <div id="exec-load-list"></div>
            </div>
        `;

        const execRes = await apiFetch('/reports.php?action=executors');
        const executors = await execRes.json();
        const list = document.getElementById('exec-load-list');
        executors.forEach(ex => {
            const item = document.createElement('div');
            item.style = 'padding: 10px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; font-size: 0.85rem;';
            item.innerHTML = `<span style="font-weight: 600;">${ex.full_name}</span> <span style="color: var(--primary);">${ex.active_requests} акт.</span>`;
            item.onclick = () => showExecutorActivity(ex.id, ex.full_name);
            list.appendChild(item);
        });

    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

async function showExecutorActivity(id, name) {
    screens.modal.classList.remove('hidden');
    screens.modalBody.innerHTML = '<div style="text-align:center; padding:40px;"><i class="bi bi-arrow-repeat spin"></i> ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch(`/reports.php?action=executor_activity&id=${id}`);
        const activity = await res.json();
        screens.modalBody.innerHTML = `
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0; font-weight: 800;">${name}</h4>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin: 5px 0 0 0;">ИСТОРИЯ АКТИВНОСТИ</p>
            </div>
            <div class="divider"></div>
            <div style="margin-bottom: 20px;">
                ${activity.map(a => `
                    <div class="history-item">
                        <div class="history-meta">${new Date(a.date).toLocaleString()}</div>
                        <div style="font-weight: 700; font-size: 0.9rem;">${a.text}</div>
                        ${a.comment ? `<div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px; background: rgba(0,0,0,0.03); padding: 5px; border-radius: 4px;">${a.comment}</div>` : ''}
                    </div>
                `).join('')}
                ${activity.length === 0 ? '<div style="text-align:center; padding:20px; color:var(--text-muted);">АКТИВНОСТЬ НЕ НАЙДЕНА</div>' : ''}
            </div>
        `;
    } catch (e) { screens.modalBody.innerHTML = 'ОШИБКА ЗАГРУЗКИ'; }
}

function renderProfile() {
    document.getElementById('view-title').innerText = 'ПРОФИЛЬ';
    const currentTheme = localStorage.getItem('mobile-theme') || 'default';
    screens.main.innerHTML = `
        <div class="req-card" style="margin-top: 10px;">
            <span class="label">ПОЛЬЗОВАТЕЛЬ</span>
            <div class="value" style="margin-bottom: 5px;">${currentUser.full_name}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">${currentUser.role.toUpperCase()}</div>
        </div>

        <div class="req-card">
            <span class="label">ВЫБОР ИНТЕРФЕЙСА (ПРЕМИУМ)</span>
            <div class="theme-grid">
                <div class="theme-opt ${currentTheme === 'default' ? 'active' : ''}" data-theme="default" style="background:#6366f1; color:white;">MODERN</div>
                <div class="theme-opt ${currentTheme === 'midnight' ? 'active' : ''}" data-theme="midnight" style="background:#0f172a; color:#fbbf24;">MIDNIGHT</div>
                <div class="theme-opt ${currentTheme === 'emerald' ? 'active' : ''}" data-theme="emerald" style="background:#059669; color:white;">EMERALD</div>
            </div>
        </div>

        <button onclick="location.reload()" style="background:transparent; border: 1px solid var(--border); color:var(--text-main); box-shadow:none;">ОБНОВИТЬ ДАННЫЕ</button>
    `;

    document.querySelectorAll('.theme-opt').forEach(opt => {
        opt.onclick = () => applyTheme(opt.dataset.theme);
    });
}

async function showDetails(id) {
    screens.modal.classList.remove('hidden');
    screens.modalBody.innerHTML = '<div style="text-align:center; padding:40px;"><i class="bi bi-arrow-repeat spin"></i> ЗАГРУЗКА...</div>';
    try {
        const [reqRes, histRes, chatRes] = await Promise.all([
            apiFetch(`/requests.php?action=details&id=${id}`),
            apiFetch(`/requests.php?action=history&id=${id}`),
            apiFetch(`/requests.php?action=get_comments&id=${id}`)
        ]);
        const r = await reqRes.json();
        const history = await histRes.json();
        const chat = await chatRes.json();

        screens.modalBody.innerHTML = `
            <div class="req-header">
                <span class="req-number">${r.number}</span>
                <span class="status-badge">${getStatusLabel(r.status)}</span>
            </div>

            <span class="label">ОПИСАНИЕ</span>
            <div class="value">${r.description}</div>

            ${r.file_path ? `
                <span class="label">ВЛОЖЕНИЕ</span>
                <div class="value"><a href="../${r.file_path}" target="_blank" style="color:var(--primary); text-decoration:none;"><i class="bi bi-paperclip"></i> ПРОСМОТР ФАЙЛА</a></div>
            ` : ''}

            <div class="stat-grid" style="margin-top: 10px;">
                <div class="stat-box" style="padding: 10px;">
                    <div class="stat-lbl">МЕСТО</div>
                    <div class="stat-val" style="font-size: 0.9rem; margin-top: 5px;">${r.location}</div>
                </div>
                <div class="stat-box" style="padding: 10px;">
                    <div class="stat-lbl">ЗАЯВИТЕЛЬ</div>
                    <div class="stat-val" style="font-size: 0.9rem; margin-top: 5px;">${getUserName(r.requester_id)}</div>
                </div>
            </div>

            <div id="actions" style="margin: 20px 0;"></div>

            <div class="divider"></div>
            <span class="label">ЧАТ С ПОДДЕРЖКОЙ</span>
            <div class="chat-container" id="mobile-chat">
                ${chat.map(c => `
                    <div class="msg">
                        <div class="msg-meta"><span>${getUserName(c.user_id)}</span><span>${new Date(c.created_at).toLocaleTimeString()}</span></div>
                        <div class="msg-text">${c.message}</div>
                    </div>
                `).join('')}
                ${chat.length === 0 ? '<div style="text-align:center; color:var(--text-muted); font-size: 0.8rem; padding: 20px;">СООБЩЕНИЙ НЕТ</div>' : ''}
            </div>
            <div style="display:flex; gap:8px;">
                <input type="text" id="chat-msg" placeholder="Напишите сообщение..." style="margin-bottom:0; flex:1; height: 50px;">
                <button id="chat-send" style="width: 60px; height: 50px; padding: 0;"><i class="bi bi-send-fill"></i></button>
            </div>

            <div class="divider"></div>
            <span class="label">ИСТОРИЯ ИЗМЕНЕНИЙ</span>
            <div style="margin-bottom: 20px;">
                ${history.map(h => `
                    <div class="history-item">
                        <div class="history-meta">${new Date(h.changed_at).toLocaleString()}</div>
                        <div style="font-weight: 600;">${getStatusLabel(h.status)}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${h.comment}</div>
                    </div>
                `).join('')}
            </div>
        `;

        document.getElementById('chat-send').onclick = async () => {
            const msg = document.getElementById('chat-msg').value;
            if (!msg) return;
            const res = await apiFetch('/requests.php?action=add_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: id, message: msg })
            });
            if (res.ok) showDetails(id);
        };

        const actions = document.getElementById('actions');
        if (['executor', 'admin', 'manager'].includes(currentUser.role)) {
            if (['new', 'assigned'].includes(r.status)) {
                const btn = document.createElement('button');
                btn.innerHTML = '<i class="bi bi-play-fill"></i> В РАБОТУ';
                btn.onclick = () => {
                    const comment = prompt('Комментарий к статусу:', 'Взято в работу');
                    if (comment !== null) updateStatus(id, 'in_progress', comment);
                };
                actions.appendChild(btn);
            } else if (r.status === 'in_progress') {
                const btn = document.createElement('button');
                btn.innerHTML = '<i class="bi bi-check-all"></i> ВЫПОЛНЕНО';
                btn.style.background = 'var(--success)';
                btn.onclick = () => {
                    const comment = prompt('Комментарий к результату:', 'Работы завершены');
                    if (comment !== null) updateStatus(id, 'completed', comment);
                };
                actions.appendChild(btn);

                const failBtn = document.createElement('button');
                failBtn.innerHTML = '<i class="bi bi-x-circle"></i> НЕ ВЫПОЛНЕНО';
                failBtn.style = 'background:transparent; border: 1px solid var(--error); color:var(--error); margin-top:12px; box-shadow:none;';
                failBtn.onclick = () => {
                    const comment = prompt('Причина невыполнения:');
                    if (comment) updateStatus(id, 'rejected', comment);
                };
                actions.appendChild(failBtn);
            }
        }

        if (r.requester_id == currentUser.id && r.status === 'completed') {
            const btn = document.createElement('button');
            btn.innerHTML = '<i class="bi bi-lock-fill"></i> ЗАКРЫТЬ ЗАЯВКУ';
            btn.style.background = 'var(--success)';
            btn.onclick = () => updateStatus(id, 'closed', 'ПОДТВЕРЖДЕНО ПОЛЬЗОВАТЕЛЕМ');
            actions.appendChild(btn);

            const rejBtn = document.createElement('button');
            rejBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> НА ДОРАБОТКУ';
            rejBtn.style = 'background:transparent; border: 1px solid var(--error); color:var(--error); margin-top:12px; box-shadow:none;';
            rejBtn.onclick = () => {
                const comment = prompt('Укажите причину возврата:');
                if (comment) updateStatus(id, 'rejected', comment);
            };
            actions.appendChild(rejBtn);
        }
    } catch (e) { screens.modalBody.innerHTML = 'ОШИБКА ПРИ ЗАГРУЗКЕ'; }
}

async function renderGlobalChat() {
    document.getElementById('view-title').innerText = 'ОБЩИЙ ЧАТ';
    screens.main.innerHTML = `
        <div class="chat-container" id="global-chat-box" style="height: calc(100vh - 200px);">
            <div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>
        </div>
        <div style="display:flex; gap:8px;">
            <input type="text" id="global-chat-input" placeholder="Ваше сообщение..." style="margin-bottom:0; flex:1; height: 50px;">
            <button id="global-chat-send" style="width: 60px; height: 50px; padding: 0;"><i class="bi bi-send-fill"></i></button>
        </div>
    `;

    const box = document.getElementById('global-chat-box');
    const load = async () => {
        try {
            const res = await apiFetch('/chat.php?action=list');
            const msgs = await res.json();
            box.innerHTML = msgs.map(m => `
                <div class="msg" style="${m.user_id == currentUser.id ? 'margin-left: 20%; border-right: 4px solid var(--primary);' : 'margin-right: 20%; border-left: 4px solid var(--accent);'}">
                    <div class="msg-meta"><span>${m.user_name}</span><span>${new Date(m.created_at).toLocaleTimeString()}</span></div>
                    <div class="msg-text">${m.message}</div>
                </div>
            `).join('');
            box.scrollTop = box.scrollHeight;
        } catch (e) { box.innerHTML = 'ОШИБКА ЧАТА'; }
    };

    load();
    const interval = setInterval(() => { if (document.getElementById('global-chat-box')) load(); else clearInterval(interval); }, 5000);

    document.getElementById('global-chat-send').onclick = async () => {
        const input = document.getElementById('global-chat-input');
        const res = await apiFetch('/chat.php?action=send', {
            method: 'POST',
            body: JSON.stringify({ message: input.value })
        });
        if (res.ok) { input.value = ''; load(); }
    };
}

async function updateStatus(id, status, comment = 'ОБНОВЛЕНО ЧЕРЕЗ MOBILE') {
    const res = await apiFetch('/requests.php?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status, comment })
    });
    if (res.ok) {
        screens.modal.classList.add('hidden');
        renderDashboard();
    } else {
        alert('ОШИБКА ОБНОВЛЕНИЯ');
    }
}

document.getElementById('modal-close').onclick = () => {
    screens.modal.classList.add('hidden');
};

function renderKanban(requests, containerId) {
    const statuses = [
        { id: 'new', label: 'НОВЫЕ' },
        { id: 'assigned', label: 'НАЗНАЧЕНЫ' },
        { id: 'in_progress', label: 'В РАБОТЕ' },
        { id: 'completed', label: 'ВЫПОЛНЕНЫ' },
        { id: 'closed', label: 'ЗАКРЫТЫ' },
        { id: 'rejected', label: 'ОТКЛОНЕНЫ' }
    ];

    const container = document.getElementById(containerId);
    container.innerHTML = '<div class="kanban-board"></div>';
    const board = container.querySelector('.kanban-board');

    statuses.forEach(status => {
        const colCount = requests.filter(r => r.status === status.id).length;
        const col = document.createElement('div');
        col.className = 'kanban-col';
        col.innerHTML = `
            <div class="kanban-col-header">
                <span>${status.label}</span>
                <span style="opacity: 0.6;">${colCount}</span>
            </div>
            <div class="kanban-list"></div>
        `;
        board.appendChild(col);

        const list = col.querySelector('.kanban-list');
        requests.filter(r => r.status === status.id).forEach(r => {
            const card = document.createElement('div');
            card.className = 'kanban-card';
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; margin-bottom: 6px;">
                    <span style="font-weight:800; color:var(--primary); font-size:0.8rem;">${r.number}</span>
                    <span style="width:8px; height:8px; border-radius:50%; background:${r.priority === 'high' ? 'var(--error)' : 'var(--primary)'}"></span>
                </div>
                <div style="font-size:0.8rem; line-height:1.3; margin-bottom:8px;" class="req-desc">${r.description}</div>
                <div style="font-size:0.65rem; color:var(--text-muted); display:flex; justify-content:space-between;">
                    <span>${getUserName(r.assigned_to)}</span>
                    <span>${new Date(r.created_at).toLocaleDateString()}</span>
                </div>
            `;
            card.onclick = () => showDetails(r.id);
            list.appendChild(card);
        });
    });
}
