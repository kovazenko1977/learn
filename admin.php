<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Functions.php';
require_once __DIR__ . '/includes/Auth.php';

Storage::getPDO();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель Администратора — Память</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <span class="icon">🕯️</span>
        <span>Страницы Памяти — Администрирование</span>
    </a>
    <ul class="navbar-nav" id="navLinks">
        <li><a href="index.php" class="nav-link">🔍 На сайт</a></li>
        <li><a href="admin.php" class="nav-link active">⚙️ Панель управления</a></li>
    </ul>
</nav>

<div class="container">

    <!-- Admin Login Form (shown if NOT logged in as admin) -->
    <div id="adminLoginFormContainer" style="display: none; max-width: 400px; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
        <h2 style="margin-bottom: 1rem; font-size: 1.4rem; color: #0f172a; text-align: center;">Вход для Администратора</h2>
        <form onsubmit="submitAdminLogin(event)">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Логин администратора</label>
                <input type="text" id="adminLogin" class="form-control" value="12345" required>
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Пароль</label>
                <input type="password" id="adminPass" class="form-control" value="12345" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Войти в панель</button>
        </form>
    </div>

    <!-- Admin Panel Content (shown if LOGGED IN) -->
    <div id="adminDashboardContainer" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;">
            <h1 style="font-size: 1.8rem; color: #0f172a;">Панель управления администратора</h1>
            <button class="btn btn-outline btn-sm" onclick="submitAdminLogout()">Выйти из панели</button>
        </div>

        <!-- Navigation Subtabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; flex-wrap: wrap;">
            <button class="btn btn-primary btn-sm admin-tab-btn" onclick="switchAdminTab('moderation')">🔔 Модерация и уведомления (<span id="pendingNotifBadge">0</span>)</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('pages')">📚 Все страницы памяти</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('users')">👥 Пользователи</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('settings')">⚙️ Настройки и Безопасность</button>
        </div>

        <!-- TAB 1: Moderation -->
        <div id="tabModeration" class="admin-tab-content">
            <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: #0f172a;">Страницы на модерации</h3>
                <div id="pendingPagesList">Загрузка данных...</div>
            </div>

            <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Уведомления системы</h3>
                    <button class="btn btn-outline btn-sm" onclick="markNotifsRead()">Отметить все прочитанными</button>
                </div>
                <div id="adminNotificationsList">Загрузка уведомлений...</div>
            </div>
        </div>

        <!-- TAB 2: Pages Management -->
        <div id="tabPages" class="admin-tab-content" style="display: none;">
            <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
                <h3 style="margin-bottom: 1rem; color: #0f172a;">Управление всеми страницами</h3>
                <div id="allPagesList">Загрузка данных...</div>
            </div>
        </div>

        <!-- TAB 3: Users Management -->
        <div id="tabUsers" class="admin-tab-content" style="display: none;">
            <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
                <h3 style="margin-bottom: 1rem; color: #0f172a;">Зарегистрированные пользователи</h3>
                <div id="allUsersList">Загрузка данных...</div>
            </div>
        </div>

        <!-- TAB 4: Settings & Security -->
        <div id="tabSettings" class="admin-tab-content" style="display: none;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Security settings -->
                <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 1rem; color: #0f172a;">Изменение логина и пароля Администратора</h3>
                    <form onsubmit="submitUpdateAdminProfile(event)">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Новый логин администратора</label>
                            <input type="text" id="newAdminLogin" class="form-control" placeholder="12345" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Новый пароль (оставьте пустым, если не меняете)</label>
                            <input type="password" id="newAdminPass" class="form-control" placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-accent">Сохранить учетные данные</button>
                    </form>
                </div>

                <!-- Site settings -->
                <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 1rem; color: #0f172a;">Параметры работы системы</h3>
                    <form onsubmit="submitSaveSiteSettings(event)">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Название сайта</label>
                            <input type="text" id="settingSiteTitle" class="form-control" placeholder="Страницы Памяти">
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Автоматическая публикация без предмодерации</label>
                            <select id="settingAutoApprove" class="form-control">
                                <option value="0">Отключена (Модерация администратором)</option>
                                <option value="1">Включена (Авто-публикация сразу)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить настройки</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Страницы Памяти. Панель Администратора.</p>
</footer>

