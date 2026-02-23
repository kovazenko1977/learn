<?php include 'includes/header.php'; ?>

<div class="window">
    <div class="title-bar">
        <div class="title-bar-text">Лечебные процедуры - Проводник</div>
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

    <div class="window-body" style="display: flex; flex-direction: row; height: 100%; margin: 0; padding: 0;">
        <!-- Modern Sidebar -->
        <aside class="explorer-sidebar">
            <div class="explorer-group">
                <div class="explorer-group-header">Услуги</div>
                <div class="explorer-group-body">
                    <a href="#cardio" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/heart-health.png"> Кардиология</a>
                    <a href="#nerve" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/brain.png"> Нервная система</a>
                    <a href="#respiratory" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/lungs.png"> Органы дыхания</a>
                    <a href="#muscle" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/skeleton.png"> Опорно-двигательный</a>
                    <a href="#skin" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/skin.png"> Дерматология</a>
                </div>
            </div>

            <div class="explorer-group">
                <div class="explorer-group-header">Действия</div>
                <div class="explorer-group-body">
                    <a href="booking.php" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/calendar.png"> Забронировать</a>
                    <a href="diagnostics.php" class="explorer-link"><img src="https://img.icons8.com/fluency/16/000000/stethoscope.png"> Диагностика</a>
                </div>
            </div>
        </aside>

        <!-- Main Content Explorer Style -->
        <main class="explorer-content" style="overflow-y: auto; padding: 20px;">
            <h2 id="cardio">Сердечно-сосудистая система</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2026/01/Screenshot_4-1.png" alt="Ванны">
                    <h3>Лечебные ванны</h3>
                    <p>Жемчужные, хвойные и минеральные ванны для укрепления сосудов и нормализации давления.</p>
                </div>
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2026/01/Screenshot_3-1.png" alt="Душ">
                    <h3>Циркулярный душ</h3>
                    <p>Интенсивное воздействие струй воды улучшает кровообращение и тонус организма.</p>
                </div>
            </div>

            <h2 id="nerve">Нервная система</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2026/01/Screenshot_2-2.png" alt="Массаж">
                    <h3>Лечебный массаж</h3>
                    <p>Классический и аппаратный массаж для снятия мышечного напряжения и стресса.</p>
                </div>
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_7-removebg-preview.png" alt="Сон">
                    <h3>Электросон</h3>
                    <p>Воздействие импульсных токов для нормализации сна и психоэмоционального состояния.</p>
                </div>
            </div>

            <h2 id="respiratory">Органы дыхания</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2026/01/подари-родителям-300x247.png" alt="Галотерапия">
                    <h3>Галотерапия</h3>
                    <p>Пребывание в соляной комнате для очищения дыхательных путей и укрепления иммунитета.</p>
                </div>
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_1-removebg-preview-1.png" alt="Ингаляции">
                    <h3>Ингаляции</h3>
                    <p>Лекарственные и травяные ингаляции для лечения хронических бронхитов.</p>
                </div>
            </div>

            <h2 id="muscle">Костно-мышечная система</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/e1zb4ekmx5rmw0cvc7wrec3d10-1024x578.png" alt="Грязелечение">
                    <h3>Грязелечение</h3>
                    <p>Аппликации сапропелевых грязей для лечения суставов и позвоночника.</p>
                </div>
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/линия-removebg-preview-300x58.png" alt="Парафин">
                    <h3>Парафино-озокерит</h3>
                    <p>Теплолечение для улучшения подвижности суставов и снятия боли.</p>
                </div>
            </div>

            <h2 id="skin">Кожные заболевания</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Copilot_20251228_012015-683x1024.png" alt="Светолечение">
                    <h3>Светолечение (Биоптрон)</h3>
                    <p>Поляризованный свет для лечения псориаза, экзем и ускорения заживления ран.</p>
                </div>
            </div>

            <h2 id="cosmetic">Косметология</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_1-removebg-preview-1.png" alt="Уход">
                    <h3>Эстетическая косметология</h3>
                    <p>Процедуры по уходу за кожей лица и тела, омолаживающие маски и пилинги.</p>
                </div>
            </div>

            <h2 id="special">Специализированное лечение</h2>
            <div class="procedure-grid">
                <div class="procedure-card">
                    <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_7-removebg-preview.png" alt="Vacumed">
                    <h3>Аппарат VACUMED</h3>
                    <p>Интервальная вакуумная терапия для улучшения лимфодренажа и кровоснабжения нижних конечностей.</p>
                </div>
            </div>

            <div style="margin-top: 40px; padding: 15px; background: #ffffcc; border: 1px solid #e6db55;">
                <strong>💡 Важно:</strong> Все процедуры назначаются врачом после первичного осмотра и ознакомления с вашей санаторно-курортной картой.
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
