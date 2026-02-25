document.addEventListener('DOMContentLoaded', () => {
    const cameraInput = document.getElementById('camera-input');
    const uploadSection = document.getElementById('upload-section');
    const previewSection = document.getElementById('preview-section');
    const previewImg = document.getElementById('preview-img');
    const scanLine = document.getElementById('scan-line');
    const analyzeBtn = document.getElementById('analyze-btn');
    const retakeBtn = document.getElementById('retake-btn');
    const saveBtn = document.getElementById('save-btn');
    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingStep = document.getElementById('loading-step');
    const resultsSection = document.getElementById('results-section');
    const cardsList = document.getElementById('cards-list');
    const totalAnalysis = document.getElementById('total-analysis');

    const tabScan = document.getElementById('tab-scan');
    const tabHistory = document.getElementById('tab-history');
    const sectionScan = document.getElementById('section-scan');
    const sectionHistory = document.getElementById('section-history');
    const historyList = document.getElementById('history-list');

    let cardsData = [];
    let selectedCards = [];
    let currentInterpretation = "";

    // Load cards data
    fetch('data/cards.json')
        .then(response => response.json())
        .then(data => {
            cardsData = data;
        });

    // Tab Switching
    tabScan.addEventListener('click', () => {
        tabScan.classList.add('active');
        tabHistory.classList.remove('active');
        sectionScan.style.display = 'block';
        sectionHistory.style.display = 'none';
    });

    tabHistory.addEventListener('click', () => {
        tabHistory.classList.add('active');
        tabScan.classList.remove('active');
        sectionScan.style.display = 'none';
        sectionHistory.style.display = 'block';
        loadHistory();
    });

    cameraInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewImg.src = event.target.result;
                uploadSection.style.display = 'none';
                previewSection.style.display = 'block';
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    retakeBtn.addEventListener('click', () => {
        uploadSection.style.display = 'block';
        previewSection.style.display = 'none';
        cameraInput.value = '';
    });

    analyzeBtn.addEventListener('click', async () => {
        scanLine.style.display = 'block';
        loadingOverlay.style.display = 'flex';

        const steps = [
            'Настройка астрального резонанса...',
            'Распознавание древних символов...',
            'Анализ числовых вибраций...',
            'Считывание информационного поля...',
            'Формирование пророчества...'
        ];

        let stepIndex = 0;
        const interval = setInterval(() => {
            if (stepIndex < steps.length) {
                loadingStep.innerText = steps[stepIndex];
                stepIndex++;
            }
        }, 800);

        try {
            // OCR Logic
            const worker = await Tesseract.createWorker('rus');
            const { data: { text } } = await worker.recognize(previewImg.src);
            await worker.terminate();

            clearInterval(interval);
            console.log('Detected text:', text);

            const detected = [];
            cardsData.forEach(card => {
                if (text.toLowerCase().includes(card.name.toLowerCase())) {
                    detected.push(card);
                }
            });

            if (detected.length > 0) {
                selectedCards = detected.slice(0, 3);
            } else {
                selectedCards = [...cardsData].sort(() => 0.5 - Math.random()).slice(0, 3);
            }

            showResults();
        } catch (error) {
            clearInterval(interval);
            console.error('Analysis failed:', error);
            selectedCards = [...cardsData].sort(() => 0.5 - Math.random()).slice(0, 3);
            showResults();
        }
    });

    function showResults() {
        loadingOverlay.style.display = 'none';
        previewSection.style.display = 'none';
        resultsSection.style.display = 'block';

        const positions = ['Прошлое', 'Настоящее', 'Будущее'];

        cardsList.innerHTML = '';
        selectedCards.forEach((card, index) => {
            const cardEl = document.createElement('div');
            cardEl.className = 'tarot-card-result';
            cardEl.innerHTML = `
                <div class="card-icon">🃏</div>
                <div>
                    <div style="font-size: 0.8rem; color: var(--win-accent); font-weight: bold; text-transform: uppercase;">${positions[index] || 'Влияние'}</div>
                    <div style="font-weight: bold; font-size: 1.1rem; color: #fff;">${card.name}</div>
                    <div style="font-size: 0.95rem; color: var(--win-text-secondary); margin-top: 4px;">${card.meaning}</div>
                </div>
            `;
            cardsList.appendChild(cardEl);
        });

        currentInterpretation = generateInterpretation(selectedCards);
        totalAnalysis.innerText = currentInterpretation;

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    function generateInterpretation(cards) {
        if (cards.length < 3) return "Потоки энергии слишком слабы для точного анализа.";

        const intro = `Ваш расклад из карт "${cards[0].name}", "${cards[1].name}" и "${cards[2].name}" открывает путь к пониманию текущей ситуации. `;
        const body = `Ваш прошлый опыт (${cards[0].name}) научил вас, что ${cards[0].meaning.toLowerCase()}. ` +
                     `В настоящем (${cards[1].name}) важно помнить: ${cards[1].meaning.toLowerCase()}. ` +
                     `Будущее (${cards[2].name}) несет в себе ${cards[2].meaning.toLowerCase()}.`;
        const advice = `\n\nМудрый совет: Сосредоточьтесь на энергии ${cards[1].name}, чтобы максимально благотворно войти в этап ${cards[2].name}.`;

        return intro + body + advice;
    }

    // Save Spread
    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i data-lucide="loader" class="spin"></i> Сохранение...';
        lucide.createIcons();

        const spreadData = {
            cards: selectedCards,
            interpretation: currentInterpretation,
            image: previewImg.src // We save the image as base64 in the JSON file
        };

        try {
            const response = await fetch('save_spread.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(spreadData)
            });
            const result = await response.json();
            if (result.success) {
                saveBtn.innerHTML = '<i data-lucide="check"></i> Сохранено!';
                saveBtn.style.background = '#4CAF50';
                saveBtn.style.color = '#fff';
            } else {
                alert('Ошибка при сохранении: ' + result.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i data-lucide="bookmark"></i> Сохранить в Каталог';
            }
        } catch (e) {
            console.error(e);
            alert('Ошибка сети');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i data-lucide="bookmark"></i> Сохранить в Каталог';
        }
        lucide.createIcons();
    });

    async function loadHistory() {
        historyList.innerHTML = '<div class="spinner" style="width:40px;height:40px;margin:20px auto;"></div>';
        try {
            const response = await fetch('get_history.php');
            const data = await response.json();

            if (data.length === 0) {
                historyList.innerHTML = '<p style="color: var(--win-text-secondary); padding: 20px;">Ваш каталог пока пуст...</p>';
                return;
            }

            historyList.innerHTML = '';
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'card';
                div.style.textAlign = 'left';
                div.style.padding = '20px';

                const date = new Date(item.date).toLocaleString('ru-RU');
                const cardNames = item.cards.map(c => c.name).join(' / ');

                div.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <span style="font-size: 0.8rem; color: var(--win-accent);">${date}</span>
                        <i data-lucide="chevron-down" style="width:16px; opacity:0.5;"></i>
                    </div>
                    <div style="font-weight: bold; margin-bottom: 10px; color: #fff;">${cardNames}</div>
                    <p style="font-size: 0.9rem; color: var(--win-text-secondary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${item.interpretation}
                    </p>
                `;
                historyList.appendChild(div);
            });
            lucide.createIcons();
        } catch (e) {
            historyList.innerHTML = '<p style="color: #ff4444;">Ошибка загрузки истории</p>';
        }
    }
});
