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
        <span>Страницы Памяти — Панель Управления</span>
    </a>
    <ul class="navbar-nav" id="navLinks">
        <li><a href="index.php" class="nav-link">🔍 На сайт</a></li>
        <li><a href="admin.php" class="nav-link active">⚙️ Панель Управления</a></li>
    </ul>
</nav>

<div class="container">

    <!-- Admin Panel Content (shown if LOGGED IN) -->
    <div id="adminDashboardContainer" style="display: block;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="font-size: 2rem; color: var(--gold-light);">Панель Управления Администратора</h1>
                <p style="color: var(--text-muted);">Полный контроль над мемориальными страницами, пользователями и целостностью данных</p>
            </div>
            <button class="btn btn-outline btn-sm" onclick="submitAdminLogout()">Выйти из системы</button>
        </div>

        <!-- Navigation Subtabs -->
        <div style="display: flex; gap: 12px; margin-bottom: 2rem; border-bottom: 2px solid var(--gold-border); padding-bottom: 0.75rem; flex-wrap: wrap;">
            <button class="btn btn-primary btn-sm admin-tab-btn" onclick="switchAdminTab('moderation')">🔔 Модерация (<span id="pendingNotifBadge">0</span>)</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('pages')">📚 Все Страницы Памяти</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('users')">👥 Пользователи</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('backup')">💾 Резервное Копирование</button>
            <button class="btn btn-outline btn-sm admin-tab-btn" onclick="switchAdminTab('settings')">⚙️ Настройки и Безопасность</button>
        </div>

        <!-- TAB 1: Moderation -->
        <div id="tabModeration" class="admin-tab-content">
            <div class="panel-card" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.25rem; color: var(--gold-light);">Заявки на модерацию</h3>
                <div id="pendingPagesList">Загрузка данных...</div>
            </div>

            <div class="panel-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 10px;">
                    <h3 style="color: var(--gold-light);">Уведомления системы</h3>
                    <button class="btn btn-outline btn-sm" onclick="markNotifsRead()">Отметить все прочитанными</button>
                </div>
                <div id="adminNotificationsList">Загрузка уведомлений...</div>
            </div>
        </div>

        <!-- TAB 2: Pages Management -->
        <div id="tabPages" class="admin-tab-content" style="display: none;">
            <div class="panel-card">
                <h3 style="margin-bottom: 1.25rem; color: var(--gold-light);">Управление всем каталогом мемориалов</h3>
                <div id="allPagesList">Загрузка данных...</div>
            </div>
        </div>

        <!-- TAB 3: Users Management -->
        <div id="tabUsers" class="admin-tab-content" style="display: none;">
            <div class="panel-card">
                <h3 style="margin-bottom: 1.25rem; color: var(--gold-light);">Зарегистрированные пользователи (по номерам телефонов)</h3>
                <div id="allUsersList">Загрузка данных...</div>
            </div>
        </div>

        <!-- TAB 4: Backup & Restore -->
        <div id="tabBackup" class="admin-tab-content" style="display: none;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Export Backup -->
                <div class="panel-card">
                    <h3 style="margin-bottom: 1rem; color: var(--gold-light);">💾 Создать и скачать Резервную Копию</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">
                        Скачайте полный архив базы данных в формате JSON, включая все мемориальные страницы, списки родственников, свечи, соболезнования и настройки.
                    </p>
                    <a href="api/index.php?action=admin_export_backup" target="_blank" class="btn btn-accent" style="width: 100%;">📥 Скачать полную резервную копию (JSON)</a>
                </div>

                <!-- Import Backup -->
                <div class="panel-card">
                    <h3 style="margin-bottom: 1rem; color: var(--gold-light);">📤 Восстановить данные из файла</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem;">
                        Загрузите ранее сохраненный JSON-файл резервной копии для восстановления структуры и данных всех таблиц.
                    </p>
                    <form onsubmit="submitImportBackup(event)">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label>Выберите файл резервной копии (.json)</label>
                            <input type="file" id="backupFile" accept=".json" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">♻️ Восстановить данные</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB 5: Settings & Security -->
        <div id="tabSettings" class="admin-tab-content" style="display: none;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Security settings -->
                <div class="panel-card">
                    <h3 style="margin-bottom: 1rem; color: var(--gold-light);">Изменение логина и пароля Администратора</h3>
                    <form onsubmit="submitUpdateAdminProfile(event)">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Новый логин администратора</label>
                            <input type="text" id="newAdminLogin" class="form-control" placeholder="12345" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Новый пароль (оставьте пустым, если не меняете)</label>
                            <input type="password" id="newAdminPass" class="form-control" placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-accent" style="width: 100%;">Сохранить учетные данные</button>
                    </form>
                </div>

                <!-- Site settings -->
                <div class="panel-card">
                    <h3 style="margin-bottom: 1rem; color: var(--gold-light);">Параметры работы системы</h3>
                    <form onsubmit="submitSaveSiteSettings(event)">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Название сайта</label>
                            <input type="text" id="settingSiteTitle" class="form-control" placeholder="Страницы Памяти">
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Автоматическая публикация без предмодерации</label>
                            <select id="settingAutoApprove" class="form-control">
                                <option value="0">Отключена (Модерация администратором)</option>
                                <option value="1">Включена (Авто-публикация сразу)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Сохранить настройки</button>
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
        loadAdminDashboard();
    } else {
        alert('Доступ в панель управления разрешен только администраторам. Войдите с логином и паролем администратора.');
        window.location.href = 'user.php';
    }
}

