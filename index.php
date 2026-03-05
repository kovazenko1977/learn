<?php
require_once 'Managers/AuthManager.php';
$auth = new \Managers\AuthManager();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALCO.BY - Управление предприятием</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-white border-b shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <a href="#" onclick="showPage('dashboard')" class="text-2xl font-bold text-blue-600">ALCO.BY</a>
                <?php if ($user): ?>
                <nav class="hidden md:flex space-x-6 text-sm font-medium">
                    <button onclick="showPage('products')" class="hover:text-blue-600 transition">Товары</button>
                    <button onclick="showPage('clients')" class="hover:text-blue-600 transition">Клиенты</button>
                    <button onclick="showPage('orders')" class="hover:text-blue-600 transition">Заказы</button>
                    <button onclick="showPage('lk')" class="hover:text-blue-600 transition">Кабинет</button>
                </nav>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($user): ?>
                    <button onclick="logout()" class="text-sm font-medium text-red-600 hover:underline">Выйти (<?= htmlspecialchars($user['username']) ?>)</button>
                <?php else: ?>
                    <button onclick="showPage('login')" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">Войти</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="app-content" class="flex-grow max-w-7xl mx-auto px-4 py-8 w-full">
        <!-- Контент загружается динамически -->
    </main>

    <footer class="bg-white border-t py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500">
            &copy; 2024 ALCO.BY. Все права защищены.
        </div>
    </footer>

    <script>
        const user = <?= json_encode($user) ?>;

        function showPage(page) {
            const content = document.getElementById('app-content');
            if (!user && page !== 'login') {
                page = 'login';
            }

            if (page === 'dashboard') {
                content.innerHTML = `
                    <h1 class="text-3xl font-bold mb-8">Панель управления</h1>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="text-gray-500 text-sm font-medium">Товары в наличии</h3>
                            <p class="text-3xl font-bold text-blue-600 mt-2">1,402</p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="text-gray-500 text-sm font-medium">Активные заказы</h3>
                            <p class="text-3xl font-bold text-green-600 mt-2">28</p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="text-gray-500 text-sm font-medium">Клиенты</h3>
                            <p class="text-3xl font-bold text-purple-600 mt-2">560</p>
                        </div>
                    </div>
                `;
            } else if (page === 'login') {
                content.innerHTML = `
                    <div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-xl border border-gray-100 mt-12">
                        <h2 class="text-2xl font-bold text-center mb-6">Вход в систему</h2>
                        <div id="login-error" class="hidden mb-4 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100 text-center"></div>
                        <form onsubmit="handleLogin(event)" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Имя пользователя</label>
                                <input type="text" id="username" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                                <input type="password" id="password" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Войти</button>
                        </form>
                        <p class="mt-6 text-center text-xs text-gray-400">Демо: admin / admin123</p>
                    </div>
                `;
            } else if (page === 'products') {
                content.innerHTML = '<h1 class="text-3xl font-bold mb-8">Каталог товаров</h1><div id="products-list" class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 p-8 text-center">Загрузка...</div>';
                fetch('api.php?action=get_products')
                    .then(r => r.json())
                    .then(data => {
                        const list = document.getElementById('products-list');
                        if (data.length === 0) {
                            list.innerHTML = 'Товары пока не добавлены.';
                        } else {
                            // Render table
                        }
                    });
            } else if (page === 'lk') {
                content.innerHTML = `
                    <h1 class="text-3xl font-bold mb-8">Личный кабинет</h1>
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 max-w-2xl">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl font-bold uppercase">
                                ${user.username[0]}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">${user.username}</h2>
                                <p class="text-gray-500 text-sm uppercase font-bold tracking-wider">${user.role === 'admin' ? 'Администратор' : 'Клиент'}</p>
                            </div>
                        </div>
                        <div class="border-t pt-6 space-y-4">
                            <button class="w-full text-left px-4 py-3 border rounded-xl hover:bg-gray-50 transition">Мои заказы</button>
                            <button class="w-full text-left px-4 py-3 border rounded-xl hover:bg-gray-50 transition">Настройки уведомлений</button>
                        </div>
                    </div>
                `;
            }
        }

        function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            fetch('api.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ username, password })
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    location.reload();
                } else {
                    const err = document.getElementById('login-error');
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            });
        }

        function logout() {
            fetch('api.php?action=logout').then(() => location.reload());
        }

        // Initialize
        showPage(user ? 'dashboard' : 'login');
    </script>
</body>
</html>
