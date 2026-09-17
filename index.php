<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>МедСервис — PWA Корпоративное Приложение Больницы</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0284c7">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="МедСервис">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Offline Status Indicator -->
    <div id="offlineBanner" class="offline-banner">
        ⚠️ Нет подключения к интернету. Работает в автономном режиме.
    </div>

    <!-- PWA Installation Banner -->
    <div id="pwaInstallBanner" style="display:none; background:linear-gradient(135deg, #0284c7, #2563eb); color:white; padding:12px 16px; align-items:center; justify-content:space-between; box-shadow:0 4px 12px rgba(2,132,199,0.3); z-index:900; position:sticky; top:0;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="font-size:24px;">📲</div>
            <div>
                <div style="font-weight:bold; font-size:14px;">Установить «МедСервис» на телефон</div>
                <div style="font-size:11px; opacity:0.9;">Быстрый доступ с экрана Домой и работа без лагов</div>
            </div>
        </div>
        <div style="display:flex; gap:8px;">
            <button class="btn" style="background:white; color:#0284c7; padding:6px 14px; font-size:13px;" onclick="app.promptPwaInstall()">Установите</button>
            <button class="btn" style="background:transparent; color:white; border:none; font-size:18px;" onclick="document.getElementById('pwaInstallBanner').style.display='none'">×</button>
        </div>
    </div>

    <!-- PWA Push Notification Request Banner -->
    <div id="pwaNotificationBanner" style="display:none; background-color:#3b82f6; color:white; padding:10px 16px; align-items:center; justify-content:space-between; z-index:899; position:sticky; top:0;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="font-size:20px;">🔔</div>
            <div style="font-size:13px;">Включите Push-уведомления для мгновенного оповещения о новых заявках и авариях</div>
        </div>
        <div style="display:flex; gap:8px;">
            <button class="btn" style="background:white; color:#3b82f6; padding:4px 10px; font-size:12px;" onclick="app.requestNotificationPermission()">Включить</button>
            <button class="btn" style="background:transparent; color:white; border:none; font-size:16px;" onclick="document.getElementById('pwaNotificationBanner').style.display='none'">×</button>
        </div>
    </div>

    <div class="app-container">

        <!-- PC Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">🏥</div>
                <div>
                    <div class="brand-title">МедСервис</div>
                    <div style="font-size: 11px; color: var(--text-muted);" id="hospitalBrandSub">Больница</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a class="nav-item active" data-view="dashboard">
                    <span>🏠</span> <span>Главная</span>
                </a>
                <a class="nav-item" data-view="requests">
                    <span>📋</span> <span>Заявки</span>
                    <span class="nav-badge" id="badgeRequests" style="display:none;">0</span>
                </a>
                <a class="nav-item" data-view="create-request">
                    <span>➕</span> <span>Создать заявку</span>
                </a>
                <a class="nav-item" data-view="chats">
                    <span>💬</span> <span>Чаты</span>
                    <span class="nav-badge" id="badgeChats" style="display:none;">0</span>
                </a>
                <a class="nav-item" data-view="directory">
                    <span>☎</span> <span>Телефонный справочник</span>
                </a>
                <a class="nav-item" data-view="employees">
                    <span>👥</span> <span>Сотрудники</span>
                </a>
                <a class="nav-item" data-view="services">
                    <span>🏢</span> <span>Службы</span>
                </a>
                <a class="nav-item" data-view="notifications">
                    <span>🔔</span> <span>Уведомления</span>
                    <span class="nav-badge" id="badgeNotifications" style="display:none;">0</span>
                </a>
                <a class="nav-item" data-view="analytics" id="navAnalytics">
                    <span>📊</span> <span>Аналитика</span>
                </a>
                <a class="nav-item" data-view="settings">
                    <span>⚙</span> <span>Настройки</span>
                </a>

                <div style="margin-top: 20px; padding: 0 8px;">
                    <button class="btn btn-danger" style="width:100%; justify-content:center;" onclick="app.triggerEmergency()">
                        🚨 АВАРИЯ
                    </button>
                </div>
            </nav>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">

            <!-- Top Header Bar -->
            <header class="top-header">
                <div class="user-greeting">
                    <h2 id="greetingText">Добрый день!</h2>
                    <p id="greetingSubText">Городская Клиническая Больница</p>
                </div>

                <div class="header-actions">
                    <button class="btn btn-outline btn-sm" id="themeToggleBtn" onclick="app.toggleTheme()">
                        🌙 Темная тема
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="app.openProfileModal()">
                        👤 <span id="headerUserName">Профиль</span>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="app.logout()">
                        🚪 Выход
                    </button>
                </div>
            </header>

            <!-- VIEW 1: DASHBOARD -->
            <section id="view-dashboard" class="app-view">
                <!-- Status Counters Grid -->
                <div class="stats-grid">
                    <div class="stat-card" onclick="app.switchView('requests', {status: 'Новая'})">
                        <div class="stat-info">
                            <div class="stat-val" id="cntNew">0</div>
                            <div class="stat-label">Новые заявки</div>
                        </div>
                        <div class="stat-icon stat-new">🔵</div>
                    </div>

                    <div class="stat-card" onclick="app.switchView('requests', {status: 'В работе'})">
                        <div class="stat-info">
                            <div class="stat-val" id="cntInProgress">0</div>
                            <div class="stat-label">В работе</div>
                        </div>
                        <div class="stat-icon stat-progress">🟡</div>
                    </div>

                    <div class="stat-card" onclick="app.switchView('requests', {status: 'Выполнено'})">
                        <div class="stat-info">
                            <div class="stat-val" id="cntCompleted">0</div>
                            <div class="stat-label">Выполнено</div>
                        </div>
                        <div class="stat-icon stat-completed">🟢</div>
                    </div>

                    <div class="stat-card" onclick="app.switchView('requests', {priority: 'Аварийный'})">
                        <div class="stat-info">
                            <div class="stat-val" id="cntEmergency">0</div>
                            <div class="stat-label">Аварийные</div>
                        </div>
                        <div class="stat-icon stat-emergency">🔴</div>
                    </div>
                </div>

                <!-- Fast Services Cards -->
                <div class="section-title">
                    <span>Часто используемые службы</span>
                    <a href="#" style="font-size:13px; color:var(--primary); text-decoration:none;" onclick="app.switchView('directory')">Весь справочник →</a>
                </div>
                <div class="services-scroll" id="fastServicesContainer">
                    <!-- Service cards rendered dynamically -->
                </div>

                <!-- Recent Requests List -->
                <div class="section-title" style="margin-top:20px;">
                    <span>Последние заявки</span>
                    <button class="btn btn-primary btn-sm" onclick="app.switchView('create-request')">+ Создать заявку</button>
                </div>
                <div id="recentRequestsContainer">
                    <!-- Request cards rendered dynamically -->
                </div>
            </section>

            <!-- VIEW 2: REQUESTS LIST -->
            <section id="view-requests" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>📋 Список заявок</span>
                    <button class="btn btn-primary btn-sm" onclick="app.switchView('create-request')">+ Создать заявку</button>
                </div>

                <!-- Filters -->
                <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; width:100%;">
                    <select id="filterStatus" class="form-select" style="width:auto; flex:1; min-width:140px;" onchange="app.loadRequests()">
                        <option value="">Все статусы</option>
                        <option value="Новая">🔵 Новые</option>
                        <option value="Принято">🟡 Принято</option>
                        <option value="В исполнении">🟠 В исполнении</option>
                        <option value="Выполнено">🟢 Выполнено</option>
                    </select>

                    <select id="filterPriority" class="form-select" style="width:auto; flex:1; min-width:140px;" onchange="app.loadRequests()">
                        <option value="">Все приоритеты</option>
                        <option value="Обычный">Обычный</option>
                        <option value="Важный">Важный</option>
                        <option value="Срочный">Срочный</option>
                        <option value="Аварийный">🚨 Аварийный</option>
                    </select>

                    <button class="btn btn-outline" onclick="app.exportRequestsCSV()" id="btnExportCSV">📥 Экспорт CSV</button>
                </div>

                <div id="requestsListContainer">
                    <!-- Dynamic requests list -->
                </div>
            </section>

            <!-- VIEW 3: CREATE REQUEST -->
            <section id="view-create-request" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>➕ Создание заявки</span>
                </div>

                <div style="background-color:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); max-width:700px; width:100%;">
                    <form id="createRequestForm" onsubmit="app.submitCreateRequest(event)">

                        <div class="form-group">
                            <label class="form-label">Категория проблемы</label>
                            <select id="reqCategory" class="form-select" required onchange="app.onCategoryChange(this.value)">
                                <option value="Электрика">⚡ Электрика</option>
                                <option value="Сантехника">🚰 Сантехника</option>
                                <option value="Отопление">🔥 Отопление</option>
                                <option value="Уборка">🧹 Уборка</option>
                                <option value="Территория">🌳 Территория</option>
                                <option value="Ремонт помещений">🛠 Ремонт помещений</option>
                                <option value="Мебель">🪑 Мебель</option>
                                <option value="Оборудование">🩺 Оборудование</option>
                                <option value="IT">💻 IT-служба</option>
                                <option value="Другое">🔧 Другое</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Местоположение</label>
                            <div class="form-row">
                                <input type="text" id="reqBuilding" class="form-input" placeholder="Корпус №1" required>
                                <input type="text" id="reqFloor" class="form-input" placeholder="Этаж 2" required>
                                <input type="text" id="reqRoom" class="form-input" placeholder="Кабинет 205" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Описание проблемы</label>
                            <textarea id="reqDescription" class="form-textarea" rows="4" placeholder="Опишите детально, что именно произошло..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Фотографии (до нескольких штук)</label>
                            <input type="file" id="reqPhotos" class="form-input" accept="image/jpeg,image/png,image/webp" multiple>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Приоритет</label>
                            <select id="reqPriority" class="form-select" required>
                                <option value="Обычный">🟢 Обычный</option>
                                <option value="Важный">🟡 Важный</option>
                                <option value="Срочный">🟠 Срочный</option>
                                <option value="Аварийный">🔴 Аварийный</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:16px;">
                            ОТПРАВИТЬ ЗАЯВКУ
                        </button>
                    </form>
                </div>
            </section>

            <!-- VIEW 4: DIRECTORY / PHONEBOOK -->
            <section id="view-directory" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>☎ Телефонный справочник служб</span>
                </div>

                <div class="form-group">
                    <input type="text" id="directorySearchInput" class="form-input" placeholder="Введите службу, фамилию или телефон..." oninput="app.filterDirectory(this.value)">
                </div>

                <div id="directoryServicesGrid" class="stats-grid">
                    <!-- Directory services cards -->
                </div>
            </section>

            <!-- VIEW 5: CHATS -->
            <section id="view-chats" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>💬 Чаты и сообщения</span>
                </div>

                <div class="chat-window">
                    <div style="padding:14px; border-bottom:1px solid var(--border-color); font-weight:bold; display:flex; justify-content:space-between; align-items:center;" id="activeChatTitle">
                        <span>Общий чат сотрудников</span>
                        <select id="chatRoomSelector" class="form-select" style="width:auto; padding:4px 8px; font-size:12px;" onchange="app.selectChat(this.value, this.options[this.selectedIndex].text)">
                            <option value="1">Общий чат</option>
                        </select>
                    </div>
                    <div class="chat-messages" id="chatMessagesArea">
                        <!-- Messages -->
                    </div>
                    <div class="chat-input-bar">
                        <input type="text" id="chatInputText" class="form-input" placeholder="Напишите сообщение..." onkeydown="if(event.key==='Enter') app.sendChatMessage()">
                        <button class="btn btn-primary" onclick="app.sendChatMessage()">Отправить</button>
                    </div>
                </div>
            </section>

            <!-- VIEW 6: EMPLOYEES -->
            <section id="view-employees" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>👥 Список сотрудников</span>
                </div>
                <div id="employeesListContainer">
                    <!-- Employees grid -->
                </div>
            </section>

            <!-- VIEW 7: SERVICES MANAGEMENT -->
            <section id="view-services" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>🏢 Службы больницы</span>
                </div>
                <div id="servicesListContainer">
                    <!-- Services management -->
                </div>
            </section>

            <!-- VIEW 8: NOTIFICATIONS -->
            <section id="view-notifications" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>🔔 Центр уведомлений</span>
                </div>
                <div id="notificationsListContainer">
                    <!-- Notifications -->
                </div>
            </section>

            <!-- VIEW 9: ANALYTICS -->
            <section id="view-analytics" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>📊 Аналитика и статистика</span>
                </div>
                <div id="analyticsChartsContainer">
                    <!-- Analytics dashboard -->
                </div>
            </section>

            <!-- VIEW 10: SETTINGS & BACKUP -->
            <section id="view-settings" class="app-view" style="display:none;">
                <div class="section-title">
                    <span>⚙ Расширенные настройки системы</span>
                </div>
                <div style="background-color:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); max-width:800px; width:100%;">
                    <form id="adminSettingsForm" onsubmit="app.saveAdminSettings(event)">
                        <h3 style="margin-bottom:12px;">🏥 Информация об учреждении</h3>
                        <div class="form-group">
                            <label class="form-label">Название больницы / учреждения</label>
                            <input type="text" id="settingHospitalName" class="form-input" required placeholder="ГКБ №1 МедСервис">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Телефон справочной</label>
                                <input type="text" id="settingHospitalPhone" class="form-input" placeholder="+375 17 222-33-44">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Аварийный номер</label>
                                <input type="text" id="settingEmergencyContact" class="form-input" placeholder="+375 29 111-00-00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Адрес учреждения</label>
                            <input type="text" id="settingHospitalAddress" class="form-input" placeholder="г. Минск, ул. Больничная 10">
                        </div>

                        <hr style="border:none; border-top:1px solid var(--border-color); margin:20px 0;">

                        <h3 style="margin-bottom:12px;">⏱ Параметры SLA и Регламента</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Реакция на аварийную заявку (мин)</label>
                                <input type="number" id="settingSlaEmergencyMins" class="form-input" value="10" min="1" max="120">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Время выполнения обычной заявки (часов)</label>
                                <input type="number" id="settingSlaNormalHours" class="form-input" value="24" min="1" max="168">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Макс. размер файла фото (МБ)</label>
                            <input type="number" id="settingMaxUploadMb" class="form-input" value="10" min="1" max="50">
                        </div>

                        <div style="margin-top:16px;">
                            <button type="submit" class="btn btn-primary">💾 Сохранить параметры</button>
                        </div>
                    </form>

                    <hr style="border:none; border-top:1px solid var(--border-color); margin:24px 0;">

                    <h3>💾 Резервное копирование и аудит</h3>
                    <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">
                        Экспорт и восстановление базы данных JSON
                    </p>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <button class="btn btn-primary" onclick="app.downloadBackup()">💾 Скачать бэкап JSON</button>
                        <button class="btn btn-secondary" onclick="app.loadAuditLogs()">📜 Журнал аудита</button>
                    </div>
                    <div id="auditLogsContainer" style="margin-top:16px; font-size:12px; max-height:200px; overflow-y:auto; display:none;"></div>
                </div>
            </section>

        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a class="mobile-nav-item active" data-view="dashboard">
            <span class="mobile-nav-icon">🏠</span>
            <span>Главная</span>
        </a>
        <a class="mobile-nav-item" data-view="requests">
            <span class="mobile-nav-icon">📋</span>
            <span>Заявки</span>
        </a>
        <a class="mobile-nav-item" onclick="app.switchView('create-request')">
            <div class="mobile-create-btn">➕</div>
        </a>
        <a class="mobile-nav-item" data-view="chats">
            <span class="mobile-nav-icon">💬</span>
            <span>Чаты</span>
        </a>
        <a class="mobile-nav-item" data-view="directory">
            <span class="mobile-nav-icon">☎</span>
            <span>Службы</span>
        </a>
        <a class="mobile-nav-item" onclick="app.openProfileModal()">
            <span class="mobile-nav-icon">👤</span>
            <span>Профиль</span>
        </a>
    </nav>

    <!-- MODAL: ONBOARDING CAROUSEL -->
    <div id="onboardingModal" class="modal-overlay">
        <div class="modal-container" style="text-align:center;">
            <div id="onboardingSlide1" class="onboarding-slide">
                <div style="font-size:60px; margin-bottom:12px;">🏥</div>
                <h2>Все заявки в одном приложении</h2>
                <p style="color:var(--text-muted); margin:12px 0 24px;">Удобное управление хозяйственно-техническими задачами больницы.</p>
                <button class="btn btn-primary" style="width:100%;" onclick="app.nextOnboardingSlide(2)">Далее →</button>
            </div>
            <div id="onboardingSlide2" class="onboarding-slide" style="display:none;">
                <div style="font-size:60px; margin-bottom:12px;">⚡</div>
                <h2>Быстро сообщайте о неисправностях</h2>
                <p style="color:var(--text-muted); margin:12px 0 24px;">Создавайте заявки за 30 секунд с фотографией и указанием кабинета.</p>
                <button class="btn btn-primary" style="width:100%;" onclick="app.nextOnboardingSlide(3)">Далее →</button>
            </div>
            <div id="onboardingSlide3" class="onboarding-slide" style="display:none;">
                <div style="font-size:60px; margin-bottom:12px;">💬</div>
                <h2>Общайтесь с коллегами</h2>
                <p style="color:var(--text-muted); margin:12px 0 24px;">Встроенные общие, личные чаты и обсуждение внутри заявок.</p>
                <button class="btn btn-primary" style="width:100%;" onclick="app.nextOnboardingSlide(4)">Далее →</button>
            </div>
            <div id="onboardingSlide4" class="onboarding-slide" style="display:none;">
                <div style="font-size:60px; margin-bottom:12px;">☎</div>
                <h2>Всегда под рукой справочник</h2>
                <p style="color:var(--text-muted); margin:12px 0 24px;">Прямые звонки в электрику, сантехнику, IT и руководству в 1 клик.</p>
                <button class="btn btn-primary" style="width:100%;" onclick="app.finishOnboarding()">НАЧАТЬ РАБОТУ</button>
            </div>
        </div>
    </div>

    <!-- MODAL: FIRST LAUNCH INSTALLER SETUP -->
    <div id="installerModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">🏥 Первоначальная настройка</div>
            </div>
            <form onsubmit="app.submitInstaller(event)">
                <div class="form-group">
                    <label class="form-label">Название медицинского учреждения</label>
                    <input type="text" id="setupHospitalName" class="form-input" value="Городская Клиническая Больница «МедСервис»" required>
                </div>
                <div class="form-group">
                    <label class="form-label">ФИО Главного администратора</label>
                    <input type="text" id="setupAdminName" class="form-input" value="Сергей Иванов" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Номер мобильного телефона (Логин)</label>
                    <input type="tel" id="setupAdminPhone" class="form-input" value="+375291234567" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Пароль (6 цифр)</label>
                    <input type="password" id="setupAdminPassword" class="form-input" value="123456" maxlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">
                    Создать систему «МедСервис»
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: AUTHENTICATION / LOGIN -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-container" style="max-width:400px;">
            <div class="modal-header">
                <div class="modal-title">🔐 Авторизация</div>
            </div>
            <form onsubmit="app.submitLogin(event)">
                <div class="form-group">
                    <label class="form-label">Номер телефона</label>
                    <input type="tel" id="loginPhone" class="form-input" placeholder="+375291234567" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Пароль (6 цифр)</label>
                    <input type="password" id="loginPassword" class="form-input" placeholder="123456" required>
                </div>
                <div style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="loginRememberMe" checked style="width:16px; height:16px; cursor:pointer;">
                    <label for="loginRememberMe" style="font-size:13px; cursor:pointer; user-select:none;">Запомнить меня</label>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; margin-bottom:12px;">Войти</button>
            </form>
            <div style="text-align:center;">
                <a href="#" style="font-size:13px; color:var(--primary);" onclick="app.showRegisterModal()">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </div>
    </div>

    <!-- MODAL: REGISTRATION -->
    <!-- Modal 5: Permissions Matrix Modal -->
    <div id="permissionsModal" class="modal-overlay">
        <div class="modal-container" style="max-width:520px;">
            <div class="modal-header">
                <div class="modal-title">🔑 Управление правами сотрудника</div>
                <button class="modal-close" onclick="app.hidePermissionsModal()">×</button>
            </div>
            <form id="permissionsForm" onsubmit="app.saveUserPermissions(event)">
                <input type="hidden" id="permUserId">
                <p id="permUserName" style="font-weight:bold; margin-bottom:12px; color:var(--primary);"></p>
                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_create_requests"> ➕ Размещать заявки
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_view_all_requests"> 📋 Видеть все заявки (иначе только свои)
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_assign_executors"> 👷 Назначать исполнителей
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_change_status"> 🔄 Изменять статусы заявок
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_manage_directories"> 🛠 Управлять службами и оборудованием
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_manage_users"> 👥 Управлять пользователями и правами
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_view_analytics"> 📊 Просматривать аналитику
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_chat_access"> 💬 Доступ к корпоративным чатам
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                        <input type="checkbox" id="perm_export_backup"> 💾 Скачивать экспорт и резервные копии
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">💾 Сохранить права</button>
            </form>
        </div>
    </div>

    <div id="registerModal" class="modal-overlay">
        <div class="modal-container" style="max-width:480px;">
            <div class="modal-header">
                <div class="modal-title">📝 Регистрация сотрудника</div>
                <button class="modal-close" onclick="app.hideRegisterModal()">×</button>
            </div>
            <form onsubmit="app.submitRegister(event)">
                <div class="form-group">
                    <label class="form-label">ФИО</label>
                    <input type="text" id="regName" class="form-input" placeholder="Иванов Иван Иванович" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Номер мобильного телефона</label>
                    <input type="tel" id="regPhone" class="form-input" placeholder="+375291234567" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Подразделение</label>
                    <input type="text" id="regDept" class="form-input" placeholder="Терапевтическое отделение" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Должность</label>
                    <input type="text" id="regPos" class="form-input" placeholder="Врач-терапевт" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Пароль (6 цифр)</label>
                    <input type="password" id="regPassword" class="form-input" placeholder="123456" maxlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Зарегистрироваться</button>
            </form>
        </div>
    </div>

    <!-- Application Script -->
    <script src="assets/js/app.js"></script>
    <script>
        // Register PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js').then((reg) => {
                    console.log('ServiceWorker registered with scope: ', reg.scope);
                }).catch((err) => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }

        // Online / Offline Status Event Listeners
        window.addEventListener('online', () => {
            document.getElementById('offlineBanner').classList.remove('active');
        });
        window.addEventListener('offline', () => {
            document.getElementById('offlineBanner').classList.add('active');
        });
    </script>
</body>
</html>
