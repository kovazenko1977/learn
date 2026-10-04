<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Functions.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Modal.php';

// Initialize DB schema on page load
Storage::getPDO();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Память — Вечная Книга Соболезнований и Захоронений</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Ambient Animated Particles / Embers Container -->
<div id="particlesContainer" class="particles-bg"></div>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <span class="icon">🕯️</span>
        <span>Страницы Памяти</span>
    </a>
    <ul class="navbar-nav" id="navLinks">
        <li><a href="index.php" class="nav-link active">🔍 Поиск</a></li>
        <li><a href="user.php" class="nav-link">🔑 Вход / Регистрация</a></li>
    </ul>
</nav>

<div class="container">
    <!-- Hero Banner with Tragic Atmosphere & Animated Candles -->
    <div class="hero">
        <div class="hero-candles-side left">🕯️</div>
        <div class="hero-candles-side right">🕯️</div>

        <div class="hero-subtitle">«Память сильнее времени. Пока мы помним — они живы»</div>
        <h1 class="shimmer-title">Вечная Книга Памяти и Места Захоронений</h1>
        <p class="hero-text">Сохраните светлую и нерушимую память о дорогих сердцу людях. История их жизни, памятные галереи и точно зафиксированное место захоронения с памятной QR-табличкой.</p>

        <div class="hero-actions">
            <button class="btn btn-accent btn-lg" onclick="App.openCreateModal()">➕ Создать Мемориальную Страницу</button>
        </div>
    </div>

    <!-- Memorial Ribbon / Quote Ticker -->
    <div class="memorial-ribbon">
        <span>🖤 Любовь не умирает...</span>
        <span>🕯️ Светлая и вечная память...</span>
        <span>🕊️ Ты навсегда в наших сердцах...</span>
        <span>🕯️ Никто не забыт, ничто не забыто...</span>
    </div>

    <!-- Feature Cards Section (Вечная память в цифровом веке) -->
    <div class="feature-overview-section" style="margin: 2.5rem 0;">
        <h2 style="text-align: center; color: var(--gold-light); font-size: 1.6rem; margin-bottom: 0.5rem; font-family: 'Cinzel', 'Georgia', serif;">
            ✨ Вечная память в цифровом веке
        </h2>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
            Современные цифровые мемориалы для сохранения семейной истории на поколения
        </p>

        <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
            <div class="feature-card glass-card" style="background: rgba(12, 16, 24, 0.75); border: 1px solid var(--gold-border); border-radius: 12px; padding: 1.5rem; text-align: center; transition: all 0.3s ease;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📱</div>
                <h3 style="color: var(--gold-light); font-size: 1.1rem; margin-bottom: 0.5rem;">QR-код для мемориала</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                    Компактная металлизированная табличка с QR-кодом устанавливается на памятник или крест.
                </p>
            </div>

            <div class="feature-card glass-card" style="background: rgba(12, 16, 24, 0.75); border: 1px solid var(--gold-border); border-radius: 12px; padding: 1.5rem; text-align: center; transition: all 0.3s ease;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📖</div>
                <h3 style="color: var(--gold-light); font-size: 1.1rem; margin-bottom: 0.5rem;">История жизни и фотогалерея</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                    Подробная биография, яркие моменты, достижения и до 10 фотографий высокого разрешения.
                </p>
            </div>

            <div class="feature-card glass-card" style="background: rgba(12, 16, 24, 0.75); border: 1px solid var(--gold-border); border-radius: 12px; padding: 1.5rem; text-align: center; transition: all 0.3s ease;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📍</div>
                <h3 style="color: var(--gold-light); font-size: 1.1rem; margin-bottom: 0.5rem;">Контакты и геопозиция</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                    Точные GPS-координаты могилы с навигатором и контакты родственников для связи.
                </p>
            </div>

            <div class="feature-card glass-card" style="background: rgba(12, 16, 24, 0.75); border: 1px solid var(--gold-border); border-radius: 12px; padding: 1.5rem; text-align: center; transition: all 0.3s ease;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🕯️</div>
                <h3 style="color: var(--gold-light); font-size: 1.1rem; margin-bottom: 0.5rem;">Поддержка и свечи</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                    Возможность зажечь виртуальную свечу памяти и оставить тёплые слова соболезнования.
                </p>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-card">
        <div class="search-card-header">
            <h3>🔍 Поиск мемориала в книге памяти</h3>
            <p>Введите фамилию, имя или годы жизни для поиска захоронения</p>
        </div>
        <form id="searchForm" onsubmit="performSearch(event)">
            <div class="search-grid">
                <div class="form-group">
                    <label>ФИО усопшего</label>
                    <input type="text" id="searchQuery" class="form-control" placeholder="Например: Иванов Иван Иванович">
                </div>
                <div class="form-group">
                    <label>Год рождения</label>
                    <input type="number" id="searchBirthYear" class="form-control" placeholder="ГГГГ" min="1800" max="2030">
                </div>
                <div class="form-group">
                    <label>Год смерти</label>
                    <input type="number" id="searchDeathYear" class="form-control" placeholder="ГГГГ" min="1800" max="2030">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Найти</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Grid -->
    <div id="resultsGrid" class="cards-grid">
        <!-- Rendered dynamically -->
    </div>
