<?php include 'includes/header.php'; ?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="xp-card" style="padding: 0;">
        <div style="background: white; border-bottom: 1px solid #7F9DB9; padding: 20px; display: flex; gap: 20px;">
            <div style="width: 150px; background: linear-gradient(to bottom, #7BA2E7 0%, #638AD9 100%); color: white; padding: 10px; font-weight: bold; font-size: 14px;">
                Мастер диагностики
            </div>
            <div style="flex: 1;">
                <h2 style="margin: 0; font-size: 18px;">Шаг <span id="stepNum">1</span> из 5</h2>
                <p style="font-size: 11px; color: #666;" id="stepTitle">Общие данные</p>
            </div>
        </div>

        <form id="diagForm" style="padding: 20px;">
            <div id="step-1" class="step-content">
                <p>Укажите вашу базовую информацию:</p>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 11px;">Возраст:</label>
                    <input type="number" class="form-control" style="width: 100px;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px;">Цель пребывания:</label>
                    <select class="form-control">
                        <option>Лечение</option>
                        <option>Профилактика</option>
                        <option>Отдых</option>
                    </select>
                </div>
            </div>

            <div id="step-2" class="step-content" style="display:none;">
                <p>Что вас беспокоит?</p>
                <label style="display: block; font-size: 11px;"><input type="checkbox"> Сердце / Давление</label>
                <label style="display: block; font-size: 11px;"><input type="checkbox"> Суставы / Спина</label>
                <label style="display: block; font-size: 11px;"><input type="checkbox"> Стресс / Сон</label>
                <label style="display: block; font-size: 11px;"><input type="checkbox"> Органы дыхания</label>
            </div>

            <div id="step-3" class="step-content" style="display:none;">
                <p>Детализация симптомов:</p>
                <label style="display: block; font-size: 11px;">Частота жалоб:</label>
                <select class="form-control"><option>Редко</option><option>Часто</option><option>Постоянно</option></select>
            </div>

            <div id="step-4" class="step-content" style="display:none;">
                <p>Факторы риска:</p>
                <label style="display: block; font-size: 11px;"><input type="checkbox"> Курение</label>
                <label style="display: block; font-size: 11px;"><input type="checkbox"> Малоподвижный образ жизни</label>
            </div>

            <div id="step-5" class="step-content" style="display:none;">
                <p>Готово! Нажмите "Завершить", чтобы получить результат.</p>
                <div id="result-box" style="display:none; padding: 10px; border: 1px solid #7F9DB9; background: #FFFFE1;">
                    <strong>Рекомендация:</strong><br>
                    Программа "Здоровое сердце". Рекомендуемые процедуры: ванны с бишофитом, магнитотерапия ОртоСПОК.
                </div>
            </div>

            <div style="margin-top: 30px; text-align: right; border-top: 1px solid #BDD2F1; padding-top: 15px;">
                <button type="button" class="xp-btn" id="prevBtn" style="display: none; min-width: 80px;">< Назад</button>
                <button type="button" class="xp-btn xp-btn-primary" id="nextBtn" style="min-width: 80px;">Далее ></button>
            </div>
        </form>
    </div>
</div>

<script>
let currentStep = 1;
const titles = ["Общие данные", "Основные жалобы", "Подробные симптомы", "Факторы риска", "Результат"];

document.getElementById('nextBtn').onclick = function() {
    if (currentStep < 5) {
        document.getElementById('step-' + currentStep).style.display = 'none';
        currentStep++;
        document.getElementById('step-' + currentStep).style.display = 'block';
        document.getElementById('stepNum').textContent = currentStep;
        document.getElementById('stepTitle').textContent = titles[currentStep-1];
        document.getElementById('prevBtn').style.display = 'inline-block';
        if (currentStep === 5) this.textContent = 'Завершить';
    } else {
        document.getElementById('result-box').style.display = 'block';
        this.style.display = 'none';
    }
};

document.getElementById('prevBtn').onclick = function() {
    if (currentStep > 1) {
        document.getElementById('step-' + currentStep).style.display = 'none';
        currentStep--;
        document.getElementById('step-' + currentStep).style.display = 'block';
        document.getElementById('stepNum').textContent = currentStep;
        document.getElementById('stepTitle').textContent = titles[currentStep-1];
        document.getElementById('nextBtn').textContent = 'Далее >';
        if (currentStep === 1) this.style.display = 'none';
    }
};
</script>

<?php include 'includes/footer.php'; ?>
