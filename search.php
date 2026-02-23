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
        <!-- Search Companion Sidebar -->
        <aside style="width: 250px; background: #748aff; padding: 15px; display: flex; flex-direction: column; gap: 20px; color: white; border-right: 1px solid #002d96;">
            <div style="text-align: center;">
                <img src="https://upload.wikimedia.org/wikipedia/en/thumb/5/5f/Windows_XP_Search_Companion_character_Rover.png/150px-Windows_XP_Search_Companion_character_Rover.png" alt="Rover" style="width: 80px;">
                <p style="font-size: 12px; font-weight: bold; margin-top: 10px;">Что вы хотите найти?</p>
            </div>

            <div style="background: white; color: #333; padding: 10px; border-radius: 8px;">
                <label style="font-size: 11px; font-weight: bold;">Часть имени файла или слова в нем:</label>
                <input type="text" id="searchInput" style="width: 100%; margin-top: 5px; border: 1px solid #7cb7f1;" placeholder="Процедура, номер...">
                <button onclick="performSearch()" class="xp-btn-large" style="width: 100%; margin-top: 10px; padding: 5px;">Найти</button>
            </div>

            <div style="font-size: 11px;">
                <p>Вы также можете искать по:</p>
                <ul style="padding-left: 20px;">
                    <li style="cursor: pointer; text-decoration: underline;" onclick="filterSearch('procedure')">Процедурам</li>
                    <li style="cursor: pointer; text-decoration: underline;" onclick="filterSearch('room')">Номерам</li>
                    <li style="cursor: pointer; text-decoration: underline;" onclick="filterSearch('price')">Ценам</li>
                </ul>
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
    { name: "Прейскурант", type: "price", link: "prices.php", img: "https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Money_Cash.svg/32px-Money_Cash.svg.png" }
];

function performSearch() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    if (!query) return;

    const results = data.filter(item => item.name.toLowerCase().includes(query));
    displayResults(results);
}

function filterSearch(type) {
    const results = data.filter(item => item.type === type);
    displayResults(results);
}

function displayResults(results) {
    const container = document.getElementById('searchResults');
    const info = document.getElementById('resultsInfo');
    container.innerHTML = '';

    if (results.length === 0) {
        info.innerText = 'Ничего не найдено.';
        return;
    }

    info.innerText = `Найдено элементов: ${results.length}`;

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
