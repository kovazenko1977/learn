<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM PRO - Desktop OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=3540&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            overflow: hidden;
            transition: background 0.5s ease;
        }
        body.light-mode { background: #f1f5f9; }
        [v-cloak] { display: none; }
        .glass { background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .light-mode .glass { background: rgba(255, 255, 255, 0.8); border: 1px solid rgba(0,0,0,0.05); color: #1e293b; }
        .window-shadow { box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6); }
        .taskbar { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(15px); border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .light-mode .taskbar { background: rgba(255, 255, 255, 0.9); border-top: 1px solid rgba(0,0,0,0.1); }
        .desktop-icon { @apply flex flex-col items-center p-3 rounded-2xl transition-all hover:bg-white/10 cursor-pointer w-28 text-center gap-2; }
        .light-mode .desktop-icon { @apply hover:bg-black/5; }
        .light-mode .desktop-icon span { color: #1e293b; text-shadow: none !important; }
        .start-menu { background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }

        /* Kanban inside window scroll */
        .kanban-container { display: flex; gap: 1.5rem; overflow-x: auto; padding-bottom: 1rem; height: 100%; }
        .kanban-column { flex-shrink: 0; width: 300px; background: rgba(15, 23, 42, 0.4); border-radius: 1.25rem; display: flex; flex-col; border: 1px solid rgba(255, 255, 255, 0.05); }

        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body :class="['h-screen select-none transition-colors duration-500', isDarkMode ? 'text-slate-200' : 'text-slate-800 light-mode']">
    <div id="app" v-cloak class="h-full relative">
        <!-- Login Screen -->
        <div v-if="!token" class="absolute inset-0 z-[1000] flex items-center justify-center bg-slate-950/90 backdrop-blur-xl transition-all duration-500">
            <div class="w-full max-w-md glass p-10 rounded-[2.5rem] window-shadow border-white/10">
                <div class="text-center mb-10">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-2xl shadow-blue-500/40">
                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <h1 class="text-4xl font-black text-white tracking-tight">Service <span class="text-blue-500">PRO</span></h1>
                    <p class="text-slate-400 mt-3 font-medium">Система управления предприятием</p>
                </div>
                <form @submit.prevent="login" class="space-y-5">
                    <div class="relative group">
                        <input v-model="loginForm.username" type="text" placeholder="Логин" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 outline-none focus:ring-2 focus:ring-blue-500 focus:bg-slate-900 transition-all text-white placeholder-slate-500">
                    </div>
                    <div class="relative group">
                        <input v-model="loginForm.password" type="password" placeholder="Пароль" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 outline-none focus:ring-2 focus:ring-blue-500 focus:bg-slate-900 transition-all text-white placeholder-slate-500">
                    </div>
                    <button class="w-full bg-blue-600 hover:bg-blue-500 active:scale-95 text-white font-bold py-4 rounded-2xl transition-all shadow-xl shadow-blue-500/30 flex items-center justify-center gap-2">
                        <span>Войти в аккаунт</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </button>
                    <p v-if="error" class="text-red-400 text-sm text-center font-medium bg-red-400/10 py-2 rounded-lg">{{ error }}</p>
                </form>
            </div>
        </div>

        <!-- Desktop Area -->
        <div v-if="token" class="h-full p-6 flex flex-col items-start gap-4 content-start flex-wrap overflow-hidden" @click="isStartMenuOpen = false">
            <div v-for="app in desktopApps" :key="app.id" class="desktop-icon" @dblclick="openApp(app)">
                <div :class="['w-16 h-16 rounded-2xl mb-1 flex items-center justify-center shadow-2xl transition-transform active:scale-90', app.color]">
                    <span v-html="app.icon"></span>
                </div>
                <span class="text-xs font-bold drop-shadow-[0_2px_2px_rgba(0,0,0,1)] text-white tracking-wide">{{ app.name }}</span>
            </div>
        </div>

        <!-- Start Menu -->
        <div v-if="isStartMenuOpen" class="absolute bottom-14 left-4 w-[400px] h-[550px] start-menu rounded-[2rem] window-shadow p-8 z-[500] flex flex-col overflow-hidden animate-in slide-in-from-bottom-4 duration-300">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center font-bold text-xl">{{ user.full_name[0] }}</div>
                <div>
                    <h3 class="font-bold text-lg text-white">{{ user.full_name }}</h3>
                    <p class="text-xs text-blue-400 font-bold uppercase tracking-widest">{{ user.role }}</p>
                </div>
            </div>

            <div class="flex-1 space-y-1 overflow-y-auto pr-2 scrollbar-hide">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Приложения</h4>
                <div v-for="app in availableApps" @click="openApp(app); isStartMenuOpen = false" class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/10 transition-all cursor-pointer group">
                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform', app.color]" v-html="app.smallIcon"></div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-white">{{ app.name }}</p>
                        <p class="text-[10px] text-slate-500">{{ app.desc }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button @click="logout" class="flex items-center gap-2 text-slate-400 hover:text-white transition-all text-sm font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span>Выход</span>
                    </button>
                    <button @click="isDarkMode = !isDarkMode" class="text-slate-400 hover:text-white">
                        <svg v-if="isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.243 16.243l.707.707M7.757 7.757l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" /></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </button>
                </div>
                <span class="text-[10px] font-black text-slate-600 uppercase">WES.BY CRM OS</span>
            </div>
        </div>

        <!-- Windows Layer -->
        <div v-for="win in windows" :key="win.id" v-show="!win.minimized" :style="{ zIndex: win.zIndex, left: win.x + 'px', top: win.y + 'px', width: win.width + 'px', height: win.height + 'px' }" class="absolute glass rounded-[2rem] window-shadow flex flex-col overflow-hidden animate-in zoom-in-95 duration-200" @mousedown="focusWindow(win)">
            <!-- Window Titlebar -->
            <div class="h-12 bg-slate-900/40 flex items-center justify-between px-6 cursor-default" @mousedown="startDrag($event, win)">
                <div class="flex items-center gap-3">
                    <div :class="['w-6 h-6 rounded-lg flex items-center justify-center', win.appColor]" v-html="win.smallIcon"></div>
                    <span class="text-xs font-black text-white uppercase tracking-widest">{{ win.title }}</span>
                </div>
                <div class="flex gap-3">
                    <button @click.stop="minimizeWindow(win)" class="w-3.5 h-3.5 rounded-full bg-yellow-500/30 hover:bg-yellow-500 transition-colors"></button>
                    <button @click.stop="closeWindow(win)" class="w-3.5 h-3.5 rounded-full bg-red-500/30 hover:bg-red-500 transition-colors"></button>
                </div>
            </div>

            <!-- Window Content -->
            <div class="flex-1 overflow-hidden relative bg-slate-950/20">
                <!-- App Components -->
                <div class="w-full h-full p-8 overflow-y-auto scrollbar-hide">

                    <!-- NEW REQUEST -->
                    <div v-if="win.appId === 'new_request'" class="max-w-2xl mx-auto">
                        <div class="mb-10">
                            <h2 class="text-3xl font-black text-white mb-2">Новая заявка</h2>
                            <p class="text-slate-400">Опишите вашу проблему, и мы решим её максимально быстро.</p>
                        </div>
                        <form @submit.prevent="submitRequest(win)" class="space-y-6">
                            <div v-for="field in settings.form_fields" :key="field.id" class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ field.label }}</label>
                                <input v-if="field.type !== 'textarea'" v-model="win.data[field.id]" :type="field.type" :required="field.required" class="w-full bg-slate-900/60 border border-slate-800 rounded-2xl px-5 py-3.5 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                <textarea v-else v-model="win.data[field.id]" :required="field.required" rows="5" class="w-full bg-slate-900/60 border border-slate-800 rounded-2xl px-5 py-3.5 outline-none focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Приоритет</label>
                                <div class="grid grid-cols-3 gap-4">
                                    <button v-for="p in ['Low', 'Medium', 'High']" type="button" @click="win.data.priority = p" :class="['py-3 rounded-2xl border font-bold transition-all', win.data.priority === p ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-900/40 border-slate-800 text-slate-500']">
                                        {{ p === 'Low' ? 'Низкий' : p === 'Medium' ? 'Средний' : 'Высокий' }}
                                    </button>
                                </div>
                            </div>
                            <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-2xl shadow-blue-500/20 mt-4">Опубликовать заявку</button>
                        </form>
                    </div>

                    <!-- KANBAN APP -->
                    <div v-if="win.appId === 'kanban'" class="h-full flex flex-col">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-3xl font-black text-white mb-1">Канбан-доска</h2>
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Оперативное управление задачами</p>
                            </div>
                            <input v-model="search" type="text" placeholder="Мгновенный поиск по ID или теме..." class="bg-slate-900/60 border border-slate-800 rounded-2xl px-6 py-3 text-sm w-96 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="kanban-container scrollbar-hide">
                            <div v-for="status in ['New', 'In Work', 'Completed', 'Rejected']" class="kanban-column flex flex-col p-4">
                                <div class="flex items-center justify-between mb-6 px-2">
                                    <div>
                                        <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ statusTranslations[status] }}</h3>
                                        <span class="bg-white/5 text-slate-400 px-3 py-1 rounded-full text-[10px] font-black">{{ filteredTasks.filter(t => t.status === status).length }}</span>
                                    </div>
                                    <button v-if="isAdmin && status === 'New'" @click="massAssign(status)" class="text-[9px] font-black text-blue-500 hover:text-blue-400 uppercase tracking-tighter bg-blue-500/10 px-2 py-1 rounded-lg transition-all">Mass Assign</button>
                                </div>
                                <div class="flex-1 space-y-4 overflow-y-auto scrollbar-hide" @dragover.prevent @drop="dropTask($event, status)">
                                    <div v-for="task in filteredTasks.filter(t => t.status === status)" :key="task.id" draggable="true" @dragstart="dragTask($event, task)" @click="openTaskDetail(task)" class="bg-slate-900/60 border border-slate-800/50 p-5 rounded-[1.5rem] hover:border-blue-500/50 transition-all cursor-pointer group active:scale-95">
                                        <div class="flex justify-between items-center mb-3">
                                            <span :class="['px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest', priorityClass(task.priority)]">{{ task.priority }}</span>
                                            <span class="text-[10px] font-bold text-slate-600 font-mono">#{{ task.id.toString().slice(-4) }}</span>
                                        </div>
                                        <h4 class="font-bold text-white mb-2 leading-snug">{{ task.fields.f_subject }}</h4>
                                        <p class="text-[11px] text-slate-500 line-clamp-2 mb-4">{{ task.fields.f_description }}</p>
                                        <div class="flex items-center justify-between pt-4 border-t border-white/5">
                                            <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] font-black">{{ task.creator[0] }}</div>
                                            <span class="text-[9px] font-black text-slate-600">{{ formatDate(task.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ANALYTICS APP -->
                    <div v-if="win.appId === 'analytics'">
                        <h2 class="text-3xl font-black text-white mb-8">Аналитика системы</h2>
                        <div class="grid grid-cols-4 gap-6 mb-10">
                            <div v-for="(val, label) in { 'Всего задач': stats.total, 'Активных': (stats.by_status?.New + stats.by_status?.['In Work']), 'SLA OK': stats.sla?.on_time, 'AVG Время': stats.avg_completion_time + 'ч' }" class="bg-slate-900/40 border border-slate-800 p-6 rounded-[2rem] flex flex-col justify-center">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">{{ label }}</p>
                                <p class="text-4xl font-black text-white">{{ val }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-8">
                            <div class="bg-slate-900/40 border border-slate-800 p-8 rounded-[2.5rem]">
                                <h3 class="text-xl font-bold mb-6">Распределение по статусам</h3>
                                <div class="space-y-6">
                                    <div v-for="(count, status) in stats.by_status" class="space-y-2">
                                        <div class="flex justify-between text-xs font-black uppercase tracking-widest">
                                            <span class="text-slate-400">{{ statusTranslations[status] }}</span>
                                            <span class="text-white">{{ count }}</span>
                                        </div>
                                        <div class="w-full bg-slate-950 h-3 rounded-full overflow-hidden">
                                            <div :class="['h-full transition-all duration-1000', statusColor(status)]" :style="{ width: (count / stats.total * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-900/40 border border-slate-800 p-8 rounded-[2.5rem]">
                                <h3 class="text-xl font-bold mb-6">Загрузка исполнителей</h3>
                                <div class="space-y-6">
                                    <div v-for="emp in stats.workload" class="space-y-2">
                                        <div class="flex justify-between text-xs font-black uppercase tracking-widest">
                                            <span class="text-slate-400">{{ emp.name }}</span>
                                            <span class="text-white">{{ emp.count }} задач</span>
                                        </div>
                                        <div class="w-full bg-slate-950 h-3 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 transition-all duration-1000" :style="{ width: (emp.count / Math.max(...stats.workload.map(e => e.count), 1) * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- USER MANAGER -->
                    <div v-if="win.appId === 'users'">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-3xl font-black text-white">Команда</h2>
                            <button @click="openCreateUser" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-2xl font-bold text-sm shadow-xl shadow-blue-500/20 transition-all">+ Новый сотрудник</button>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div v-for="u in usersList" class="bg-slate-900/40 border border-slate-800 p-6 rounded-3xl flex items-center justify-between group">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-800 flex items-center justify-center text-2xl font-black text-blue-500 group-hover:bg-blue-600 group-hover:text-white transition-all">{{ u.full_name[0] }}</div>
                                    <div>
                                        <p class="text-lg font-bold text-white">{{ u.full_name }}</p>
                                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ u.role }}</p>
                                    </div>
                                </div>
                                <button class="p-3 text-slate-600 hover:text-white transition-all opacity-0 group-hover:opacity-100">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- MY REQUESTS -->
                    <div v-if="win.appId === 'my_requests'">
                        <h2 class="text-3xl font-black text-white mb-8">Мои заявки</h2>
                        <div class="space-y-4">
                            <div v-for="t in tasks" :key="t.id" class="bg-slate-900/40 border border-slate-800 p-6 rounded-[2rem] hover:bg-slate-900/60 transition-all flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-[10px] font-black text-slate-600 font-mono tracking-widest">#{{ t.id }}</span>
                                        <span :class="['px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest', priorityClass(t.priority)]">{{ t.priority }}</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-1">{{ t.fields.f_subject }}</h4>
                                    <p class="text-sm text-slate-500 line-clamp-1">{{ t.fields.f_description }}</p>
                                </div>
                                <div class="text-right ml-8">
                                    <div :class="['inline-block px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest mb-2', t.status === 'Completed' ? 'bg-green-500/20 text-green-400' : 'bg-blue-500/20 text-blue-400']">{{ statusTranslations[t.status] }}</div>
                                    <p class="text-[10px] font-bold text-slate-600">{{ formatDate(t.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="!tasks.length" class="text-center py-20 text-slate-600">
                                <p class="text-lg font-bold">У вас пока нет заявок</p>
                                <button @click="openApp(apps.new_request)" class="mt-4 text-blue-500 hover:underline">Создать первую</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Details Modal (Overlaying everything but staying inside desktop) -->
        <div v-if="selectedTask" class="absolute inset-0 z-[800] flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-10">
            <div class="w-full max-w-5xl h-[80vh] bg-slate-900 rounded-[3rem] window-shadow border border-white/10 flex flex-col overflow-hidden">
                <div class="p-8 border-b border-white/5 flex justify-between items-center bg-slate-900/80">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span :class="['px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-[0.2em]', priorityClass(selectedTask.priority)]">{{ selectedTask.priority }}</span>
                            <h3 class="text-2xl font-black text-white">{{ selectedTask.fields.f_subject }}</h3>
                        </div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">ID #{{ selectedTask.id }} • Создана: {{ formatDate(selectedTask.created_at) }}</p>
                    </div>
                    <button @click="selectedTask = null" class="w-12 h-12 rounded-2xl bg-slate-800 hover:bg-red-500 text-slate-400 hover:text-white transition-all flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex flex-1 overflow-hidden">
                    <div class="w-2/3 overflow-y-auto p-10 space-y-10 scrollbar-hide">
                        <div>
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Описание задачи</h4>
                            <div class="bg-slate-950/50 p-8 rounded-[2rem] text-slate-200 leading-relaxed border border-white/5">
                                {{ selectedTask.fields.f_description }}
                            </div>
                        </div>

                        <div>
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Диалог</h4>
                            <div class="space-y-6 mb-10">
                                <div v-for="c in selectedTask.comments" class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-[10px] font-black">{{ c.by[0] }}</div>
                                        <span class="text-xs font-black text-white tracking-wide">{{ c.by }}</span>
                                        <span class="text-[10px] text-slate-600 font-bold">{{ formatDate(c.at) }}</span>
                                    </div>
                                    <div class="bg-white/5 p-5 rounded-[1.5rem] rounded-tl-none border border-white/5 ml-11 text-sm text-slate-300">
                                        {{ c.text }}
                                    </div>
                                </div>
                                <div v-if="!selectedTask.comments?.length" class="text-center py-10 text-slate-600 text-sm font-bold italic bg-white/5 rounded-[2rem]">Сообщений пока нет</div>
                            </div>
                            <div class="flex gap-4 items-center sticky bottom-0 bg-slate-900 py-4">
                                <input v-model="newComment" @keyup.enter="addComment" type="text" placeholder="Ваш ответ..." class="flex-1 bg-slate-950 border border-white/5 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                <button @click="addComment" class="w-14 h-14 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl transition-all shadow-xl shadow-blue-500/20 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="w-1/3 border-l border-white/5 bg-slate-950/20 p-10 space-y-10 overflow-y-auto scrollbar-hide">
                        <div>
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Управление</h4>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-slate-600 uppercase">Статус</label>
                                    <select v-model="selectedTask.status" @change="updateTaskStatus(selectedTask)" class="w-full bg-slate-900 border border-white/5 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500">
                                        <option v-for="(name, code) in statusTranslations" :value="code">{{ name }}</option>
                                    </select>
                                </div>
                                <div v-if="isAdmin" class="space-y-2">
                                    <label class="text-[9px] font-black text-slate-600 uppercase">Исполнитель</label>
                                    <select v-model="selectedTask.assigned_to" @change="assignTask(selectedTask.id, selectedTask.assigned_to)" class="w-full bg-slate-900 border border-white/5 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500">
                                        <option :value="null">Не назначен</option>
                                        <option v-for="u in usersList" :value="u.id">{{ u.full_name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Дедлайн (SLA)</h4>
                            <div class="p-5 bg-slate-900 rounded-2xl border border-white/5">
                                <p class="text-sm font-black text-white mb-1">{{ formatDate(selectedTask.deadline) }}</p>
                                <p class="text-[10px] font-black text-blue-500 uppercase">Соблюдение регламента</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Лог событий</h4>
                            <div class="space-y-6">
                                <div v-for="h in selectedTask.history" class="relative pl-6 border-l-2 border-white/5">
                                    <div class="absolute -left-1.5 top-0 w-3 h-3 rounded-full bg-blue-600 shadow-lg shadow-blue-500/50"></div>
                                    <p class="text-[11px] font-bold text-slate-300 leading-tight mb-1">{{ h.msg }}</p>
                                    <p class="text-[9px] font-black text-slate-600 uppercase">{{ formatDate(h.at) }} • {{ h.by }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Taskbar -->
        <div v-if="token" class="absolute bottom-0 left-0 right-0 h-14 taskbar flex items-center justify-between px-6 z-[600]">
            <div class="flex items-center gap-3">
                <button @click="isStartMenuOpen = !isStartMenuOpen" :class="['w-10 h-10 rounded-xl transition-all flex items-center justify-center shadow-lg', isStartMenuOpen ? 'bg-blue-600 text-white scale-90' : 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white hover:scale-110 active:scale-95']">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                </button>
                <div class="w-px h-6 bg-white/10 mx-2"></div>
                <div v-for="win in windows" :key="win.id" @click="toggleWindow(win)" :class="['h-10 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all cursor-pointer flex items-center gap-2 border', win.id === activeWindowId && !win.minimized ? 'bg-white/10 border-white/20 text-white shadow-xl' : 'bg-transparent border-transparent text-slate-500 hover:bg-white/5']">
                    <div :class="['w-2 h-2 rounded-full', win.appColor.replace('bg-', 'bg-')]"></div>
                    {{ win.title }}
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-[10px] font-black text-white uppercase tracking-widest">{{ currentTime }}</p>
                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ currentDate }}</p>
                </div>
                <div class="w-px h-6 bg-white/10"></div>
                <button class="text-slate-400 hover:text-white transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        const { createApp, ref, computed, onMounted, watch } = Vue;

        const apps = {
            new_request: { id: 'new_request', name: 'Новая заявка', desc: 'Создать обращение в техподдержку', color: 'bg-blue-600', icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>', smallIcon: '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>' },
            my_requests: { id: 'my_requests', name: 'Мои заявки', desc: 'История и статус ваших обращений', color: 'bg-emerald-600', icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2" /></svg>', smallIcon: '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2" /></svg>' },
            kanban: { id: 'kanban', name: 'Канбан-доска', desc: 'Управление жизненным циклом заявок', color: 'bg-indigo-600', icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>', smallIcon: '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>' },
            analytics: { id: 'analytics', name: 'Аналитика', desc: 'Отчеты, SLA и KPI сотрудников', color: 'bg-purple-600', icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>', smallIcon: '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
            users: { id: 'users', name: 'Команда', desc: 'Управление персоналом и правами', color: 'bg-orange-600', icon: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', smallIcon: '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>' }
        };

        createApp({
            setup() {
                const token = ref(localStorage.getItem('token'));
                const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
                const loginForm = ref({ username: '', password: '' });
                const error = ref('');
                const windows = ref([]);
                const nextZIndex = ref(10);
                const activeWindowId = ref(null);
                const isStartMenuOpen = ref(false);
                const isDarkMode = ref(true);
                const settings = ref({});
                const tasks = ref([]);
                const stats = ref({});
                const usersList = ref([]);
                const currentTime = ref('');
                const currentDate = ref('');
                const search = ref('');
                const selectedTask = ref(null);
                const newComment = ref('');

                const statusTranslations = { 'New': 'Новые', 'In Work': 'В работе', 'Completed': 'Выполнено', 'Rejected': 'Отклонено' };

                const isAdmin = computed(() => ['Administrator', 'Head of Department'].includes(user.value.role));

                const availableApps = computed(() => {
                    const base = [apps.new_request, apps.my_requests];
                    if (isAdmin.value) {
                        base.push(apps.kanban, apps.analytics);
                    }
                    if (user.value.role === 'Administrator') {
                        base.push(apps.users);
                    }
                    return base;
                });

                const desktopApps = computed(() => availableApps.value.slice(0, 4));

                const updateTime = () => {
                    const now = new Date();
                    currentTime.value = now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                    currentDate.value = now.toLocaleDateString('ru-RU', { day: '2-digit', month: 'long' });
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
                        fetchData();
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

                const fetchData = async () => {
                    if (!token.value) return;
                    const headers = { 'Authorization': `Bearer ${token.value}` };

                    const [setResp, taskResp, statsResp, userResp] = await Promise.all([
                        fetch('api/settings.php', { headers }),
                        fetch('api/tasks.php', { headers }),
                        fetch('api/dashboard.php', { headers }),
                        fetch('api/users.php', { headers })
                    ]);

                    settings.value = await setResp.json();
                    tasks.value = await taskResp.json();
                    stats.value = await statsResp.json();
                    if (userResp.ok) usersList.value = await userResp.json();

                    if (selectedTask.value) {
                        selectedTask.value = tasks.value.find(t => t.id == selectedTask.value.id);
                    }
                };

                const openApp = (app) => {
                    const existing = windows.value.find(w => w.appId === app.id);
                    if (existing) {
                        existing.minimized = false;
                        focusWindow(existing);
                        return;
                    }
                    const win = {
                        id: Date.now(),
                        appId: app.id,
                        title: app.name,
                        appColor: app.color,
                        smallIcon: app.smallIcon,
                        x: 100 + (windows.value.length * 40),
                        y: 80 + (windows.value.length * 40),
                        width: ['kanban', 'analytics', 'users'].includes(app.id) ? 1200 : 800,
                        height: ['kanban', 'analytics', 'users'].includes(app.id) ? 800 : 700,
                        zIndex: nextZIndex.value++,
                        minimized: false,
                        data: { priority: 'Medium' }
                    };
                    windows.value.push(win);
                    activeWindowId.value = win.id;
                };

                const focusWindow = (win) => {
                    win.zIndex = nextZIndex.value++;
                    activeWindowId.value = win.id;
                };

                const closeWindow = (win) => {
                    windows.value = windows.value.filter(w => w.id !== win.id);
                };

                const minimizeWindow = (win) => { win.minimized = true; };
                const toggleWindow = (win) => {
                    if (win.minimized) { win.minimized = false; focusWindow(win); }
                    else if (activeWindowId.value === win.id) { win.minimized = true; }
                    else { focusWindow(win); }
                };

                const startDrag = (e, win) => {
                    const startX = e.clientX - win.x;
                    const startY = e.clientY - win.y;
                    focusWindow(win);
                    const onMove = (me) => { win.x = me.clientX - startX; win.y = me.clientY - startY; };
                    const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); };
                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                };

                const submitRequest = async (win) => {
                    const resp = await fetch('api/tasks.php?action=create', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify(win.data)
                    });
                    if (resp.ok) {
                        alert('Заявка успешно создана!');
                        closeWindow(win);
                        fetchData();
                    }
                };

                const openTaskDetail = (task) => { selectedTask.value = task; };

                const addComment = async () => {
                    if (!newComment.value.trim()) return;
                    await fetch('api/tasks.php?action=add_comment', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: selectedTask.value.id, comment: newComment.value })
                    });
                    newComment.value = '';
                    fetchData();
                };

                const updateTaskStatus = async (task) => {
                    await fetch('api/tasks.php?action=update_status', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: task.id, status: task.status })
                    });
                    fetchData();
                };

                const assignTask = async (taskId, execId) => {
                    await fetch('api/tasks.php?action=assign', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: taskId, executor_id: execId })
                    });
                    fetchData();
                };

                const massAssign = async (status) => {
                    const executorId = prompt('Enter Executor ID (or select from list):');
                    if (!executorId) return;

                    const tasksToAssign = tasks.value.filter(t => t.status === status).map(t => t.id);
                    if (!tasksToAssign.length) return;

                    await fetch('api/tasks.php?action=mass_assign', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: tasksToAssign, executor_id: executorId })
                    });
                    fetchData();
                };

                const dragTask = (ev, task) => { ev.dataTransfer.setData('taskId', task.id); };
                const dropTask = async (ev, status) => {
                    const taskId = ev.dataTransfer.getData('taskId');
                    await fetch('api/tasks.php?action=update_status', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token.value}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: taskId, status })
                    });
                    fetchData();
                };

                const priorityClass = (p) => {
                    if (p === 'High') return 'bg-red-500/20 text-red-400 border border-red-500/50';
                    if (p === 'Medium') return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/50';
                    return 'bg-blue-500/20 text-blue-400 border border-blue-500/50';
                };

                const statusColor = (s) => {
                    if (s === 'New') return 'bg-blue-500';
                    if (s === 'In Work') return 'bg-yellow-500';
                    if (s === 'Completed') return 'bg-green-500';
                    return 'bg-red-500';
                };

                const formatDate = (d) => d ? new Date(d).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '-';

                const filteredTasks = computed(() => {
                    if (!search.value) return tasks.value;
                    const s = search.value.toLowerCase();
                    return tasks.value.filter(t => t.fields?.f_subject?.toLowerCase().includes(s) || t.id.toString().includes(s));
                });

                onMounted(() => {
                    updateTime(); setInterval(updateTime, 1000);
                    if (token.value) fetchData();
                    setInterval(fetchData, 10000);
                });

                return {
                    token, user, loginForm, error, windows, activeWindowId, isStartMenuOpen, isDarkMode, settings, tasks, stats, usersList, currentTime, currentDate, search, selectedTask, newComment,
                    isAdmin, availableApps, desktopApps, statusTranslations, apps,
                    login, logout, openApp, focusWindow, closeWindow, minimizeWindow, toggleWindow, startDrag,
                    submitRequest, openTaskDetail, addComment, updateTaskStatus, assignTask, massAssign, dragTask, dropTask, priorityClass, statusColor, formatDate, filteredTasks
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
