<?php include 'includes/header.php'; ?>

<!-- Desktop Icons Layer -->
<div class="desktop-icons">
    <div class="desktop-icon" onclick="window.location.href='rooms.php'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c2/My_Documents_XP.png/32px-My_Documents_XP.png" alt="">
        <span>Мои Номера</span>
    </div>
    <div class="desktop-icon" onclick="window.location.href='prices.php'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Money_Cash.svg/32px-Money_Cash.svg.png" alt="">
        <span>Прайс-лист</span>
    </div>
    <div class="desktop-icon" onclick="window.location.href='infrastructure.php'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e6/My_Computer_XP.png/32px-My_Computer_XP.png" alt="">
        <span>Территория</span>
    </div>
    <div class="desktop-icon" onclick="window.location.href='contact.php'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Winemine_icon.png/32px-Winemine_icon.png" alt="">
        <span>Сетевое окружение</span>
    </div>
    <div class="desktop-icon" onclick="window.location.href='diagnostics.php'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/74/Internet_Explorer_6_logo.svg/32px-Internet_Explorer_6_logo.svg.png" alt="">
        <span>IE Диагностика</span>
    </div>
    <div class="desktop-icon" onclick="window.location.href='procedures.php'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/ef/Crystal_Clear_app_ark.png/32px-Crystal_Clear_app_ark.png" alt="">
        <span>Процедуры</span>
    </div>
</div>

<div class="window" style="width: 85%; max-width: 900px; margin: 40px auto; position: relative; z-index: 10;">
    <div class="title-bar">
        <div class="title-bar-text">Санаторий "Березина" - Microsoft Internet Explorer</div>
        <div class="title-bar-controls">
            <button aria-label="Minimize"></button>
            <button aria-label="Maximize"></button>
            <button aria-label="Close"></button>
        </div>
    </div>

    <div class="window-body" style="padding: 0; margin: 0; display: flex; flex-direction: column;">
        <!-- Browser-like toolbar -->
        <div class="toolbar" style="padding: 5px; background: #f0f0f0; border-bottom: 1px solid #ccc; display: flex; gap: 10px; align-items: center;">
            <button class="toolbar-btn">← Назад</button>
            <button class="toolbar-btn">Вперед →</button>
            <div style="flex: 1; background: white; border: 1px inset #ccc; padding: 2px 5px; font-size: 12px;">https://gu-berezina.by/index.php</div>
            <button class="toolbar-btn">Перейти</button>
        </div>

        <div style="padding: 20px; overflow-y: auto; max-height: 60vh;">
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Copilot_20251228_012015-683x1024.png" alt="Герой" style="width: 300px; height: 200px; object-fit: cover; border: 4px solid #fff; box-shadow: 2px 2px 5px rgba(0,0,0,0.3);">
                <div>
                    <h1 style="color: #003399; margin-top: 0;">Добро пожаловать в "Березину"!</h1>
                    <p>Государственное учреждение «Республиканский санаторий «БЕРЕЗИНА» для ветеранов войны, труда и инвалидов» — это современный лечебно-диагностический центр в сердце соснового бора.</p>
                    <button onclick="window.location.href='booking.php'" class="xp-btn-large">Забронировать путевку</button>
                </div>
            </div>

            <div class="info-blocks" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <fieldset>
                    <legend>Приветствие главного врача</legend>
                    <p style="font-style: italic;">"Пусть время, проведённое в «Березине», подарит вам здоровье, лёгкость, вдохновение и новые силы."</p>
                    <p><strong>Шапель Юлия Владиславовна</strong>, Главный врач.</p>
                </fieldset>

                <fieldset>
                    <legend>Полезная информация</legend>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Площадь: 11 гектаров</li>
                        <li>Профиль: Сердечно-сосудистый</li>
                        <li>Питание: 5-разовое диетическое</li>
                        <li>Расстояние: 70 км от Минска</li>
                    </ul>
                </fieldset>
            </div>

            <div style="margin-top: 20px; background: #eef3fa; padding: 15px; border: 1px solid #7cb7f1;">
                <h3 style="margin-top: 0;">🌲 Оздоровительные факторы</h3>
                <p>Наш санаторий расположен среди живописной природы Борисовщины. Сосновый лес выделяет фитонциды — природные антибиотики, которые подавляют болезнетворные бактерии. Проживание в такой среде — мощная профилактика ОРВИ и болезней дыхательных путей.</p>
                <a href="infrastructure.php" style="color: #003399; font-weight: bold;">Подробнее о территории...</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
