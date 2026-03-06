<?php include 'includes/header.php'; ?>

<div class="window">
    <div class="title-bar">
        <div class="title-bar-text">Питание в санатории - Справка</div>
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
        <div style="display: flex; gap: 20px; align-items: start;">
            <img src="https://gu-berezina.by/wp-content/uploads/2025/12/линия-removebg-preview-1024x199.png" alt="Питание" style="width: 200px; height: auto; border: 1px solid #ccc;">
            <div>
                <h1>Здоровое и вкусное питание</h1>
                <p>Правильное питание — залог успешного лечения. В санатории «Березина» организовано <strong>5-разовое диетическое питание</strong> по заказному меню.</p>

                <h3>Наши принципы:</h3>
                <ul>
                    <li><strong>Сбалансированность:</strong> Оптимальное сочетание белков, жиров и углеводов.</li>
                    <li><strong>Разнообразие:</strong> Меню обновляется ежедневно и включает блюда белорусской и европейской кухни.</li>
                    <li><strong>Диетотерапия:</strong> Соблюдение диет (Диета Б, Диета П, Диета М) по назначению врача.</li>
                    <li><strong>Свежесть:</strong> Использование только натуральных и свежих продуктов от местных поставщиков.</li>
                </ul>

                <div class="field-row" style="background: #f0f0f0; padding: 10px; border: 1px inset #fff;">
                    <p><strong>График приема пищи:</strong></p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li>Завтрак: 08:30 – 09:30</li>
                        <li>Обед: 13:30 – 14:30</li>
                        <li>Полдник: 16:30 – 17:00</li>
                        <li>Ужин: 18:30 – 19:30</li>
                        <li>Кефир: 21:00</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 20px;">
            <div style="flex: 1;">
                <h3>Специальные предложения:</h3>
                <p>Для пациентов с сахарным диабетом предусмотрено специальное меню с дробным режимом питания. Также в обеденном зале всегда в доступе свежая выпечка и витаминные напитки.</p>
            </div>

            <!-- Windows Media Player Simulation -->
            <div style="width: 200px; background: #000; color: #0f0; border: 2px solid #555; padding: 5px; font-family: 'Courier New', monospace; font-size: 10px;">
                <div style="background: #333; padding: 2px; color: #fff; margin-bottom: 5px;">Windows Media Player</div>
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; border: 1px solid #555; margin-bottom: 5px;">
                    <div style="width: 10px; height: 30px; background: #0f0; margin: 2px; animation: wave 1s infinite alternate;"></div>
                    <div style="width: 10px; height: 45px; background: #0f0; margin: 2px; animation: wave 1.2s infinite alternate;"></div>
                    <div style="width: 10px; height: 20px; background: #0f0; margin: 2px; animation: wave 0.8s infinite alternate;"></div>
                </div>
                <div style="text-align: center;">Атмосфера столовой.mp3</div>
                <div style="display: flex; justify-content: space-around; margin-top: 5px;">
                    <span>[ |< ]</span> <span>[ > ]</span> <span>[ >| ]</span>
                </div>
                <style>
                @keyframes wave { from { height: 10px; } to { height: 50px; } }
                </style>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
