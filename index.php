<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM PRO - Desktop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            overflow: hidden;
        }
        [v-cloak] { display: none; }
        .glass { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(20px); }
        .window-shadow { shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .taskbar { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); }
        .desktop-icon { @apply flex flex-col items-center p-2 rounded-xl transition-all hover:bg-white/10 cursor-pointer w-24 text-center; }
    </style>
</head>
<body class="text-slate-200 h-screen">
    <div id="app" v-cloak class="h-full relative select-none">
        <!-- Login Screen -->
        <div v-if="!token" class="absolute inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-md">
            <div class="w-full max-w-md glass border border-slate-700/50 p-10 rounded-[2rem] shadow-2xl">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-blue-600 rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">Service CRM PRO</h1>
                    <p class="text-slate-400 mt-2">Вход в систему управления</p>
                </div>
                <form @submit.prevent="login" class="space-y-4">
                    <div>
                        <input v-model="loginForm.username" type="text" placeholder="Логин" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <div>
                        <input v-model="loginForm.password" type="password" placeholder="Пароль" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-500/20">Войти</button>
                    <p v-if="error" class="text-red-400 text-sm text-center">{{ error }}</p>
                </form>
            </div>
        </div>

        <!-- Desktop Area -->
        <div v-if="token" class="h-full p-4 flex flex-col items-start gap-4 content-start flex-wrap">
            <div class="desktop-icon" @dblclick="openWindow('new_request')">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl mb-2 flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                </div>
                <span class="text-xs font-medium drop-shadow-md">Новая заявка</span>
            </div>

            <div class="desktop-icon" @dblclick="openWindow('my_requests')">
                <div class="w-16 h-16 bg-emerald-600 rounded-2xl mb-2 flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2" /></svg>
                </div>
                <span class="text-xs font-medium drop-shadow-md">Мои заявки</span>
            </div>

            <div v-if="isAdmin" class="desktop-icon" @dblclick="openWindow('admin_panel')">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl mb-2 flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <span class="text-xs font-medium drop-shadow-md">Панель управления</span>
            </div>
        </div>

        <!-- Windows Layer -->
        <div v-for="win in windows" :key="win.id" v-show="!win.minimized" :style="{ zIndex: win.zIndex, left: win.x + 'px', top: win.y + 'px', width: win.width + 'px', height: win.height + 'px' }" class="absolute glass border border-slate-700/50 rounded-2xl shadow-2xl flex flex-col overflow-hidden" @mousedown="focusWindow(win)">
            <!-- Window Titlebar -->
            <div class="h-10 bg-slate-900/50 flex items-center justify-between px-4 cursor-default" @mousedown="startDrag($event, win)">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ win.title }}</span>
                </div>
                <div class="flex gap-2">
                    <button @click.stop="minimizeWindow(win)" class="w-3 h-3 rounded-full bg-yellow-500/50 hover:bg-yellow-500"></button>
                    <button @click.stop="closeWindow(win)" class="w-3 h-3 rounded-full bg-red-500/50 hover:bg-red-500"></button>
                </div>
            </div>
            <!-- Window Content -->
            <div class="flex-1 overflow-hidden relative">
                <iframe v-if="win.id === 'admin_panel'" src="admin.php" class="w-full h-full border-none"></iframe>
                <div v-else class="w-full h-full p-6 overflow-y-auto">
                    <!-- New Request Form -->
                    <div v-if="win.id === 'new_request'" class="max-w-xl mx-auto">
                        <h2 class="text-2xl font-bold mb-6 text-white">Создание заявки</h2>
                        <form @submit.prevent="submitRequest(win)" class="space-y-4">
                            <div v-for="field in settings.form_fields" :key="field.id" class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase">{{ field.label }}</label>
                                <input v-if="field.type !== 'textarea'" v-model="win.data[field.id]" :type="field.type" :required="field.required" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                <textarea v-else v-model="win.data[field.id]" :required="field.required" rows="4" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 outline-none focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase">Приоритет</label>
                                <select v-model="win.data.priority" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Low">Низкий</option>
                                    <option value="Medium">Средний</option>
                                    <option value="High">Высокий</option>
                                </select>
                            </div>
                            <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition-all">Отправить заявку</button>
                        </form>
                    </div>

                    <!-- My Requests List -->
                    <div v-if="win.id === 'my_requests'">
                         <h2 class="text-2xl font-bold mb-6 text-white">История заявок</h2>
                         <div class="space-y-3">
                            <div v-for="t in tasks" :key="t.id" class="bg-slate-900/50 border border-slate-800 p-4 rounded-xl hover:bg-slate-900 transition-all">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-mono text-slate-500">#{{ t.id }}</span>
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', priorityClass(t.priority)]">{{ t.priority }}</span>
                                </div>
                                <h4 class="font-bold text-white">{{ t.fields.f_subject }}</h4>
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-xs text-slate-500">{{ formatDate(t.created_at) }}</span>
                                    <span class="text-xs font-bold text-blue-400">{{ t.status }}</span>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Taskbar -->
        <div v-if="token" class="absolute bottom-0 left-0 right-0 h-12 taskbar border-t border-slate-800/50 flex items-center justify-between px-4">
            <div class="flex items-center gap-2">
                <button class="p-2 hover:bg-white/10 rounded-lg transition-all text-blue-400">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                </button>
                <div class="w-px h-6 bg-slate-800 mx-2"></div>
                <div v-for="win in windows" :key="win.id" @click="toggleWindow(win)" :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-all cursor-pointer border', win.id === activeWindowId ? 'bg-white/10 border-slate-600 text-white' : 'hover:bg-white/5 border-transparent text-slate-400']">
                    {{ win.title }}
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium text-slate-400">
                <span>{{ currentTime }}</span>
                <button @click="logout" class="p-2 hover:text-white transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        const { createApp, ref, computed, onMounted } = Vue;

        createApp({
            setup() {
                const token = ref(localStorage.getItem('token'));
                const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
                const loginForm = ref({ username: '', password: '' });
                const error = ref('');
                const windows = ref([]);
                const nextZIndex = ref(10);
                const activeWindowId = ref(null);
                const settings = ref({});
                const tasks = ref([]);
                const currentTime = ref('');

                const isAdmin = computed(() => user.value.role === 'Administrator');

                const updateTime = () => {
                    const now = new Date();
                    currentTime.value = now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                };

                const login = async () => {
                    const resp = await fetch('api/auth.php', {
                        method: 'POST',
                        body: JSON.stringify(loginForm.value)
                    });
                    const data = await resp.json();
                    if (data.token) {
                        token.value = data.token;
                        user.value = data.user;
                        localStorage.setItem('token', data.token);
                        localStorage.setItem('user', JSON.stringify(data.user));
                        initDesktop();
                    } else {
                        error.value = data.error;
                    }
                };

                const logout = () => {
                    token.value = null;
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    windows.value = [];
                };

                const initDesktop = async () => {
                    const headers = { 'Authorization': `Bearer ${token.value}` };
                    const [setResp, taskResp] = await Promise.all([
                        fetch('api/settings.php', { headers }),
                        fetch('api/tasks.php', { headers })
                    ]);
                    settings.value = await setResp.json();
                    tasks.value = await taskResp.json();
                };

                const openWindow = (id) => {
                    const existing = windows.value.find(w => w.id === id);
                    if (existing) {
                        existing.minimized = false;
                        focusWindow(existing);
                        return;
                    }

                    const titles = {
                        'new_request': 'Новая заявка',
                        'my_requests': 'Мои заявки',
                        'admin_panel': 'Панель управления'
                    };

                    const win = {
                        id,
                        title: titles[id],
                        x: 100 + (windows.value.length * 30),
                        y: 50 + (windows.value.length * 30),
                        width: id === 'admin_panel' ? 1200 : 600,
                        height: id === 'admin_panel' ? 800 : 700,
                        zIndex: nextZIndex.value++,
                        minimized: false,
                        data: { priority: 'Medium' }
                    };
                    windows.value.push(win);
                    activeWindowId.value = id;
                };

                const focusWindow = (win) => {
                    win.zIndex = nextZIndex.value++;
                    activeWindowId.value = win.id;
                };

                const closeWindow = (win) => {
                    windows.value = windows.value.filter(w => w.id !== win.id);
                };

                const minimizeWindow = (win) => {
                    win.minimized = true;
                };

                const toggleWindow = (win) => {
                    if (win.minimized) {
                        win.minimized = false;
                        focusWindow(win);
                    } else if (activeWindowId.value === win.id) {
                        win.minimized = true;
                    } else {
                        focusWindow(win);
                    }
                };

                const startDrag = (e, win) => {
                    const startX = e.clientX - win.x;
                    const startY = e.clientY - win.y;
                    focusWindow(win);

                    const onMouseMove = (moveEvent) => {
                        win.x = moveEvent.clientX - startX;
                        win.y = moveEvent.clientY - startY;
                    };

                    const onMouseUp = () => {
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                    };

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                };

                const submitRequest = async (win) => {
                    const resp = await fetch('api/tasks.php?action=create', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token.value}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(win.data)
                    });
                    if (resp.ok) {
                        alert('Заявка успешно создана!');
                        closeWindow(win);
                        initDesktop();
                    }
                };

                const priorityClass = (p) => {
                    if (p === 'High') return 'bg-red-500/20 text-red-400 border border-red-500/50';
                    if (p === 'Medium') return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/50';
                    return 'bg-blue-500/20 text-blue-400 border border-blue-500/50';
                };

                const formatDate = (d) => new Date(d).toLocaleString('ru-RU');

                onMounted(() => {
                    updateTime();
                    setInterval(updateTime, 1000);
                    if (token.value) initDesktop();
                });

                return {
                    token, user, loginForm, error, windows, activeWindowId, settings, tasks, currentTime, isAdmin,
                    login, logout, openWindow, focusWindow, closeWindow, minimizeWindow, toggleWindow, startDrag,
                    submitRequest, priorityClass, formatDate
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
