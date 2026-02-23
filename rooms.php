<?php include 'includes/header.php'; ?>

<div class="window">
    <div class="title-bar">
        <div class="title-bar-text">Проживание и номера - Проводник</div>
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

    <div class="window-body" style="display: flex; flex-direction: row; height: calc(100% - 30px); margin: 0; padding: 0;">
        <aside class="explorer-sidebar">
            <div class="explorer-group">
                <div class="explorer-group-header">Задачи</div>
                <div class="explorer-group-body">
                    <a href="booking.php" class="explorer-link">Забронировать номер</a>
                </div>
            </div>
        </aside>

        <main class="explorer-content" style="overflow-y: auto; padding: 20px;">
            <h2>Варианты проживания</h2>
            <p>Наш санаторий предлагает комфортные условия для проживания, создавая атмосферу домашнего тепла и уюта.</p>

            <div class="procedure-grid">
                <!-- Double one-room -->
                <div class="procedure-card" style="width: 100%; max-width: none;">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/IMG_1975-1024x683.jpg" alt="Однокомнатный номер" style="max-height: 300px; object-fit: cover;">
                    <h3>Двухместный однокомнатный номер</h3>
                    <p>Стандартный уютный номер для двоих. Идеально подходит для комфортного отдыха.</p>
                    <ul>
                        <li>2 раздельные кровати</li>
                        <li>Телевизор</li>
                        <li>Шкаф для одежды</li>
                        <li>Балкон</li>
                        <li>Санузел с душем</li>
                    </ul>
                </div>

                <!-- Two-room Lux -->
                <div class="procedure-card" style="width: 100%; max-width: none;">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/IMG_2028-1024x683.jpg" alt="Двухкомнатный Люкс" style="max-height: 300px; object-fit: cover;">
                    <h3>Двухкомнатный двухместный номер «ЛЮКС»</h3>
                    <p>Просторный номер повышенной комфортности с отдельной гостиной и спальней.</p>
                    <ul>
                        <li>Двуспальная кровать</li>
                        <li>Мягкая мебель в гостиной</li>
                        <li>Холодильник, Электрочайник</li>
                        <li>Современный телевизор</li>
                        <li>Улучшенная отделка интерьера</li>
                    </ul>
                </div>

                <!-- Three-room Lux -->
                <div class="procedure-card" style="width: 100%; max-width: none;">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/IMG_2057-1024x683.jpg" alt="Трехкомнатный Люкс" style="max-height: 300px; object-fit: cover;">
                    <h3>Трехкомнатный двухместный номер «ЛЮКС»</h3>
                    <p>Максимальный уровень комфорта. Большая площадь и расширенный набор удобств.</p>
                    <ul>
                        <li>Спальня, Гостиная и Кабинет/Столовая</li>
                        <li>Полный набор бытовой техники</li>
                        <li>Просторная ванная комната</li>
                        <li>Кондиционер</li>
                        <li>Панорамный вид из окон</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
