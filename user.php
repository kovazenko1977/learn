<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Functions.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Modal.php';

Storage::getPDO();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный Кабинет — Память</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <span class="icon">🕯️</span>
        <span>Страницы Памяти</span>
    </a>
    <ul class="navbar-nav" id="navLinks">
        <li><a href="index.php" class="nav-link">🔍 Поиск</a></li>
        <li><a href="user.php" class="nav-link active">🔑 Вход / Регистрация</a></li>
    </ul>
</nav>

<div class="container" style="max-width: 900px;">

    <!-- Auth forms container (shown when NOT logged in) -->
    <div id="authContainer" style="display: none; max-width: 420px; margin: 3rem auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
            <button id="tabBtnLogin" onclick="switchTab('login')" style="background:none; border:none; font-size:1.1rem; font-weight:bold; cursor:pointer; color:var(--accent);">Вход</button>
            <button id="tabBtnRegister" onclick="switchTab('register')" style="background:none; border:none; font-size:1.1rem; font-weight:bold; cursor:pointer; color:#94a3b8;">Регистрация</button>
        </div>

        <!-- Login Form -->
        <form id="loginForm" onsubmit="submitLogin(event)">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Номер телефона (Логин)</label>
                <input type="tel" id="loginPhone" class="form-control" placeholder="+79001234567" required>
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Пароль</label>
                <input type="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Войти в личный кабинет</button>
        </form>

        <!-- Register Form -->
        <form id="registerForm" onsubmit="submitRegister(event)" style="display: none;">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Номер телефона (Логин) *</label>
                <input type="tel" id="regPhone" class="form-control" placeholder="+79001234567" required>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Ваше ФИО / Имя</label>
                <input type="text" id="regFullName" class="form-control" placeholder="Иванов Иван">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Установите пароль *</label>
                <input type="password" id="regPassword" class="form-control" placeholder="Минимум 4 символа" required minlength="4">
            </div>
            <button type="submit" class="btn btn-accent" style="width: 100%;">Зарегистрироваться</button>
        </form>
    </div>

    <!-- Cabinet Dashboard (shown when LOGGED IN) -->
    <div id="cabinetContainer" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;">
            <div>
                <h1 style="font-size: 1.8rem; color: #0f172a;">Личный кабинет пользователя</h1>
                <p style="color: #64748b;" id="userInfoText">Загрузка данных...</p>
            </div>
            <button onclick="App.openCreateModal()" class="btn btn-accent">➕ Создать страницу памяти</button>
        </div>

        <div style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 1rem; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem;">Мои страницы памяти</h3>
            <div id="userPagesList">
                <div style="text-align: center; color: #94a3b8; padding: 2rem;">Загрузка списка ваших страниц...</div>
            </div>
        </div>
    </div>

</div>

<?php renderCreatePageModal(); ?>

<!-- Modal Edit Page -->
<div class="modal-backdrop" id="editPageModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Редактирование страницы памяти</h3>
            <button class="modal-close" onclick="App.closeModal('editPageModal')">&times;</button>
        </div>
        <form id="editPageForm" onsubmit="submitEditPage(event)">
            <input type="hidden" name="id" id="editPageId">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>ФИО усопшего *</label>
                <input type="text" name="full_name" id="editFullName" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Дата / Год рождения</label>
                    <input type="text" name="birth_date" id="editBirthDate" class="form-control">
                </div>
                <div class="form-group">
                    <label>Дата / Год смерти</label>
                    <input type="text" name="death_date" id="editDeathDate" class="form-control">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Обновить фотографию (оставьте пустым, если не нужно менять)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Эпитафия</label>
                <input type="text" name="epitaph" id="editEpitaph" class="form-control">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Биография / Памятная история</label>
                <textarea name="biography" id="editBiography" class="form-control" rows="4"></textarea>
            </div>

            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem; font-size: 1rem; color: #1e293b;">📍 Место захоронения</h4>
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Кладбище</label>
                    <input type="text" name="cemetery" id="editCemetery" class="form-control">
                </div>
                <div class="form-group">
                    <label>Участок</label>
                    <input type="text" name="section" id="editSection" class="form-control">
                </div>
                <div class="form-group">
                    <label>№ Могилы</label>
                    <input type="text" name="grave_num" id="editGraveNum" class="form-control">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Широта (Latitude)</label>
                    <input type="number" step="any" name="latitude" id="editLatitude" class="form-control">
                </div>
                <div class="form-group">
                    <label>Долгота (Longitude)</label>
                    <input type="number" step="any" name="longitude" id="editLongitude" class="form-control">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="App.closeModal('editPageModal')">Отмена</button>
                <button type="submit" class="btn btn-accent">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Страницы Памяти. Все права защищены.</p>
</footer>

