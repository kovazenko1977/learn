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
    <title>Память - Книга Памяти и Захоронений</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

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
    <div class="hero">
        <h1>Электронная книга памяти и место захоронения</h1>
        <p>Сохраните светлую память о близких, историю их жизни, фотографии и геопозицию захоронения с QR-кодом на мемориальную табличку.</p>
        <button class="btn btn-accent" onclick="App.openCreateModal()">➕ Добавить страницу памяти</button>
    </div>

    <!-- Search Section -->
    <div class="search-card">
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
    <p>&copy; <?php echo date('Y'); ?> Страницы Памяти. Все права защищены. Система электронных мемориалов с QR-кодами.</p>
</footer>

<script src="assets/js/main.js"></script>
<script>
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
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 3rem; color: #64748b;">По вашему запросу ничего не найдено. Попробуйте изменить критерии поиска.</div>';
        return;
    }

    grid.innerHTML = pages.map(p => {
        const photo = p.photo ? p.photo : 'https://images.unsplash.com/photo-1518241353330-0f7941c2d9b5?w=400&auto=format&fit=crop&q=80';
        return `
            <div class="memorial-card">
                <img src="${photo}" alt="${p.full_name}" class="memorial-card-img">
                <div class="memorial-card-body">
                    <div class="memorial-card-title">${p.full_name}</div>
                    <div class="memorial-card-dates">🕯️ ${p.birth_date || '???'} — ${p.death_date || '???'}</div>
                    ${p.epitaph ? `<div class="memorial-card-epitaph">"${p.epitaph}"</div>` : ''}
                    <div class="memorial-card-footer">
                        <span class="memorial-card-location">📍 ${p.cemetery || 'Место не указано'}</span>
                        <a href="page.php?code=${p.code}" class="btn btn-primary btn-sm">Перейти к странице</a>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function addRelativeRow() {
    const container = document.getElementById('relativesContainer');
    const div = document.createElement('div');
    div.className = 'relative-input-row';
    div.style = 'display: grid; grid-template-columns: 1fr 1.5fr 1.5fr; gap: 8px; margin-bottom: 8px;';
    div.innerHTML = `
        <input type="text" class="form-control rel-type" placeholder="Степень родства">
        <input type="text" class="form-control rel-name" placeholder="ФИО родственника">
        <input type="text" class="form-control rel-phone" placeholder="Телефон родственника">
    `;
    container.appendChild(div);
}

async function submitCreatePage(e) {
    e.preventDefault();
    const form = document.getElementById('createPageForm');
    const formData = new FormData(form);

    // Collect relatives array
    const relatives = [];
    document.querySelectorAll('#relativesContainer .relative-input-row').forEach(row => {
        const type = row.querySelector('.rel-type').value.trim();
        const name = row.querySelector('.rel-name').value.trim();
        const phone = row.querySelector('.rel-phone').value.trim();
        if (name) {
            relatives.push({ relation_type: type, name: name, phone: phone, is_public: 1 });
        }
    });

    formData.append('relatives', JSON.stringify(relatives));

    const res = await App.fetch('create_page', {}, {
        method: 'POST',
        body: formData
    });

    if (res.success) {
        alert(res.message);
        App.closeModal('createPageModal');
        form.reset();
        if (res.status === 'approved') {
            window.location.href = `page.php?code=${res.code}`;
        } else {
            window.location.href = 'user.php';
        }
    } else {
        alert('Ошибка: ' + res.error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    performSearch();
});
</script>
</body>
</html>