</div>

<?php renderCreatePageModal(); ?>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Электронная Книга Памяти. Все права защищены. Постоянное хранение данных и мемориальные QR-коды.</p>
</footer>

<script src="assets/js/main.js"></script>
<script>
// Create floating ember particles in background
function initEmberParticles() {
    const container = document.getElementById('particlesContainer');
    if (!container) return;

    for (let i = 0; i < 25; i++) {
        const particle = document.createElement('div');
        particle.className = 'ember-particle';
        particle.style.left = Math.random() * 100 + 'vw';
        particle.style.animationDuration = (Math.random() * 8 + 6) + 's';
        particle.style.animationDelay = (Math.random() * 5) + 's';
        particle.style.width = (Math.random() * 3 + 2) + 'px';
        particle.style.height = particle.style.width;
        container.appendChild(particle);
    }
}

async function performSearch(e) {
    if (e) e.preventDefault();
    const query = document.getElementById('searchQuery').value;
    const birthYear = document.getElementById('searchBirthYear').value;
    const deathYear = document.getElementById('searchDeathYear').value;

    const res = await App.fetch('search_pages', { q: query, birth_year: birthYear, death_year: deathYear });
    renderCards(res.pages || []);
}

function renderCards(pages) {
    const grid = document.getElementById('resultsGrid');
    if (!pages || pages.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 4rem; color: #a3a3a3;">Захоронений по данному запросу не найдено. Вы можете создать новую страницу памяти.</div>';
        return;
    }

    grid.innerHTML = pages.map((p, idx) => {
        const photo = p.photo ? p.photo : 'https://images.unsplash.com/photo-1518241353330-0f7941c2d9b5?w=400&auto=format&fit=crop&q=80';
        return `
            <div class="memorial-card" style="animation-delay: ${idx * 0.1}s;">
                <img src="${photo}" alt="${p.full_name}" class="memorial-card-img">
                <div class="memorial-card-body">
                    <div class="memorial-card-title">${p.full_name}</div>
                    <div class="memorial-card-dates">🕯️ ${formatMemorialDates(p.birth_date, p.death_date)}</div>
                    ${p.epitaph ? `<div class="memorial-card-epitaph">"${p.epitaph}"</div>` : ''}
                    <div class="memorial-card-footer">
                        <span class="memorial-card-location">📍 ${p.cemetery || 'Место не указано'}</span>
                        <a href="page.php?code=${p.code}" class="btn btn-primary btn-sm">Перейти к мемориалу</a>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    initEmberParticles();
    performSearch();
});
</script>
</body>
</html>
