<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Служба ХОП - Подача заявки</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link id="google-font" rel="stylesheet">
    <style id="theme-styles">
        :root {
            --primary: #6366f1;
            --bg-main: #f8fafc;
            --bg-glass: rgba(255, 255, 255, 0.8);
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --border-color: rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        .text-main { color: var(--text-main) !important; }
        .text-dim { color: var(--text-main); opacity: 0.6; }
        .glass { background: var(--bg-glass); backdrop-filter: blur(10px); border: 1px solid var(--border-color); }
        input, select, textarea { background-color: var(--bg-card) !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <!-- Boot Loader -->
    <div id="boot-loader" class="fixed inset-0 z-[100] bg-slate-900 flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="w-20 h-20 bg-indigo-600 rounded-3xl flex items-center justify-center text-4xl mb-6 shadow-2xl shadow-indigo-500/20 animate-pulse">🏢</div>
        <h2 class="text-white font-bold tracking-widest uppercase text-sm mb-4">Служба ХОП PRO</h2>
        <div class="w-48 h-1 bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-indigo-500 w-0 animate-[load_3s_linear_forwards]"></div>
        </div>
        <p class="text-slate-500 text-[10px] mt-4 uppercase font-bold tracking-[0.3em]">Загрузка системы</p>
        <style>
            @keyframes load { from { width: 0%; } to { width: 100%; } }
        </style>
    </div>

    <div id="app" class="max-w-xl w-full glass rounded-3xl shadow-2xl overflow-hidden">
        <!-- Auth Check -->
        <div id="auth-container" class="p-8 hidden">
             <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-main">Авторизация</h1>
                <p class="text-dim text-sm mt-1">Войдите под своим логином и паролем</p>
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
                <div class="flex-1">
                    <h1 id="system-name" class="text-2xl font-bold text-main">Служба ХОП</h1>
                    <p id="welcome-text" class="text-dim text-sm">Хозяйственное Обеспечение Предприятия</p>
                </div>
                <button id="logoutBtn" class="ml-auto text-xs text-dim hover:text-red-500">Выйти</button>
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

                    <div class="relative">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Описание проблемы</label>
                        <textarea name="description" id="desc-field" rows="4" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" placeholder="Опишите, что именно нужно сделать..." required></textarea>
                        <button type="button" onclick="startVoice('desc-field')" class="absolute right-3 bottom-10 text-slate-400 hover:text-indigo-600"><i data-lucide="mic" class="w-5 h-5"></i></button>
                        <p class="text-[9px] text-slate-400 mt-1">Опишите задачу максимально подробно для ускорения выполнения.</p>
                    </div>

                    <div id="dynamic-fields" class="space-y-4">
                        <!-- Сюда будут подгружаться поля из конструктора -->
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Вложения (фото, документы)</label>
                        <input type="file" id="fileInput" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-[9px] text-slate-400 mt-1">Можно выбрать несколько файлов (картинки, PDF, DOCX и др.)</p>
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
            console.log('Checking auth, token exists:', !!token);
            if (!token) {
                document.getElementById('auth-container').classList.remove('hidden');
                document.getElementById('auth-container').classList.add('flex'); // Add flex to make it visible
                document.getElementById('main-container').classList.add('hidden');
                console.log('Auth container visible');
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

            // Handle multiple file uploads
            if (fileInput.files.length > 0) {
                data.attachments = [];
                for (let i = 0; i < fileInput.files.length; i++) {
                    const fData = new FormData();
                    fData.append('file', fileInput.files[i]);
                    try {
                        const uploadRes = await fetch('api/uploads.php', {
                            method: 'POST',
                            body: fData,
                            headers: { 'Authorization': `Bearer ${token}` }
                        });
                        const uploadResult = await uploadRes.json();
                        if (uploadResult.filename) {
                            data.attachments.push({
                                name: uploadResult.filename,
                                original: uploadResult.original_name
                            });
                        }
                    } catch (err) {
                        console.error('Upload failed for file ' + i, err);
                    }
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

        let settings = {};
        // Load dynamic fields from settings
        async function loadSettings() {
            try {
                const res = await fetch('api/settings.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                settings = await res.json();

                // Apply Theme
                if (settings.maintenance_mode && !settings.is_admin) {
                     document.body.innerHTML = `
                        <div class="flex flex-col items-center justify-center min-h-screen bg-slate-950 text-white p-10 text-center">
                            <h1 class="text-4xl font-black mb-4">ТЕХОБСЛУЖИВАНИЕ</h1>
                            <p class="text-slate-400 max-w-md">Система временно недоступна в связи с проведением плановых работ. Пожалуйста, попробуйте позже.</p>
                            <button onclick="localStorage.removeItem('crm_token'); location.reload()" class="mt-10 text-indigo-400 underline">Вход для администратора</button>
                        </div>
                     `;
                     return;
                }

                if (settings.active_theme) {
                    const themes = [
                        { id: 'slate', name: 'Slate Night', colors: { primary: '#6366f1', bgMain: '#0f172a', bgGlass: 'rgba(15, 23, 42, 0.9)', bgCard: 'rgba(30, 41, 59, 0.5)', textMain: '#e2e8f0', border: 'rgba(255, 255, 255, 0.1)' } },
                        { id: 'emerald', name: 'Emerald Forest', colors: { primary: '#10b981', bgMain: '#064e3b', bgGlass: 'rgba(6, 78, 59, 0.9)', bgCard: 'rgba(6, 95, 70, 0.5)', textMain: '#ecfdf5', border: 'rgba(16, 185, 129, 0.2)' } },
                        { id: 'ruby', name: 'Ruby Wine', colors: { primary: '#e11d48', bgMain: '#4c0519', bgGlass: 'rgba(76, 5, 25, 0.9)', bgCard: 'rgba(136, 19, 55, 0.5)', textMain: '#fff1f2', border: 'rgba(225, 29, 72, 0.2)' } },
                        { id: 'ocean', name: 'Deep Ocean', colors: { primary: '#0ea5e9', bgMain: '#0c4a6e', bgGlass: 'rgba(12, 74, 110, 0.9)', bgCard: 'rgba(7, 89, 133, 0.5)', textMain: '#f0f9ff', border: 'rgba(14, 165, 233, 0.2)' } },
                        { id: 'purple', name: 'Royal Purple', colors: { primary: '#a855f7', bgMain: '#3b0764', bgGlass: 'rgba(59, 7, 100, 0.9)', bgCard: 'rgba(88, 28, 135, 0.5)', textMain: '#faf5ff', border: 'rgba(168, 85, 247, 0.2)' } },
                        { id: 'gold', name: 'Cyber Gold', colors: { primary: '#f59e0b', bgMain: '#1c1917', bgGlass: 'rgba(28, 25, 23, 0.9)', bgCard: 'rgba(41, 37, 36, 0.5)', textMain: '#fef3c7', border: 'rgba(245, 158, 11, 0.3)' } },
                        { id: 'minimal-light', name: 'Minimal Light', colors: { primary: '#0f172a', bgMain: '#f8fafc', bgGlass: 'rgba(255, 255, 255, 0.9)', bgCard: '#ffffff', textMain: '#0f172a', border: 'rgba(0, 0, 0, 0.1)' } },
                        { id: 'coffee', name: 'Roasted Coffee', colors: { primary: '#a16207', bgMain: '#271b12', bgGlass: 'rgba(39, 27, 18, 0.9)', bgCard: 'rgba(63, 45, 33, 0.5)', textMain: '#fefce8', border: 'rgba(161, 98, 7, 0.2)' } },
                        { id: 'nordic', name: 'Nordic Frost', colors: { primary: '#88c0d0', bgMain: '#2e3440', bgGlass: 'rgba(46, 52, 64, 0.9)', bgCard: 'rgba(59, 66, 82, 0.5)', textMain: '#eceff4', border: 'rgba(136, 192, 208, 0.2)' } },
                        { id: 'dracula', name: 'Dracula', colors: { primary: '#bd93f9', bgMain: '#282a36', bgGlass: 'rgba(40, 42, 54, 0.9)', bgCard: 'rgba(68, 71, 90, 0.5)', textMain: '#f8f8f2', border: 'rgba(189, 147, 249, 0.2)' } },
                        { id: 'synthwave', name: 'Synthwave', colors: { primary: '#ff79c6', bgMain: '#2b213a', bgGlass: 'rgba(43, 33, 58, 0.9)', bgCard: 'rgba(58, 44, 78, 0.5)', textMain: '#f8f8f2', border: 'rgba(255, 121, 198, 0.2)' } },
                        { id: 'midnight', name: 'True Midnight', colors: { primary: '#3b82f6', bgMain: '#000000', bgGlass: 'rgba(0, 0, 0, 0.95)', bgCard: 'rgba(15, 23, 42, 0.5)', textMain: '#ffffff', border: 'rgba(255, 255, 255, 0.05)' } },
                        { id: 'matcha', name: 'Soft Matcha', colors: { primary: '#65a30d', bgMain: '#f7fee7', bgGlass: 'rgba(247, 254, 231, 0.9)', bgCard: '#ffffff', textMain: '#1a2e05', border: 'rgba(101, 163, 13, 0.1)' } },
                        { id: 'rose', name: 'Rose Quartz', colors: { primary: '#db2777', bgMain: '#fff1f2', bgGlass: 'rgba(255, 241, 242, 0.9)', bgCard: '#ffffff', textMain: '#4c0519', border: 'rgba(219, 39, 119, 0.1)' } },
                        { id: 'amber', name: 'Amber Glow', colors: { primary: '#d97706', bgMain: '#451a03', bgGlass: 'rgba(69, 26, 3, 0.9)', bgCard: 'rgba(120, 53, 15, 0.5)', textMain: '#fffbeb', border: 'rgba(217, 119, 6, 0.2)' } },
                        { id: 'indigo', name: 'Indigo Dream', colors: { primary: '#4f46e5', bgMain: '#1e1b4b', bgGlass: 'rgba(30, 27, 75, 0.9)', bgCard: 'rgba(49, 46, 129, 0.5)', textMain: '#e0e7ff', border: 'rgba(79, 70, 229, 0.2)' } },
                        { id: 'gray-modern', name: 'Gray Modern', colors: { primary: '#18181b', bgMain: '#f4f4f5', bgGlass: 'rgba(255, 255, 255, 0.9)', bgCard: '#ffffff', textMain: '#18181b', border: 'rgba(0, 0, 0, 0.05)' } },
                        { id: 'teal', name: 'Teal Lagoon', colors: { primary: '#0d9488', bgMain: '#042f2e', bgGlass: 'rgba(4, 47, 46, 0.9)', bgCard: 'rgba(19, 78, 74, 0.5)', textMain: '#f0fdfa', border: 'rgba(13, 148, 136, 0.2)' } },
                        { id: 'orange', name: 'Vivid Orange', colors: { primary: '#ea580c', bgMain: '#431407', bgGlass: 'rgba(67, 20, 7, 0.9)', bgCard: 'rgba(124, 45, 18, 0.5)', textMain: '#fff7ed', border: 'rgba(234, 88, 12, 0.2)' } },
                        { id: 'sky', name: 'Sky High', colors: { primary: '#0284c7', bgMain: '#f0f9ff', bgGlass: 'rgba(240, 249, 255, 0.9)', bgCard: '#ffffff', textMain: '#082f49', border: 'rgba(2, 132, 199, 0.1)' } },
                        { id: 'pink', name: 'Cyber Pink', colors: { primary: '#f472b6', bgMain: '#1e0714', bgGlass: 'rgba(30, 7, 20, 0.9)', bgCard: 'rgba(62, 11, 40, 0.5)', textMain: '#fdf2f8', border: 'rgba(244, 114, 182, 0.2)' } },
                        { id: 'lime', name: 'Acid Lime', colors: { primary: '#bef264', bgMain: '#1a2e05', bgGlass: 'rgba(26, 46, 5, 0.9)', bgCard: 'rgba(32, 45, 8, 0.5)', textMain: '#f7fee7', border: 'rgba(190, 242, 100, 0.2)' } },
                        { id: 'chocolate', name: 'Dark Chocolate', colors: { primary: '#78350f', bgMain: '#1c1917', bgGlass: 'rgba(28, 25, 23, 0.9)', bgCard: 'rgba(41, 37, 36, 0.5)', textMain: '#fef3c7', border: 'rgba(120, 53, 15, 0.2)' } },
                        { id: 'royal', name: 'Royal Blue', colors: { primary: '#2563eb', bgMain: '#1e1b4b', bgGlass: 'rgba(30, 27, 75, 0.9)', bgCard: 'rgba(49, 46, 129, 0.5)', textMain: '#f0f9ff', border: 'rgba(37, 99, 235, 0.2)' } },
                        { id: 'sepia', name: 'Sepia Memory', colors: { primary: '#92400e', bgMain: '#fef3c7', bgGlass: 'rgba(254, 243, 199, 0.9)', bgCard: '#fffbeb', textMain: '#451a03', border: 'rgba(146, 64, 14, 0.1)' } }
                    ];
                    const t = themes.find(x => x.id === settings.active_theme);
                    if (t) {
                        Object.entries(t.colors).forEach(([k, v]) => {
                            document.documentElement.style.setProperty(`--${k}`, v);
                        });
                    }
                }

                // Apply Font
                if (settings.font_family) {
                    document.getElementById('google-font').href = `https://fonts.googleapis.com/css2?family=${settings.font_family.replace(/ /g, '+')}:wght@300;400;500;600;700&display=swap`;
                    document.body.style.fontFamily = `'${settings.font_family}', sans-serif`;
                }

                if (settings.system_name) document.getElementById('system-name').innerText = settings.system_name;
                if (settings.welcome_text) document.getElementById('welcome-text').innerText = settings.welcome_text;

                if (settings.form_fields) {
                    const container = document.getElementById('dynamic-fields');
                    container.innerHTML = '';
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
        function startVoice(fieldId) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return alert('Браузер не поддерживает голос');
            const recognition = new SpeechRecognition();
            recognition.lang = 'ru-RU';
            recognition.onresult = (event) => {
                document.getElementById(fieldId).value += ' ' + event.results[0][0].transcript;
            };
            recognition.start();
        }
        checkAuth();
        setTimeout(() => {
            const loader = document.getElementById('boot-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 500);
            }
        }, 3000);
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
        // Boot Loader
        setTimeout(() => {
            const loader = document.getElementById('boot-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>
