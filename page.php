<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Functions.php';
require_once __DIR__ . '/includes/Auth.php';

$code = $_GET['code'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница Памяти</title>
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
        <li><a href="user.php" class="nav-link">🔑 Вход / Регистрация</a></li>
    </ul>
</nav>

<div class="container" id="pageContainer" style="max-width: 900px;">
    <div style="text-align: center; padding: 4rem; color: var(--text-muted);">Загрузка данных страницы...</div>
</div>

<!-- Modal Print / QR Plaque -->
<div class="modal-backdrop" id="plaqueModal">
    <div class="modal-dialog" style="max-width: 550px;">
        <div class="modal-header">
            <h3 class="modal-title">Табличка с QR-кодом на могилу</h3>
            <button class="modal-close" onclick="App.closeModal('plaqueModal')">&times;</button>
        </div>
        <div id="printablePlaqueArea">
            <div class="plaque-box">
                <div style="font-size: 1.5rem; margin-bottom: 0.25rem;">🕯️</div>
                <h2 id="plaqueName">Имя Фамилия</h2>
                <div class="dates" id="plaqueDates">01.01.1950 — 01.01.2023</div>
                <div class="qr-container" id="plaqueQrCode"></div>
                <div class="notice">
                    Отсканируйте QR-код смартфоном,<br>чтобы открыть виртуальный мемориал и оставить воспоминание.
                </div>
                <div style="margin-top: 10px; font-size: 0.75rem; color: var(--gold-light);" id="plaqueUrl">https://memory-site.ru</div>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
            <button class="btn btn-outline" onclick="App.closeModal('plaqueModal')">Закрыть</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Распечатать / Сохранить в PDF</button>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Страницы Памяти. Все права защищены.</p>
</footer>

<script src="assets/js/main.js"></script>
<script>
const pageCode = "<?php echo htmlspecialchars($code); ?>";
let currentData = null;

async function loadPage() {
    if (!pageCode) {
        document.getElementById('pageContainer').innerHTML = '<div style="text-align:center; padding:3rem; color:#ef4444;">Ошибка: Не указан код страницы.</div>';
        return;
    }

    const res = await App.fetch('get_page', { code: pageCode });
    if (!res.success) {
        document.getElementById('pageContainer').innerHTML = `<div style="text-align:center; padding:3rem; color:#ef4444;">${res.error || 'Страница не найдена или еще находится на модерации.'}</div>`;
        return;
    }

    currentData = res;
    renderPageDetails(res);
}

function renderPageDetails(data) {
    const p = data.page;
    document.title = `${p.full_name} — Страница Памяти`;
    const photo = p.photo ? p.photo : 'https://images.unsplash.com/photo-1518241353330-0f7941c2d9b5?w=400&auto=format&fit=crop&q=80';

    const relativesHtml = (data.relatives && data.relatives.length > 0)
        ? data.relatives.map(r => `
            <div class="relative-card">
                <div class="relation">${r.relation_type || 'Родственник'}</div>
                <div class="name">${r.name}</div>
                ${r.phone ? `<div class="phone">📞 <a href="tel:${r.phone}">${r.phone}</a></div>` : ''}
                ${r.email ? `<div class="phone">✉️ <a href="mailto:${r.email}">${r.email}</a></div>` : ''}
            </div>
        `).join('')
        : '<p style="color: #94a3b8; font-size: 0.9rem;">Контакты родственников не указаны.</p>';

    const condolencesHtml = (data.condolences && data.condolences.length > 0)
        ? data.condolences.map(c => `
            <div style="background: rgba(6, 8, 13, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.85rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <strong style="color: var(--gold-light); font-size: 0.95rem;">${c.author_name}</strong>
                    <span style="font-size: 0.75rem; color: var(--text-dim);">${c.created_at}</span>
                </div>
                <p style="color: var(--text-primary); font-size: 0.95rem; white-space: pre-wrap;">${c.message}</p>
            </div>
        `).join('')
        : '<p style="color: var(--text-muted); font-size: 0.9rem;">Пока нет оставленных соболезнований. Будьте первыми.</p>';

    const mapLocationHtml = (p.latitude && p.longitude)
        ? `<div style="margin-top: 0.5rem;"><a href="https://maps.google.com/?q=${p.latitude},${p.longitude}" target="_blank" class="btn btn-outline btn-sm">🗺️ Открыть на карте Google (${p.latitude}, ${p.longitude})</a></div>`
        : '';

    const galleryHtml = (data.photos && data.photos.length > 1)
        ? `
            <div class="content-block">
                <h3>🖼️ Галерея памятных фотографий (${data.photos.length})</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-top: 1rem;">
                    ${data.photos.map(ph => `
                        <a href="${ph}" target="_blank">
                            <img src="${ph}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid var(--gold-border); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" alt="Фото усопшего">
                        </a>
                    `).join('')}
                </div>
            </div>
        `
        : '';

    document.getElementById('pageContainer').innerHTML = `
        <div class="single-page-header">
            <img src="${photo}" alt="${p.full_name}" class="single-page-photo">
            <h1 class="single-page-title">${p.full_name}</h1>
            <div class="single-page-dates">🕯️ ${formatMemorialDates(p.birth_date, p.death_date)}</div>
            ${p.epitaph ? `<div class="single-page-epitaph">"${p.epitaph}"</div>` : ''}

            <div style="display: flex; gap: 10px; margin-top: 1.5rem; flex-wrap: wrap; justify-content: center;">
                <button class="btn btn-accent" onclick="lightCandle(${p.id})">🕯️ Зажечь свечу памяти (<span id="candleCount">${data.candle_count}</span>)</button>
                <button class="btn btn-outline" style="color: #fff; border-color: rgba(255,255,255,0.3);" onclick="openPlaqueModal()">📱 Табличка с QR-кодом</button>
            </div>
        </div>

        ${galleryHtml}

        <div class="single-content-grid">
            <div>
                <!-- Biography -->
                <div class="content-block">
                    <h3>📖 Биография и память</h3>
                    <div style="white-space: pre-wrap; color: var(--text-primary); font-size: 1.05rem;">${p.biography ? p.biography : 'Информация о биографии пока не добавлена.'}</div>
                </div>

                <!-- Condolences Wall -->
                <div class="content-block">
                    <h3>💬 Слова соболезнования и воспоминания</h3>
                    <form onsubmit="submitCondolence(event, ${p.id})" style="margin-bottom: 1.5rem; background: rgba(6, 8, 13, 0.7); border: 1px solid var(--gold-border); padding: 1rem; border-radius: 8px;">
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <input type="text" id="condAuthor" class="form-control" placeholder="Ваше имя" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <textarea id="condMessage" class="form-control" rows="2" placeholder="Напишите слова соболезнования или воспоминание..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-accent btn-sm">Оставить запись</button>
                    </form>
                    <div id="condolencesList">${condolencesHtml}</div>
                </div>
            </div>

            <div>
                <!-- Burial Information -->
                <div class="content-block">
                    <h3>📍 Место захоронения</h3>
                    <p style="margin-bottom: 0.5rem;"><strong>Кладбище:</strong> ${p.cemetery || 'Не указано'}</p>
                    <p style="margin-bottom: 0.5rem;"><strong>Участок:</strong> ${p.section || 'Не указан'}</p>
                    <p style="margin-bottom: 0.5rem;"><strong>Могила №:</strong> ${p.grave_num || 'Не указан'}</p>
                    ${mapLocationHtml}
                </div>

                <!-- Relatives Contacts -->
                <div class="content-block">
                    <h3>👨‍👩‍👧 Контакты родственников</h3>
                    ${relativesHtml}
                </div>

                <!-- Page QR Code Box -->
                <div class="content-block" style="text-align: center;">
                    <h3>📱 Прямая ссылка</h3>
                    <div id="inlineQrCode" style="display: flex; justify-content: center; margin: 1rem 0;"></div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">Постоянная ссылка для сканирования смартфоном</p>
                    <button class="btn btn-outline btn-sm" onclick="copyPermalink('${data.permalink}')">📋 Скопировать ссылку</button>
                </div>
            </div>
        </div>
    `;

    generateQRCode('inlineQrCode', data.permalink, 140);
}

async function lightCandle(pageId) {
    const name = prompt('Представьтесь, пожалуйста (необязательно):', 'Гость');
    const res = await App.fetch('add_candle', { page_id: pageId, author_name: name || 'Гость' }, { method: 'POST' });
    if (res.success) {
        document.getElementById('candleCount').innerText = res.candle_count;
        alert('Вы зажгли свечу памяти. Светлая память!');
    } else {
        alert(res.error || 'Ошибка при попытке зажечь свечу');
    }
}

async function submitCondolence(e, pageId) {
    e.preventDefault();
    const author = document.getElementById('condAuthor').value.trim();
    const msg = document.getElementById('condMessage').value.trim();

    const res = await App.fetch('add_condolence', { page_id: pageId, author_name: author, message: msg }, { method: 'POST' });
    if (res.success) {
        alert(res.message);
        loadPage();
    } else {
        alert(res.error || 'Ошибка сохранения');
    }
}

function openPlaqueModal() {
    if (!currentData) return;
    const p = currentData.page;
    document.getElementById('plaqueName').innerText = p.full_name;
    document.getElementById('plaqueDates').innerText = `🕯️ ${formatMemorialDates(p.birth_date, p.death_date)}`;
    document.getElementById('plaqueUrl').innerText = currentData.permalink;

    generateQRCode('plaqueQrCode', currentData.permalink, 180);

    document.getElementById('plaqueModal').classList.add('active');
}

function copyPermalink(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('Постоянная ссылка скопирована в буфер обмена!');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadPage();
});
</script>
</body>
</html>