<script src="assets/js/main.js"></script>
<script>
async function checkAdminSession() {
    const res = await App.fetch('auth_current');
    if (res.success && res.is_admin) {
        document.getElementById('adminLoginFormContainer').style.display = 'none';
        document.getElementById('adminDashboardContainer').style.display = 'block';
        loadAdminDashboard();
    } else {
        document.getElementById('adminLoginFormContainer').style.display = 'block';
        document.getElementById('adminDashboardContainer').style.display = 'none';
    }
}

async function submitAdminLogin(e) {
    e.preventDefault();
    const login = document.getElementById('adminLogin').value.trim();
    const pass = document.getElementById('adminPass').value.trim();

    const res = await App.fetch('admin_login', { login: login, password: pass }, { method: 'POST' });
    if (res.success) {
        checkAdminSession();
    } else {
        alert(res.error || 'Ошибка входа администратора');
    }
}

async function submitAdminLogout() {
    await App.fetch('admin_logout');
    checkAdminSession();
}

function switchAdminTab(tabName) {
    document.querySelectorAll('.admin-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.admin-tab-btn').forEach(el => {
        el.classList.remove('btn-primary');
        el.classList.add('btn-outline');
    });

    if (tabName === 'moderation') {
        document.getElementById('tabModeration').style.display = 'block';
        event.target.classList.add('btn-primary');
        loadModerationData();
    } else if (tabName === 'pages') {
        document.getElementById('tabPages').style.display = 'block';
        event.target.classList.add('btn-primary');
        loadAllPagesData();
    } else if (tabName === 'users') {
        document.getElementById('tabUsers').style.display = 'block';
        event.target.classList.add('btn-primary');
        loadUsersData();
    } else if (tabName === 'settings') {
        document.getElementById('tabSettings').style.display = 'block';
        event.target.classList.add('btn-primary');
        loadAdminSettingsData();
    }
}

async function loadAdminDashboard() {
    loadModerationData();
    loadNotificationsData();
}

async function loadModerationData() {
    const res = await App.fetch('admin_get_pages', { status: 'pending' });
    const container = document.getElementById('pendingPagesList');

    if (!res.success || !res.pages || res.pages.length === 0) {
        container.innerHTML = '<div style="color: #10b981; font-weight: 500;">Нет новых страниц, ожидающих проверки. Все заявки обработаны!</div>';
        return;
    }

    container.innerHTML = res.pages.map(p => `
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap;">
                <strong style="font-size: 1.1rem; color: #0f172a;">${p.full_name} (${p.birth_date || '???'} — ${p.death_date || '???'})</strong>
                <span class="badge badge-pending">На проверке</span>
            </div>
            <p style="font-size: 0.85rem; color: #475569; margin-bottom: 0.5rem;"><strong>Автор/Телефон:</strong> ${p.owner_phone || 'Неизвестно'} | <strong>Кладбище:</strong> ${p.cemetery || 'Не указано'}</p>
            ${p.epitaph ? `<p style="font-style: italic; font-size: 0.85rem; color: #64748b; margin-bottom: 0.75rem;">"${p.epitaph}"</p>` : ''}
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-success btn-sm" onclick="approvePage(${p.id})">✅ Опубликовать</button>
                <button class="btn btn-danger btn-sm" onclick="rejectPage(${p.id})">❌ Отклонить</button>
                <a href="page.php?code=${p.code}" target="_blank" class="btn btn-outline btn-sm">👁️ Предпросмотр</a>
            </div>
        </div>
    `).join('');
}

async function loadNotificationsData() {
    const res = await App.fetch('admin_get_notifications');
    if (!res.success) return;

    document.getElementById('pendingNotifBadge').innerText = res.unread_count || 0;

    const container = document.getElementById('adminNotificationsList');
    if (!res.notifications || res.notifications.length === 0) {
        container.innerHTML = '<div style="color: #94a3b8;">Уведомлений нет.</div>';
        return;
    }

    container.innerHTML = res.notifications.map(n => `
        <div style="padding: 0.75rem; border-bottom: 1px solid #f1f5f9; ${n.is_read == 0 ? 'background: #fffbe3;' : ''}">
            <div style="font-size: 0.9rem; color: #1e293b;">${n.message}</div>
            <div style="font-size: 0.75rem; color: #94a3b8;">${n.created_at}</div>
        </div>
    `).join('');
}

async function markNotifsRead() {
    await App.fetch('admin_mark_notifications_read');
    loadNotificationsData();
}

