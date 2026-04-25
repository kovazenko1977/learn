<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Служба ХОП - Подача заявки</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div id="app" class="max-w-xl w-full glass rounded-3xl shadow-2xl overflow-hidden">
        <!-- Auth Check -->
        <div id="auth-container" class="p-8 hidden">
             <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Авторизация</h1>
                <p class="text-slate-500 text-sm mt-1">Войдите под своим логином и паролем</p>
            </div>
            <form id="loginForm" class="space-y-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Ваш логин</label>
                    <input type="text" name="username" placeholder="ivanov_ii" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none focus:border-indigo-500 transition-all" required>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Пароль</label>
                    <input type="password" name="password" placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none focus:border-indigo-500 transition-all" required>
                </div>
                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold shadow-lg shadow-slate-200 mt-4">Войти в систему</button>
                <div class="bg-blue-50 p-3 rounded-lg text-[10px] text-blue-600 border border-blue-100 mt-4">
                    <b>Справка:</b> Если у вас нет данных для входа, обратитесь к администратору программы для получения логина и пароля.
                </div>
            </form>
        </div>

        <div id="main-container" class="p-8 hidden">
            <div class="flex items-center space-x-4 mb-8">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-indigo-200">
                    🏢
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Служба ХОП</h1>
                    <p class="text-slate-500">Хозяйственное Обеспечение Предприятия</p>
                </div>
                <button id="logoutBtn" class="ml-auto text-xs text-slate-400 hover:text-red-500">Выйти</button>
            </div>

            <div id="form-container">
                <form id="requestForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Куда направить заявку?</label>
                        <select name="department_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" required title="Выберите отдел, который должен выполнить работу">
                            <option value="">Выберите отдел</option>
                            <option value="1">Сантехника</option>
                            <option value="2">Электрика</option>
                            <option value="3">Оборудование</option>
                            <option value="4">Мебель</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Приоритет</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="priority" value="low" class="peer sr-only">
                                <div class="text-center py-2 rounded-lg border border-slate-200 peer-checked:bg-slate-100 peer-checked:border-slate-400 transition-all text-sm">Низкий</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="priority" value="medium" class="peer sr-only" checked>
                                <div class="text-center py-2 rounded-lg border border-slate-200 peer-checked:bg-blue-50 peer-checked:border-blue-400 peer-checked:text-blue-700 transition-all text-sm">Средний</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="priority" value="high" class="peer sr-only">
                                <div class="text-center py-2 rounded-lg border border-slate-200 peer-checked:bg-red-50 peer-checked:border-red-400 peer-checked:text-red-700 transition-all text-sm">Срочно</div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Описание проблемы</label>
                        <textarea name="description" rows="4" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" placeholder="Опишите, что именно нужно сделать..." required></textarea>
                        <p class="text-[9px] text-slate-400 mt-1">Опишите задачу максимально подробно для ускорения выполнения.</p>
                    </div>

                    <div id="dynamic-fields" class="space-y-4">
                        <!-- Сюда будут подгружаться поля из конструктора -->
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Фото (необязательно)</label>
                        <input type="file" id="fileInput" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold hover:bg-slate-800 transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-xl">
                        Отправить заявку
                    </button>
                </form>
            </div>

            <div id="success-message" class="hidden text-center py-12">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                    ✓
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Заявка принята!</h2>
                <p class="text-slate-500 mb-8">Номер вашей заявки: <span id="task-id" class="font-bold text-slate-900"></span></p>
                <button onclick="location.reload()" class="text-indigo-600 font-semibold hover:underline">Отправить еще одну</button>
            </div>
        </div>

        <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
            <span>© 2024 Служба ХОП CRM PRO</span>
            <a href="admin.php" class="hover:text-slate-600">Панель управления</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('requestForm');
        const fileInput = document.getElementById('fileInput');
        let token = localStorage.getItem('crm_token');

        function checkAuth() {
            if (!token) {
                document.getElementById('auth-container').classList.remove('hidden');
                document.getElementById('main-container').classList.add('hidden');
            } else {
                document.getElementById('auth-container').classList.add('hidden');
                document.getElementById('main-container').classList.remove('hidden');
                loadSettings();
            }
        }

        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());
            const res = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.token) {
                token = result.token;
                localStorage.setItem('crm_token', token);
                checkAuth();
            } else {
                alert('Ошибка входа');
            }
        };

        document.getElementById('logoutBtn').onclick = () => {
            localStorage.removeItem('crm_token');
            location.reload();
        };

        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Handle file upload if any
            if (fileInput.files.length > 0) {
                const fData = new FormData();
                fData.append('file', fileInput.files[0]);
                try {
                    const uploadRes = await fetch('api/uploads.php', { method: 'POST', body: fData });
                    const uploadResult = await uploadRes.json();
                    if (uploadResult.filename) {
                        data.attachment = uploadResult.filename;
                    }
                } catch (err) {
                    console.error('Upload failed', err);
                }
            }

            try {
                const response = await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('form-container').classList.add('hidden');
                    document.getElementById('success-message').classList.remove('hidden');
                    document.getElementById('task-id').innerText = '#' + result.id;
                }
            } catch (err) {
                alert('Ошибка при отправке заявки');
            }
        };

        // Load dynamic fields from settings
        async function loadSettings() {
            try {
                const res = await fetch('api/settings.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const settings = await res.json();
                if (settings.form_fields) {
                    const container = document.getElementById('dynamic-fields');
                    settings.form_fields.forEach(field => {
                        const div = document.createElement('div');
                        let inputHtml = '';
                        if (field.type === 'textarea') {
                            inputHtml = `<textarea name="custom_${field.id}" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none" ${field.required ? 'required' : ''}></textarea>`;
                        } else if (field.type === 'checkbox') {
                            inputHtml = `<input type="checkbox" name="custom_${field.id}" class="w-5 h-5">`;
                        } else {
                            inputHtml = `<input type="${field.type}" name="custom_${field.id}" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none" ${field.required ? 'required' : ''}>`;
                        }

                        div.innerHTML = `
                            <label class="block text-sm font-semibold text-slate-700 mb-2">${field.label}</label>
                            ${inputHtml}
                        `;
                        container.appendChild(div);
                    });
                }
                if (settings.departments) {
                    const sel = document.querySelector('select[name="department_id"]');
                    sel.innerHTML = '<option value="">Выберите отдел</option>';
                    settings.departments.forEach(d => {
                        sel.innerHTML += `<option value="${d.id}">${d.name}</option>`;
                    });
                }
            } catch (e) {}
        }
        checkAuth();
    </script>
</body>
</html>
