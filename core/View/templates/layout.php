<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="<?= $this->url('/manifest.json') ?>">
    <meta name="theme-color" content="#2563eb">
    <title><?= $title ?? 'Sanatorium 2.0' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-link.active { background: linear-gradient(to right, #eff6ff, #ffffff); border-right: 4px solid #2563eb; color: #1e40af; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .vibrant-gradient { background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); }
        @media (max-width: 768px) {
            .sidebar-open { transform: translateX(0) !important; }
            .sidebar-closed { transform: translateX(-100%) !important; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 bg-white border-r flex flex-col fixed md:relative h-full z-40 transition-transform duration-300 sidebar-closed md:transform-none shadow-2xl md:shadow-none">
        <div class="p-8 flex items-center space-x-4">
            <div class="w-12 h-12 vibrant-gradient rounded-2xl flex items-center justify-center text-white shadow-xl shadow-blue-200">
                <i class="fas fa-microchip text-2xl"></i>
            </div>
            <span class="text-2xl font-extrabold tracking-tight text-gray-900">VSPRINT <span class="text-blue-600">2.0</span></span>
        </div>

        <div class="px-8 mb-6">
            <div class="relative">
                <input type="text" id="moduleSearch" onkeyup="filterModules()" class="w-full bg-gray-50 border-none rounded-2xl py-3 pl-10 text-xs focus:ring-2 focus:ring-blue-500" placeholder="Поиск модулей...">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-[10px]"></i>
            </div>
        </div>

        <nav id="moduleList" class="flex-grow px-6 space-y-1 py-2 overflow-y-auto custom-scrollbar">
            <a href="<?= $this->url('/') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-th-large w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Дашборд</span>
            </a>
            <div class="pt-4 pb-2 text-[10px] font-bold text-gray-400 uppercase px-4 tracking-[0.2em]">Основные</div>
            <a href="<?= $this->url('/accommodation') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-door-open w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Размещение</span>
            </a>
            <a href="<?= $this->url('/booking') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-calendar-check w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Бронирование</span>
            </a>
            <a href="<?= $this->url('/medical') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-notes-medical w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Медицина</span>
            </a>
            <a href="<?= $this->url('/finance') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-wallet w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Финансы</span>
            </a>

            <div class="pt-4 pb-2 text-[10px] font-bold text-gray-400 uppercase px-4 tracking-[0.2em]">Все модули</div>
            <div class="space-y-1" id="dynamicModules">
                <?php
                    $modulesConfig = $config->get('modules', []);
                    $modulesPath = __DIR__ . '/../../../modules';
                    $essential = ['Accommodation', 'Booking', 'Medical', 'Finance', 'Settings', 'AI', 'Reports', 'Guests', 'Notifications', 'Help', 'Loyalty', 'Transport', 'Inventory'];
                    $dirs = array_diff(scandir($modulesPath), ['.', '..']);
                    foreach ($dirs as $dir) {
                        if (in_array($dir, $essential)) continue;
                        $isEnabled = !isset($modulesConfig[$dir]) || $modulesConfig[$dir] === true;
                        if (!$isEnabled) continue;
                        echo "<a href='{$this->url('/' . strtolower($dir))}' class='sidebar-link flex items-center px-4 py-2 text-gray-500 rounded-xl hover:bg-gray-50 transition-all group module-item' data-name='".strtolower($dir)."'>
                            <i class='fas fa-cube w-6 text-gray-300 group-hover:text-blue-400 text-xs'></i>
                            <span class='ml-3 text-xs font-semibold'>$dir</span>
                        </a>";
                    }
                ?>
            </div>

            <div class="pt-4 pb-2 text-[10px] font-bold text-gray-400 uppercase px-4 tracking-[0.2em]">Система</div>
            <a href="<?= $this->url('/settings') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-cog w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Настройки</span>
            </a>
            <a href="<?= $this->url('/help') ?>" class="sidebar-link flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-50 transition-all group">
                <i class="fas fa-question-circle w-6 text-gray-400 group-hover:text-blue-500"></i>
                <span class="ml-3 font-bold text-sm">Справка</span>
            </a>
        </nav>

        <div class="p-4 border-t bg-gray-50">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold border-2 border-white shadow-sm">
                    <?= substr($user['username'] ?? 'A', 0, 1) ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-800 leading-none"><?= $user['username'] ?? 'Администратор' ?></p>
                    <p class="text-xs text-gray-500 mt-1 uppercase tracking-tighter">Главный врач</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-grow flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="h-20 bg-white border-b flex items-center justify-between px-4 md:px-10 z-10 shadow-sm glass">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="md:hidden mr-4 w-10 h-10 flex items-center justify-center bg-gray-100 rounded-xl text-gray-600">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-none"><?= $title ?></h1>
                    <div class="flex items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-1"></i> Санаторий "Солнечный" <span class="mx-2">•</span> <i class="fas fa-calendar-alt text-purple-500 mr-1"></i> <?= date('d M Y') ?>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-6">
                    <button onclick="toggleDarkMode()" class="p-2 text-gray-400 hover:text-blue-500 transition-colors">
                        <i class="fas fa-moon"></i>
                    </button>
                <div class="relative hidden sm:block">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="bg-gray-100 border-transparent focus:bg-white focus:ring-2 focus:ring-blue-500 rounded-full pl-10 pr-4 py-2 text-sm w-64 transition-all" placeholder="Поиск гостей, броней...">
                </div>
                <div class="flex items-center space-x-3">
                    <button class="p-2 text-gray-400 hover:text-blue-500 transition-colors relative">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <button onclick="toggleAI()" class="p-2 text-gray-400 hover:text-blue-500 transition-colors">
                        <i class="fas fa-robot"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-grow overflow-y-auto p-4 md:p-10 custom-scrollbar">
            <?= $content ?>
        </main>
    </div>

    <!-- AI Panel -->
    <div id="aiPanel" class="fixed bottom-6 right-6 w-96 bg-white rounded-3xl shadow-2xl border border-gray-100 hidden z-50 overflow-hidden transform transition-all translate-y-4">
        <div class="bg-blue-600 p-6 text-white flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-robot text-2xl"></i>
                <span class="font-bold">AI Помощник VSPRINT</span>
            </div>
            <button onclick="toggleAI()"><i class="fas fa-times"></i></button>
        </div>
        <div id="aiChat" class="h-80 overflow-y-auto p-6 space-y-4 text-sm scroll-smooth">
            <div class="bg-blue-50 p-4 rounded-2xl rounded-tl-none text-blue-800">
                Здравствуйте! Я ваш AI-ассистент. Чем я могу помочь сегодня?
            </div>
        </div>
        <div class="p-4 bg-gray-50 border-t flex space-x-2">
            <input id="aiInput" type="text" class="flex-grow border-none bg-white rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Ваша команда...">
            <button onclick="sendAI()" class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-100">
                <i class="fas fa-paper-plane text-xs"></i>
            </button>
        </div>
    </div>

    <script>
        function filterModules() {
            const input = document.getElementById('moduleSearch');
            const filter = input.value.toLowerCase();
            const items = document.querySelectorAll('.module-item');

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(filter)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        }

        // Module Toggler Logic
        function toggleModule(moduleName, isEnabled) {
            fetch('<?= $this->url('/api/settings/toggle-module') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({module: moduleName, enabled: isEnabled})
            }).then(() => window.location.reload());
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-open');
            sidebar.classList.toggle('sidebar-closed');
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= $this->url('/sw.js') ?>');
        }

        function toggleDarkMode() {
            document.body.classList.toggle('bg-gray-900');
            document.body.classList.toggle('text-white');
            document.querySelectorAll('.bg-white').forEach(el => {
                el.classList.toggle('bg-gray-800');
                el.classList.toggle('border-gray-700');
            });
            document.querySelectorAll('.text-gray-800').forEach(el => el.classList.toggle('text-gray-100'));
        }

        function toggleAI() {
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('hidden');
        }

        async function sendAI() {
            const input = document.getElementById('aiInput');
            const chat = document.getElementById('aiChat');
            const command = input.value;
            if(!command) return;

            chat.innerHTML += `<div class="bg-gray-100 p-4 rounded-2xl rounded-tr-none text-gray-800 self-end text-right ml-12">${command}</div>`;
            input.value = '';

            const res = await fetch('<?= $this->url('/api/ai/command') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({command})
            });
            const data = await res.json();

            chat.innerHTML += `<div class="bg-blue-50 p-4 rounded-2xl rounded-tl-none text-blue-800 mr-12 animate-pulse">Думаю...</div>`;
            chat.scrollTop = chat.scrollHeight;

            setTimeout(() => {
                chat.lastChild.remove();
                chat.innerHTML += `<div class="bg-blue-50 p-4 rounded-2xl rounded-tl-none text-blue-800 mr-12">${data.answer}</div>`;
                chat.scrollTop = chat.scrollHeight;
                if(data.action) {
                    // Logic for navigation can be added here
                }
            }, 800);
        }

        document.getElementById('aiInput')?.addEventListener('keypress', (e) => {
            if(e.key === 'Enter') sendAI();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname;
            document.querySelectorAll('.sidebar-link').forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
