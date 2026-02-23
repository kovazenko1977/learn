<?php include 'includes/header.php'; ?>

<h1>Экспресс-диагностика</h1>
<p>Пошаговый опрос в формате санаторно-курортного приёма. В конце вы получите предварительные рекомендации.</p>

<div class="card" id="diagnostics-container" style="max-width: 700px; margin: 0 auto; min-height: 400px; display: flex; flex-direction: column;">
    <!-- Step Indicators -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <span class="step-indicator active" data-step="1">1. Общие</span>
        <span class="step-indicator" data-step="2">2. Жалобы</span>
        <span class="step-indicator" data-step="3">3. Симптомы</span>
        <span class="step-indicator" data-step="4">4. Риски</span>
        <span class="step-indicator" data-step="5">5. Итог</span>
    </div>

    <form id="diagForm">
        <!-- Step 1 -->
        <div class="step-content" id="step-1">
            <h3>Этап 1 — Общие данные</h3>
            <div class="form-group">
                <label for="age">Ваш возраст</label>
                <input type="number" id="age" class="form-control" placeholder="Например, 65">
            </div>
            <div class="form-group">
                <label>Основная цель пребывания</label>
                <select class="form-control">
                    <option>Лечение хронических заболеваний</option>
                    <option>Профилактика и укрепление здоровья</option>
                    <option>Восстановление после болезни/операции</option>
                    <option>Оздоровление и внешний вид</option>
                </select>
            </div>
            <div class="form-group">
                <label>Образ жизни</label>
                <select class="form-control">
                    <option>Низкая активность (сидячая работа)</option>
                    <option>Умеренная активность</option>
                    <option>Высокая активность</option>
                </select>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="step-content" id="step-2" style="display:none;">
            <h3>Этап 2 — Основные жалобы</h3>
            <p>Отметьте беспокоящие вас направления:</p>
            <div class="form-group">
                <label><input type="checkbox"> Сердечно-сосудистые (давление, сердце, отеки)</label><br>
                <label><input type="checkbox"> Нервная система (стресс, головные боли, сон)</label><br>
                <label><input type="checkbox"> Органы дыхания (кашель, одышка)</label><br>
                <label><input type="checkbox"> Кожные проявления</label><br>
                <label><input type="checkbox"> ЖКТ и обмен веществ</label><br>
                <label><input type="checkbox"> Косметология и внешний вид</label>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="step-content" id="step-3" style="display:none;">
            <h3>Этап 3 — Подробные симптомы</h3>
            <div class="form-group">
                <label>Бывают ли у вас эпизоды повышенного давления?</label>
                <select class="form-control"><option>Нет</option><option>Редко</option><option>Часто</option></select>
            </div>
            <div class="form-group">
                <label>Ощущаете ли вы хронический стресс?</label>
                <select class="form-control"><option>Нет</option><option>Иногда</option><option>Постоянно</option></select>
            </div>
            <div class="form-group">
                <label>Беспокоят ли боли в суставах?</label>
                <select class="form-control"><option>Нет</option><option>При нагрузке</option><option>По утрам</option></select>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="step-content" id="step-4" style="display:none;">
            <h3>Этап 4 — Факторы риска</h3>
            <div class="form-group">
                <label><input type="checkbox"> Курение</label><br>
                <label><input type="checkbox"> Наследственность (сердечные заболевания у близких)</label><br>
                <label><input type="checkbox"> Нарушенный режим питания</label><br>
                <label><input type="checkbox"> Нарушенный режим сна</label>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="step-content" id="step-5" style="display:none;">
            <h3>Этап 5 — Подтверждение</h3>
            <p>Данные экспресс-диагностики не заменяют очную консультацию врача, но помогают структурировать ваш запрос.</p>
            <div id="result-box" style="display:none; padding: 20px; background: rgba(46, 125, 50, 0.1); border-radius: 8px; margin-top: 20px;">
                <h4>Ваше предварительное заключение:</h4>
                <p>Рекомендуемый профиль: <strong>Общеоздоровительный с акцентом на сердечно-сосудистую систему.</strong></p>
                <p>Рекомендуемые процедуры: Ванны лечебные, Магнитотерапия, Спелеотерапия.</p>
            </div>
        </div>

        <div style="margin-top: auto; display: flex; justify-content: space-between; padding-top: 20px;">
            <button type="button" class="btn" id="prevBtn" style="background: #ccc; display: none;">Назад</button>
            <button type="button" class="btn" id="nextBtn">Далее</button>
        </div>
    </form>
</div>

<style>
.step-indicator { opacity: 0.5; font-size: 14px; font-weight: 500; }
.step-indicator.active { opacity: 1; color: var(--accent-color); border-bottom: 2px solid var(--accent-color); }
</style>

<script>
let currentStep = 1;
const totalSteps = 5;

const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');

nextBtn.addEventListener('click', () => {
    if (currentStep < totalSteps) {
        document.getElementById(`step-${currentStep}`).style.display = 'none';
        document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.remove('active');
        currentStep++;
        document.getElementById(`step-${currentStep}`).style.display = 'block';
        document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.add('active');

        prevBtn.style.display = 'block';
        if (currentStep === totalSteps) {
            nextBtn.textContent = 'Запустить анализ';
        }
    } else {
        // Run analysis
        document.getElementById('result-box').style.display = 'block';
        nextBtn.style.display = 'none';
        prevBtn.textContent = 'Пройти заново';
        prevBtn.onclick = () => location.reload();
    }
});

prevBtn.addEventListener('click', () => {
    if (currentStep > 1) {
        document.getElementById(`step-${currentStep}`).style.display = 'none';
        document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.remove('active');
        currentStep--;
        document.getElementById(`step-${currentStep}`).style.display = 'block';
        document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.add('active');

        if (currentStep === 1) prevBtn.style.display = 'none';
        nextBtn.textContent = 'Далее';
        nextBtn.style.display = 'block';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
