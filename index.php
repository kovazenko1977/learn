<?php
require_once 'Includes/autoload.php';
$auth = new \Managers\AuthManager();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALCO.BY - Завод Плодовых Вин</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Unbounded:wght@400;700&family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        h1, h2, .brand { font-family: 'Unbounded', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .tab-active { color: #4f46e5; border-bottom: 3px solid #4f46e5; }
        .card-grad { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); }
    </style>
</head>
<body class="bg-[#f1f5f9] text-[#1e293b] min-h-screen flex flex-col">

    <div id="alerts-container" class="fixed top-20 right-6 z-[100] flex flex-col space-y-3 max-w-sm"></div>

    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-12">
                <a href="javascript:void(0)" onclick="showPage('dashboard')" class="brand text-2xl font-bold tracking-tight text-indigo-600 flex items-center">
                    <span class="mr-3 text-3xl">🍷</span> ALCO.BY
                </a>
                <?php if ($user): ?>
                <nav class="hidden lg:flex space-x-8 text-sm font-bold text-slate-500 uppercase tracking-widest">
                    <button onclick="showPage('dashboard')" id="nav-dashboard" class="nav-link h-20 transition-all hover:text-indigo-600">Главная</button>
                    <?php if ($auth->hasPermission('raw_materials_view')): ?>
                    <button onclick="showPage('warehouse')" id="nav-warehouse" class="nav-link h-20 transition-all hover:text-indigo-600">Склад</button>
                    <?php endif; ?>
                    <?php if ($auth->hasPermission('production_log')): ?>
                    <button onclick="showPage('production')" id="nav-production" class="nav-link h-20 transition-all hover:text-indigo-600">Производство</button>
                    <?php endif; ?>
                    <button onclick="showPage('products')" id="nav-products" class="nav-link h-20 transition-all hover:text-indigo-600">Продукция</button>
                    <button onclick="showPage('orders')" id="nav-orders" class="nav-link h-20 transition-all hover:text-indigo-600">Заказы</button>
                    <?php if ($user['role'] === 'client'): ?>
                    <button onclick="showPage('docs')" id="nav-docs" class="nav-link h-20 transition-all hover:text-indigo-600">Документы</button>
                    <?php endif; ?>
                    <?php if ($auth->hasPermission('*')): ?>
                    <button onclick="showPage('admin')" id="nav-admin" class="nav-link h-20 transition-all hover:text-indigo-600">Админ</button>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
            <div class="flex items-center space-x-6">
                <?php if ($user): ?>
                    <div class="text-right hidden sm:block">
                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-1"><?= htmlspecialchars($user['role']) ?></p>
                        <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($user['name']) ?></p>
                    </div>
                    <button onclick="logout()" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
                        <i class="lucide-log-out text-lg"></i>
                    </button>
                <?php else: ?>
                    <button onclick="showPage('login')" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-indigo-200 hover:bg-indigo-700 transition-all hover:-translate-y-0.5">Личный кабинет</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="app-content" class="flex-grow max-w-[1600px] mx-auto w-full px-6 py-10">
        <!-- Content -->
    </main>

    <footer class="bg-white border-t border-slate-200 py-10 mt-auto">
        <div class="max-w-[1600px] mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 items-center">
            <div class="brand text-xl font-bold text-slate-300 italic">ALCO.BY 2024</div>
            <div class="text-center text-sm text-slate-400 font-medium">Система управления производством плодовых вин v4.2</div>
            <div class="flex justify-end space-x-6 text-slate-400 text-xs font-bold uppercase tracking-widest">
                <a href="#" class="hover:text-indigo-600">Поддержка</a>
                <a href="#" class="hover:text-indigo-600">API</a>
                <a href="#" class="hover:text-indigo-600">Безопасность</a>
            </div>
        </div>
    </footer>

    <div id="metric-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[200] flex items-center justify-center p-6">
        <div class="bg-white p-12 rounded-[3rem] shadow-2xl max-w-4xl w-full border border-slate-200">
            <div class="flex justify-between items-center mb-8">
                <h3 id="metric-title" class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Детали метрики</h3>
                <button id="close-metric" onclick="document.getElementById('metric-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="lucide-x text-2xl"></i></button>
            </div>
            <div id="metric-content" class="overflow-x-auto"></div>
        </div>
    </div>

    <div id="help-overlay" class="hidden fixed inset-0 bg-indigo-900/90 backdrop-blur-xl z-[300] p-12 text-white overflow-y-auto">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-black uppercase tracking-tighter italic">Руководство Системы ALCO.BY</h2>
                <button id="close-help" onclick="toggleHelpOverlay()" class="text-white/60 hover:text-white"><i class="lucide-x-circle text-4xl"></i></button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <section>
                    <h4 class="text-indigo-300 font-black uppercase tracking-widest text-xs mb-4">Для Администратора</h4>
                    <ul class="space-y-4 text-sm font-medium">
                        <li>• <span class="text-indigo-200">Склад:</span> Полный контроль остатков сырья. Кнопка "Правка" позволяет корректировать инвентаризацию.</li>
                        <li>• <span class="text-indigo-200">Аудит:</span> Каждое действие в системе протоколируется. Проверяйте журнал в разделе Админ.</li>
                        <li>• <span class="text-indigo-200">Оповещения:</span> Используйте Глобальное Оповещение для связи с отделами.</li>
                    </ul>
                </section>
                <section>
                    <h4 class="text-indigo-300 font-black uppercase tracking-widest text-xs mb-4">Производство и Продажи</h4>
                    <ul class="space-y-4 text-sm font-medium">
                        <li>• <span class="text-indigo-200">Выпуск:</span> Запуск линии автоматически списывает сырье согласно рецептуре (BOM).</li>
                        <li>• <span class="text-indigo-200">Заказы:</span> Статус "Отгружен" резервирует и списывает готовую продукцию со склада.</li>
                        <li>• <span class="text-indigo-200">Интерактивность:</span> Кликните на любую цифру дашборда для получения расшифровки.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>

    <script>
        const currentUser = <?= json_encode($user) ?>;
        let cart = [];

        function toggleHelpOverlay() {
            document.getElementById('help-overlay').classList.toggle('hidden');
        }

        function showMetricDetails(metric) {
            const modal = document.getElementById('metric-modal');
            const title = document.getElementById('metric-title');
            const content = document.getElementById('metric-content');
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="p-20 text-center"><div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full"></div></div>';

            let action = '';
            switch(metric) {
                case 'revenue': title.innerText = 'История Выручки (Последние заказы)'; action = 'get_orders'; break;
                case 'stock': title.innerText = 'Остатки Готовой Продукции'; action = 'get_products'; break;
                case 'orders': title.innerText = 'Активные Заказы'; action = 'get_orders'; break;
                case 'production': title.innerText = 'Журнал Выпуска'; action = 'get_production_logs'; break;
            }

            fetch(`api.php?action=${action}`).then(r => r.json()).then(data => {
                if (metric === 'revenue' || metric === 'orders') {
                    content.innerHTML = `
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b">
                                <tr><th class="p-4 font-black">ID</th><th class="p-4 font-black">Клиент</th><th class="p-4 font-black">Сумма</th><th class="p-4 font-black">Статус</th></tr>
                            </thead>
                            <tbody>
                                ${Object.values(data).slice(0, 10).map(o => `
                                    <tr class="border-b">
                                        <td class="p-4 font-mono text-xs">#${o.id.slice(-4)}</td>
                                        <td class="p-4 font-bold">${o.client_name}</td>
                                        <td class="p-4 text-indigo-600 font-black">${o.total} BYN</td>
                                        <td class="p-4 uppercase text-[10px] font-black">${o.status}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else if (metric === 'stock') {
                    content.innerHTML = `
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b">
                                <tr><th class="p-4 font-black">Наименование</th><th class="p-4 font-black">Артикул</th><th class="p-4 font-black">Остаток</th><th class="p-4 font-black">Цена</th></tr>
                            </thead>
                            <tbody>
                                ${data.map(p => `
                                    <tr class="border-b">
                                        <td class="p-4 font-bold">${p.name}</td>
                                        <td class="p-4 text-slate-400">${p.sku}</td>
                                        <td class="p-4 font-black">${p.quantity} ед.</td>
                                        <td class="p-4 text-indigo-600 font-bold">${p.price} BYN</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
            });
        }

        function showPage(page) {
            const content = document.getElementById('app-content');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('tab-active'));
            const activeNav = document.getElementById('nav-' + page);
            if (activeNav) activeNav.classList.add('tab-active');

            if (!currentUser && page !== 'login') page = 'login';

            window.scrollTo({top: 0, behavior: 'smooth'});

            switch(page) {
                case 'login': renderLogin(content); break;
                case 'dashboard': renderDashboard(content); break;
                case 'warehouse': renderWarehouse(content); break;
                case 'production': renderProduction(content); break;
                case 'products': renderProducts(content); break;
                case 'orders': renderOrders(content); break;
                case 'admin': renderAdmin(content); break;
                case 'docs': renderDocs(content); break;
                default: renderDashboard(content);
            }
        }

        function renderLogin(content) {
            content.innerHTML = `
                <div class="max-w-md mx-auto mt-12 bg-white p-12 rounded-[3rem] shadow-2xl shadow-indigo-100 border border-slate-100">
                    <div class="text-center mb-12">
                        <div class="text-5xl mb-6">🏰</div>
                        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Вход в систему</h2>
                    </div>
                    <form onsubmit="handleLogin(event)" class="space-y-6">
                        <input type="text" name="username" placeholder="Логин" required class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 outline-none focus:ring-4 focus:ring-indigo-100">
                        <input type="password" name="password" placeholder="Пароль" required class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 outline-none focus:ring-4 focus:ring-indigo-100">
                        <button type="submit" id="login-btn" class="w-full bg-indigo-600 text-white py-5 rounded-[1.5rem] font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all">Авторизоваться</button>
                    </form>
                    <div class="mt-8 flex justify-center space-x-2">
                         <button onclick="fillDemo('admin','admin123')" class="text-[10px] text-slate-400 hover:text-indigo-600">Admin</button>
                         <button onclick="fillDemo('client','client123')" class="text-[10px] text-slate-400 hover:text-indigo-600">Client</button>
                    </div>
                </div>
            `;
        }

        function fillDemo(u, p) {
            document.querySelector('[name=username]').value = u;
            document.querySelector('[name=password]').value = p;
        }

        function handleLogin(e) {
            e.preventDefault();
            const body = { username: e.target.username.value, password: e.target.password.value };
            fetch('api.php?action=login', { method: 'POST', body: JSON.stringify(body) })
                .then(r => r.json()).then(res => res.success ? location.reload() : alert('Ошибка доступа'));
        }

        function logout() { fetch('api.php?action=logout').then(() => location.reload()); }

        function checkAlerts() {
            fetch('api.php?action=get_alerts').then(r => r.json()).then(data => {
                const container = document.getElementById('alerts-container');
                if (!container) return;
                container.innerHTML = data.map(a => `
                    <div class="bg-white p-5 rounded-3xl shadow-2xl border-l-8 ${a.type === 'danger' ? 'border-red-500' : 'border-amber-400'} animate-bounce glass">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">${a.category}</p>
                        <p class="text-xs font-bold mt-1 text-slate-700">${a.message}</p>
                    </div>
                `).join('');
            });
        }
        setInterval(checkAlerts, 60000);
        setTimeout(checkAlerts, 2000);

        function renderDashboard(content) {
            content.innerHTML = `
                <div id="ann-bar"></div>
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Рабочая панель</h1>
                        <p class="text-slate-400 font-medium mt-2">Оперативная сводка по предприятию [v4.2.0-PRO]</p>
                    </div>
                    <button onclick="toggleHelpOverlay()" class="text-indigo-600 font-bold text-xs uppercase tracking-widest border-b-2 border-indigo-100 hover:border-indigo-600 pb-1">Справка системы</button>
                </div>
                <div id="stats" class="grid grid-cols-1 md:grid-cols-4 gap-0 mb-12 border border-slate-200 rounded-[2rem] overflow-hidden bg-white shadow-sm"></div>

                <div id="system-status" class="mb-12 hidden"></div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                        <h3 class="text-xl font-black mb-8 text-slate-800">Динамика отгрузок</h3>
                        <div class="h-80 bg-slate-50 rounded-[2.5rem] p-10 flex items-end justify-between space-x-6">
                            <div class="w-full bg-indigo-500 rounded-t-2xl shadow-lg shadow-indigo-100" style="height: 65%"></div>
                            <div class="w-full bg-indigo-300 rounded-t-2xl" style="height: 45%"></div>
                            <div class="w-full bg-indigo-600 rounded-t-2xl shadow-lg shadow-indigo-200" style="height: 85%"></div>
                            <div class="w-full bg-emerald-400 rounded-t-2xl" style="height: 30%"></div>
                            <div class="w-full bg-indigo-500 rounded-t-2xl shadow-lg shadow-indigo-100" style="height: 70%"></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                        <h3 class="text-xl font-black mb-8 text-slate-800">События</h3>
                        <div id="events-list" class="space-y-6">
                             <div class="flex items-start space-x-4">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600"><i class="lucide-zap"></i></div>
                                <div><p class="font-bold text-sm">Линия #1 запущена</p><p class="text-xs text-slate-400">Сидр Яблочный (500 ед)</p></div>
                             </div>
                        </div>
                    </div>
                </div>
            `;
            fetch('api.php?action=get_analytics').then(r => r.json()).then(data => {
                document.getElementById('stats').innerHTML = `
                    <div onclick="showMetricDetails('revenue')" title="Кликните для просмотра истории выручки" class="p-10 border-r border-slate-100 hover:bg-slate-50 cursor-pointer transition-all relative group">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Выручка (Общая)</p>
                        <p class="text-3xl font-black text-slate-800 mt-3 group-hover:text-indigo-600 transition-colors">${data.revenue} <span class="text-xs font-bold text-slate-300 ml-1">BYN</span></p>
                        <div class="mt-4 h-1 w-12 bg-indigo-500 rounded-full"></div>
                    </div>
                    <div onclick="showMetricDetails('stock')" title="Кликните для просмотра остатков продукции" class="p-10 border-r border-slate-100 hover:bg-slate-50 cursor-pointer transition-all relative group">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Оценка Склада</p>
                        <p class="text-3xl font-black text-slate-800 mt-3 group-hover:text-indigo-600 transition-colors">${data.stock_value} <span class="text-xs font-bold text-slate-300 ml-1">BYN</span></p>
                        <div class="mt-4 h-1 w-12 bg-indigo-300 rounded-full"></div>
                    </div>
                    <div onclick="showMetricDetails('orders')" title="Кликните для просмотра активных заказов" class="p-10 border-r border-slate-100 hover:bg-slate-50 cursor-pointer transition-all relative group">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Объем Заказов</p>
                        <p class="text-3xl font-black text-slate-800 mt-3 group-hover:text-indigo-600 transition-colors">${data.order_count} <span class="text-xs font-bold text-slate-300 ml-1">ЕД.</span></p>
                        <div class="mt-4 h-1 w-12 bg-orange-400 rounded-full"></div>
                    </div>
                    <div onclick="showMetricDetails('production')" title="Кликните для просмотра журнала выпуска" class="p-10 hover:bg-slate-50 cursor-pointer transition-all relative group">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Готовая Продукция</p>
                        <p class="text-3xl font-black text-slate-800 mt-3 group-hover:text-indigo-600 transition-colors">${data.production_volume} <span class="text-xs font-bold text-slate-300 ml-1">ШТ.</span></p>
                        <div class="mt-4 h-1 w-12 bg-emerald-400 rounded-full"></div>
                    </div>
                `;

                if (currentUser.role === 'admin') {
                    const sys = document.getElementById('system-status');
                    sys.classList.remove('hidden');
                    sys.innerHTML = `
                        <div class="bg-slate-900 rounded-[2rem] p-10 text-white flex justify-between items-center shadow-2xl">
                            <div class="flex items-center space-x-12">
                                <div><p class="text-[9px] font-black uppercase text-indigo-400 tracking-widest mb-2">Статус Хранилища</p><p class="text-sm font-bold">JSON: Synchronized</p></div>
                                <div><p class="text-[9px] font-black uppercase text-indigo-400 tracking-widest mb-2">Последний Аудит</p><p class="text-sm font-bold">${new Date().toLocaleTimeString()}</p></div>
                                <div><p class="text-[9px] font-black uppercase text-indigo-400 tracking-widest mb-2">Системное время</p><p class="text-sm font-bold" id="live-clock">${new Date().toLocaleTimeString()}</p></div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></span>
                                <p class="text-[10px] font-black tracking-widest uppercase">System Online</p>
                            </div>
                        </div>
                    `;
                    setInterval(() => {
                        const cl = document.getElementById('live-clock');
                        if (cl) cl.innerText = new Date().toLocaleTimeString();
                    }, 1000);
                }
            });
            fetch('api.php?action=get_announcements').then(r => r.json()).then(data => {
                document.getElementById('ann-bar').innerHTML = data.map(a => `
                    <div class="mb-6 bg-gradient-to-r from-indigo-600 to-violet-600 text-white p-6 rounded-[2rem] flex justify-between items-center shadow-xl shadow-indigo-200">
                        <div class="flex items-center space-x-4"><span class="text-2xl">📢</span><p class="font-bold">${a.text}</p></div>
                        <p class="text-[10px] font-black opacity-60">${a.created_at}</p>
                    </div>
                `).join('');
            });
        }

        function renderWarehouse(content) {
            content.innerHTML = `
                <div class="flex justify-between items-center mb-12">
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight">Склад Сырья</h1>
                    <button onclick="exportData('warehouse')" class="text-xs font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 px-6 py-3 rounded-xl hover:bg-indigo-100 transition-all">Экспорт CSV</button>
                </div>
                <div id="rm-list" class="grid grid-cols-1 md:grid-cols-3 gap-8"></div>
            `;
            fetch('api.php?action=get_raw_materials').then(r => r.json()).then(data => {
                document.getElementById('rm-list').innerHTML = data.map(rm => `
                    <div class="card-grad p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex justify-between items-center group hover:shadow-xl transition-all">
                        <div>
                            <p class="text-lg font-black text-slate-700">${rm.name}</p>
                            <p class="text-xs font-bold text-slate-400 uppercase mt-1">${rm.unit}</p>
                            ${currentUser.role === 'admin' ? `
                                <div class="mt-4 flex space-x-2">
                                    <button onclick="editRM('${rm.id}')" class="text-[9px] font-black text-indigo-500 uppercase">Правка</button>
                                    <button onclick="deleteRM('${rm.id}')" class="text-[9px] font-black text-red-400 uppercase">Удалить</button>
                                </div>
                            ` : ''}
                        </div>
                        <div class="text-right">
                             <p class="text-3xl font-black ${rm.quantity < rm.min_quantity ? 'text-red-500' : 'text-slate-800'}">${rm.quantity}</p>
                             <p class="text-[9px] font-black ${rm.quantity < rm.min_quantity ? 'text-red-500' : 'text-slate-400'} uppercase tracking-widest">ТЕКУЩИЙ ОСТАТОК</p>
                        </div>
                    </div>
                `).join('');
            });
        }

        function editRM(id) {
            fetch('api.php?action=get_raw_materials').then(r => r.json()).then(data => {
                const rm = data.find(x => x.id === id);
                const val = prompt(`Корректировка остатка для ${rm.name} (${rm.unit}):`, rm.quantity);
                if (val !== null) {
                    fetch('api.php?action=update_raw_material', {
                        method: 'POST',
                        body: JSON.stringify({ id, quantity: parseFloat(val) })
                    }).then(() => renderWarehouse(document.getElementById('app-content')));
                }
            });
        }

        function deleteRM(id) {
            if (confirm('Вы уверены, что хотите удалить этот материал со склада?')) {
                fetch('api.php?action=delete_raw_material', {
                    method: 'POST',
                    body: JSON.stringify({ id })
                }).then(() => renderWarehouse(document.getElementById('app-content')));
            }
        }

        function renderProduction(content) {
            content.innerHTML = `
                <div class="flex justify-between items-center mb-12">
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight">Производственный Цех</h1>
                    <div class="flex space-x-4">
                        <button onclick="exportData('production')" class="text-xs font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 px-6 py-3 rounded-xl">Отчет по сырью</button>
                        <button onclick="showProduceModal()" class="bg-slate-900 text-white px-10 py-4 rounded-2xl font-bold shadow-2xl hover:bg-indigo-600 transition-all">Запуск линии</button>
                    </div>
                </div>
                <div id="prod-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center">
                    <div class="bg-white p-12 rounded-[3rem] shadow-2xl max-w-xl w-full">
                        <h3 class="text-2xl font-black mb-8">Параметры выпуска</h3>
                        <form onsubmit="handleProduce(event)" class="space-y-6">
                            <select name="pid" class="w-full bg-slate-50 p-5 rounded-2xl font-bold outline-none border-none"></select>
                            <div class="grid grid-cols-2 gap-6">
                                <input type="number" name="qty" placeholder="Кол-во" value="500" class="w-full bg-slate-50 p-5 rounded-2xl font-bold outline-none border-none">
                                <input type="text" name="batch" placeholder="Партия" value="W-${Date.now().toString().slice(-4)}" class="w-full bg-slate-50 p-5 rounded-2xl font-bold outline-none border-none">
                            </div>
                            <div class="flex space-x-4 pt-6">
                                <button type="submit" class="flex-grow bg-indigo-600 text-white py-5 rounded-2xl font-bold shadow-xl shadow-indigo-100">Начать розлив</button>
                                <button type="button" onclick="document.getElementById('prod-modal').classList.add('hidden')" class="px-10 py-5 bg-slate-100 rounded-2xl font-bold">Отмена</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                    <h3 class="text-xl font-black mb-8">Журнал производства</h3>
                    <div id="logs" class="space-y-4"></div>
                </div>
            `;
            fetch('api.php?action=get_products').then(r => r.json()).then(data => {
                document.querySelector('[name=pid]').innerHTML = data.map(p => `<option value="${p.id}">${p.name} (${p.sku})</option>`).join('');
            });
        }

        function showProduceModal() {
            document.getElementById('prod-modal').classList.remove('hidden');
            fetch('api.php?action=get_products').then(r => r.json()).then(data => {
                document.querySelector('[name=pid]').innerHTML = data.map(p => `<option value="${p.id}">${p.name} (${p.sku})</option>`).join('');
            });
        }
        function handleProduce(e) {
            e.preventDefault();
            const body = { product_id: e.target.pid.value, quantity: parseInt(e.target.qty.value), batch: e.target.batch.value };
            fetch('api.php?action=produce', { method: 'POST', body: JSON.stringify(body) }).then(r => r.json()).then(res => {
                if (res.success) { alert('Партия успешно произведена!'); location.reload(); } else alert(res.message);
            });
        }

        function renderProducts(content) {
            content.innerHTML = `
                <div class="flex justify-between items-center mb-12">
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight">Витрина Завода</h1>
                    <div class="flex space-x-4">
                        ${currentUser.role === 'admin' ? `<button onclick="showProductModal()" class="bg-slate-900 text-white px-8 py-3 rounded-xl text-xs font-bold uppercase tracking-widest">+ Товар</button>` : ''}
                        ${currentUser.role !== 'client' ? `<button onclick="exportData('products')" class="text-xs font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 px-6 py-3 rounded-xl hover:bg-indigo-100 transition-all">Экспорт CSV</button>` : ''}
                    </div>
                    <div id="cart-btn" class="hidden bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black shadow-2xl cursor-pointer hover:bg-indigo-700 transition-all">
                        🛒 Корзина: <span id="cc">0</span>
                    </div>
                </div>
                <div id="pg" class="grid grid-cols-1 md:grid-cols-4 gap-8"></div>
                <div id="cm" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-6">
                    <div class="bg-white p-12 rounded-[3rem] shadow-2xl max-w-2xl w-full">
                        <h3 class="text-2xl font-black mb-8 uppercase tracking-tighter">Ваш заказ</h3>
                        <div id="ci" class="space-y-4 mb-10 max-h-80 overflow-y-auto"></div>
                        <div class="flex space-x-4">
                            <button onclick="submitOrder()" class="flex-grow bg-emerald-600 text-white py-5 rounded-2xl font-bold shadow-xl shadow-emerald-100">Разместить заказ</button>
                            <button onclick="toggleCart()" class="px-10 py-5 bg-slate-100 rounded-2xl font-bold">Вернуться</button>
                        </div>
                    </div>
                </div>
                <div id="prod-edit-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[200] flex items-center justify-center p-6">
                     <div class="bg-white p-12 rounded-[3rem] shadow-2xl max-w-xl w-full">
                        <h3 class="text-2xl font-black mb-8" id="pem-title">Редактирование товара</h3>
                        <form onsubmit="handleProductSubmit(event)" class="space-y-4">
                            <input type="hidden" name="id">
                            <label class="block"><span class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Название</span>
                            <input type="text" name="name" placeholder="Сидр Яблочный 0.5" required class="w-full bg-slate-50 p-4 rounded-2xl outline-none mt-1"></label>
                            <label class="block"><span class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Цена (BYN)</span>
                            <input type="number" step="0.01" name="price" placeholder="4.50" required class="w-full bg-slate-50 p-4 rounded-2xl outline-none mt-1"></label>
                            <label class="block"><span class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Артикул (SKU)</span>
                            <input type="text" name="sku" placeholder="CIDER-APPLE-05" required class="w-full bg-slate-50 p-4 rounded-2xl outline-none mt-1"></label>
                            <div class="flex space-x-4 pt-6">
                                <button type="submit" class="flex-grow bg-indigo-600 text-white py-4 rounded-2xl font-bold">Сохранить</button>
                                <button type="button" onclick="document.getElementById('prod-edit-modal').classList.add('hidden')" class="px-8 py-4 bg-slate-100 rounded-2xl font-bold">Отмена</button>
                            </div>
                        </form>
                     </div>
                </div>
            `;
            fetch('api.php?action=get_products').then(r => r.json()).then(data => {
                document.getElementById('pg').innerHTML = data.map(p => `
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100 hover:shadow-2xl transition-all relative overflow-hidden group">
                        <div class="absolute -top-6 -right-6 text-9xl opacity-5 grayscale group-hover:grayscale-0 transition-all">🍷</div>
                        <h3 class="text-lg font-black text-slate-800 leading-tight">${p.name}</h3>
                        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest mt-2">${p.sku}</p>
                        ${currentUser.role === 'admin' ? `
                            <div class="mt-4 flex space-x-2">
                                <button onclick="editProduct('${p.id}')" class="text-[9px] font-black text-indigo-500 uppercase">Правка</button>
                                <button onclick="deleteProduct('${p.id}')" class="text-[9px] font-black text-red-400 uppercase">Удалить</button>
                            </div>
                        ` : ''}
                        <div class="mt-12 flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Цена</p>
                                <p class="text-4xl font-black text-slate-800 tracking-tighter">${p.price}<span class="text-sm font-medium ml-1">BYN</span></p>
                            </div>
                            ${currentUser.role === 'client' ?
                                `<button onclick="addToCart('${p.id}', '${p.name}', ${p.price})" class="w-14 h-14 bg-indigo-600 text-white rounded-3xl flex items-center justify-center hover:bg-slate-900 transition-all shadow-xl shadow-indigo-100">
                                    <i class="lucide-plus"></i>
                                </button>` :
                                `<div class="text-right">
                                    <p class="text-[10px] font-bold text-slate-300 uppercase">Остаток</p>
                                    <p class="text-xl font-black ${p.quantity < 200 ? 'text-red-500' : 'text-slate-800'}">${p.quantity} шт</p>
                                </div>`
                            }
                        </div>
                    </div>
                `).join('');
                updCart();
            });
        }

        function showProductModal() {
            const m = document.getElementById('prod-edit-modal');
            const f = m.querySelector('form');
            f.reset();
            f.id.value = '';
            document.getElementById('pem-title').innerText = 'Новый товар';
            m.classList.remove('hidden');
        }

        function editProduct(id) {
            fetch('api.php?action=get_products').then(r => r.json()).then(data => {
                const p = data.find(x => x.id === id);
                const m = document.getElementById('prod-edit-modal');
                const f = m.querySelector('form');
                f.id.value = p.id;
                f.name.value = p.name;
                f.price.value = p.price;
                f.sku.value = p.sku;
                document.getElementById('pem-title').innerText = 'Редактирование товара';
                m.classList.remove('hidden');
            });
        }

        function handleProductSubmit(e) {
            e.preventDefault();
            const f = e.target;
            const data = {
                id: f.id.value,
                name: f.name.value,
                price: parseFloat(f.price.value),
                sku: f.sku.value
            };
            const action = data.id ? 'update_product' : 'add_product';
            fetch(`api.php?action=${action}`, {
                method: 'POST',
                body: JSON.stringify(data)
            }).then(() => {
                document.getElementById('prod-edit-modal').classList.add('hidden');
                renderProducts(document.getElementById('app-content'));
            });
        }

        function deleteProduct(id) {
            if (confirm('Удалить товар из каталога?')) {
                fetch('api.php?action=delete_product', {
                    method: 'POST',
                    body: JSON.stringify({ id })
                }).then(() => renderProducts(document.getElementById('app-content')));
            }
        }

        function addToCart(id, name, price) {
            const i = cart.find(x => x.id === id);
            if (i) i.qty++; else cart.push({id, name, price, qty: 1});
            updCart();
        }

        function updCart() {
            const btn = document.getElementById('cart-btn');
            const cc = document.getElementById('cc');
            if (btn && cart.length > 0) {
                btn.classList.remove('hidden');
                btn.onclick = toggleCart;
                cc.innerText = cart.reduce((s, x) => s + x.qty, 0);
            }
        }

        function toggleCart() {
            const m = document.getElementById('cm');
            m.classList.toggle('hidden');
            document.getElementById('ci').innerHTML = cart.map(x => `
                <div class="flex justify-between items-center p-6 bg-slate-50 rounded-3xl">
                    <div><p class="font-black text-slate-700">${x.name}</p><p class="text-xs text-slate-400">${x.qty} ед. x ${x.price} BYN</p></div>
                    <p class="text-xl font-black text-indigo-600">${(x.qty * x.price).toFixed(2)}</p>
                </div>
            `).join('');
        }

        function submitOrder() {
            fetch('api.php?action=add_order', { method: 'POST', body: JSON.stringify({ items: cart }) })
                .then(r => r.json()).then(res => { alert('Заказ принят в обработку!'); cart = []; location.reload(); });
        }

        function renderOrders(content) {
            content.innerHTML = `<h1 class="text-4xl font-black text-slate-800 mb-12 tracking-tight">Журнал Заказов</h1><div id="ol" class="space-y-4"></div>`;
            fetch('api.php?action=get_orders').then(r => r.json()).then(data => {
                document.getElementById('ol').innerHTML = Object.values(data).map(o => `
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between md:items-center gap-6">
                        <div class="flex items-center space-x-6">
                            <div class="w-16 h-16 rounded-3xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-xl">#${o.id.slice(-4).toUpperCase()}</div>
                            <div>
                                <p class="font-black text-slate-800 text-lg">${o.client_name}</p>
                                <p class="text-xs font-bold text-slate-400">${o.created_at}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-12">
                             <div class="text-center">
                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Сумма</p>
                                <p class="text-2xl font-black text-slate-800">${o.total} <span class="text-sm font-medium">BYN</span></p>
                             </div>
                             <div class="flex items-center space-x-3">
                                 <div class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] ${o.status === 'pending' ? 'bg-amber-100 text-amber-600' : o.status === 'shipped' ? 'bg-indigo-100 text-indigo-600' : 'bg-rose-100 text-rose-600'}">
                                    ${o.status === 'pending' ? 'Обработка' : o.status === 'shipped' ? 'Отгружен' : 'Возврат'}
                                 </div>
                                 ${(currentUser.role === 'admin' || currentUser.role === 'sales_manager') && o.status === 'pending' ?
                                    `<button onclick="updateOrderStatus('${o.id}', 'shipped')" class="bg-indigo-600 text-white p-2 rounded-xl hover:bg-indigo-700 transition-all"><i class="lucide-truck text-xs"></i></button>` : ''}
                                 ${(currentUser.role === 'admin' || currentUser.role === 'sales_manager') && o.status === 'shipped' ?
                                    `<button onclick="updateOrderStatus('${o.id}', 'returned')" class="bg-rose-500 text-white p-2 rounded-xl hover:bg-rose-600 transition-all"><i class="lucide-undo-2 text-xs"></i></button>` : ''}
                             </div>
                        </div>
                    </div>
                `).join('') || '<div class="p-20 text-center text-slate-400 font-bold">Заказы не найдены</div>';
            });
        }

        function updateOrderStatus(id, status) {
            if(!confirm(`Сменить статус заказа на "${status}"?`)) return;
            fetch('api.php?action=update_order_status', {
                method: 'POST',
                body: JSON.stringify({ id, status })
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    alert('Статус обновлен!');
                    renderOrders(document.getElementById('app-content'));
                } else alert(res.message);
            });
        }

        function renderAdmin(content) {
            content.innerHTML = `
                <h1 class="text-4xl font-black text-slate-800 mb-12 tracking-tight">Управление Системой</h1>
                <div class="flex space-x-4 mb-8">
                    <button onclick="renderAdmin(document.getElementById('app-content'))" class="px-6 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest">Персонал</button>
                    <button onclick="renderAudit(document.getElementById('app-content'))" class="px-6 py-2 bg-white text-slate-600 rounded-xl text-xs font-bold uppercase tracking-widest border">Аудит Действий</button>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-xl font-black">Сотрудники и Доступ</h3>
                            <button onclick="showUserModal()" class="text-indigo-600 font-bold hover:underline">+ Новый</button>
                        </div>
                        <div id="prod-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-6">
                            <!-- Using prod-modal id for consistency with existing css styles if any, but better use unique for user -->
                        </div>
                        <div id="user-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-6">
                            <div class="bg-white p-12 rounded-[3rem] shadow-2xl max-w-xl w-full">
                                <h3 class="text-2xl font-black mb-8">Новый пользователь</h3>
                                <form onsubmit="handleCreateUser(event)" class="space-y-4">
                                    <input type="text" name="name" placeholder="Полное имя" required class="w-full bg-slate-50 p-4 rounded-2xl outline-none">
                                    <input type="text" name="username" placeholder="Логин" required class="w-full bg-slate-50 p-4 rounded-2xl outline-none">
                                    <input type="password" name="password" placeholder="Пароль" required class="w-full bg-slate-50 p-4 rounded-2xl outline-none">
                                    <select name="role" class="w-full bg-slate-50 p-4 rounded-2xl outline-none">
                                        <option value="admin">Администратор</option>
                                        <option value="sales_manager">Менеджер по продажам</option>
                                        <option value="production_chief">Начальник производства</option>
                                        <option value="client">Клиент</option>
                                    </select>
                                    <div class="flex space-x-4 pt-6">
                                        <button type="submit" class="flex-grow bg-indigo-600 text-white py-4 rounded-2xl font-bold">Создать</button>
                                        <button type="button" onclick="document.getElementById('user-modal').classList.add('hidden')" class="px-8 py-4 bg-slate-100 rounded-2xl font-bold">Отмена</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div id="ul" class="space-y-4"></div>
                    </div>
                    <div class="bg-indigo-900 rounded-[3rem] p-10 shadow-2xl text-white">
                        <h3 class="text-xl font-black mb-8 italic">Глобальное Оповещение</h3>
                        <p class="text-xs text-indigo-300 mb-4 font-medium uppercase tracking-widest leading-relaxed">Рассылка сообщений во все отделы предприятия через главную панель дашборда.</p>
                        <select id="ann-target" class="w-full bg-indigo-800/50 rounded-2xl p-4 border-none text-white outline-none mb-4 font-bold">
                            <option value="all">Для всех</option>
                            <option value="sales_manager">Только Отдел Продаж</option>
                            <option value="production_chief">Только Производство</option>
                            <option value="client">Только Клиенты</option>
                        </select>
                        <textarea id="ann-text" class="w-full bg-indigo-800/50 rounded-2xl p-6 border-none text-white outline-none focus:ring-4 focus:ring-indigo-500 mb-6" rows="4" placeholder="Текст сообщения..."></textarea>
                        <button onclick="sendAnn()" class="w-full bg-white text-indigo-900 py-5 rounded-2xl font-black shadow-xl hover:scale-[1.02] transition-all">Опубликовать в систему</button>
                    </div>
                </div>
            `;
            fetch('api.php?action=get_users').then(r => r.json()).then(data => {
                document.getElementById('ul').innerHTML = data.map(u => `
                    <div class="p-6 bg-slate-50 rounded-3xl flex justify-between items-center group hover:bg-white hover:shadow-xl transition-all">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-600 font-black">${u.username[0].toUpperCase()}</div>
                            <div><p class="font-black text-slate-800">${u.name}</p><p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mt-0.5">${u.role}</p></div>
                        </div>
                        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">ACTIVE</span>
                    </div>
                `).join('');
            });
        }

        function showUserModal() { document.getElementById('user-modal').classList.remove('hidden'); }

        function handleCreateUser(e) {
            e.preventDefault();
            const f = e.target;
            const data = { name: f.name.value, username: f.username.value, password: f.password.value, role: f.role.value, permissions: [] };
            // Simple default permissions based on role
            if (data.role === 'admin') data.permissions = ['*'];
            else if (data.role === 'client') data.permissions = ['products_view', 'orders_create', 'orders_view_own'];

            fetch('api.php?action=add_user', { method: 'POST', body: JSON.stringify(data) }).then(() => {
                alert('Пользователь создан!');
                document.getElementById('user-modal').classList.add('hidden');
                renderAdmin(document.getElementById('app-content'));
            });
        }

        function renderDocs(content) {
            content.innerHTML = `
                <h1 class="text-4xl font-black text-slate-800 mb-12 tracking-tight">Центр Документации</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100">
                        <h3 class="text-xl font-black mb-6">Счета и Накладные</h3>
                        <div id="invoice-list" class="space-y-4">
                             <div class="p-6 bg-slate-50 rounded-3xl flex justify-between items-center">
                                <div><p class="font-bold">Счет-фактура #INV-9402</p><p class="text-xs text-slate-400">От 05.03.2024</p></div>
                                <button class="text-indigo-600 font-bold text-sm hover:underline">PDF</button>
                             </div>
                        </div>
                    </div>
                    <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100">
                        <h3 class="text-xl font-black mb-6">Сертификаты Качества</h3>
                        <div class="space-y-4">
                             <div class="p-6 bg-slate-50 rounded-3xl flex justify-between items-center">
                                <div><p class="font-bold">Декларация на Сидр Яблочный</p><p class="text-[10px] text-emerald-500 font-black uppercase">ДЕЙСТВУЕТ</p></div>
                                <button class="text-indigo-600 font-bold text-sm hover:underline">Скачать</button>
                             </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function exportData(type) {
            alert(`Подготовка экспорта: ${type}...`);
            const action = type === 'warehouse' ? 'get_raw_materials' : 'get_products';
            fetch('api.php?action=' + action).then(r => r.json()).then(data => {
                const headers = Object.keys(data[0]).join(';');
                const rows = data.map(obj => Object.values(obj).join(';')).join('\n');
                const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers + "\n" + rows;
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `${type}_export_${Date.now()}.csv`);
                document.body.appendChild(link);
                link.click();
            });
        }

        function renderAudit(content) {
            content.innerHTML = `
                <h1 class="text-4xl font-black text-slate-800 mb-12 tracking-tight">Журнал Аудита</h1>
                <div class="flex space-x-4 mb-8">
                    <button onclick="renderAdmin(document.getElementById('app-content'))" class="px-6 py-2 bg-white text-slate-600 rounded-xl text-xs font-bold uppercase tracking-widest border">Персонал</button>
                    <button onclick="renderAudit(document.getElementById('app-content'))" class="px-6 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest">Аудит Действий</button>
                </div>
                <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead><tr class="bg-slate-50 border-b"><th class="p-6 uppercase tracking-widest font-black text-slate-400">Время</th><th class="p-6 uppercase tracking-widest font-black text-slate-400">Юзер</th><th class="p-6 uppercase tracking-widest font-black text-slate-400">Действие</th><th class="p-6 uppercase tracking-widest font-black text-slate-400">Объект</th></tr></thead>
                        <tbody id="audit-table"></tbody>
                    </table>
                </div>
            `;
            fetch('api.php?action=get_audit').then(r => r.json()).then(data => {
                document.getElementById('audit-table').innerHTML = data.reverse().map(l => `
                    <tr class="border-b hover:bg-slate-50 transition-colors">
                        <td class="p-6 text-slate-400">${l.timestamp}</td>
                        <td class="p-6 font-bold text-indigo-600">${l.user}</td>
                        <td class="p-6 uppercase font-black tracking-tighter">${l.action}</td>
                        <td class="p-6 text-slate-600">${l.collection} (${l.item_id.slice(0,8)}...)</td>
                    </tr>
                `).join('');
            });
        }

        function sendAnn() {
            const text = document.getElementById('ann-text').value;
            const target = document.getElementById('ann-target').value;
            if(!text) return;
            fetch('api.php?action=add_announcement', { method: 'POST', body: JSON.stringify({ text, target }) }).then(() => {
                alert('Объявление опубликовано!');
                showPage('dashboard');
            });
        }

        showPage(currentUser ? 'dashboard' : 'login');

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(null, null);
            });
        }
    </script>
</body>
</html>
