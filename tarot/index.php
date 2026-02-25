<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таро Сканер</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/tesseract.js@v5.0.2/dist/tesseract.min.js"></script>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 1.5rem; color: var(--win-accent);">Таро Сканер</h1>
    </div>

    <div class="container">
        <!-- Step 1: Upload/Camera -->
        <div class="card" id="upload-section">
            <div style="font-size: 5rem; margin-bottom: 20px; color: var(--win-accent);">
                <i data-lucide="layout-template"></i>
            </div>
            <h2>Анализ Расклада</h2>
            <p style="color: #666; margin-bottom: 30px;">Сфотографируйте ваш расклад карт, чтобы получить мгновенный анализ и значение каждой карты от нашего ИИ.</p>

            <label for="camera-input" class="btn-primary">
                <i data-lucide="camera"></i> Сфотографировать
            </label>
            <input type="file" id="camera-input" accept="image/*" capture="camera">
        </div>

        <!-- Step 2: Preview & Scan -->
        <div class="preview-container" id="preview-section">
            <div class="card" style="position: relative; overflow: hidden; padding: 0;">
                <img id="preview-img" src="" alt="Preview">
                <div class="scan-line" id="scan-line" style="display:none;"></div>
            </div>
            <div style="text-align: center; margin-top: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <button class="btn-primary" id="analyze-btn">
                    <i data-lucide="sparkles"></i> Начать анализ
                </button>
                <button class="btn-primary" id="retake-btn" style="background: transparent; color: #666; border: 1px solid #ddd;">
                    Переснять
                </button>
            </div>
        </div>

        <!-- Step 3: Results -->
        <div class="results" id="results-section" style="display:none;">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px; border-bottom: 1px solid #f0f0f0; background: #fafafa;">
                    <h3 style="margin: 0;">Карты в раскладе</h3>
                </div>
                <div id="cards-list"></div>
            </div>

            <div class="card" style="background: linear-gradient(135deg, #6200ee 0%, #9c27b0 100%); color: white; text-align: left;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i data-lucide="brain-circuit"></i>
                    <h3 style="margin: 0;">ИИ-Интерпретация</h3>
                </div>
                <p id="total-analysis" style="line-height: 1.6; opacity: 0.9;">Анализируем сочетание карт...</p>
            </div>

            <div style="text-align: center; margin-bottom: 40px;">
                <button class="btn-primary" onclick="location.reload()">
                    <i data-lucide="refresh-cw"></i> Новый расклад
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
        <h2 style="margin: 0; color: var(--win-accent);">Идет распознавание...</h2>
        <p id="loading-step" style="color: #666; font-size: 1.1rem; margin-top: 10px;">Подключение к нейросети</p>
    </div>

    <script src="assets/js/tarot.js"></script>
    <script>
        lucide.createIcons();

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</body>
</html>
