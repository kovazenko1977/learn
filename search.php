<?php include 'includes/header.php'; ?>

<div class="window" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
    <div class="title-bar">
        <div class="title-bar-text">Результаты поиска - Поиск</div>
        <div class="title-bar-controls">
            <button aria-label="Minimize"></button>
            <button aria-label="Maximize"></button>
            <button aria-label="Close" onclick="window.location.href='index.php'"></button>
        </div>
    </div>

    <div class="window-body" style="display: flex; flex: 1; margin: 0; padding: 0; overflow: hidden;">
        <!-- Modern Search Sidebar -->
        <aside style="width: 280px; background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px); padding: 24px; display: flex; flex-direction: column; gap: 24px; border-right: 1px solid rgba(0, 0, 0, 0.05);">
            <div>
                <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600;">Поиск</h3>
                <div style="position: relative;">
                    <input type="text" id="searchInput" style="width: 100%; padding: 10px 12px; border: 1px solid rgba(0,0,0,0.2); border-radius: 6px; font-size: 14px; background: rgba(255,255,255,0.8);" placeholder="Услуга или номер..." oninput="performSearch()">
                </div>
            </div>

            <div>
                <div style="font-size: 13px; font-weight: 600; margin-bottom: 12px; opacity: 0.7;">ФИЛЬТРЫ</div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button onclick="filterSearch('all')" class="toolbar-btn" style="text-align: left; padding: 8px; border-radius: 4px; display: flex; align-items: center; gap: 10px;">
                        <img src="https://img.icons8.com/fluency/16/000000/layers.png"> Все результаты
                    </button>
                    <button onclick="filterSearch('procedure')" class="toolbar-btn" style="text-align: left; padding: 8px; border-radius: 4px; display: flex; align-items: center; gap: 10px;">
                        <img src="https://img.icons8.com/fluency/16/000000/syringe.png"> Процедуры
                    </button>
                    <button onclick="filterSearch('room')" class="toolbar-btn" style="text-align: left; padding: 8px; border-radius: 4px; display: flex; align-items: center; gap: 10px;">
                        <img src="https://img.icons8.com/fluency/16/000000/bedroom.png"> Номера
                    </button>
                </div>
            </div>
        </aside>

        <!-- Search Results Area -->
        <main style="flex: 1; background: white; padding: 20px; overflow-y: auto;">
            <div id="resultsInfo" style="border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; font-size: 12px; color: #666;">
                Введите запрос для начала поиска...
            </div>

            <div id="searchResults" class="procedure-grid">
                <!-- Results will appear here -->
            </div>
        </main>
    </div>
</div>

<script>
const data = [
    { name: "Лечебные ванны", type: "procedure", link: "procedures.php#cardio", img: "https://gu-berezina.by/wp-content/uploads/2026/01/Screenshot_4-1.png" },
    { name: "Циркулярный душ", type: "procedure", link: "procedures.php#cardio", img: "https://gu-berezina.by/wp-content/uploads/2026/01/Screenshot_3-1.png" },
    { name: "Лечебный массаж", type: "procedure", link: "procedures.php#nerve", img: "https://gu-berezina.by/wp-content/uploads/2026/01/Screenshot_2-2.png" },
    { name: "Галотерапия", type: "procedure", link: "procedures.php#respiratory", img: "https://gu-berezina.by/wp-content/uploads/2026/01/подари-родителям-300x247.png" },
    { name: "Грязелечение", type: "procedure", link: "procedures.php#muscle", img: "https://gu-berezina.by/wp-content/uploads/2025/12/e1zb4ekmx5rmw0cvc7wrec3d10-1024x578.png" },
    { name: "Vacumed", type: "procedure", link: "procedures.php#special", img: "https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_7-removebg-preview.png" },
    { name: "1-комнатный номер", type: "room", link: "rooms.php", img: "https://gu-berezina.by/wp-content/uploads/2025/12/IMG_1975-1024x683.jpg" },
    { name: "2-комнатный Люкс", type: "room", link: "rooms.php", img: "https://gu-berezina.by/wp-content/uploads/2025/12/IMG_2028-1024x683.jpg" },
    { name: "3-комнатный Люкс", type: "room", link: "rooms.php", img: "https://gu-berezina.by/wp-content/uploads/2025/12/IMG_2057-1024x683.jpg" },
    { name: "Прейскурант", type: "price", link: "prices.php", img: "https://img.icons8.com/color/48/000000/money-bag.png" }
];

function performSearch() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    if (!query) {
        displayResults(data);
        return;
    }

    const results = data.filter(item => item.name.toLowerCase().includes(query));
    displayResults(results);
}

function filterSearch(type) {
    if (type === 'all') {
        displayResults(data);
        return;
    }
    const results = data.filter(item => item.type === type);
    displayResults(results);
}

function displayResults(results) {
    const container = document.getElementById('searchResults');
    const info = document.getElementById('resultsInfo');
    container.innerHTML = '';

    if (results.length === 0) {
        info.innerText = 'По вашему запросу ничего не найдено.';
        return;
    }

    info.innerText = `Результатов: ${results.length}`;

    results.forEach(item => {
        const card = document.createElement('div');
        card.className = 'procedure-card';
        card.style.cursor = 'pointer';
        card.onclick = () => window.location.href = item.link;
        card.innerHTML = `
            <img src="${item.img}" alt="${item.name}">
            <h3>${item.name}</h3>
            <p>${item.type === 'procedure' ? 'Медицинская услуга' : 'Информация о проживании'}</p>
        `;
        container.appendChild(card);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
