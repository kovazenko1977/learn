<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="manifest" href="<?= $this->url('/manifest.json') ?>">
    <title>Sanatorium 2.0 ERP - Профессиональная система управления</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?= $this->url('/public/assets/js/wm.js') ?>"></script>
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
            backdrop-filter: blur(25px) saturate(180%);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .start-menu {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(35px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 60px rgba(0, 0, 0, 0.6);
            border-radius: 16px;
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
            width: 100px;
            height: 110px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 6px;
        }
        @media (max-width: 640px) {
            .desktop-icon {
                width: 85px;
                height: 95px;
            }
            .desktop-icon i { font-size: 34px !important; }
            .desktop-icon span { font-size: 10px !important; }
        }
        .desktop-icon:hover {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            transform: translateY(-2px);
        }
        .desktop-icon i {
            font-size: 44px;
            margin-bottom: 10px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
        }
        .desktop-icon span {
            color: white;
            font-size: 11px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.6);
            font-weight: 700;
            line-height: 1.2;
            padding: 0 4px;
        }
        .window {
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
            border-radius: 16px;
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
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 10px; }
        .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="select-none text-slate-900 overflow-hidden">

    <!-- Desktop Area -->
    <div id="desktop" class="relative w-full h-[calc(100vh-56px)] p-6 flex flex-col flex-wrap content-start items-start overflow-hidden">

        <div class="desktop-icon" onclick="wm.createWindow('Реестр номеров', '<?= $this->url('/accommodation') ?>', 'fa-door-open', 'text-amber-400')">
            <i class="fas fa-door-open text-amber-400"></i>
            <span>Реестр номеров</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Шахматка заездов', '<?= $this->url('/booking') ?>', 'fa-calendar-check', 'text-emerald-400')">
            <i class="fas fa-calendar-check text-emerald-400"></i>
            <span>Бронирование</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Регистратура', '<?= $this->url('/guests') ?>', 'fa-id-card', 'text-blue-400')">
            <i class="fas fa-id-card text-blue-400"></i>
            <span>Регистратура</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Лечебный корпус', '<?= $this->url('/medical') ?>', 'fa-heart-pulse', 'text-rose-400')">
            <i class="fas fa-heart-pulse text-rose-400"></i>
            <span>Лечебный корпус</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('Кассовая книга', '<?= $this->url('/finance') ?>', 'fa-vault', 'text-indigo-400')">
            <i class="fas fa-vault text-indigo-400"></i>
            <span>Финансы</span>
        </div>

        <div class="desktop-icon" onclick="wm.createWindow('AI Ассистент', '<?= $this->url('/ai-chat') ?>', 'fa-brain', 'text-purple-400')">
            <i class="fas fa-brain text-purple-400"></i>
            <span>AI Ассистент</span>
        </div>
    </div>

    <!-- Start Menu -->
    <div id="start-menu" class="start-menu fixed bottom-16 sm:left-4 sm:w-[520px] sm:h-[650px] hidden flex-col overflow-hidden">
        <div class="p-8 border-b border-white/10 flex items-center justify-between bg-white/5">
            <div class="flex items-center space-x-5">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shadow-2xl">
                    <?= substr($user['username'] ?? 'A', 0, 1) ?>
                </div>
                <div>
                    <h3 class="text-white font-bold text-base"><?= $user['username'] ?? 'Администратор' ?></h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-0.5">Управляющий системой</p>
                </div>
            </div>
            <button onclick="window.location.reload()" class="w-12 h-12 rounded-2xl hover:bg-rose-500/20 text-rose-400 transition-all flex items-center justify-center border border-rose-500/10" title="Выход из системы">
                <i class="fas fa-power-off text-lg"></i>
            </button>
        </div>

        <div class="flex-grow grid grid-cols-2 p-6 gap-2 overflow-y-auto custom-scrollbar bg-slate-900/50">
            <?php
                $modulesMap = [
                    'Accommodation' => ['name' => 'Реестр номеров', 'icon' => 'fa-door-closed', 'color' => 'text-amber-400'],
                    'Booking' => ['name' => 'Шахматка заездов', 'icon' => 'fa-calendar-days', 'color' => 'text-emerald-400'],
                    'Guests' => ['name' => 'Картотека гостей', 'icon' => 'fa-address-book', 'color' => 'text-blue-400'],
                    'Medical' => ['name' => 'История болезни', 'icon' => 'fa-notes-medical', 'color' => 'text-rose-400'],
                    'Finance' => ['name' => 'Кассовая книга', 'icon' => 'fa-wallet', 'color' => 'text-indigo-400'],
                    'Cleaning' => ['name' => 'Диспетчер уборки', 'icon' => 'fa-broom', 'color' => 'text-sky-400'],
                    'Kitchen' => ['name' => 'Управление питанием', 'icon' => 'fa-utensils', 'color' => 'text-orange-400'],
                    'Warehouse' => ['name' => 'Товарный склад', 'icon' => 'fa-cubes-stacked', 'color' => 'text-slate-400'],
                    'Reports' => ['name' => 'Отчетность и КПИ', 'icon' => 'fa-chart-line', 'color' => 'text-blue-500'],
                    'Transport' => ['name' => 'Диспетчер авто', 'icon' => 'fa-truck-pickup', 'color' => 'text-blue-600'],
                    'Pool' => ['name' => 'Аква-комплекс', 'icon' => 'fa-droplet', 'color' => 'text-cyan-500'],
                    'Fitness' => ['name' => 'График тренировок', 'icon' => 'fa-heart-pulse', 'color' => 'text-red-500'],
                    'Security' => ['name' => 'Пост охраны', 'icon' => 'fa-user-shield', 'color' => 'text-indigo-600'],
                    'Settings' => ['name' => 'Конфигурация ERP', 'icon' => 'fa-gears', 'color' => 'text-slate-500']
                ];

                foreach ($modulesMap as $dir => $meta) {
                    echo "<button onclick=\"wm.createWindow('{$meta['name']}', '{$this->url('/' . strtolower($dir))}', '{$meta['icon']}', '{$meta['color']}'); toggleStart()\" class='flex items-center space-x-4 p-4 rounded-2xl hover:bg-white/10 text-slate-300 transition-all text-left border border-transparent hover:border-white/5'>
                        <div class='w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center flex-shrink-0 shadow-inner'>
                            <i class='fas {$meta['icon']} {$meta['color']} text-sm'></i>
                        </div>
                        <span class='text-xs font-semibold leading-tight'>{$meta['name']}</span>
                    </button>";
                }
            ?>
        </div>

        <div class="p-6 bg-black/40 border-t border-white/5 flex items-center justify-between sm:rounded-b-16">
            <div class="flex space-x-2">
                <button onclick="wm.createWindow('Настройки', '<?= $this->url('/settings') ?>', 'fa-gears', 'text-slate-400'); toggleStart()" class="w-10 h-10 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center border border-white/5"><i class="fas fa-gears text-sm"></i></button>
                <button class="w-10 h-10 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center border border-white/5"><i class="fas fa-folder-open text-sm"></i></button>
            </div>
            <div class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.3em]">Sanatorium ERP v2.0.4</div>
        </div>
    </div>

    <!-- Taskbar -->
    <footer class="h-14 w-full glass-taskbar fixed bottom-0 left-0 flex items-center px-4 z-[6000] safe-area-bottom">
        <button id="start-btn" onclick="toggleStart()" class="w-11 h-11 rounded-2xl bg-blue-600 hover:bg-blue-500 flex items-center justify-center text-white text-2xl transition-all shadow-xl shadow-blue-500/30 mr-5 active:scale-90 border border-white/10">
            <i class="fas fa-shapes"></i>
        </button>

        <div id="taskbar-icons" class="flex items-center space-x-3 flex-grow overflow-x-auto h-full py-2 no-scrollbar"></div>

        <div class="flex items-center space-x-5 pl-5 border-l border-white/10 ml-3">
            <div class="hidden sm:flex flex-col items-end justify-center text-white">
                <span id="taskbar-time" class="text-sm font-bold tracking-wider">00:00</span>
                <span id="taskbar-date" class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">01.01.2024</span>
            </div>
            <div class="w-1.5 h-9 bg-white/10 rounded-full"></div>
        </div>
    </footer>

    <script>
        function toggleStart() {
            const menu = document.getElementById('start-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                menu.classList.add('flex');
            } else {
                menu.classList.add('hidden');
                menu.classList.remove('flex');
            }
        }

        document.addEventListener('click', (e) => {
            const menu = document.getElementById('start-menu');
            if (!e.target.closest('#start-menu') && !e.target.closest('#start-btn')) {
                menu.classList.add('hidden');
                menu.classList.remove('flex');
            }
        });

        function updateClock() {
            const now = new Date();
            const timeEl = document.getElementById('taskbar-time');
            const dateEl = document.getElementById('taskbar-date');
            if (timeEl) timeEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            if (dateEl) {
                const options = { day: 'numeric', month: 'short', year: 'numeric' };
                dateEl.innerText = now.toLocaleDateString('ru-RU', options).replace('.', '').toUpperCase();
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        document.addEventListener('touchstart', (e) => {
            if (e.touches.length > 1) e.preventDefault();
        }, { passive: false });
    </script>
</body>
</html>