<script src="assets/js/main.js"></script>
<script>
function switchTab(tab) {
    if (tab === 'login') {
        document.getElementById('loginForm').style.display = 'block';
        document.getElementById('registerForm').style.display = 'none';
        document.getElementById('tabBtnLogin').style.color = 'var(--accent)';
        document.getElementById('tabBtnRegister').style.color = '#94a3b8';
    } else {
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
        document.getElementById('tabBtnLogin').style.color = '#94a3b8';
        document.getElementById('tabBtnRegister').style.color = 'var(--accent)';
    }
}

async function checkUserSession() {
    const res = await App.fetch('auth_current');
    if (res.success && res.user) {
        App.currentUser = res.user;
        document.getElementById('authContainer').style.display = 'none';
        document.getElementById('cabinetContainer').style.display = 'block';
        document.getElementById('userInfoText').innerText = `Вы вошли под номером ${res.user.phone} (${res.user.full_name || 'Имя не указано'})`;
        loadUserPages();
    } else {
        document.getElementById('authContainer').style.display = 'block';
        document.getElementById('cabinetContainer').style.display = 'none';
    }
}

async function submitLogin(e) {
    e.preventDefault();
    const phone = document.getElementById('loginPhone').value;
    const pass = document.getElementById('loginPassword').value;

    const res = await App.fetch('auth_login', { phone: phone, password: pass }, { method: 'POST' });
    if (res.success) {
        checkUserSession();
    } else {
        alert(res.error || 'Ошибка входа');
    }
}

async function submitRegister(e) {
    e.preventDefault();
    const phone = document.getElementById('regPhone').value;
    const name = document.getElementById('regFullName').value;
    const pass = document.getElementById('regPassword').value;

    const res = await App.fetch('auth_register', { phone: phone, full_name: name, password: pass }, { method: 'POST' });
    if (res.success) {
        checkUserSession();
    } else {
        alert(res.error || 'Ошибка регистрации');
    }
}

async function loadUserPages() {
    const res = await App.fetch('user_get_pages');
    const container = document.getElementById('userPagesList');

    if (!res.success || !res.pages || res.pages.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 2rem;">У вас пока нет созданных страниц памяти.</div>';
        return;
    }

    container.innerHTML = res.pages.map(p => {
        let badgeClass = 'badge-pending';
        let statusText = 'На модерации';
        if (p.status === 'approved') {
            badgeClass = 'badge-approved';
            statusText = 'Опубликовано';
        } else if (p.status === 'rejected') {
            badgeClass = 'badge-rejected';
            statusText = 'Отклонено';
        }

        return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-weight: bold; font-size: 1.05rem; color: #0f172a;">${p.full_name}</div>
                    <div style="font-size: 0.85rem; color: #64748b;">Дата создания: ${p.created_at} | Просмотров: ${p.views}</div>
                    ${p.rejection_reason ? `<div style="color: #ef4444; font-size: 0.85rem; margin-top: 4px;">Причина отклонения: ${p.rejection_reason}</div>` : ''}
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="badge ${badgeClass}">${statusText}</span>
                    ${p.status === 'approved' ? `<a href="page.php?code=${p.code}" target="_blank" class="btn btn-outline btn-sm">👁️ Просмотр</a>` : ''}
                    <button class="btn btn-primary btn-sm" onclick="openEditPageModal(${p.id})">✏️ Редактировать</button>
                </div>
            </div>
        `;
    }).join('');
}

async function openEditPageModal(pageId) {
    const res = await App.fetch('get_page', { id: pageId });
    if (!res.success || !res.page) {
        alert('Не удалось загрузить данные страницы');
        return;
    }

    const p = res.page;
    document.getElementById('editPageId').value = p.id;
    document.getElementById('editFullName').value = p.full_name;
    document.getElementById('editBirthDate').value = p.birth_date || '';
    document.getElementById('editDeathDate').value = p.death_date || '';
    document.getElementById('editEpitaph').value = p.epitaph || '';
    document.getElementById('editBiography').value = p.biography || '';
    document.getElementById('editCemetery').value = p.cemetery || '';
    document.getElementById('editSection').value = p.section || '';
    document.getElementById('editGraveNum').value = p.grave_num || '';
    document.getElementById('editLatitude').value = p.latitude || 0;
    document.getElementById('editLongitude').value = p.longitude || 0;

    document.getElementById('editPageModal').classList.add('active');
}

async function submitEditPage(e) {
    e.preventDefault();
    const form = document.getElementById('editPageForm');
    const formData = new FormData(form);

    const res = await App.fetch('update_page', {}, {
        method: 'POST',
        body: formData
    });

    if (res.success) {
        alert(res.message);
        App.closeModal('editPageModal');
        loadUserPages();
    } else {
        alert(res.error || 'Ошибка при сохранении');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    checkUserSession();
});
</script>
</body>
</html>
