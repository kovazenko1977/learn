<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="manifest" href="<?= $this->url('/manifest.json') ?>">
    <title><?= $title ?? 'Sanatorium 2.0' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&q=80&w=3540&ixlib=rb-4.0.3') center/cover no-repeat;
            overflow: hidden;
            height: 100vh;
            width: 100vw;
        }
        .glass-taskbar {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .start-menu {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            display: none;
            z-index: 5000;
        }
        @media (max-width: 640px) {
            .start-menu {
                width: 100vw;
                height: calc(100vh - 56px);
                bottom: 56px;
                left: 0;
                border-radius: 0;
            }
        }
        .desktop-icon {
            width: 90px;
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin: 4px;
        }
        @media (max-width: 640px) {
            .desktop-icon {
                width: 80px;
                height: 90px;
            }
            .desktop-icon i { font-size: 32px !important; }
            .desktop-icon span { font-size: 10px !important; }
        }
        .desktop-icon:hover {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
        }
        .desktop-icon i {
            font-size: 42px;
            margin-bottom: 8px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .desktop-icon span {
            color: white;
            font-size: 11px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            font-weight: 600;
            line-height: 1.2;
        }
        .window {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s;
        }
        @media (max-width: 640px) {
            .window {
                border-radius: 0 !important;
                width: 100% !important;
                height: calc(100vh - 56px) !important;
                top: 0 !important;
                left: 0 !important;
            }
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }

        /* Mobile fixes */
        .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="select-none text-slate-900 overflow-hidden">

    <!-- Desktop Area -->
    <div id="desktop" class="relative w-full h-[calc(100vh-56px)] p-4 flex flex-col flex-wrap content-start items-start overflow-hidden">

        <div class="desktop-icon" onclick="wm.createWindow('Размещение', '<?= $this->url('/accommodation') ?>', 'fa-door-open', 'text-amber-400')">
            <i class="fas fa-door-open text-amber-400"></i>
            <span>Размещение</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Бронирование', '<?= $this->url('/booking') ?>', 'fa-calendar-check', 'text-emerald-400')">
            <i class="fas fa-calendar-check text-emerald-400"></i>
            <span>Бронирование</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Гости', '<?= $this->url('/guests') ?>', 'fa-users', 'text-blue-400')">
            <i class="fas fa-users text-blue-400"></i>
            <span>Гости</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Медицина', '<?= $this->url('/medical') ?>', 'fa-heart-pulse', 'text-rose-400')">
            <i class="fas fa-heart-pulse text-rose-400"></i>
            <span>Медицина</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Финансы', '<?= $this->url('/finance') ?>', 'fa-vault', 'text-indigo-400')">
            <i class="fas fa-vault text-indigo-400"></i>
            <span>Финансы</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('AI Центр', '<?= $this->url('/ai-chat') ?>', 'fa-brain', 'text-purple-400')">
            <i class="fas fa-brain text-purple-400"></i>
            <span>AI Центр</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Настройки', '<?= $this->url('/settings') ?>', 'fa-sliders', 'text-slate-400')">
            <i class="fas fa-sliders text-slate-400"></i>
            <span>Настройки</span>
        </div>
    </div>

    <!-- Start Menu -->
    <div id="start-menu" class="start-menu fixed bottom-16 sm:left-4 sm:w-[480px] sm:h-[600px] flex flex-col overflow-hidden">
        <div class="p-6 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold shadow-lg">
                    <?= substr($user['username'] ?? 'A', 0, 1) ?>
                </div>
                <div>
                    <h3 class="text-white font-bold text-sm"><?= $user['username'] ?? 'Администратор' ?></h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Sanatorium 2.0 Pro</p>
                </div>
            </div>
            <button onclick="window.location.reload()" class="p-3 rounded-xl hover:bg-white/10 text-rose-400 transition-colors">
                <i class="fas fa-power-off"></i>
            </button>
        </div>

        <div class="flex-grow grid grid-cols-2 p-4 gap-1 overflow-y-auto custom-scrollbar">
            <?php
                $translations = [
                    'Accommodation' => 'Размещение',
                    'Booking' => 'Бронирование',
                    'Medical' => 'Медицина',
                    'Finance' => 'Финансы',
                    'AI' => 'AI Помощник',
                    'Settings' => 'Настройки',
                    'Guests' => 'База гостей',
                    'Reports' => 'Отчетность',
                    'Analytics' => 'Аналитика',
                    'Services' => 'Доп. услуги',
                    'Kitchen' => 'Питание/Меню',
                    'Cleaning' => 'Уборка номеров',
                    'Warehouse' => 'Складской учет',
                    'HR' => 'Кадры'
                ];

                $modulesPath = __DIR__ . '/../../../modules';
                $dirs = array_diff(scandir($modulesPath), ['.', '..']);
                sort($dirs);
                foreach ($dirs as $dir) {
                    $displayName = $translations[$dir] ?? $dir;
                    // Skip technical/redundant folders for cleaner UI
                    if (str_starts_with($dir, 'V') || strlen($dir) < 3) continue;

                    echo "<button onclick=\"wm.createWindow('$displayName', '{$this->url('/' . strtolower($dir))}', 'fa-cube', 'text-blue-400'); toggleStart()\" class='flex items-center space-x-3 p-3 rounded-xl hover:bg-white/5 text-slate-300 transition-all text-left'>
                        <div class='w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center flex-shrink-0'>
                            <i class='fas fa-cube text-blue-400 text-xs'></i>
                        </div>
                        <span class='text-xs font-medium truncate'>$displayName</span>
                    </button>";
                }
            ?>
        </div>

        <div class="p-4 bg-black/20 border-t border-white/5 flex items-center justify-between sm:rounded-b-12">
            <div class="flex space-x-1">
                <button class="p-2 text-slate-400 hover:text-white transition-colors"><i class="fas fa-gear text-sm"></i></button>
                <button class="p-2 text-slate-400 hover:text-white transition-colors"><i class="fas fa-folder text-sm"></i></button>
            </div>
            <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Build 2024.1.2-stable</div>
        </div>
    </div>

    <!-- Taskbar -->
    <footer class="h-14 w-full glass-taskbar fixed bottom-0 left-0 flex items-center px-4 z-[6000] safe-area-bottom">
        <button id="start-btn" onclick="toggleStart()" class="w-10 h-10 rounded-xl bg-blue-600 hover:bg-blue-500 flex items-center justify-center text-white text-xl transition-all shadow-lg shadow-blue-500/20 mr-4 active:scale-90">
            <i class="fas fa-shapes"></i>
        </button>

        <div id="taskbar-icons" class="flex items-center space-x-2 flex-grow overflow-x-auto h-full py-2 scrollbar-hide no-scrollbar"></div>

        <div class="flex items-center space-x-4 pl-4 border-l border-white/10 ml-2">
            <div class="hidden sm:flex flex-col items-end justify-center text-white">
                <span id="taskbar-time" class="text-sm font-bold">00:00</span>
                <span id="taskbar-date" class="text-[10px] text-slate-400">01.01.2024</span>
            </div>
            <div class="w-1 h-8 bg-white/10 rounded-full"></div>
        </div>
    </footer>

    <script src="<?= $this->url('/public/assets/js/wm.js') ?>"></script>
    <script>
        function toggleStart() {
            const menu = document.getElementById('start-menu');
            const isVisible = menu.style.display === 'flex';
            menu.style.display = isVisible ? 'none' : 'flex';
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#start-menu') && !e.target.closest('#start-btn')) {
                document.getElementById('start-menu').style.display = 'none';
            }
        });

        function updateClock() {
            const now = new Date();
            const timeEl = document.getElementById('taskbar-time');
            const dateEl = document.getElementById('taskbar-date');
            if (timeEl) timeEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            if (dateEl) dateEl.innerText = now.toLocaleDateString();
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Prevent double tap zoom on mobile
        document.addEventListener('touchstart', (e) => {
            if (e.touches.length > 1) e.preventDefault();
        }, { passive: false });
    </script>
</body>
</html>