async function approvePage(id) {
    const res = await App.fetch('admin_approve_page', { id: id }, { method: 'POST' });
    if (res.success) {
        alert(res.message);
        loadModerationData();
    } else {
        alert(res.error || 'Ошибка при одобрении');
    }
}

async function rejectPage(id) {
    const reason = prompt('Укажите причину отклонения публикации:', 'Недостаточно информации или некорректные данные');
    if (reason === null) return;

    const res = await App.fetch('admin_reject_page', { id: id, reason: reason }, { method: 'POST' });
    if (res.success) {
        alert(res.message);
        loadModerationData();
    } else {
        alert(res.error || 'Ошибка при отклонении');
    }
}

async function loadAllPagesData() {
    const res = await App.fetch('admin_get_pages');
    const container = document.getElementById('allPagesList');

    if (!res.success || !res.pages || res.pages.length === 0) {
        container.innerHTML = '<div style="color: #94a3b8;">Страниц памяти в базе не найдено.</div>';
        return;
    }

    container.innerHTML = `
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">ФИО</th>
                    <th style="padding: 10px;">Телефон Владельца</th>
                    <th style="padding: 10px;">Статус</th>
                    <th style="padding: 10px;">Просмотры</th>
                    <th style="padding: 10px;">Действия</th>
                </tr>
            </thead>
            <tbody>
                ${res.pages.map(p => `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px;">${p.id}</td>
                        <td style="padding: 10px; font-weight: 600;">${p.full_name}</td>
                        <td style="padding: 10px;">${p.owner_phone || '—'}</td>
                        <td style="padding: 10px;"><span class="badge badge-${p.status}">${p.status}</span></td>
                        <td style="padding: 10px;">${p.views}</td>
                        <td style="padding: 10px;">
                            <a href="page.php?code=${p.code}" target="_blank" class="btn btn-outline btn-sm">👁️ View</a>
                            <button class="btn btn-danger btn-sm" onclick="deletePage(${p.id})">🗑️ Удалить</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function deletePage(id) {
    if (!confirm('Вы действительно хотите полностью удалить эту страницу памяти?')) return;
    const res = await App.fetch('admin_delete_page', { id: id }, { method: 'POST' });
    if (res.success) {
        alert(res.message);
        loadAllPagesData();
    } else {
        alert(res.error || 'Ошибка удаления');
    }
}

async function loadUsersData() {
    const res = await App.fetch('admin_get_users');
    const container = document.getElementById('allUsersList');

    if (!res.success || !res.users || res.users.length === 0) {
        container.innerHTML = '<div style="color: #94a3b8;">Пользователи не найдены.</div>';
        return;
    }

    container.innerHTML = `
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Телефон (Логин)</th>
                    <th style="padding: 10px;">ФИО / Имя</th>
                    <th style="padding: 10px;">Создано страниц</th>
                    <th style="padding: 10px;">Дата регистрации</th>
                </tr>
            </thead>
            <tbody>
                ${res.users.map(u => `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px;">${u.id}</td>
                        <td style="padding: 10px; font-weight: 600;">${u.phone}</td>
                        <td style="padding: 10px;">${u.full_name || 'Не указано'}</td>
                        <td style="padding: 10px;">${u.pages_count}</td>
                        <td style="padding: 10px;">${u.created_at}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function loadAdminSettingsData() {
    const res = await App.fetch('admin_get_settings');
    if (res.success && res.settings) {
        document.getElementById('settingSiteTitle').value = res.settings.site_title || '';
        document.getElementById('settingAutoApprove').value = res.settings.auto_approve || '0';
    }
}

async function submitUpdateAdminProfile(e) {
    e.preventDefault();
    const login = document.getElementById('newAdminLogin').value.trim();
    const pass = document.getElementById('newAdminPass').value.trim();

    const res = await App.fetch('admin_update_profile', { login: login, password: pass }, { method: 'POST' });
    if (res.success) {
        alert(res.message);
    } else {
        alert(res.error || 'Ошибка изменения учетных данных');
    }
}

async function submitSaveSiteSettings(e) {
    e.preventDefault();
    const title = document.getElementById('settingSiteTitle').value.trim();
    const autoApprove = document.getElementById('settingAutoApprove').value;

    const res = await App.fetch('admin_save_settings', { settings: { site_title: title, auto_approve: autoApprove } }, { method: 'POST' });
    if (res.success) {
        alert(res.message);
    } else {
        alert(res.error || 'Ошибка сохранения');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    checkAdminSession();
});
</script>
</body>
</html>
