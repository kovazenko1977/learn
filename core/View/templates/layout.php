<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="<?= $this->url('/manifest.json') ?>">
    <title>VSPRINT 2.0 - Рабочий стол</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&q=80&w=3540&ixlib=rb-4.0.3') center/cover no-repeat;
            overflow: hidden;
            height: 100vh;
        }
        .glass-taskbar {
            background: rgba(45, 108, 191, 0.4);
            backdrop-filter: blur(25px) saturate(180%);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
        }
        .start-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid #71a3d9;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
            border-radius: 8px 8px 0 0;
            display: none;
            z-index: 1000;
        }
        .start-button {
            background: radial-gradient(circle, #5ca9fb 0%, #2d6cbf 100%);
            box-shadow: 0 0 10px rgba(92, 169, 251, 0.5);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .start-button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(92, 169, 251, 0.8);
        }
        .desktop-icon {
            width: 90px;
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            margin: 10px;
        }
        .desktop-icon:hover {
            background: rgba(255, 255, 255, 0.2);
            outline: 1px solid rgba(255, 255, 255, 0.3);
        }
        .desktop-icon i {
            font-size: 42px;
            margin-bottom: 8px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }
        .desktop-icon span {
            color: white;
            font-size: 11px;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.8);
            font-weight: 500;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
</head>
<body class="select-none">

    <!-- Desktop Area -->
    <div id="desktop" class="relative w-full h-[calc(100vh-48px)] p-4 flex flex-col flex-wrap content-start">
        <!-- System Icons -->
        <div class="desktop-icon" onclick="wm.createWindow('Размещение', '<?= $this->url('/accommodation') ?>', 'fa-door-open')">
            <i class="fas fa-door-open"></i>
            <span>Размещение</span>
        </div>
        <div class="desktop-icon" onclick="wm.createWindow('Бронирование', '<?= $this->url('/booking') ?>', 'fa-calendar-check')">
            <i class="fas fa-calendar-check"></i>
            <span>Бронирование</span>
        </div>
        <div class="desktop-icon" onclick="wm.createWindow('Медицина', '<?= $this->url('/medical') ?>', 'fa-notes-medical')">
            <i class="fas fa-notes-medical"></i>
            <span>Медицина</span>
        </div>
        <div class="desktop-icon" onclick="wm.createWindow('Финансы', '<?= $this->url('/finance') ?>', 'fa-wallet')">
            <i class="fas fa-wallet"></i>
            <span>Финансы</span>
        </div>
        <div class="desktop-icon" onclick="wm.createWindow('Помощник AI', '<?= $this->url('/ai-chat') ?>', 'fa-robot')">
            <i class="fas fa-robot text-blue-300"></i>
            <span>AI Ассистент</span>
        </div>
        <div class="desktop-icon" onclick="wm.createWindow('Настройки', '<?= $this->url('/settings') ?>', 'fa-cog')">
            <i class="fas fa-cog text-gray-300"></i>
            <span>Настройки</span>
        </div>
    </div>

    <!-- Start Menu -->
    <div id="start-menu" class="start-menu fixed bottom-12 left-0 w-[420px] h-[550px] grid grid-cols-5 overflow-hidden">
        <div class="col-span-3 p-4 bg-white">
            <div class="flex items-center space-x-3 mb-6 p-2">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold border border-gray-200">
                    <?= substr($user['username'] ?? 'A', 0, 1) ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-800"><?= $user['username'] ?? 'Администратор' ?></p>
                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Главный врач</p>
                </div>
            </div>
            <div class="space-y-1 overflow-y-auto h-[400px] custom-scrollbar">
                <?php
                    $modulesPath = __DIR__ . '/../../../modules';
                    $dirs = array_diff(scandir($modulesPath), ['.', '..']);
                    foreach ($dirs as $dir) {
                        echo "<button onclick=\"wm.createWindow('$dir', '{$this->url('/' . strtolower($dir))}', 'fa-cube'); toggleStart()\" class='w-full text-left px-3 py-2 hover:bg-blue-50 rounded flex items-center space-x-3 group transition-colors'>
                            <i class='fas fa-cube text-gray-400 group-hover:text-blue-500 text-xs'></i>
                            <span class='text-xs font-medium text-gray-700'>$dir</span>
                        </button>";
                    }
                ?>
            </div>
        </div>
        <div class="col-span-2 bg-[#d9e7f9] border-l border-[#b1cbe5] p-4 flex flex-col">
            <div class="flex-grow space-y-4">
                <button class="w-full text-left text-xs font-semibold text-[#1e395b] hover:underline">Документы</button>
                <button class="w-full text-left text-xs font-semibold text-[#1e395b] hover:underline">Изображения</button>
                <button class="w-full text-left text-xs font-semibold text-[#1e395b] hover:underline">Музыка</button>
                <div class="h-px bg-blue-200 my-2"></div>
                <button class="w-full text-left text-xs font-semibold text-[#1e395b] hover:underline">Панель управления</button>
                <button class="w-full text-left text-xs font-semibold text-[#1e395b] hover:underline">Устройства и принтеры</button>
                <button class="w-full text-left text-xs font-semibold text-[#1e395b] hover:underline">Справка и поддержка</button>
            </div>
            <div class="mt-auto pt-4 border-t border-blue-200">
                <button onclick="window.location.reload()" class="w-full bg-gradient-to-b from-[#ebf3fe] to-[#cfe3ff] border border-[#a1c1e8] py-1.5 rounded text-[10px] font-bold text-[#1e395b] flex items-center justify-center space-x-2 shadow-sm">
                    <i class="fas fa-power-off text-red-500"></i>
                    <span>Завершение сеанса</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Taskbar -->
    <footer class="h-12 w-full glass-taskbar fixed bottom-0 left-0 flex items-center px-1 z-[2000]">
        <button id="start-btn" onclick="toggleStart()" class="start-button w-10 h-10 rounded-full flex items-center justify-center text-white text-xl mr-2">
            <i class="fab fa-windows"></i>
        </button>

        <div id="taskbar-icons" class="flex items-center space-x-1 flex-grow overflow-x-auto h-full px-2">
            <!-- Active windows will appear here -->
        </div>

        <div class="h-full flex items-center px-4 border-l border-white/10 space-x-4">
            <div class="flex flex-col items-center justify-center text-white">
                <span id="taskbar-time" class="text-[11px] font-bold leading-none">00:00</span>
                <span id="taskbar-date" class="text-[9px] opacity-70 leading-tight">01.01.2024</span>
            </div>
            <div class="w-2 h-10 border-l border-white/20"></div>
        </div>
    </footer>

    <script src="<?= $this->url('/public/assets/js/wm.js') ?>"></script>
    <script>
        function toggleStart() {
            const menu = document.getElementById('start-menu');
            const btn = document.getElementById('start-btn');
            if (menu.style.display === 'grid') {
                menu.style.display = 'none';
            } else {
                menu.style.display = 'grid';
            }
        }

        // Close start menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#start-menu') && !e.target.closest('#start-btn')) {
                document.getElementById('start-menu').style.display = 'none';
            }
        });

        // Clock Update
        function updateClock() {
            const now = new Date();
            document.getElementById('taskbar-time').innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            document.getElementById('taskbar-date').innerText = now.toLocaleDateString();
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Handle initial load
        window.addEventListener('load', () => {
             // Optional: open dashboard on start
             // wm.createWindow('Центр управления', '<?= $this->url('/dashboard-api') ?>', 'fa-chart-line');
        });
    </script>
</body>
</html>
