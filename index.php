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

    <script>
        const currentUser = <?= json_encode($user) ?>;
        let cart = [];

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
                <div class="mb-12">
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight">Рабочая панель</h1>
                    <p class="text-slate-400 font-medium mt-2">Оперативная сводка по предприятию</p>
                </div>
                <div id="stats" class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12"></div>
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
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 text-7xl opacity-5">💰</div>
                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Выручка</p>
                        <p class="text-3xl font-black text-slate-800 mt-2">${data.revenue} <span class="text-sm font-medium text-slate-400">BYN</span></p>
                    </div>
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 text-7xl opacity-5">🍾</div>
                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Продукция</p>
                        <p class="text-3xl font-black text-indigo-600 mt-2">${data.stock_value} <span class="text-sm font-medium text-slate-400">BYN</span></p>
                    </div>
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
                         <div class="absolute -right-4 -bottom-4 text-7xl opacity-5">📦</div>
                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Заказов</p>
                        <p class="text-3xl font-black text-orange-500 mt-2">${data.order_count}</p>
                    </div>
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
                         <div class="absolute -right-4 -bottom-4 text-7xl opacity-5">🧪</div>
                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Произведено</p>
                        <p class="text-3xl font-black text-emerald-600 mt-2">${data.production_volume} <span class="text-sm font-medium text-slate-400">ед.</span></p>
                    </div>
                `;
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
            content.innerHTML = `<h1 class="text-4xl font-black text-slate-800 mb-12 tracking-tight">Склад Сырья</h1><div id="rm-list" class="grid grid-cols-1 md:grid-cols-3 gap-8"></div>`;
            fetch('api.php?action=get_raw_materials').then(r => r.json()).then(data => {
                document.getElementById('rm-list').innerHTML = data.map(rm => `
                    <div class="card-grad p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex justify-between items-center group hover:scale-105 transition-all">
                        <div>
                            <p class="text-lg font-black text-slate-700">${rm.name}</p>
                            <p class="text-xs font-bold text-slate-400 uppercase mt-1">${rm.unit}</p>
                        </div>
                        <div class="text-right">
                             <p class="text-3xl font-black ${rm.quantity < rm.min_quantity ? 'text-red-500' : 'text-slate-800'}">${rm.quantity}</p>
                             ${rm.quantity < rm.min_quantity ? '<p class="text-[9px] font-black text-red-500 uppercase tracking-widest">ДЕФИЦИТ</p>' : ''}
                        </div>
                    </div>
                `).join('');
            });
        }

        function renderProduction(content) {
            content.innerHTML = `
                <div class="flex justify-between items-center mb-12">
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight">Производственный Цех</h1>
                    <button onclick="showProduceModal()" class="bg-slate-900 text-white px-10 py-4 rounded-2xl font-bold shadow-2xl hover:bg-indigo-600 transition-all">Запуск линии</button>
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

        function showProduceModal() { document.getElementById('prod-modal').classList.remove('hidden'); }
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
            `;
            fetch('api.php?action=get_products').then(r => r.json()).then(data => {
                document.getElementById('pg').innerHTML = data.map(p => `
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100 hover:shadow-2xl transition-all relative overflow-hidden group">
                        <div class="absolute -top-6 -right-6 text-9xl opacity-5 grayscale group-hover:grayscale-0 transition-all">🍷</div>
                        <h3 class="text-lg font-black text-slate-800 leading-tight">${p.name}</h3>
                        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest mt-2">${p.sku}</p>
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
                             <div class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] ${o.status === 'pending' ? 'bg-amber-100 text-amber-600' : 'bg-indigo-100 text-indigo-600'}">
                                ${o.status === 'pending' ? 'Обработка' : 'Отгружен'}
                             </div>
                        </div>
                    </div>
                `).join('');
            });
        }

        function renderAdmin(content) {
            content.innerHTML = `
                <h1 class="text-4xl font-black text-slate-800 mb-12 tracking-tight">Управление Системой</h1>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                        <h3 class="text-xl font-black mb-8">Сотрудники и Доступ</h3>
                        <div id="ul" class="space-y-4"></div>
                    </div>
                    <div class="bg-indigo-900 rounded-[3rem] p-10 shadow-2xl text-white">
                        <h3 class="text-xl font-black mb-8 italic">Глобальное Оповещение</h3>
                        <p class="text-xs text-indigo-300 mb-4 font-medium uppercase tracking-widest leading-relaxed">Рассылка сообщений во все отделы предприятия через главную панель дашборда.</p>
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

        function sendAnn() {
            const text = document.getElementById('ann-text').value;
            if(!text) return;
            fetch('api.php?action=add_announcement', { method: 'POST', body: JSON.stringify({ text }) }).then(() => {
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
