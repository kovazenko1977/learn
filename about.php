<?php include 'includes/header.php'; ?>

<div class="window">
    <div class="title-bar">
        <div class="title-bar-text">О санатории - Блокнот</div>
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
        <h1>Республиканский санаторий «БЕРЕЗИНА»</h1>
        <p>Наше учреждение предназначено для лечения и оздоровления ветеранов войны, труда и инвалидов. Мы гордимся своей историей и высоким качеством медицинского обслуживания.</p>

        <h3>Наша миссия</h3>
        <p>Обеспечение доступного и эффективного оздоровления граждан, укрепление здоровья нации через сочетание современных медицинских технологий и природных лечебных факторов.</p>

        <h3>Расположение</h3>
        <p>Санаторий находится в Минской области, Борисовском районе, в живописном месте на берегу реки Березина. Вокруг — вековой сосновый лес, создающий уникальный микроклимат.</p>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Copilot_20251228_012015-683x1024.png" alt="Вид" style="width: 50%; height: 200px; object-fit: cover; border: 1px solid #ccc;">
            <img src="https://gu-berezina.by/wp-content/uploads/2025/12/IMG_1975-1024x683.jpg" alt="Вид" style="width: 50%; height: 200px; object-fit: cover; border: 1px solid #ccc;">
        </div>

        <h3 style="margin-top: 30px;">Основные направления деятельности:</h3>
        <ul>
            <li>Кардиология (болезни системы кровообращения)</li>
            <li>Неврология (болезни нервной системы)</li>
            <li>Пульмонология (болезни органов дыхания)</li>
            <li>Травматология и ортопедия (болезни костно-мышечной системы)</li>
        </ul>

        <p style="margin-top: 20px;">Мы всегда рады новым гостям и делаем всё возможное, чтобы ваше пребывание у нас было максимально полезным и приятным.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
