<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM PRO - Портал сотрудников</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body { font-family: 'Inter', sans-serif; overflow: hidden; background: #0f172a; height: 100vh; width: 100vw; }
        .desktop-bg {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            z-index: -1;
        }
        .window {
            position: absolute; min-width: 350px; min-height: 200px;
            background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex; flex-direction: column; overflow: hidden;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .window-header {
            padding: 12px 16px; background: rgba(255, 255, 255, 0.05);
            cursor: move; display: flex; align-items: center; justify-content: space-between;
            user-select: none;
        }
        .taskbar {
            position: fixed; bottom: 0; left: 0; right: 0; height: 48px;
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 9999; display: flex; align-items: center; padding: 0 12px;
        }
        .desktop-icon {
            width: 80px; display: flex; flex-direction: column; align-items: center;
            cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;
            color: white; text-align: center; font-size: 12px;
        }
        .desktop-icon:hover { background: rgba(255, 255, 255, 0.1); }
        .desktop-icon svg { width: 32px; height: 32px; margin-bottom: 4px; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            body { overflow: auto; height: auto; min-height: 100vh; }
            .window {
                position: static !important; width: 100% !important; height: auto !important;
                min-width: 0 !important; min-height: 0 !important; margin-bottom: 1rem;
                box-shadow: none; border-radius: 0; border-left: none; border-right: none;
            }
            .window-header { cursor: default; }
            .taskbar { display: none; }
            .desktop-icon { width: 100%; flex-direction: row; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.05); }
            .desktop-icon svg { margin-bottom: 0; }
            .desktop-bg { position: fixed; }
        }
    </style>
</head>
<body>
    <div id="app" class="h-full w-full relative" @mousemove="onMouseMove" @mouseup="onMouseUp">
        <div class="desktop-bg"></div>

        <!-- Boot Loader -->
        <div v-if="booting" id="boot-loader" class="fixed inset-0 z-[10000] bg-slate-950 flex flex-col items-center justify-center">
            <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <div class="text-blue-400 font-medium tracking-widest text-sm uppercase">Service CRM PRO is starting...</div>
        </div>

        <!-- Login Overlay -->
        <div v-if="!isAuthenticated && !booting" class="fixed inset-0 z-[9000] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
            <div class="bg-slate-800 p-8 rounded-2xl border border-slate-700 w-full max-w-md shadow-2xl mx-4">
                <h1 class="text-2xl font-bold text-white mb-6 text-center">Вход в систему</h1>
                <div class="space-y-4">
                    <input v-model="loginForm.username" type="text" placeholder="Логин" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input v-model="loginForm.password" @keyup.enter="login" type="password" placeholder="Пароль" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition duration-200">Войти</button>
                    <div v-if="error" class="text-red-400 text-sm text-center">{{ error }}</div>
                </div>
            </div>
        </div>

        <!-- Desktop Area -->
        <div v-if="isAuthenticated && !booting" class="h-full w-full p-4 md:p-6 flex flex-col md:flex-wrap gap-4 items-start overflow-auto">
            <div class="desktop-icon" @click="toggleWindow('new_request')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span class="font-medium">Новая заявка</span>
            </div>
            <div class="desktop-icon" @click="toggleWindow('my_requests')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span class="font-medium">Мои заявки</span>
            </div>

            <!-- Dynamic Windows -->
            <div v-for="win in windows" :key="win.id" v-show="!win.minimized || isMobile" class="window"
                 :style="isMobile ? {} : { top: win.y + 'px', left: win.x + 'px', width: win.width + 'px', height: win.height + 'px', zIndex: win.zIndex }"
                 @mousedown="focusWindow(win.id)">
                <div class="window-header" @mousedown="onMouseDown($event, win.id)">
                    <span class="text-white font-medium text-sm">{{ win.title }}</span>
                    <div class="flex gap-2">
                        <button v-if="!isMobile" @click="win.minimized = true" class="w-3 h-3 rounded-full bg-yellow-500 hover:bg-yellow-600"></button>
                        <button @click="closeWindow(win.id)" class="w-3 h-3 rounded-full bg-red-500 hover:bg-red-600"></button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-4 text-white">
                    <!-- New Request Form -->
                    <div v-if="win.id === 'new_request'" class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 uppercase">Заголовок</label>
                            <input v-model="newRequest.title" class="w-full bg-slate-900/50 border border-slate-700 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1 uppercase">Категория</label>
                                <select v-model="newRequest.category" class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-sm outline-none">
                                    <option v-for="cat in settings.categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1 uppercase">Приоритет</label>
                                <select v-model="newRequest.priority" class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-sm outline-none">
                                    <option v-for="p in settings.priorities" :key="p.id" :value="p.id">{{ p.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div v-for="field in settings.form_fields" :key="field.id">
                            <label class="block text-xs text-slate-400 mb-1 uppercase">{{ field.label }}</label>
                            <textarea v-if="field.type === 'textarea'" v-model="newRequest[field.id]" class="w-full bg-slate-900/50 border border-slate-700 rounded p-2 text-sm outline-none" rows="3"></textarea>
                            <input v-else v-model="newRequest[field.id]" :type="field.type" class="w-full bg-slate-900/50 border border-slate-700 rounded p-2 text-sm outline-none">
                        </div>
                        <button @click="submitRequest" class="w-full bg-blue-600 py-3 rounded-lg font-bold text-sm hover:bg-blue-700 transition">Отправить заявку</button>
                    </div>

                    <!-- My Requests List -->
                    <div v-if="win.id === 'my_requests'">
                        <div v-if="requests.length === 0" class="text-center py-10 text-slate-500">У вас пока нет заявок</div>
                        <div v-else class="space-y-3">
                            <div v-for="req in requests" :key="req.id" class="bg-slate-800/50 border border-slate-700 rounded-xl p-4 hover:bg-slate-800 transition cursor-pointer">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-semibold text-sm">{{ req.title }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase" :class="statusClass(req.status)">{{ req.status }}</span>
                                </div>
                                <div class="flex justify-between text-[10px] text-slate-400">
                                    <span>{{ req.category }}</span>
                                    <span>{{ req.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Taskbar (Desktop only) -->
        <div v-if="isAuthenticated && !booting && !isMobile" class="taskbar">
             <button @click="showStartMenu = !showStartMenu" class="h-10 w-10 bg-blue-600 rounded flex items-center justify-center hover:bg-blue-700 transition">
                 <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
             </button>
             <div class="flex-1 px-4 flex gap-2 overflow-x-auto">
                 <button v-for="win in windows" :key="'tb-'+win.id"
                         @click="toggleMinimize(win.id)"
                         class="h-10 px-4 rounded bg-slate-800 text-white text-xs border border-slate-700 truncate max-w-[150px] transition"
                         :class="{ 'bg-slate-700 border-blue-500': !win.minimized && win.zIndex === maxZ }">
                     {{ win.title }}
                 </button>
             </div>
             <div class="text-slate-400 text-sm font-medium mr-4">
                 {{ currentTime }}
             </div>
             <button @click="logout" class="text-xs text-slate-500 hover:text-white transition">Выход</button>
        </div>

        <!-- Start Menu Placeholder -->
        <div v-if="showStartMenu" class="fixed bottom-14 left-4 w-64 bg-slate-800 border border-slate-700 rounded-xl p-4 shadow-2xl z-[10001]">
            <div class="flex items-center gap-3 mb-4 p-2">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center font-bold text-white uppercase">{{ user?.username[0] }}</div>
                <div>
                    <div class="text-sm font-bold text-white">{{ user?.full_name }}</div>
                    <div class="text-[10px] text-slate-400">{{ user?.role }}</div>
                </div>
            </div>
            <button @click="logout" class="w-full text-left p-2 hover:bg-slate-700 rounded text-sm text-slate-300">Завершить сеанс</button>
        </div>
    </div>

    <script>
        const { createApp } = Vue;
        createApp({
            data() {
                return {
                    booting: true,
                    isAuthenticated: false,
                    error: null,
                    loginForm: { username: '', password: '' },
                    user: null,
                    currentTime: '',
                    settings: { categories: [], priorities: [], form_fields: [] },
                    windows: [],
                    maxZ: 100,
                    dragging: null,
                    requests: [],
                    newRequest: { title: '', category: '', priority: 'medium' },
                    isMobile: false,
                    showStartMenu: false
                }
            },
            mounted() {
                setTimeout(() => { this.booting = false; }, 1500);
                this.updateClock();
                setInterval(this.updateClock, 1000);
                this.checkAuth();
                this.fetchSettings();
                this.checkMobile();
                window.addEventListener('resize', this.checkMobile);
            },
            methods: {
                checkMobile() {
                    this.isMobile = window.innerWidth <= 768;
                },
                updateClock() {
                    this.currentTime = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                },
                async fetchSettings() {
                    const res = await fetch('api/settings.php');
                    this.settings = await res.json();
                    if (this.settings.categories.length) this.newRequest.category = this.settings.categories[0];
                },
                async fetchRequests() {
                    const token = localStorage.getItem('crm_token');
                    const res = await fetch('api/tasks.php', {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    this.requests = await res.json();
                },
                async checkAuth() {
                    const token = localStorage.getItem('crm_token');
                    if (token) {
                        try {
                            const res = await fetch('api/auth.php?action=me', {
                                headers: { 'Authorization': `Bearer ${token}` }
                            });
                            const data = await res.json();
                            if (data.success) {
                                this.isAuthenticated = true;
                                this.user = data.user;
                                if (this.user.role === 'Administrator') window.location.href = 'admin.php';
                                this.fetchRequests();
                            }
                        } catch (e) { localStorage.removeItem('crm_token'); }
                    }
                },
                async login() {
                    this.error = null;
                    try {
                        const res = await fetch('api/auth.php?action=login', {
                            method: 'POST',
                            body: JSON.stringify(this.loginForm)
                        });
                        const data = await res.json();
                        if (data.success) {
                            localStorage.setItem('crm_token', data.token);
                            this.checkAuth();
                        } else { this.error = data.message; }
                    } catch (e) { this.error = 'Ошибка соединения'; }
                },
                logout() {
                    localStorage.removeItem('crm_token');
                    window.location.reload();
                },
                toggleWindow(type) {
                    const existing = this.windows.find(w => w.id === type);
                    if (existing) {
                        this.closeWindow(type);
                    } else {
                        this.openWindow(type);
                    }
                },
                openWindow(type) {
                    const existing = this.windows.find(w => w.id === type);
                    if (existing) {
                        existing.minimized = false;
                        this.focusWindow(type);
                        return;
                    }
                    const titles = { 'new_request': 'Новая заявка', 'my_requests': 'Мои заявки' };
                    this.maxZ++;
                    this.windows.push({
                        id: type,
                        title: titles[type],
                        x: 100 + (this.windows.length * 30),
                        y: 100 + (this.windows.length * 30),
                        width: 500,
                        height: 500,
                        zIndex: this.maxZ,
                        minimized: false
                    });
                },
                focusWindow(id) {
                    if (this.isMobile) return;
                    const win = this.windows.find(w => w.id === id);
                    if (win) {
                        this.maxZ++;
                        win.zIndex = this.maxZ;
                    }
                },
                closeWindow(id) {
                    this.windows = this.windows.filter(w => w.id !== id);
                },
                toggleMinimize(id) {
                    const win = this.windows.find(w => w.id === id);
                    if (win) {
                        if (win.minimized) {
                            win.minimized = false;
                            this.focusWindow(id);
                        } else if (win.zIndex === this.maxZ) {
                            win.minimized = true;
                        } else {
                            this.focusWindow(id);
                        }
                    }
                },
                onMouseDown(e, id) {
                    if (this.isMobile) return;
                    const win = this.windows.find(w => w.id === id);
                    this.dragging = { win, offsetX: e.clientX - win.x, offsetY: e.clientY - win.y };
                    this.focusWindow(id);
                },
                onMouseMove(e) {
                    if (this.dragging) {
                        this.dragging.win.x = e.clientX - this.dragging.offsetX;
                        this.dragging.win.y = e.clientY - this.dragging.offsetY;
                    }
                },
                onMouseUp() {
                    this.dragging = null;
                },
                async submitRequest() {
                    const token = localStorage.getItem('crm_token');
                    const res = await fetch('api/tasks.php', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify(this.newRequest)
                    });
                    if (res.ok) {
                        this.closeWindow('new_request');
                        this.fetchRequests();
                        alert('Заявка создана успешно!');
                        this.newRequest = { title: '', category: this.settings.categories[0], priority: 'medium' };
                    }
                },
                statusClass(status) {
                    const classes = {
                        'New': 'bg-blue-500/20 text-blue-400',
                        'Assigned': 'bg-purple-500/20 text-purple-400',
                        'In Work': 'bg-yellow-500/20 text-yellow-400',
                        'Completed': 'bg-green-500/20 text-green-400',
                        'Rejected': 'bg-red-500/20 text-red-400'
                    };
                    return classes[status] || 'bg-slate-500/20 text-slate-400';
                }
            }
        }).mount('#app');
    </script>
</body>
</html>