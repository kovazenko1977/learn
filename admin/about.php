<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

Auth::requireAuth();

$sectionsCount = count(Section::all());
$newsCount = count(NewsItem::all());

$features = [
    ['icon' => 'bi-code-slash', 'title' => '1. Шорткоды для WordPress & Elementor', 'desc' => 'Быстрое встраивание любого раздела на страницу сайта через шорткод [news_section id="..."] или HTML/JS.'],
    ['icon' => 'bi-shield-lock-fill', 'title' => '2. Защита входа по PIN-коду (222120)', 'desc' => 'Безопасный доступ в панель управления по 6-значному PIN-коду без подсказок.'],
    ['icon' => 'bi-key-fill', 'title' => '3. Смена пароля администратора', 'desc' => 'Возможность в любой момент сменить цифровой пароль доступа в настройках безопасности.'],
    ['icon' => 'bi-shield-shaded', 'title' => '4. Защита от подбора паролей (Brute-force)', 'desc' => 'Автоматическая блокировка IP-адреса на 15 минут при 5 неудачных попытках входа подряд.'],
    ['icon' => 'bi-folder2-open', 'title' => '5. Мульти-разделы и категории новостей', 'desc' => 'Неограниченное число разделов (Новости, События, Акции, Пресс-релизы, Блог) со своими настройками.'],
    ['icon' => 'bi-camera-video-fill', 'title' => '6. Загрузка видеофайлов (MP4, WebM)', 'desc' => 'Прямая загрузка видеороликов на сервер с поддержкой перемотки и потокового воспроизведения.'],
    ['icon' => 'bi-youtube', 'title' => '7. Встраивание видео (YouTube / Vimeo / Rutube)', 'desc' => 'Автоматическое распознавание ссылок и генерация адаптивных плееров в контенте.'],
    ['icon' => 'bi-image', 'title' => '8. Загрузка фото и превью (JPG, PNG, WEBP, GIF)', 'desc' => 'Поддержка загрузки обложек, фотогалерей и автоматическая адаптация размеров изображений.'],
    ['icon' => 'bi-images', 'title' => '9. Полноценный Медиа-менеджер', 'desc' => 'Интерфейс просмотра всех файлов, фильтрация по фото/видео, копирование прямых URL и удаление.'],
    ['icon' => 'bi-pencil-square', 'title' => '10. Визуальный WYSIWYG-редактор (Quill.js)', 'desc' => 'Удобное форматирование текста, заголовки, цитаты, списки, ссылки и загрузка изображений по клику.'],
    ['icon' => 'bi-code', 'title' => '11. Режим прямого редактирования HTML', 'desc' => 'Переключение редактора в исходный HTML-код для вставки скриптов, виджетов и кастомных тегов.'],
    ['icon' => 'bi-phone', 'title' => '12. Адаптивность для Mobile & PC', 'desc' => 'Идеальное отображение и управление на любых устройствах: смартфонах, планшетах и компьютерах.'],
    ['icon' => 'bi-moon-stars', 'title' => '13. Темная и светлая тема оформления', 'desc' => 'Мгновенное переключение темного и светлого интерфейса с сохранением предпочтений в браузере.'],
    ['icon' => 'bi-calendar-event', 'title' => '14. Планирование публикации по дате и времени', 'desc' => 'Отложенный запуск материалов: публикация становится видимой точно в заданное время.'],
    ['icon' => 'bi-calendar-x', 'title' => '15. Автоматическое снятие с публикации (дедлайн)', 'desc' => 'Срок действия акции или события: новость автоматически скрывается по истечении дедлайна.'],
    ['icon' => 'bi-pin-angle-fill', 'title' => '16. Закрепление важных записей (Sticky/Top)', 'desc' => 'Вывод ключевых новостей и срочных объявлений поверх остальных вне зависимости от даты.'],
    ['icon' => 'bi-file-earmark-check', 'title' => '17. Статусы «Опубликовано» и «Черновик»', 'desc' => 'Подготовка и вычитка материалов без риска случайной публикации на боевом сайте.'],
    ['icon' => 'bi-grid-3x3-gap', 'title' => '18. Режимы отображения (Карточки / Таблица)', 'desc' => 'Выбор визуального стиля для каждого раздела: плиточная сетка карточек или компактная таблица.'],
    ['icon' => 'bi-search', 'title' => '19. Мгновенный живой поиск по новостям', 'desc' => 'Интерактивная строка поиска на сайте и в панели администратора с фильтрацией на лету.'],
    ['icon' => 'bi-file-earmark-diff', 'title' => '20. История правок и версионность', 'desc' => 'Сохранение ревизий каждого изменения текста с возможностью просмотреть предыдущие редакции.'],
    ['icon' => 'bi-eye-fill', 'title' => '21. Счетчик уникальных просмотров', 'desc' => 'Точный учет прочтений каждой новости с фильтрацией повторных обращений за текущие сутки.'],
    ['icon' => 'bi-heart-fill', 'title' => '22. Социальные реакции читателей (Лайки)', 'desc' => 'Интерактивная кнопка реакций с защитой от накрутки (1 голос с устройства).'],
    ['icon' => 'bi-share-fill', 'title' => '23. Кнопки мгновенного шеринга в соцсети', 'desc' => 'Быстрая отправка ссылки в Telegram, WhatsApp, Viber, VK одним касанием.'],
    ['icon' => 'bi-qr-code', 'title' => '24. Генератор QR-кода для новости', 'desc' => 'Открытие QR-кода для мгновенного считывания камерой телефона прямо с экрана.'],
    ['icon' => 'bi-link-45deg', 'title' => '25. Копирование прямой ссылки в 1 клик', 'desc' => 'Кнопка быстрого сохранения адреса новости в буфер обмена.'],
    ['icon' => 'bi-clock-history', 'title' => '26. Расчет времени на чтение', 'desc' => 'Автоматический подсчет минут, необходимых читателю для ознакомления со статьей.'],
    ['icon' => 'bi-list-ol', 'title' => '27. Автоматическое оглавление (TOC)', 'desc' => 'Генерация кликабельного содержания статьи на основе заголовков материала.'],
    ['icon' => 'bi-segmented-nav', 'title' => '28. Индикатор прогресса чтения', 'desc' => 'Тонкая динамическая полоса вверху экрана, отражающая глубину прокрутки статьи.'],
    ['icon' => 'bi-universal-access', 'title' => '29. Панель доступности (A- / A+)', 'desc' => 'Увеличение и уменьшение шрифта для слабовидящих пользователей непосредственно в шорткоде.'],
    ['icon' => 'bi-tags-fill', 'title' => '30. Система тегов и фильтрации', 'desc' => 'Привязка ключевых слов к статьям и фильтрация материалов по выбранным хештегам.'],
    ['icon' => 'bi-palette-fill', 'title' => '31. Кастомизация дизайна и стилей (CSS)', 'desc' => 'Индивидуальные цвета, градиенты, радиусы скругления и пользовательский CSS для каждого раздела.'],
    ['icon' => 'bi-envelope-at-fill', 'title' => '32. Форма подписки на рассылку', 'desc' => 'Встроенный блок сбора email-адресов читателей с управлением базой подписчиков.'],
    ['icon' => 'bi-file-earmark-spreadsheet', 'title' => '33. Экспорт подписчиков в CSV', 'desc' => 'Выгрузка списка собранных email в формате Excel/CSV для дальнейшей маркетинговой рассылки.'],
    ['icon' => 'bi-box-arrow-up-right', 'title' => '34. Экспорт новостей в JSON / CSV', 'desc' => 'Полная выгрузка базы материалов для миграции или архивации контента.'],
    ['icon' => 'bi-cloud-arrow-down-fill', 'title' => '35. Резервное копирование в ZIP и Восстановление', 'desc' => 'Создание полного архива всей базы данных и картинок одной кнопкой с возможностью отката.'],
    ['icon' => 'bi-heart-pulse-fill', 'title' => '36. Мониторинг здоровья системы', 'desc' => 'Контроль свободного места на диске, прав на запись, версии PHP и лимитов загрузки файлов.'],
    ['icon' => 'bi-journal-text', 'title' => '37. Аудит-лог безопасности и действий', 'desc' => 'Детальная хроника входов, правок, загрузок и удалений с фиксацией IP-адресов и времени.'],
    ['icon' => 'bi-people-fill', 'title' => '38. Управление пользователями и ролями', 'desc' => 'Создание аккаунтов администраторов и редакторов с разграничением прав доступа.'],
    ['icon' => 'bi-rss-fill', 'title' => '39. RSS-лента и XML Sitemap', 'desc' => 'Автоматическая генерация ленты новостей /api/rss.php и карты сайта для поисковиков /api/sitemap.php.'],
    ['icon' => 'bi-send-fill', 'title' => '40. Webhook-уведомления (Telegram / Slack / CRM)', 'desc' => 'Мгновенная отправка HTTP POST запросов при публикации новых материалов во внешние системы.']
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О программе - 40 функций системы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .feature-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 16px;
            padding: 1.25rem;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .feature-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(79, 172, 254, 0.15);
            color: #4facfe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
        }
        [data-theme="dark"] .feature-card {
            background: rgba(30, 30, 30, 0.8);
            border-color: rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body>
    <button class="btn btn-primary d-lg-none mobile-menu-btn" id="mobileMenuBtn">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="sidebar" id="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="media.php"><i class="bi bi-images"></i> Медиа менеджер</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link" href="logs.php"><i class="bi bi-list-check"></i> Журнал</a>
            <a class="nav-link" href="health.php"><i class="bi bi-heart-pulse"></i> Состояние</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link active" href="about.php"><i class="bi bi-info-circle"></i> О программе (40 функций)</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="mb-1">О программе и 40 функций CMS</h2>
                <p class="text-muted mb-0 small">Профессиональное веб-приложение для новостей, событий и медиа с шорткодами Elementor/WP</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary rounded-circle" id="themeToggle"><i class="bi bi-moon"></i></button>
            </div>
        </header>

        <!-- Main Banner -->
        <div class="glass-card mb-4 text-center py-4">
            <h3 class="mb-2"><b>News</b>Manager Pro</h3>
            <p class="lead mb-3">Современное решение для публикации новостей, акций, медиаматериалов и анонсов через шорткоды</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap small text-muted">
                <span><i class="bi bi-folder-check text-primary"></i> Разделов: <b><?php echo $sectionsCount; ?></b></span>
                <span><i class="bi bi-file-earmark-text text-primary"></i> Новостей: <b><?php echo $newsCount; ?></b></span>
                <span><i class="bi bi-cpu text-primary"></i> PHP: <b><?php echo PHP_VERSION; ?></b></span>
                <span><i class="bi bi-shield-lock text-success"></i> Защита PIN: <b>Активна</b></span>
            </div>
            <hr class="my-4 mx-auto" style="width: 250px;">
            <div class="small">
                <p class="mb-1">Разработано: <a href="https://wes.by" target="_blank" class="text-decoration-none fw-semibold">wes.by</a></p>
                <p class="mb-1">Контактный телефон: <a href="tel:+375333533971" class="text-decoration-none fw-semibold">+375 33 353-39-71</a></p>
                <p class="mb-0">Разработчик: <b>Коваженко С.Б.</b></p>
            </div>
        </div>

        <!-- 40 Features Grid -->
        <div class="glass-card mb-4">
            <h4 class="mb-4"><i class="bi bi-stars text-warning"></i> 40 ключевых функций системы</h4>
            <div class="row g-3">
                <?php foreach ($features as $f): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="feature-card">
                        <div class="feature-icon-wrap">
                            <i class="bi <?php echo $f['icon']; ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-2" style="font-size: 0.95rem;"><?php echo htmlspecialchars($f['title']); ?></h6>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($f['desc']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- WordPress / Elementor Integration Guide -->
        <div class="glass-card mb-4">
            <h4 class="mb-3"><i class="bi bi-wordpress text-primary"></i> Инструкция по интеграции в WordPress & Elementor</h4>

            <h6 class="mt-4">Вариант 1: Через шорткод в functions.php (рекомендуемый)</h6>
            <p class="small text-muted">Добавьте следующий код в файл <code>functions.php</code> вашей дочерней темы WordPress:</p>
            <pre class="bg-light p-3 rounded-3 small border"><code>add_shortcode('news_section', function($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    if (empty($atts['id'])) return '';

    $base_url = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>';
    $path = '<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>';
    $path = str_replace('/admin', '', $path);
    $url = $base_url . $path . '/api/shortcode.php?id=' . urlencode($atts['id']);

    $response = wp_remote_get($url, ['timeout' => 15, 'sslverify' => false]);
    if (is_wp_error($response)) return '&lt;!-- NewsManager Error: ' . $response->get_error_message() . ' --&gt;';

    return wp_remote_retrieve_body($response);
});</code></pre>
            <p class="small text-muted">После этого в Elementor добавьте виджет «Шорткод» (Shortcode) со значением: <code>[news_section id="ID_РАЗДЕЛА"]</code>.</p>

            <h6 class="mt-4">Вариант 2: Вставка через Elementor виджет HTML / Iframe</h6>
            <p class="small text-muted">Универсальный способ для любых конструкторов и страниц:</p>
            <pre class="bg-light p-3 rounded-3 small border"><code>&lt;iframe src="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . rtrim(str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME'])), "/") . '/api/shortcode.php?id=ID_РАЗДЕЛА'; ?>"
        width="100%" height="800" frameborder="0" style="border:none; width:100%; min-height:600px;"&gt;&lt;/iframe&gt;</code></pre>

            <h6 class="mt-4">Вариант 3: Автозагрузка через JavaScript виджет</h6>
            <pre class="bg-light p-3 rounded-3 small border"><code>&lt;div data-news-section="ID_РАЗДЕЛА"&gt;&lt;/div&gt;
&lt;script src="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . rtrim(str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME'])), "/") . '/assets/js/shortcode.js'; ?>"&gt;&lt;/script&gt;</code></pre>
        </div>

        <div class="text-center text-muted small py-3">
            &copy; <?php echo date('Y'); ?> NewsManager Pro. Разработчик: Коваженко С.Б. Все права защищены.
        </div>
    </div>

    <script>
        // Dark theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const currentTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', currentTheme);
            themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
            themeToggle.onclick = () => {
                const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                themeToggle.innerHTML = theme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
            };
        }

        // Mobile menu toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (mobileBtn && sidebar) {
            mobileBtn.onclick = () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            };
            backdrop.onclick = () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            };
        }
    </script>
</body>
</html>
