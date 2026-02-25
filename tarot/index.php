<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таро Сканер ИИ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/tesseract.js@v5.0.2/dist/tesseract.min.js"></script>
</head>
<body>
    <div class="header">
        <div style="font-size: 1.8rem; font-weight: 800; letter-spacing: 2px; color: var(--win-accent);">ТАРО СКАНЕР</div>
        <div class="dedication">Посвящается моей любимой жене Жанне</div>
    </div>

    <div class="container">
        <div class="nav-tabs">
            <button class="tab-btn active" id="tab-scan">Сканирование</button>
            <button class="tab-btn" id="tab-history">Мой Каталог</button>
        </div>

        <!-- Section: Scan -->
        <div id="section-scan">
            <div class="card" id="upload-section">
                <div style="font-size: 5rem; margin-bottom: 20px; color: var(--win-accent); filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.3));">
                    <i data-lucide="sparkles"></i>
                </div>
                <h2>Раскройте Тайны Будущего</h2>
                <p style="color: var(--win-text-secondary); margin-bottom: 35px; line-height: 1.6;">Сфотографируйте ваш расклад. Наш искусственный интеллект распознает карты и проведет глубокий сакральный анализ.</p>

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <label for="camera-input" class="btn-primary">
                        <i data-lucide="camera"></i> Сделать Фото
                    </label>
                    <input type="file" id="camera-input" accept="image/*" capture="camera" style="display: none;">

                    <label for="gallery-input" class="btn-primary" style="background: rgba(255,255,255,0.1); color: var(--win-accent); border: 1px solid var(--win-accent);">
                        <i data-lucide="image"></i> Из Галереи
                    </label>
                    <input type="file" id="gallery-input" accept="image/*" style="display: none;">
                </div>
            </div>

            <div class="preview-container" id="preview-section">
                <div class="card" style="position: relative; overflow: hidden; padding: 0;">
                    <img id="preview-img" src="" alt="Preview">
                    <div class="scan-line" id="scan-line" style="display:none;"></div>
                </div>
                <div style="text-align: center; margin-top: 25px; display: flex; flex-direction: column; align-items: center; gap: 12px;">
                    <button class="btn-primary" id="analyze-btn">
                        <i data-lucide="brain-circuit"></i> Начать Анализ
                    </button>
                    <button class="btn-secondary" id="retake-btn">
                        Изменить Фото
                    </button>
                </div>
            </div>

            <div class="results" id="results-section" style="display:none;">
                <div class="card" style="padding: 0; overflow: hidden; border-color: rgba(212, 175, 55, 0.3);">
                    <div style="padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); background: rgba(255, 255, 255, 0.02);">
                        <h3 style="margin: 0; color: var(--win-accent);">Символизм Расклада</h3>
                    </div>
                    <div id="cards-list"></div>
                </div>

                <div class="card" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(46, 26, 71, 0.5) 100%); text-align: left;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                        <i data-lucide="scroll" style="color: var(--win-accent);"></i>
                        <h3 style="margin: 0; color: var(--win-accent);">Толкование ИИ</h3>
                    </div>
                    <p id="total-analysis" style="line-height: 1.8; color: var(--win-text); font-size: 1.05rem;">Анализируем эфирные потоки...</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 50px;">
                    <button class="btn-primary" id="save-btn">
                        <i data-lucide="bookmark"></i> Сохранить в Каталог
                    </button>
                    <button class="btn-secondary" onclick="location.reload()">
                        Новый Расклад
                    </button>
                </div>
            </div>
        </div>

        <!-- Section: History -->
        <div id="section-history" style="display: none;">
            <div class="card">
                <h2 style="color: var(--win-accent); margin-bottom: 25px;">Архив Мудрости</h2>
                <div id="history-list">
                    <p style="color: var(--win-text-secondary);">Ваш каталог пока пуст...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div style="margin-bottom: 15px;">
            <i data-lucide="moon" style="width: 16px; opacity: 0.5;"></i>
            <i data-lucide="star" style="width: 16px; opacity: 0.5;"></i>
            <i data-lucide="sun" style="width: 16px; opacity: 0.5;"></i>
        </div>
        <div>&copy; 2024 Таро Сканер ИИ. Все права защищены.</div>
        <div style="margin-top: 5px; opacity: 0.6;">Разработано с любовью для Жанны.</div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
        <h2 id="loading-title" style="margin: 0; color: var(--win-accent); letter-spacing: 1px;">МЕДИТАЦИЯ...</h2>
        <p id="loading-step" style="color: var(--win-text-secondary); font-size: 1.1rem; margin-top: 15px;">Настройка связи с подсознанием</p>
    </div>

    <script src="assets/js/tarot.js"></script>
    <script>
        lucide.createIcons();
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => { navigator.serviceWorker.register('sw.js'); });
        }
    </script>
</body>
</html>
