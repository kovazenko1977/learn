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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Unbounded:wght@400;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap');
        :root {
            --bg-deep: #0a0b10;
            --bg-card: #14151f;
            --bg-accent: #1c1d29;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --brand: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-deep); color: var(--text-main); }
        h1, h2, .brand { font-family: 'Unbounded', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .sidebar-item-active { background: var(--brand); color: white; box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }
        .card-dark { background: var(--bg-card); border: 1px solid rgba(255,255,255,0.05); }
        .input-dark { background: var(--bg-accent); border: 1px solid rgba(255,255,255,0.1); color: white; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen flex overflow-hidden">

    <div id="alerts-container" class="fixed top-20 right-6 z-[100] flex flex-col space-y-3 max-w-sm"></div>

    <?php if ($user): ?>
    <aside class="w-72 border-r border-white/5 bg-[#0e0f17] flex flex-col p-8 z-50">
        <div class="brand text-2xl font-black tracking-tighter text-white mb-12 flex items-center">
            <span class="mr-3">🍷</span> ALCO.PRO
        </div>
        <nav class="space-y-2 flex-grow">
            <button onclick="showPage('dashboard')" id="nav-dashboard" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-layout-grid text-lg"></i> <span>Сводка</span>
            </button>
            <?php if ($auth->hasPermission('raw_materials_view')): ?>
            <button onclick="showPage('warehouse')" id="nav-warehouse" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-package text-lg"></i> <span>Склад</span>
            </button>
            <?php endif; ?>
            <?php if ($auth->hasPermission('production_log')): ?>
            <button onclick="showPage('production')" id="nav-production" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-factory text-lg"></i> <span>Цех</span>
            </button>
            <?php endif; ?>
            <button onclick="showPage('products')" id="nav-products" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-wine text-lg"></i> <span>Продукция</span>
            </button>
            <button onclick="showPage('orders')" id="nav-orders" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-shopping-bag text-lg"></i> <span>Заказы</span>
            </button>
            <?php if ($auth->hasPermission('*')): ?>
            <button onclick="showPage('analytics')" id="nav-analytics" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-bar-chart-3 text-lg"></i> <span>Аналитика</span>
            </button>
            <button onclick="showPage('admin')" id="nav-admin" class="nav-link w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-slate-400 hover:bg-white/5 hover:text-white">
                <i class="lucide-settings text-lg"></i> <span>Админ</span>
            </button>
            <?php endif; ?>
        </nav>
        <div class="pt-8 border-t border-white/5">
            <div class="flex items-center space-x-4 mb-6 px-2">
                <div class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center font-black text-white"><?= mb_substr($user['name'],0,1) ?></div>
                <div class="overflow-hidden">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest truncate"><?= htmlspecialchars($user['role']) ?></p>
                    <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
                </div>
            </div>
            <button onclick="logout()" class="w-full flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all text-[11px] font-bold uppercase tracking-widest text-red-400 hover:bg-red-500/10">
                <i class="lucide-log-out text-lg"></i> <span>Выход</span>
            </button>
        </div>
    </aside>
    <?php endif; ?>

    <main class="flex-grow flex flex-col h-screen relative">
        <div id="app-content" class="flex-grow overflow-y-auto p-12 custom-scroll">
            <!-- Content -->
        </div>
    </main>

    <div id="metric-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-md z-[200] flex items-center justify-center p-12">
        <div class="bg-[#14151f] p-12 rounded-[2.5rem] shadow-2xl max-w-5xl w-full border border-white/5">
            <div class="flex justify-between items-center mb-10">
                <h3 id="metric-title" class="text-2xl font-black text-white uppercase italic tracking-tighter">Metric Expansion</h3>
                <button id="close-metric" onclick="document.getElementById('metric-modal').classList.add('hidden')" class="text-slate-600 hover:text-white transition-all"><i class="lucide-x-circle text-4xl"></i></button>
            </div>
            <div id="metric-content" class="overflow-x-auto custom-scroll max-h-[70vh]"></div>
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
                case 'revenue': title.innerText = 'Sales Transaction Ledger'; action = 'get_orders'; break;
                case 'stock': title.innerText = 'Finished Goods Inventory'; action = 'get_products'; break;
                case 'orders': title.innerText = 'Pending Commitment Queue'; action = 'get_orders'; break;
                case 'production': title.innerText = 'Execution Run Archive'; action = 'get_production_logs'; break;
            }

            fetch(`api.php?action=${action}`).then(r => r.json()).then(data => {
                let html = `
                    <table class="w-full text-left text-[11px]">
                        <thead class="border-b border-white/10 opacity-40 uppercase tracking-widest font-black">
                            <tr>
                `;
                if (metric === 'production') {
                    html += `<th class="p-6">Time</th><th class="p-6">Entity</th><th class="p-6">Volume</th><th class="p-6">Batch ID</th>`;
                } else if (metric === 'revenue' || metric === 'orders') {
                    html += `<th class="p-6">ID</th><th class="p-6">Counterparty</th><th class="p-6">Value</th><th class="p-6">Status</th>`;
                } else if (metric === 'stock') {
                    html += `<th class="p-6">Entity</th><th class="p-6">SKU</th><th class="p-6">Available</th><th class="p-6">Unit Price</th>`;
                }
                html += `</tr></thead><tbody class="divide-y divide-white/5">`;

                if (metric === 'production') {
                    html += data.slice(-15).reverse().map(l => `
                        <tr>
                            <td class="p-6 text-slate-500 font-mono">${l.timestamp}</td>
                            <td class="p-6 font-bold text-white uppercase italic">${l.product_name}</td>
                            <td class="p-6 font-black text-indigo-400 mono">${l.quantity.toLocaleString()}</td>
                            <td class="p-6 font-mono text-slate-400">${l.batch}</td>
                        </tr>
                    `).join('');
                } else if (metric === 'revenue' || metric === 'orders') {
                    html += Object.values(data).slice(0, 15).reverse().map(o => `
                        <tr>
                            <td class="p-6 text-slate-500 font-mono">#${o.id.slice(-6).toUpperCase()}</td>
                            <td class="p-6 font-bold text-white uppercase">${o.client_name}</td>
                            <td class="p-6 font-black text-indigo-400 mono">${o.total.toLocaleString()} BYN</td>
                            <td class="p-6"><span class="px-3 py-1 rounded-full bg-white/5 border border-white/10 text-[9px] font-black uppercase tracking-widest">${o.status}</span></td>
                        </tr>
                    `).join('');
                } else if (metric === 'stock') {
                    html += data.map(p => `
                        <tr>
                            <td class="p-6 font-bold text-white italic">${p.name}</td>
                            <td class="p-6 font-mono text-slate-500">${p.sku}</td>
                            <td class="p-6 font-black text-indigo-400 mono">${p.quantity.toLocaleString()}</td>
                            <td class="p-6 font-black text-emerald-400 mono">${p.price} BYN</td>
                        </tr>
                    `).join('');
                }
                html += `</tbody></table>`;
                content.innerHTML = html;
            });
        }

        function showPage(page) {
            const content = document.getElementById('app-content');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('sidebar-item-active'));
            const activeNav = document.getElementById('nav-' + page);
            if (activeNav) activeNav.classList.add('sidebar-item-active');

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
                case 'analytics': renderAnalytics(content); break;
                case 'docs': renderDocs(content); break;
                default: renderDashboard(content);
            }
        }

        function renderLogin(content) {
            content.innerHTML = `
                <div class="fixed inset-0 flex items-center justify-center bg-[#0a0b10] z-[1000]">
                    <div class="max-w-md w-full p-12 bg-[#14151f] rounded-[2.5rem] border border-white/5 shadow-2xl">
                        <div class="text-center mb-12">
                            <div class="text-6xl mb-8">🏰</div>
                            <h2 class="text-3xl font-black text-white uppercase tracking-tighter italic">ALCO.PRO</h2>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mt-2">Enterprise Access Control</p>
                        </div>
                        <form onsubmit="handleLogin(event)" class="space-y-6">
                            <div class="space-y-2">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Identity</p>
                                <input type="text" name="username" placeholder="Логин" required class="w-full bg-[#1c1d29] border border-white/10 rounded-2xl px-6 py-5 outline-none focus:ring-2 focus:ring-indigo-500/50 text-white transition-all">
                            </div>
                            <div class="space-y-2">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Security Key</p>
                                <input type="password" name="password" placeholder="Пароль" required class="w-full bg-[#1c1d29] border border-white/10 rounded-2xl px-6 py-5 outline-none focus:ring-2 focus:ring-indigo-500/50 text-white transition-all">
                            </div>
                            <button type="submit" id="login-btn" class="w-full bg-indigo-600 text-white py-5 rounded-2xl font-black shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all hover:scale-[1.02] active:scale-95 uppercase tracking-widest text-xs mt-4">Инициализировать вход</button>
                        </form>
                        <div class="mt-10 flex justify-center space-x-4 opacity-30 hover:opacity-100 transition-opacity">
                             <button onclick="fillDemo('admin','admin123')" class="text-[9px] font-black text-slate-400 hover:text-indigo-400 uppercase tracking-widest">Master</button>
                             <button onclick="fillDemo('client','client123')" class="text-[9px] font-black text-slate-400 hover:text-indigo-400 uppercase tracking-widest">Node</button>
                        </div>
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
                <div class="mb-12 flex justify-between items-center">
                    <div>
                        <h1 class="text-4xl font-black text-white tracking-tight uppercase italic">Enterprise Summary</h1>
                        <p class="text-slate-500 font-bold text-[10px] uppercase tracking-[0.4em] mt-2">Core Node Status: <span class="text-emerald-500">Synchronized</span> // Network: 10.0.4.22</p>
                    </div>
                    <div class="flex space-x-3">
                         <button onclick="toggleHelpOverlay()" class="w-12 h-12 rounded-xl card-dark flex items-center justify-center text-slate-400 hover:text-white transition-all"><i class="lucide-help-circle"></i></button>
                         <button class="w-12 h-12 rounded-xl card-dark flex items-center justify-center text-slate-400 hover:text-white transition-all"><i class="lucide-bell"></i></button>
                    </div>
                </div>

                <div id="stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12"></div>

                <div id="system-status" class="mb-12 hidden"></div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 card-dark rounded-[2rem] p-10">
                        <div class="flex justify-between items-center mb-10">
                            <div>
                                <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Operational Pulse</h3>
                                <p class="text-xl font-black text-white mt-1">Производственный тренд</p>
                            </div>
                            <select class="bg-transparent border border-white/10 rounded-lg text-[10px] font-bold px-3 py-1 outline-none"><option>7 Days</option></select>
                        </div>
                        <div class="h-64 flex items-end justify-between space-x-6 px-4">
                            ${[45, 65, 55, 85, 75, 95, 80].map((h, i) => `
                                <div class="flex-grow bg-indigo-600/10 rounded-t-xl relative group transition-all hover:bg-indigo-600/40" style="height: ${h}%">
                                    <div class="absolute inset-x-0 top-0 h-1 bg-indigo-500 shadow-[0_0_15px_rgba(99,102,241,1)]"></div>
                                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] px-3 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-all font-black">${h * 15}</div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="flex justify-between mt-6 px-4 text-[10px] font-black text-slate-600 uppercase tracking-widest">
                            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                        </div>
                    </div>
                    <div class="card-dark rounded-[2rem] p-10">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-10">Security Protocol</h3>
                        <div id="events-list" class="space-y-6">
                             <div class="flex items-start space-x-4">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20"><i class="lucide-shield-check"></i></div>
                                <div><p class="font-bold text-sm text-white">System Integrity Check</p><p class="text-[10px] text-slate-500 mt-1 uppercase font-black tracking-widest">15 min ago // PASSED</p></div>
                             </div>
                             <div class="flex items-start space-x-4">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20"><i class="lucide-activity"></i></div>
                                <div><p class="font-bold text-sm text-white">Node Replication</p><p class="text-[10px] text-slate-500 mt-1 uppercase font-black tracking-widest">45 min ago // COMPLETE</p></div>
                             </div>
                        </div>
                    </div>
                </div>
            `;
            fetch('api.php?action=get_analytics').then(r => r.json()).then(data => {
                document.getElementById('stats').innerHTML = `
                    <div onclick="showMetricDetails('revenue')" class="card-dark p-8 rounded-3xl hover:border-indigo-500 transition-all cursor-pointer">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Revenue.Brutto</p>
                        <p class="text-3xl font-black text-white mt-3 mono tracking-tighter">${data.revenue.toLocaleString()}<span class="text-xs text-slate-600 ml-1 font-bold">BYN</span></p>
                    </div>
                    <div onclick="showMetricDetails('stock')" class="card-dark p-8 rounded-3xl hover:border-indigo-500 transition-all cursor-pointer">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Inventory.Assets</p>
                        <p class="text-3xl font-black text-white mt-3 mono tracking-tighter">${data.stock_value.toLocaleString()}<span class="text-xs text-slate-600 ml-1 font-bold">BYN</span></p>
                    </div>
                    <div onclick="showMetricDetails('orders')" class="card-dark p-8 rounded-3xl hover:border-indigo-500 transition-all cursor-pointer">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Order.Volume</p>
                        <p class="text-3xl font-black text-white mt-3 mono tracking-tighter">${data.order_count}<span class="text-xs text-slate-600 ml-1 font-bold">SKU</span></p>
                    </div>
                    <div onclick="showMetricDetails('production')" class="card-dark p-8 rounded-3xl hover:border-indigo-500 transition-all cursor-pointer">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Yield.Efficiency</p>
                        <p class="text-3xl font-black text-emerald-400 mt-3 mono tracking-tighter">${data.production_volume}<span class="text-xs text-slate-600 ml-1 font-bold">PCS</span></p>
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
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight uppercase">Складской Учет (Raw)</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Inventory Management & Forecasting</p>
                    </div>
                    <button onclick="exportData('warehouse')" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20">Экспорт Реестра</button>
                </div>
                <div id="rm-list" class="grid grid-cols-1 md:grid-cols-3 gap-8"></div>
            `;
            fetch('api.php?action=get_raw_materials').then(r => r.json()).then(data => {
                document.getElementById('rm-list').innerHTML = data.map(rm => `
                    <div class="card-dark p-8 rounded-3xl border border-white/5 flex justify-between items-center group hover:border-indigo-500/50 transition-all">
                        <div>
                            <p class="text-lg font-black text-white">${rm.name}</p>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">${rm.unit}</p>
                            ${currentUser.role === 'admin' || currentUser.role === 'production_chief' ? `
                                <div class="mt-4 flex space-x-4">
                                    <button onclick="editRM('${rm.id}')" class="text-[9px] font-black text-indigo-400 uppercase tracking-widest hover:text-white">Корректировка</button>
                                    <button onclick="deleteRM('${rm.id}')" class="text-[9px] font-black text-slate-600 uppercase tracking-widest hover:text-red-400">Списать</button>
                                </div>
                            ` : ''}
                        </div>
                        <div class="text-right">
                             <p class="text-3xl font-black mono ${rm.quantity < rm.min_quantity ? 'text-red-500' : 'text-white'}">${rm.quantity.toLocaleString()}</p>
                             <p class="text-[9px] font-black ${rm.quantity < rm.min_quantity ? 'text-red-500' : 'text-slate-600'} uppercase tracking-widest mt-1">Stock Level</p>
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
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight uppercase">Производственный Цех (Shop Floor)</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Line Operations & Batch Execution</p>
                    </div>
                    <div class="flex space-x-4">
                        <button onclick="exportData('production')" class="bg-white/5 border border-white/10 text-slate-300 px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition-all">Export BOM Report</button>
                        <button onclick="showProduceModal()" class="bg-emerald-600 text-white px-10 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all">+ Запуск Линии</button>
                    </div>
                </div>
                <div id="prod-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] flex items-center justify-center p-6">
                    <div class="bg-[#14151f] p-12 rounded-[2.5rem] border border-white/5 shadow-2xl max-w-xl w-full">
                        <h3 class="text-2xl font-black mb-10 text-white uppercase italic">Параметры выпуска</h3>
                        <form onsubmit="handleProduce(event)" class="space-y-6">
                            <div class="space-y-2">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Product Assignment</p>
                                <select name="pid" class="w-full bg-[#1c1d29] border border-white/10 rounded-2xl px-6 py-5 outline-none text-white font-bold"></select>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Batch Size (Units)</p>
                                    <input type="number" name="qty" value="500" class="w-full bg-[#1c1d29] border border-white/10 rounded-2xl px-6 py-5 outline-none text-white font-bold">
                                </div>
                                <div class="space-y-2">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Batch Identifier</p>
                                    <input type="text" name="batch" value="W-${Date.now().toString().slice(-4)}" class="w-full bg-[#1c1d29] border border-white/10 rounded-2xl px-6 py-5 outline-none text-white font-mono font-bold">
                                </div>
                            </div>
                            <div class="flex space-x-4 pt-10">
                                <button type="submit" class="flex-grow bg-indigo-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-indigo-500/20">Инициировать выпуск</button>
                                <button type="button" onclick="document.getElementById('prod-modal').classList.add('hidden')" class="px-10 py-5 bg-[#1c1d29] border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-400">Отмена</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-dark rounded-[2rem] p-10">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-10">Архив Выпуска (Последние 10 партий)</h3>
                    <div id="logs" class="space-y-4"></div>
                </div>
            `;
            fetch('api.php?action=get_production_logs').then(r => r.json()).then(data => {
                document.getElementById('logs').innerHTML = data.slice(-10).reverse().map(l => `
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-2xl border border-white/5 group hover:border-indigo-500/30 transition-all">
                        <div class="flex items-center space-x-6">
                            <div class="w-12 h-12 rounded-xl bg-[#1c1d29] border border-white/10 flex items-center justify-center text-xs font-black mono text-slate-400">#${l.batch.slice(-4)}</div>
                            <div>
                                <p class="text-sm font-black text-white uppercase italic tracking-tight">${l.product_name}</p>
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-1">${l.timestamp}</p>
                            </div>
                        </div>
                        <div class="text-right">
                             <p class="text-lg font-black text-indigo-400 mono">${l.quantity.toLocaleString()} <span class="text-[10px] text-slate-600">UNIT</span></p>
                             <div class="flex items-center justify-end space-x-2 mt-1">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                <p class="text-[9px] font-black text-emerald-500/80 uppercase tracking-widest">Validated</p>
                             </div>
                        </div>
                    </div>
                `).join('') || '<p class="text-[10px] text-slate-400 font-bold uppercase p-10 text-center">Логи не найдены</p>';
            });
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
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight uppercase italic">Finished Goods (SKU)</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Commercial Catalog & Pricing</p>
                    </div>
                    <div class="flex space-x-4 items-center">
                        ${currentUser.role === 'admin' ? `<button onclick="showProductModal()" class="bg-white/5 border border-white/10 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10">+ New SKU</button>` : ''}
                        ${currentUser.role !== 'client' ? `<button onclick="exportData('products')" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20">XLS Export</button>` : ''}
                        <div id="cart-btn" class="hidden bg-emerald-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20 cursor-pointer">
                            Cart: <span id="cc">0</span> SKU
                        </div>
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
                    <div class="card-dark rounded-3xl p-8 border border-white/5 hover:border-indigo-500/50 transition-all relative overflow-hidden group">
                        <h3 class="text-lg font-black text-white leading-tight uppercase italic tracking-tight">${p.name}</h3>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">${p.sku}</p>
                        ${currentUser.role === 'admin' ? `
                            <div class="mt-4 flex space-x-4">
                                <button onclick="editProduct('${p.id}')" class="text-[9px] font-black text-indigo-400 uppercase tracking-widest hover:text-white">Configure</button>
                                <button onclick="deleteProduct('${p.id}')" class="text-[9px] font-black text-slate-600 uppercase tracking-widest hover:text-red-500">Archive</button>
                            </div>
                        ` : ''}
                        <div class="mt-12 flex justify-between items-end">
                            <div>
                                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Price Point</p>
                                <p class="text-3xl font-black text-white tracking-tighter mono">${p.price}<span class="text-xs text-slate-500 ml-1">BYN</span></p>
                            </div>
                            ${currentUser.role === 'client' ?
                                `<button onclick="addToCart('${p.id}', '${p.name}', ${p.price})" class="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center hover:bg-white transition-all hover:text-emerald-600 shadow-lg shadow-emerald-500/20">
                                    <i class="lucide-plus"></i>
                                </button>` :
                                `<div class="text-right">
                                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Inventory</p>
                                    <p class="text-xl font-black mono ${p.quantity < 200 ? 'text-red-500' : 'text-white'}">${p.quantity.toLocaleString()}</p>
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
            content.innerHTML = `
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight uppercase italic">Order Register (Sales)</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Commercial Commitment Log</p>
                    </div>
                    <button class="bg-white/5 border border-white/10 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10">Bulk Process</button>
                </div>
                <div id="ol" class="space-y-4"></div>
            `;
            fetch('api.php?action=get_orders').then(r => r.json()).then(data => {
                document.getElementById('ol').innerHTML = Object.values(data).reverse().map(o => `
                    <div class="card-dark p-8 rounded-3xl border border-white/5 flex flex-col md:flex-row justify-between md:items-center gap-8 group hover:border-indigo-500/30 transition-all">
                        <div class="flex items-center space-x-8">
                            <div class="w-16 h-16 rounded-2xl bg-[#1c1d29] border border-white/10 flex items-center justify-center text-indigo-400 font-black text-xl mono shadow-inner">#${o.id.slice(-4).toUpperCase()}</div>
                            <div>
                                <p class="font-black text-white text-lg uppercase italic tracking-tight">${o.client_name}</p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">${o.created_at}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-12">
                             <div class="text-right">
                                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Order Total</p>
                                <p class="text-2xl font-black text-white mono">${o.total.toLocaleString()} <span class="text-xs text-slate-600">BYN</span></p>
                             </div>
                             <div class="flex items-center space-x-4 border-l border-white/5 pl-8">
                                 <div class="px-6 py-2 rounded-full text-[9px] font-black uppercase tracking-widest border ${o.status === 'pending' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : o.status === 'shipped' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'bg-rose-500/10 text-rose-500 border-rose-500/20'}">
                                    ${o.status === 'pending' ? 'Pending' : o.status === 'shipped' ? 'Shipped' : 'Void'}
                                 </div>
                                 ${(currentUser.role === 'admin' || currentUser.role === 'sales_manager') && o.status === 'pending' ?
                                    `<button onclick="updateOrderStatus('${o.id}', 'shipped')" class="bg-indigo-600 text-white w-10 h-10 rounded-xl flex items-center justify-center hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20"><i class="lucide-truck text-sm"></i></button>` : ''}
                                 ${(currentUser.role === 'admin' || currentUser.role === 'sales_manager') && o.status === 'shipped' ?
                                    `<button onclick="updateOrderStatus('${o.id}', 'returned')" class="bg-rose-500 text-white w-10 h-10 rounded-xl flex items-center justify-center hover:bg-rose-600 transition-all shadow-lg shadow-rose-500/20"><i class="lucide-rotate-ccw text-sm"></i></button>` : ''}
                             </div>
                        </div>
                    </div>
                `).join('') || '<div class="card-dark rounded-3xl p-20 text-center text-slate-500 font-black uppercase tracking-widest">No active orders detected</div>';
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
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight uppercase italic">Admin.Node (Security)</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Access Control & Protocol History</p>
                    </div>
                    <div class="flex bg-white/5 p-1 rounded-2xl border border-white/10">
                        <button onclick="renderAdmin(document.getElementById('app-content'))" class="px-6 py-2.5 bg-indigo-600 shadow-lg shadow-indigo-500/20 rounded-xl text-[10px] font-black uppercase tracking-widest text-white">Operators</button>
                        <button onclick="renderAudit(document.getElementById('app-content'))" class="px-6 py-2.5 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:text-white transition-all">Audit Trail</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="card-dark rounded-[2rem] p-10">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Authorized Personnel</h3>
                            <button onclick="showUserModal()" class="text-indigo-400 font-black text-[10px] uppercase hover:text-white">+ Add Personnel</button>
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
                    <div class="p-6 bg-white/5 border border-white/5 rounded-2xl flex justify-between items-center group hover:border-white/10 transition-all">
                        <div class="flex items-center space-x-6">
                            <div class="w-12 h-12 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-black mono text-xl italic">${u.username[0].toUpperCase()}</div>
                            <div>
                                <p class="font-bold text-white uppercase italic tracking-tighter">${u.name}</p>
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-1">${u.role}</p>
                            </div>
                        </div>
                        <span class="text-[8px] font-black text-emerald-400 border border-emerald-400/20 px-3 py-1 rounded-full bg-emerald-400/5">CONNECTED</span>
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
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight uppercase italic">Compliance & Documents</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Legal, Invoices & Certification</p>
                    </div>
                    <button class="bg-emerald-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20">EDM Sync</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="card-dark p-10 rounded-[2rem]">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-10">Financial Records</h3>
                        <div id="invoice-list" class="space-y-4">
                             <div class="p-6 bg-white/5 border border-white/5 rounded-2xl flex justify-between items-center group hover:border-indigo-500/30">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-500"><i class="lucide-file-text"></i></div>
                                    <div><p class="font-bold text-white text-sm">Invoice #INV-9402</p><p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-1">Processed // 05.03.2024</p></div>
                                </div>
                                <button class="text-indigo-400 font-black text-[10px] uppercase hover:text-white transition-all">Download</button>
                             </div>
                        </div>
                    </div>
                    <div class="card-dark p-10 rounded-[2rem]">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-10">Quality Certification</h3>
                        <div class="space-y-4">
                             <div class="p-6 bg-white/5 border border-white/5 rounded-2xl flex justify-between items-center group hover:border-emerald-500/30">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500"><i class="lucide-award"></i></div>
                                    <div><p class="font-bold text-white text-sm">ISO-9001 Compliance</p><p class="text-[9px] font-black text-emerald-500/60 uppercase tracking-widest mt-1">Active // 2024-2025</p></div>
                                </div>
                                <button class="text-indigo-400 font-black text-[10px] uppercase hover:text-white transition-all">Verify</button>
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

        function renderAnalytics(content) {
            content.innerHTML = `
                <div class="mb-12 flex justify-between items-end">
                    <div>
                        <h1 class="text-4xl font-black text-white tracking-tight uppercase italic">Intelligence Command Center</h1>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-2">Aggregate Node Analysis // Enterprise AI-Insights</p>
                    </div>
                    <div class="flex space-x-4">
                        <button onclick="exportData('full_report')" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20">Generate 1C-Level XML</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-12" id="analytics-summary"></div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                    <div class="card-dark p-10 rounded-[2rem] border border-white/5 lg:col-span-2">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-10">Financial Performance Matrix</h3>
                        <canvas id="chart-main-finance" height="350"></canvas>
                    </div>
                    <div class="card-dark p-10 rounded-[2rem] border border-white/5">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-10">Asset Allocation</h3>
                        <canvas id="chart-assets-pie" height="350"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                    <div class="card-dark p-8 rounded-2xl border border-white/5">
                        <h4 class="text-[9px] font-black text-slate-600 uppercase mb-6 tracking-widest">Yield Variance</h4>
                        <canvas id="chart-yield-line" height="150"></canvas>
                    </div>
                    <div class="card-dark p-8 rounded-2xl border border-white/5">
                        <h4 class="text-[9px] font-black text-slate-600 uppercase mb-6 tracking-widest">Order Fulfillment</h4>
                        <canvas id="chart-order-donut" height="150"></canvas>
                    </div>
                    <div class="card-dark p-8 rounded-2xl border border-white/5">
                        <h4 class="text-[9px] font-black text-slate-600 uppercase mb-6 tracking-widest">Material Consumption</h4>
                        <canvas id="chart-rm-bar" height="150"></canvas>
                    </div>
                    <div class="card-dark p-8 rounded-2xl border border-white/5">
                        <h4 class="text-[9px] font-black text-slate-600 uppercase mb-6 tracking-widest">Growth Velocity</h4>
                        <canvas id="chart-velocity" height="150"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                    <div class="card-dark p-8 rounded-[2rem] border border-white/5">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-10 italic">Inventory Attrition</h3>
                        <div id="rm-turnover" class="space-y-4"></div>
                    </div>
                    <div class="card-dark p-8 rounded-[2rem] border border-white/5 lg:col-span-2">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-10 italic">Active Production Streams</h3>
                        <div id="production-efficiency" class="grid grid-cols-2 gap-6"></div>
                    </div>
                </div>

                <div class="card-dark rounded-[2.5rem] p-12 mb-12">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.4em] mb-12 italic tracking-tighter">Enterprise SKU Performance Ledger</h3>
                    <div id="sku-finance" class="overflow-x-auto custom-scroll"></div>
                </div>
            `;

            fetch('api.php?action=get_enterprise_dashboard').then(r => r.json()).then(data => {
                const metrics = data.metrics;
                document.getElementById('analytics-summary').innerHTML = `
                    <div class="card-dark p-8 rounded-3xl border-l-4 border-indigo-500"><p class="text-[9px] font-black text-slate-500 uppercase">Gross Revenue</p><p class="text-2xl font-black text-white mt-2 mono">${metrics.revenue.toLocaleString()} <span class="text-xs text-slate-700">BYN</span></p></div>
                    <div class="card-dark p-8 rounded-3xl border-l-4 border-emerald-500"><p class="text-[9px] font-black text-slate-500 uppercase">Production Yield</p><p class="text-2xl font-black text-emerald-400 mt-2 mono">${metrics.avg_yield.toFixed(1)}%</p></div>
                    <div class="card-dark p-8 rounded-3xl border-l-4 border-amber-500"><p class="text-[9px] font-black text-slate-500 uppercase">Inventory Value</p><p class="text-2xl font-black text-white mt-2 mono">${metrics.inventory_value.toLocaleString()} <span class="text-xs text-slate-700">BYN</span></p></div>
                    <div class="card-dark p-8 rounded-3xl border-l-4 border-rose-500"><p class="text-[9px] font-black text-slate-500 uppercase">Active Commitments</p><p class="text-2xl font-black text-white mt-2 mono">${metrics.active_orders} <span class="text-xs text-slate-700">REQ</span></p></div>
                `;

                const chartCfg = (type, labels, dataset, color) => ({
                    type,
                    data: { labels, datasets: [{ data: dataset, backgroundColor: color, borderColor: color, tension: 0.4, pointRadius: 0 }] },
                    options: { plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
                });

                new Chart(document.getElementById('chart-main-finance'), {
                    type: 'line',
                    data: {
                        labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL'],
                        datasets: [{ label: 'Revenue', data: data.charts.sales_trend, borderColor: '#6366f1', tension: 0.4, fill: true, backgroundColor: 'rgba(99,102,241,0.05)' }]
                    },
                    options: { plugins: { legend: { display: false } }, scales: { y: { grid: { color: 'rgba(255,255,255,0.05)' } }, x: { grid: { display: false } } } }
                });

                new Chart(document.getElementById('chart-assets-pie'), {
                    type: 'doughnut',
                    data: { labels: ['Cider', 'Wine'], datasets: [{ data: data.charts.revenue_by_category, backgroundColor: ['#6366f1', '#10b981'], borderWidth: 0 }] },
                    options: { plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 10 } } } }, cutout: '70%' }
                });

                new Chart(document.getElementById('chart-yield-line'), chartCfg('line', [...Array(12).keys()], data.charts.waste_ratios, '#10b981'));
                new Chart(document.getElementById('chart-order-donut'), chartCfg('doughnut', ['P', 'S', 'V'], data.charts.order_status_split, ['#f59e0b', '#6366f1', '#ef4444']));
                new Chart(document.getElementById('chart-rm-bar'), chartCfg('bar', [...Array(6).keys()], data.charts.rm_distribution, '#6366f1'));
                new Chart(document.getElementById('chart-velocity'), chartCfg('line', [...Array(7).keys()], data.charts.production_volume_trend, '#8b5cf6'));

                document.getElementById('rm-turnover').innerHTML = data.reports.material_turnover.map(rm => `
                    <div class="space-y-2">
                        <div class="flex justify-between text-[10px] font-bold"><span class="text-slate-400">${rm.name}</span><span class="text-white mono">${rm.quantity}</span></div>
                        <div class="w-full h-1 bg-white/5 rounded-full overflow-hidden"><div class="h-full bg-indigo-500 shadow-[0_0_10px_#6366f1]" style="width: ${Math.min(100, (rm.quantity/rm.min_quantity)*50)}%"></div></div>
                    </div>
                `).join('');

                document.getElementById('production-efficiency').innerHTML = data.reports.production_history.slice(0, 4).map(l => `
                    <div class="p-4 bg-white/5 border border-white/10 rounded-xl">
                        <p class="text-[9px] font-black text-slate-500 uppercase mb-1">${l.batch}</p>
                        <p class="text-xs font-bold text-white mb-2 truncate">${l.product_name}</p>
                        <div class="flex items-center space-x-2"><span class="text-[10px] font-black text-emerald-400 mono">${(1-l.waste_factor)*100}% YIELD</span></div>
                    </div>
                `).join('');

                document.getElementById('sku-finance').innerHTML = `
                    <table class="w-full text-left text-[11px]">
                        <thead>
                            <tr class="border-b border-white/10 opacity-40 uppercase tracking-[0.2em] font-black">
                                <th class="pb-8">Entity Identifier</th><th class="pb-8">Commercial SKU</th><th class="pb-8 text-right">Unit Price</th><th class="pb-8 text-right">Volume Sold</th><th class="pb-8 text-right">Gross Contribution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            ${data.reports.stock_balance.map(p => `
                                <tr class="group hover:bg-white/5 transition-all">
                                    <td class="py-8 font-black text-white italic tracking-tight text-sm">${p.name}</td>
                                    <td class="py-8 font-mono text-slate-500">${p.sku}</td>
                                    <td class="py-8 text-right font-black mono text-indigo-400">${p.price} BYN</td>
                                    <td class="py-8 text-right font-black mono text-white">12,440</td>
                                    <td class="py-8 text-right font-black mono text-emerald-400">${(p.price * 12440).toLocaleString()} BYN</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            });
        }

        function renderAudit(content) {
            content.innerHTML = `
                <div class="mb-10 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Журнал Аудита</h1>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Протокол всех значимых действий в системе</p>
                    </div>
                    <div class="flex bg-slate-100 p-1 rounded-2xl border border-slate-200">
                        <button onclick="renderAdmin(document.getElementById('app-content'))" class="px-6 py-2.5 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:text-slate-700">Сотрудники</button>
                        <button onclick="renderAudit(document.getElementById('app-content'))" class="px-6 py-2.5 bg-white shadow-sm rounded-xl text-[10px] font-black uppercase tracking-widest text-indigo-600">Журнал Аудита</button>
                    </div>
                </div>
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left text-[11px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="p-6 uppercase tracking-widest font-black text-slate-500">Timestamp</th>
                                <th class="p-6 uppercase tracking-widest font-black text-slate-500">Operator</th>
                                <th class="p-6 uppercase tracking-widest font-black text-slate-500">Action</th>
                                <th class="p-6 uppercase tracking-widest font-black text-slate-500">Object ID</th>
                            </tr>
                        </thead>
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
