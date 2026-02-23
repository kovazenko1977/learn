<?php include 'includes/header.php'; ?>

<div class="window">
    <div class="title-bar">
        <div class="title-bar-text">Инфраструктура и Территория</div>
        <div class="title-bar-controls">
            <button aria-label="Minimize">
                <svg width="10" height="1" viewBox="0 0 10 1"><path d="M0 0.5H10" stroke="currentColor" stroke-width="1"/></svg>
            </button>
            <button aria-label="Maximize">
                <svg width="10" height="10" viewBox="0 0 10 10"><rect x="0.5" y="0.5" width="9" height="9" fill="none" stroke="currentColor" stroke-width="1"/></svg>
            </button>
            <button aria-label="Close" onclick="window.location.href='index.php'">
                <svg width="10" height="10" viewBox="0 0 10 10"><path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.2"/></svg>
            </button>
        </div>
    </div>

    <div class="window-body" style="padding: 20px; overflow-y: auto;">
        <section>
            <h2>Природные лечебные факторы</h2>
            <p>Санаторий расположен на площади <strong>11 гектаров</strong> в живописном сосновом бору на берегу реки Березина.</p>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <div class="procedure-card">
                        <img src="https://gu-berezina.by/wp-content/uploads/2026/01/Copilot_20251228_012015-scaled.jpg" alt="Лес" style="width: 100%; height: 200px; object-fit: cover;">
                        <p style="font-size: 11px; margin-top: 10px;"><strong>Хвойный фитонцид:</strong> Воздух нашего леса насыщен природными антибиотиками, которые укрепляют легкие и уничтожают болезнетворные бактерии.</p>
                    </div>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <div class="procedure-card">
                        <img src="https://gu-berezina.by/wp-content/uploads/2025/12/IMG_2028-1024x683.jpg" alt="Река" style="width: 100%; height: 200px; object-fit: cover;">
                        <p style="font-size: 11px; margin-top: 10px;"><strong>Река Березина:</strong> Умиротворяющий вид на воду и обустроенная набережная способствуют психологической разгрузке.</p>
                    </div>
                </div>
            </div>
        </section>

        <section style="margin-top: 30px;">
            <h2>Бассейн и Сауна</h2>
            <div style="background: #eef3fa; padding: 15px; border-left: 5px solid #0055e5;">
                <p><strong>Бассейн:</strong> Размеры 9 x 3.4 м, глубина от 1.2 до 1.75 м. Температура воды поддерживается на уровне 28–30 °C.</p>
                <p><strong>Сауна:</strong> Отличное место для терморегуляции, снятия стресса и укрепления иммунитета.</p>
            </div>
        </section>

        <section style="margin-top: 30px;">
            <h2>Дополнительные услуги</h2>
            <ul>
                <li>Библиотека с богатым книжным фондом</li>
                <li>Танцевальный зал для вечерних мероприятий</li>
                <li>Спортивные площадки на открытом воздухе</li>
                <li>Тренажерный зал</li>
                <li>Пункт проката (велосипеды, лыжи)</li>
            </ul>
        </section>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
