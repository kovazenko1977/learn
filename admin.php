<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM - Панель управления</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .drag-over { @apply border-2 border-dashed border-blue-500 bg-blue-50; }
        .dark .card { @apply bg-slate-900/80 border-slate-800; }
        .dark .input-field { @apply bg-slate-800 border-slate-700 text-white; }
        .dark header { @apply bg-slate-900 border-slate-800 text-white; }
        .dark .bg-slate-50 { @apply bg-slate-950; }
        .dark .bg-slate-100\/50 { @apply bg-slate-900\/50; }
        .dark .text-slate-900 { @apply text-white; }
        .dark .text-slate-700 { @apply text-slate-300; }
        .dark .text-slate-500 { @apply text-slate-400; }
        .dark .modal-content { @apply bg-slate-900 text-white; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen transition-colors duration-300" :class="{ 'dark bg-slate-950 text-slate-100': isDarkMode }">
    <div id="app" v-cloak>
        <!-- Professional Login Overlay -->
        <div v-if="!user" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a] overflow-hidden p-4">
            <!-- Animated Background -->
            <div class="absolute inset-0 z-0">
                <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/20 blur-[120px] rounded-full"></div>
                <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/20 blur-[120px] rounded-full"></div>
            </div>

            <div class="relative z-10 max-w-md w-full animate-in fade-in zoom-in duration-500">
                <div class="card !bg-white/5 !backdrop-blur-2xl !border-white/10 !shadow-2xl !p-10 !rounded-[2.5rem]">
                    <div class="text-center mb-10">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-tr from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/30 mb-6 rotate-3">
                             <span class="text-3xl font-black text-white">CRM</span>
                        </div>
                        <h2 class="text-3xl font-black text-white mb-2">Добро пожаловать</h2>
                        <p class="text-slate-400 text-sm">Система управления заявками PRO</p>
                    </div>

                    <form @submit.prevent="login" class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Логин</label>
                            <div class="relative">
                                <input v-model="loginForm.username" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all placeholder-white/20" placeholder="Ваш логин" required>
                                <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Пароль</label>
                            <div class="relative">
                                <input v-model="loginForm.password" type="password" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all placeholder-white/20" placeholder="••••••••" required>
                                <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                        </div>

                        <div v-if="loginError" class="bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold py-3 px-4 rounded-xl text-center">
                            {{ loginError }}
                        </div>

                        <div class="flex justify-end">
                            <button type="button" @click="showForgotModal = true" class="text-xs font-bold text-blue-500 hover:text-blue-400 transition-colors">Забыли пароль?</button>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-5 rounded-2xl shadow-xl shadow-blue-500/20 transition-all active:scale-[0.98] mt-4 flex items-center justify-center gap-3">
                            <span>Войти в панель</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </button>
                    </form>

                    <div class="mt-10 text-center">
                        <p class="text-slate-500 text-xs tracking-tight">Разработано WES.BY</p>
                    </div>
                </div>
            </div>
        </div>

        <template v-else>
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 sticky top-0 z-30 transition-all duration-300">
                <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <span class="text-xl font-black tracking-tighter">CRM<span class="text-blue-600">PRO</span></span>
                        <!-- Mobile Menu Trigger -->
                        <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-slate-500">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                        </button>
                        <nav class="hidden md:flex gap-4">
                            <button @click="tab = 'tasks'" :class="tab === 'tasks' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">{{ t.tasks }}</button>
                            <button v-if="isAdmin" @click="tab = 'users'" :class="tab === 'users' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">{{ t.users }}</button>
                            <button v-if="isAdmin" @click="tab = 'settings'" :class="tab === 'settings' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">{{ t.settings }}</button>
                            <button @click="tab = 'analytics'" :class="tab === 'analytics' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">{{ t.analytics }}</button>
                            <button @click="tab = 'profile'" :class="tab === 'profile' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">{{ t.profile }}</button>
                        </nav>
                    </div>
                    <div class="flex items-center gap-4">
                        <button @click="toggleLang" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-blue-500 transition-colors">
                            {{ lang }}
                        </button>
                        <button @click="isDarkMode = !isDarkMode" class="p-2 text-slate-400 hover:text-blue-500 transition-colors">
                            <svg v-if="!isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </button>
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400 hidden sm:inline">{{ user.full_name }} ({{ user.role }})</span>
                        <button @click="logout" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </button>
                    </div>
                </div>
                <!-- Mobile Navigation -->
                <transition name="fade">
                    <div v-if="mobileMenu" class="md:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-4 space-y-2">
                        <button v-for="t in menuItems" :key="t.id" v-if="t.show" @click="tab = t.id; mobileMenu = false" :class="tab === t.id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600' : 'text-slate-600 dark:text-slate-400'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all">{{ t.label }}</button>
                    </div>
                </transition>
            </header>

            <main class="max-w-7xl mx-auto px-4 py-6 md:py-8">
                <!-- Task Management -->
                <div v-if="tab === 'tasks'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">{{ t.control_center }}</h2>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">{{ t.monitoring }}</p>
                        </div>
                        <div class="flex flex-wrap gap-4 flex-1 max-w-2xl">
                            <div class="relative flex-1 min-w-[250px]">
                                <input v-model="searchQuery" type="text" :placeholder="t.search_placeholder" class="input-field !pl-12 !py-3 w-full shadow-sm">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <select v-model="filterPriority" class="input-field !py-3 w-auto shadow-sm">
                                <option value="">{{ t.all_priorities }}</option>
                                <option value="low">Низкий</option>
                                <option value="medium">Средний</option>
                                <option value="high">Высокий</option>
                                <option value="urgent">Срочный</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button @click="exportCSV" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors">CSV</button>
                            <button @click="exportPDF" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors">PDF</button>
                            <a href="index.php" class="btn-primary flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ t.create }}
                            </a>
                        </div>
                    </div>

                    <!-- Kanban / List view -->
                    <div class="flex flex-col xl:flex-row gap-6">
                        <div class="flex-1 overflow-x-auto pb-8 -mx-4 px-4 lg:grid lg:grid-cols-5 gap-6 scrollbar-hide">
                        <div v-for="status in statuses" :key="status.id" class="flex-shrink-0 w-80 lg:w-auto space-y-5">
                            <div class="flex items-center justify-between px-4 py-2 bg-white/50 dark:bg-slate-900/50 rounded-2xl backdrop-blur-sm border border-white/20 dark:border-slate-800 shadow-sm">
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ status.label }}</h3>
                                <span class="bg-blue-600 text-white px-2.5 py-0.5 rounded-lg text-[10px] font-bold shadow-lg shadow-blue-500/20">{{ filteredTasks(status.id).length }}</span>
                            </div>
                            <div v-if="canAssign" class="flex gap-2 mb-2 px-2">
                                <button @click="selectAll(status.id)" class="text-[10px] text-blue-600 font-bold uppercase">Все</button>
                                <button @click="deselectAll(status.id)" class="text-[10px] text-slate-400 font-bold uppercase">Сброс</button>
                                <select v-if="selectedTaskIds.length > 0" @change="massAssign($event.target.value)" class="text-[10px] bg-white border border-slate-200 rounded px-1">
                                    <option value="">Назначить...</option>
                                    <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                                </select>
                            </div>
                            <div
                                class="bg-slate-100/30 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-3 min-h-[600px] space-y-4 transition-all duration-300"
                                @dragover.prevent
                                @drop="onDrop($event, status.id)"
                            >
                                <div
                                    v-for="task in filteredTasks(status.id)"
                                    :key="task.id"
                                    class="card !p-5 cursor-grab active:cursor-grabbing hover:scale-[1.02] hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-300 relative group border-slate-100 dark:border-slate-800 shadow-sm"
                                    draggable="true"
                                    @dragstart="onDragStart($event, task)"
                                    @click="openTask(task)"
                                >
                                    <input v-if="canAssign" type="checkbox" v-model="selectedTaskIds" :value="task.id" @click.stop class="absolute top-4 right-4 w-4 h-4 rounded border-slate-300">
                                    <div class="flex items-start justify-between mb-4">
                                        <span class="text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-md">#{{ task.id }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(task.created_at) }}</span>
                                    </div>
                                    <h4 class="font-bold text-sm mb-2 text-slate-800 dark:text-slate-100 leading-snug group-hover:text-blue-600 transition-colors">{{ task.title }}</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mb-5 leading-relaxed">{{ task.description }}</p>

                                    <div class="flex items-center justify-between mt-auto">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center text-[10px] font-black text-slate-600 dark:text-slate-400 border border-white/50 dark:border-slate-600 shadow-sm" :title="task.category">{{ (task.category || 'U')[0].toUpperCase() }}</div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ task.category }}</span>
                                        </div>
                                        <div :class="priorityColor(task.priority)" class="text-[9px] font-black uppercase tracking-widest px-2 py-1 bg-current/10 rounded-lg">
                                            {{ priorityLabel(task.priority) }}
                                        </div>
                                    </div>

                                    <div v-if="canAssign" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <select
                                            :value="task.executor_id"
                                            @change="assignTask(task, $event.target.value)"
                                            @click.stop
                                            class="w-full text-[10px] bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-2 px-3 focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer"
                                        >
                                            <option :value="null">👤 Без исполнителя</option>
                                            <option v-for="u in executors" :key="u.id" :value="u.id">👤 {{ u.full_name }}</option>
                                        </select>
                                    </div>
                                    <div v-else-if="task.executor_name" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[8px] text-blue-600">👤</div>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ task.executor_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <!-- Assignment Sidebar -->
                        <div v-if="canAssign" class="w-full xl:w-72 space-y-4">
                            <div class="flex items-center justify-between px-4 py-2 bg-white/50 dark:bg-slate-900/50 rounded-2xl border border-white/20 dark:border-slate-800 shadow-sm">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ t.assignment }}</h3>
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="u in executors"
                                    :key="u.id"
                                    class="card !p-4 border-2 border-transparent transition-all duration-300"
                                    :class="{'!border-blue-500 bg-blue-50/50 dark:bg-blue-900/20': dragOverExecutor === u.id}"
                                    @dragover.prevent="dragOverExecutor = u.id"
                                    @dragleave="dragOverExecutor = null"
                                    @drop="onDropExecutor($event, u)"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center text-xs font-black text-slate-600 dark:text-slate-400 border border-white/50 dark:border-slate-600 shadow-sm">
                                            {{ u.full_name[0] }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 dark:text-white">{{ u.full_name }}</div>
                                            <div class="text-[10px] text-slate-500">{{ tasks.filter(t => t.executor_id == u.id && t.status !== 'completed').length }} {{ t.active_tasks }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Management -->
                <div v-if="tab === 'users'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Пользователи</h2>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Управление доступом и ролями</p>
                        </div>
                        <button @click="showUserModal = true; editingUser = null; userForm = {username: '', password: '', role: 'executor', full_name: ''}" class="btn-primary flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            Новый пользователь
                        </button>
                    </div>

                    <div class="card !p-0 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">ФИО / Логин</th>
                                    <th class="px-6 py-4">Роль</th>
                                    <th class="px-6 py-4 text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="u in usersList" :key="u.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 text-xs font-bold text-slate-400">#{{ u.id }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ u.full_name }}</div>
                                        <div class="text-[10px] text-slate-500">@{{ u.username }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="{
                                            'bg-purple-100 text-purple-700': u.role === 'admin',
                                            'bg-blue-100 text-blue-700': u.role === 'head',
                                            'bg-green-100 text-green-700': u.role === 'executor',
                                            'bg-orange-100 text-orange-700': u.role === 'responsible'
                                        }" class="text-[10px] font-black uppercase px-2 py-1 rounded-lg">
                                            {{ u.role }}
                                        </span>
                                    </td>
                                     <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                        <button @click="editUser(u)" class="text-blue-600 hover:text-blue-700 p-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                        <button v-if="u.id !== user.id" @click="deleteUser(u.id)" class="text-red-500 hover:text-red-600 p-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div v-if="tab === 'settings'" class="max-w-4xl space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                     <div class="card">
                         <h3 class="text-xl font-bold mb-6">Конструктор форм</h3>
                         <div class="space-y-4">
                             <div v-for="(field, index) in settings.form_fields" :key="index" class="flex items-center gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                                 <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                     <input v-model="field.label" class="input-field" placeholder="Название поля">
                                     <select v-model="field.type" class="input-field">
                                         <option value="text">Текст</option>
                                         <option value="number">Число</option>
                                         <option value="date">Дата</option>
                                         <option value="checkbox">Галочка</option>
                                     </select>
                                     <div class="flex items-center gap-2">
                                         <input type="checkbox" v-model="field.required" :id="'req_' + index">
                                         <label :for="'req_' + index" class="text-xs font-bold">Обязательное</label>
                                     </div>
                                 </div>
                                 <button @click="settings.form_fields.splice(index, 1)" class="text-red-500 p-2 hover:bg-red-50 rounded-lg transition-colors">✕</button>
                             </div>
                             <button @click="settings.form_fields.push({id: Date.now(), label: '', type: 'text', required: false})" class="w-full border-2 border-dashed border-slate-300 rounded-xl py-4 text-slate-500 hover:border-blue-500 hover:text-blue-500 transition-all">
                                 + Добавить поле
                             </button>
                         </div>
                     </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                         <div class="card">
                             <h3 class="text-xl font-bold mb-6">MySQL Connection</h3>
                             <div class="space-y-4">
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Host</label>
                                     <input v-model="settings.mysql.host" type="text" class="input-field" placeholder="localhost">
                                 </div>
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Database</label>
                                     <input v-model="settings.mysql.db" type="text" class="input-field" placeholder="crm_db">
                                 </div>
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">User</label>
                                     <input v-model="settings.mysql.user" type="text" class="input-field" placeholder="root">
                                 </div>
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Password</label>
                                     <input v-model="settings.mysql.pass" type="password" class="input-field" placeholder="••••••••">
                                 </div>
                             </div>
                         </div>

                         <div class="card">
                             <h3 class="text-xl font-bold mb-6">Основные настройки</h3>
                             <div class="space-y-4">
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Режим хранения</label>
                                     <select v-model="settings.storage_mode" class="input-field">
                                         <option value="json">JSON</option>
                                         <option value="mysql">MySQL</option>
                                     </select>
                                 </div>
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Email администратора</label>
                                     <input v-model="settings.admin_email" type="email" class="input-field">
                                 </div>
                                 <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                     <label class="block text-sm font-bold mb-4 uppercase tracking-widest text-slate-400">Правила SLA (часов)</label>
                                     <div class="grid grid-cols-2 gap-4">
                                         <div v-for="priority in ['low', 'medium', 'high', 'urgent']" :key="priority">
                                             <label class="block text-xs font-semibold mb-1 uppercase">{{ priorityLabel(priority) }}</label>
                                             <input v-model.number="settings.sla_rules[priority]" type="number" class="input-field">
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                         <div class="card">
                             <h3 class="text-xl font-bold mb-6">Уведомления Telegram</h3>
                             <div class="space-y-4">
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Bot Token</label>
                                     <input v-model="settings.telegram_bot_token" type="text" class="input-field">
                                 </div>
                                 <div>
                                     <label class="block text-sm font-semibold mb-1">Chat ID</label>
                                     <input v-model="settings.telegram_chat_id" type="text" class="input-field">
                                 </div>
                             </div>
                         </div>
                     </div>
                     <button @click="saveSettings" class="btn-primary px-12 py-4">Сохранить всё</button>
                </div>

                <!-- Profile Tab -->
                <div v-if="tab === 'profile'" class="max-w-md mx-auto space-y-8">
                    <div class="card">
                        <h3 class="text-xl font-bold mb-6">Настройки профиля</h3>
                        <form @submit.prevent="updatePassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Текущий пароль</label>
                                <input v-model="profileForm.currentPassword" type="password" class="input-field" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Новый пароль</label>
                                <input v-model="profileForm.newPassword" type="password" class="input-field" required>
                            </div>
                            <button type="submit" class="w-full btn-primary py-3">Сменить пароль</button>
                        </form>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div v-if="tab === 'analytics'" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="card bg-blue-600 text-white">
                            <p class="text-blue-100 text-sm font-bold uppercase mb-1">Всего заявок</p>
                            <h3 class="text-4xl font-black">{{ tasks.length }}</h3>
                        </div>
                        <div class="card">
                            <p class="text-slate-400 text-sm font-bold uppercase mb-1">Выполнено</p>
                            <h3 class="text-4xl font-black">{{ tasks.filter(t => t.status === 'completed').length }}</h3>
                        </div>
                        <div class="card">
                            <p class="text-slate-400 text-sm font-bold uppercase mb-1">В работе</p>
                            <h3 class="text-4xl font-black">{{ tasks.filter(t => t.status === 'in_work').length }}</h3>
                        </div>
                        <div class="card">
                            <p class="text-slate-400 text-sm font-bold uppercase mb-1">Новых</p>
                            <h3 class="text-4xl font-black">{{ tasks.filter(t => t.status === 'new').length }}</h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="card bg-slate-900 text-white">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Ср. время выполнения</p>
                                    <h3 class="text-3xl font-black">{{ avgCompletionTime }}ч</h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Выполнение SLA</p>
                                    <h3 class="text-3xl font-black text-green-400">{{ slaCompliance }}%</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="card">
                            <h3 class="text-xl font-bold mb-6">Загрузка исполнителей</h3>
                            <div class="space-y-4">
                                <div v-for="e in executorsLoad" :key="e.id" class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium">{{ e.name }}</span>
                                        <span class="text-slate-500">{{ e.count }} задач</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-blue-600 h-full transition-all" :style="{ width: e.percent + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <h3 class="text-xl font-bold mb-6">Контроль SLA</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-red-50 rounded-2xl border border-red-100">
                                    <span class="text-red-700 font-bold">Просрочено</span>
                                    <span class="text-2xl font-black text-red-600">{{ tasks.filter(t => isLate(t.deadline) && t.status !== 'completed').length }}</span>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-green-50 rounded-2xl border border-green-100">
                                    <span class="text-green-700 font-bold">В срок</span>
                                    <span class="text-2xl font-black text-green-600">{{ tasks.filter(t => !isLate(t.deadline) && t.status !== 'completed').length }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Forgot Password Modal -->
            <div v-if="showForgotModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                <div class="max-w-md w-full bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden p-8">
                    <h2 class="text-2xl font-black mb-4 text-slate-900 dark:text-white">Восстановление доступа</h2>
                    <p class="text-sm text-slate-500 mb-6">Введите ваш логин или Email, и мы отправим инструкции по восстановлению.</p>
                    <form @submit.prevent="resetPassword" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400">Логин или Email</label>
                            <input v-model="forgotForm.identity" type="text" class="input-field" required placeholder="admin">
                        </div>
                        <div v-if="forgotSuccess" class="bg-green-500/10 border border-green-500/20 text-green-600 text-xs font-bold py-3 px-4 rounded-xl text-center">
                            Инструкции отправлены на привязанную почту
                        </div>
                        <div class="flex gap-4 pt-4">
                            <button type="button" @click="showForgotModal = false; forgotSuccess = false" class="flex-1 px-6 py-3 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold dark:text-white">Отмена</button>
                            <button type="submit" class="flex-1 btn-primary" :disabled="forgotSuccess">Отправить</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Modal -->
            <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                <div class="max-w-md w-full bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden p-8">
                    <h2 class="text-2xl font-black mb-6">{{ editingUser ? 'Редактировать' : 'Новый пользователь' }}</h2>
                    <form @submit.prevent="saveUser" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400">ФИО</label>
                            <input v-model="userForm.full_name" type="text" class="input-field" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400">Логин</label>
                            <input v-model="userForm.username" type="text" class="input-field" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400">Пароль {{ editingUser ? '(оставьте пустым для сохранения)' : '' }}</label>
                            <input v-model="userForm.password" type="password" class="input-field" :required="!editingUser">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400">Роль</label>
                            <select v-model="userForm.role" class="input-field" required>
                                <option value="admin">Администратор</option>
                                <option value="head">Начальник отдела</option>
                                <option value="executor">Исполнитель</option>
                                <option value="responsible">Ответственный сотрудник</option>
                            </select>
                        </div>
                        <div class="flex gap-4 pt-4">
                            <button type="button" @click="showUserModal = false" class="flex-1 px-6 py-3 border border-slate-200 rounded-2xl font-bold">Отмена</button>
                            <button type="submit" class="flex-1 btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Task Detail Modal -->
            <div v-if="selectedTask" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                <div class="max-w-4xl w-full max-h-[90vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                        <div>
                            <span class="badge mb-2 block" :class="'status-' + selectedTask.status">{{ statusLabel(selectedTask.status) }}</span>
                            <h2 class="text-2xl font-bold">#{{ selectedTask.id }} {{ selectedTask.title }}</h2>
                        </div>
                        <button @click="selectedTask = null" class="text-slate-400 hover:text-slate-900 text-3xl">✕</button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-2 space-y-6">
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Описание</h4>
                                <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ selectedTask.description }}</p>
                            </div>
                            <div v-if="selectedTask.attachment" class="mt-4">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Вложение</h4>
                                <a :href="selectedTask.attachment" target="_blank" class="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                    <span class="text-sm font-medium">Посмотреть файл</span>
                                </a>
                            </div>
                            <div class="pt-8 border-t border-slate-100 dark:border-slate-800">
                                <h4 class="text-lg font-bold mb-4">Комментарии</h4>
                                <div class="space-y-4 mb-6">
                                    <div v-for="c in comments" :key="c.id" class="p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs font-bold text-blue-600">{{ c.user_name || 'User' }}</span>
                                            <span class="text-[10px] text-slate-400">{{ formatDate(c.created_at) }}</span>
                                        </div>
                                        <p class="text-sm text-slate-700 dark:text-slate-300">{{ c.text }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <textarea v-model="newComment" class="input-field flex-1" placeholder="Ваш комментарий..." rows="2"></textarea>
                                    <button @click="addComment" class="btn-primary !px-4 self-end">Отправить</button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-8 bg-slate-50/50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mb-2">Приоритет</h4>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" :class="'bg-' + (selectedTask.priority === 'urgent' ? 'red' : selectedTask.priority === 'high' ? 'orange' : 'blue') + '-500'"></div>
                                    <span class="font-bold uppercase text-xs">{{ priorityLabel(selectedTask.priority) }}</span>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mb-2">Дедлайн (SLA)</h4>
                                <p class="font-mono text-sm" :class="isLate(selectedTask.deadline) ? 'text-red-500 font-bold' : ''">{{ formatDate(selectedTask.deadline) }}</p>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mb-2">Исполнитель</h4>
                                <div v-if="canAssign">
                                     <select :value="selectedTask.executor_id" @change="assignTask(selectedTask, $event.target.value)" class="input-field !text-xs">
                                         <option :value="null">Не назначен</option>
                                         <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                                     </select>
                                </div>
                                <p v-else class="text-sm font-medium">{{ selectedTask.executor_name || 'Не назначен' }}</p>
                            </div>
                            <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mb-4">История</h4>
                                <div class="space-y-3">
                                    <div v-for="h in history" :key="h.id" class="text-[10px] leading-tight">
                                        <span class="text-slate-400 block">{{ formatDate(h.created_at) }}</span>
                                        <span class="font-bold text-blue-600">{{ h.user_name }}</span>:
                                        {{ statusLabel(h.old_status) }} ➝ {{ statusLabel(h.new_status) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