async function submitAdminLogout() {
    await App.fetch('admin_logout');
    window.location.href = 'index.php';
}

function switchAdminTab(tabName) {
    document.querySelectorAll('.admin-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.admin-tab-btn').forEach(el => {
        el.classList.remove('btn-primary');
        el.classList.add('btn-outline');
    });

    const activeBtn = event ? event.target : null;

    if (tabName === 'moderation') {
        document.getElementById('tabModeration').style.display = 'block';
        if (activeBtn) { activeBtn.classList.add('btn-primary'); activeBtn.classList.remove('btn-outline'); }
        loadModerationData();
    } else if (tabName === 'pages') {
        document.getElementById('tabPages').style.display = 'block';
        if (activeBtn) { activeBtn.classList.add('btn-primary'); activeBtn.classList.remove('btn-outline'); }
        loadAllPagesData();
    } else if (tabName === 'users') {
        document.getElementById('tabUsers').style.display = 'block';
        if (activeBtn) { activeBtn.classList.add('btn-primary'); activeBtn.classList.remove('btn-outline'); }
        loadUsersData();
    } else if (tabName === 'backup') {
        document.getElementById('tabBackup').style.display = 'block';
        if (activeBtn) { activeBtn.classList.add('btn-primary'); activeBtn.classList.remove('btn-outline'); }
    } else if (tabName === 'settings') {
        document.getElementById('tabSettings').style.display = 'block';
        if (activeBtn) { activeBtn.classList.add('btn-primary'); activeBtn.classList.remove('btn-outline'); }
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
        container.innerHTML = '<div style="color: #34d399; font-weight: 600; padding: 1rem 0;">✅ Нет новых страниц, ожидающих проверки. Все заявки обработаны!</div>';
        return;
    }

    container.innerHTML = res.pages.map(p => `
        <div style="background: rgba(6, 8, 13, 0.7); border: 1px solid var(--gold-border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 10px;">
                <strong style="font-size: 1.15rem; color: var(--gold-light);">${p.full_name} (${p.birth_date || '???'} — ${p.death_date || '???'})</strong>
                <span class="badge badge-pending">На проверке</span>
            </div>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem;"><strong>Телефон заявителя:</strong> ${p.owner_phone || 'Неизвестно'} | <strong>Кладбище:</strong> ${p.cemetery || 'Не указано'}</p>
            ${p.epitaph ? `<p style="font-style: italic; font-size: 0.9rem; color: var(--text-dim); margin-bottom: 1rem;">"${p.epitaph}"</p>` : ''}
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
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
        container.innerHTML = '<div style="color: var(--text-dim);">Уведомлений нет.</div>';
        return;
    }

    container.innerHTML = res.notifications.map(n => `
        <div style="padding: 0.85rem 0; border-bottom: 1px solid var(--border-color); ${n.is_read == 0 ? 'background: rgba(212, 175, 55, 0.08); padding-left: 10px; border-left: 3px solid var(--gold-primary);' : ''}">
            <div style="font-size: 0.95rem; color: var(--text-primary);">${n.message}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim);">${n.created_at}</div>
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
        container.innerHTML = '<div style="color: var(--text-dim); padding: 1rem 0;">Страниц памяти в базе не найдено.</div>';
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон Владельца</th>
                    <th>Статус</th>
                    <th>Просмотры</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                ${res.pages.map(p => `
                    <tr>
                        <td>${p.id}</td>
                        <td style="font-weight: 700; color: var(--gold-light);">${p.full_name}</td>
                        <td>${p.owner_phone || '—'}</td>
                        <td><span class="badge badge-${p.status}">${p.status}</span></td>
                        <td>${p.views}</td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="page.php?code=${p.code}" target="_blank" class="btn btn-outline btn-sm">👁️ Просмотр</a>
                                <button class="btn btn-danger btn-sm" onclick="deletePage(${p.id})">🗑️ Удалить</button>
                            </div>
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
        container.innerHTML = '<div style="color: var(--text-dim); padding: 1rem 0;">Пользователи не найдены.</div>';
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Телефон (Логин)</th>
                    <th>ФИО / Имя</th>
                    <th>Создано страниц</th>
                    <th>Дата регистрации</th>
                </tr>
            </thead>
            <tbody>
                ${res.users.map(u => `
                    <tr>
                        <td>${u.id}</td>
                        <td style="font-weight: 700; color: var(--gold-light);">${u.phone}</td>
                        <td>${u.full_name || 'Не указано'}</td>
                        <td>${u.pages_count}</td>
                        <td>${u.created_at}</td>
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

async function submitImportBackup(e) {
    e.preventDefault();
    const fileInput = document.getElementById('backupFile');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Выберите файл резервной копии');
        return;
    }

    if (!confirm('Внимание! Восстановление из резервной копии перезапишет текущую базу данных. Продолжить?')) return;

    const formData = new FormData();
    formData.append('backup_file', fileInput.files[0]);

    const res = await App.fetch('admin_import_backup', {}, {
        method: 'POST',
        body: formData
    });

    if (res.success) {
        alert(res.message);
        loadAdminDashboard();
    } else {
        alert(res.error || 'Ошибка при восстановлении резервной копии');
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
