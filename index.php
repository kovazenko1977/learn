<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Service PRO</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { slate: { 950: '#020617' } } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; transition: background-color 0.3s; }
        [v-cloak] { display: none; }
        .kanban-column { min-height: 300px; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-slate-950">
    <div id="app" v-cloak :class="{'dark': darkMode}">
        <div class="min-h-screen bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-gray-100 flex flex-col">

            <!-- Login Screen -->
            <div v-if="!token" class="flex-1 flex items-center justify-center bg-gradient-to-br from-blue-600 to-indigo-900">
                <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md">
                    <div class="flex justify-between items-center mb-10">
                        <h1 class="text-3xl font-black tracking-tighter text-gray-800 dark:text-white">SERVICE<span class="text-blue-600">PRO</span></h1>
                        <select v-model="lang" class="bg-gray-100 dark:bg-slate-800 text-xs font-bold rounded-lg px-2 py-1 outline-none">
                            <option value="ru">RU</option>
                            <option value="en">EN</option>
                        </select>
                    </div>
                    <form @submit.prevent="login" class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">{{ t('login') }}</label>
                            <input v-model="loginForm.username" type="text" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 border-2 border-transparent focus:border-blue-500 rounded-2xl outline-none transition-all font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">{{ t('password') }}</label>
                            <input v-model="loginForm.password" type="password" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 border-2 border-transparent focus:border-blue-500 rounded-2xl outline-none transition-all font-bold">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-5 rounded-2xl font-black shadow-xl shadow-blue-500/30 hover:bg-blue-700 active:scale-[0.98] transition-all">{{ t('enter') }}</button>
                    </form>
                </div>
            </div>

            <!-- Main App -->
            <div v-else class="flex flex-1 h-screen overflow-hidden">
                <!-- Sidebar -->
                <aside class="w-72 bg-white dark:bg-slate-900 flex flex-col border-r dark:border-slate-800 shadow-sm z-20">
                    <div class="p-8 text-2xl font-black tracking-tighter flex items-center">
                        <span class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white text-sm mr-3 shadow-lg shadow-blue-500/20">S</span>
                        SERVICE<span class="text-blue-600">PRO</span>
                    </div>

                    <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
                        <div class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ t('menu') }}</div>
                        <a v-for="m in menuItems" :key="m.view" @click="view = m.view"
                           :class="view === m.view ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-slate-800'"
                           class="flex items-center px-4 py-3.5 rounded-2xl cursor-pointer font-bold text-sm transition-all group">
                            <span class="mr-3 text-lg transition-transform group-hover:scale-110">{{ m.icon }}</span>
                            {{ t(m.view) }}
                        </a>

                        <template v-if="user.role === 'Administrator'">
                            <div class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mt-6">{{ t('admin_section') }}</div>
                            <a v-for="m in adminMenuItems" :key="m.view" @click="view = m.view"
                               :class="view === m.view ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-slate-800'"
                               class="flex items-center px-4 py-3.5 rounded-2xl cursor-pointer font-bold text-sm transition-all group">
                                <span class="mr-3 text-lg transition-transform group-hover:scale-110">{{ m.icon }}</span>
                                {{ t(m.view) }}
                            </a>
                        </template>
                    </nav>

                    <div class="p-6 border-t dark:border-slate-800">
                        <div class="flex items-center p-4 bg-gray-50 dark:bg-slate-800/50 rounded-2xl mb-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold mr-3 shadow-lg shadow-blue-500/20">
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-sm font-black truncate">{{ user.name }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase">{{ user.role }}</div>
                            </div>
                        </div>
                        <button @click="logout" class="w-full py-3 text-red-500 text-xs font-black uppercase tracking-widest hover:bg-red-50 dark:hover:bg-red-900/10 rounded-xl transition-colors">
                            {{ t('logout') }}
                        </button>
                    </div>
                </aside>

                <!-- Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden bg-gray-50 dark:bg-slate-950">
                    <!-- Top Header -->
                    <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b dark:border-slate-800 flex items-center justify-between px-10 z-10">
                        <h2 class="text-xl font-black tracking-tight">{{ t(view) }}</h2>

                        <div class="flex items-center space-x-6">
                            <div class="flex bg-gray-100 dark:bg-slate-800 p-1 rounded-xl">
                                <button @click="lang = 'ru'" :class="lang === 'ru' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-gray-400'" class="px-3 py-1 rounded-lg text-[10px] font-black transition">RU</button>
                                <button @click="lang = 'en'" :class="lang === 'en' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-gray-400'" class="px-3 py-1 rounded-lg text-[10px] font-black transition">EN</button>
                            </div>
                            <button @click="toggleDarkMode" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-slate-800 hover:scale-110 transition-transform">
                                <span v-if="!darkMode">🌙</span>
                                <span v-else>☀️</span>
                            </button>
                        </div>
                    </header>

                    <main class="flex-1 overflow-y-auto p-10">
                        <!-- Dashboard -->
                        <div v-if="view === 'dashboard'" class="max-w-7xl mx-auto space-y-10">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div v-for="card in statCards" :key="card.label" class="bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-sm border dark:border-slate-800">
                                    <div :class="card.color" class="text-[10px] font-black uppercase tracking-[0.2em] mb-2">{{ card.label }}</div>
                                    <div class="text-4xl font-black">{{ card.value }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                                <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-sm border dark:border-slate-800">
                                    <h3 class="font-black text-xs uppercase tracking-widest text-gray-400 mb-8">{{ t('status_dist') }}</h3>
                                    <div class="h-80"><canvas id="statusChart"></canvas></div>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-sm border dark:border-slate-800">
                                    <h3 class="font-black text-xs uppercase tracking-widest text-gray-400 mb-8">{{ t('employee_load') }}</h3>
                                    <div class="h-80"><canvas id="loadChart"></canvas></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks -->
                        <div v-if="view === 'tasks'" class="max-w-7xl mx-auto">
                            <div class="flex justify-between items-center mb-10">
                                <div class="flex bg-gray-200 dark:bg-slate-800 p-1 rounded-2xl">
                                    <button @click="taskLayout = 'table'" :class="taskLayout === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-gray-500'" class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition">{{ t('list_view') }}</button>
                                    <button @click="taskLayout = 'kanban'" :class="taskLayout === 'kanban' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-gray-500'" class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition">{{ t('kanban_view') }}</button>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <a :href="'api/export.php?token=' + token" class="px-6 py-3 bg-white dark:bg-slate-900 border dark:border-slate-800 rounded-2xl text-xs font-black uppercase tracking-widest transition shadow-sm">{{ t('export') }}</a>
                                    <button v-if="canCreateTask" @click="showCreateModal = true" class="px-8 py-3 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-500/30 hover:bg-blue-700 transition">+ {{ t('create') }}</button>
                                </div>
                            </div>

                            <div v-if="taskLayout === 'kanban'" class="flex space-x-6 overflow-x-auto pb-10 h-[calc(100vh-280px)]">
                                <div v-for="s in statusOptions" :key="s" class="flex-shrink-0 w-80 flex flex-col">
                                    <div class="flex items-center justify-between mb-4 px-4">
                                        <h3 class="font-black text-[10px] uppercase tracking-[0.2em] text-gray-400">{{ s }}</h3>
                                        <span class="bg-gray-200 dark:bg-slate-800 text-gray-500 px-3 py-1 rounded-full text-[10px] font-black">{{ tasks.filter(t => t.status === s).length }}</span>
                                    </div>
                                    <div :id="'col-' + s" :data-status="s" class="kanban-column flex-1 space-y-4 bg-gray-100/30 dark:bg-slate-900/30 p-4 rounded-[2rem] overflow-y-auto">
                                        <div v-for="t in tasks.filter(x => x.status === s)" :key="t.id" :data-id="t.id" @click="openTask(t)" class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 cursor-move hover:border-blue-400 transition-all group">
                                            <div class="flex justify-between items-start mb-4">
                                                <span :class="priorityClass(t.priority)" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase">{{ t.priority }}</span>
                                            </div>
                                            <div class="font-bold text-sm mb-4 line-clamp-3 leading-snug">{{ t.title }}</div>
                                            <div v-if="t.is_overdue" class="text-[9px] text-red-500 font-black mb-3">⚠️ {{ t('overdue_caps') }}</div>
                                            <div class="flex items-center justify-between pt-4 border-t dark:border-slate-700">
                                                <div class="text-[10px] text-gray-400">{{ formatDate(t.created_at) }}</div>
                                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-blue-600">
                                                    {{ (t.executor_name || '?').charAt(0) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border dark:border-slate-800 overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-slate-800/30 border-b dark:border-slate-800">
                                        <tr>
                                            <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('id') }}</th>
                                            <th class="px-6 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('title') }}</th>
                                            <th class="px-6 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('priority') }}</th>
                                            <th class="px-6 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('status') }}</th>
                                            <th class="px-6 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('executor') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y dark:divide-slate-800">
                                        <tr v-for="t in tasks" :key="t.id" @click="openTask(t)" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/5 cursor-pointer transition">
                                            <td class="px-8 py-5 text-xs font-mono text-gray-400">#{{ t.id.substring(0, 6) }}</td>
                                            <td class="px-6 py-5">
                                                <div class="font-bold text-sm">{{ t.title }}</div>
                                                <div v-if="t.is_overdue" class="text-[9px] text-red-500 font-black uppercase mt-1">{{ t('overdue') }}</div>
                                            </td>
                                            <td class="px-6 py-5"><span :class="priorityClass(t.priority)" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase">{{ t.priority }}</span></td>
                                            <td class="px-6 py-5"><span :class="statusClass(t.status)" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase">{{ t.status }}</span></td>
                                            <td class="px-6 py-5 font-bold text-sm text-gray-600 dark:text-gray-400">{{ t.executor_name || '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Users Management -->
                        <div v-if="view === 'admin'" class="max-w-6xl mx-auto">
                            <div class="flex justify-between items-center mb-10">
                                <h2 class="text-3xl font-black">Пользователи</h2>
                                <button @click="openUserModal()" class="px-8 py-3 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-500/30">+ Новый</button>
                            </div>
                            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border dark:border-slate-800 overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-slate-800/30 border-b dark:border-slate-800">
                                        <tr>
                                            <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Имя</th>
                                            <th class="px-6 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Логин</th>
                                            <th class="px-6 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Роль</th>
                                            <th class="px-6 py-6 text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y dark:divide-slate-800">
                                        <tr v-for="u in usersList" :key="u.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/50">
                                            <td class="px-8 py-5 font-black text-sm">{{ u.name }}</td>
                                            <td class="px-6 py-5 text-sm text-gray-500">{{ u.username }}</td>
                                            <td class="px-6 py-5"><span class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-3 py-1 rounded-full text-[10px] font-black uppercase">{{ u.role }}</span></td>
                                            <td class="px-6 py-5 text-right space-x-4">
                                                <button @click="openUserModal(u)" class="text-blue-600 font-bold text-xs hover:underline">Правка</button>
                                                <button @click="deleteUser(u.id)" class="text-red-500 font-bold text-xs hover:underline">Удалить</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Form Constructor -->
                        <div v-if="view === 'forms'" class="max-w-4xl mx-auto">
                            <h2 class="text-3xl font-black mb-10">{{ t('forms') }}</h2>
                            <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border dark:border-slate-800">
                                <div id="form-fields-sort" class="space-y-4 mb-10">
                                    <div v-for="(f, i) in settings.form_fields" :key="f.id" :data-id="f.id" class="flex items-center bg-gray-50 dark:bg-slate-800/50 p-5 rounded-2xl border dark:border-slate-700 cursor-move group">
                                        <span class="text-gray-300 mr-4">☰</span>
                                        <div class="flex-1">
                                            <div class="font-black text-sm">{{ f.label }}</div>
                                            <div class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">{{ f.type }}</div>
                                        </div>
                                        <button @click="settings.form_fields.splice(i, 1)" class="opacity-0 group-hover:opacity-100 text-red-500 text-xs font-black uppercase tracking-widest transition">{{ t('delete') }}</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-8 bg-blue-50/50 dark:bg-blue-900/10 rounded-3xl border border-blue-100 dark:border-blue-900/30 mb-8">
                                    <input v-model="newField.label" :placeholder="t('field_name')" class="px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 font-bold outline-none focus:ring-2 focus:ring-blue-500">
                                    <select v-model="newField.type" class="px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 font-bold outline-none">
                                        <option value="text">Текст</option>
                                        <option value="number">Число</option>
                                        <option value="date">Дата</option>
                                        <option value="textarea">Описание</option>
                                    </select>
                                    <button @click="addField" class="bg-blue-600 text-white font-black py-3 rounded-xl shadow-lg shadow-blue-500/20">{{ t('add') }}</button>
                                </div>
                                <button @click="saveSettings" class="w-full bg-green-600 text-white py-5 rounded-[2rem] font-black text-lg shadow-xl shadow-green-500/20 hover:bg-green-700 transition">{{ t('save_form') }}</button>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div v-if="view === 'settings'" class="max-w-4xl mx-auto">
                            <h2 class="text-3xl font-black mb-10">{{ t('settings') }}</h2>
                            <div class="space-y-10">
                                <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border dark:border-slate-800">
                                    <h3 class="font-black text-xs uppercase tracking-widest text-gray-400 mb-8">Общие параметры</h3>
                                    <div class="space-y-8">
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Категории</label>
                                            <input v-model="settings.categories_raw" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Приоритеты</label>
                                            <input v-model="settings.priorities_raw" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">SLA (часы)</label>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                <div v-for="p in settings.priorities" :key="p">
                                                    <div class="text-[10px] font-bold text-gray-500 mb-1">{{ p }}</div>
                                                    <input v-model.number="settings.sla[p]" type="number" class="w-full px-4 py-2 border rounded-xl dark:bg-slate-800 dark:border-slate-700 outline-none">
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="saveSettings" class="px-10 py-4 bg-blue-600 text-white rounded-2xl font-black shadow-lg shadow-blue-500/20">{{ t('save') }}</button>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border border-blue-100 dark:border-blue-900/20">
                                    <h3 class="font-black text-lg mb-6 text-blue-600">MySQL Интеграция</h3>
                                    <div class="grid grid-cols-2 gap-6 mb-10">
                                        <input v-model="mysql.host" placeholder="Хост" class="px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none outline-none">
                                        <input v-model="mysql.name" placeholder="База данных" class="px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none outline-none">
                                        <input v-model="mysql.user" placeholder="Пользователь" class="px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none outline-none">
                                        <input v-model="mysql.pass" type="password" placeholder="Пароль" class="px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none outline-none">
                                    </div>
                                    <button @click="initMySQL" class="w-full bg-slate-900 dark:bg-blue-600 text-white py-5 rounded-3xl font-black text-lg">🚀 Активировать MySQL & Мигрировать</button>
                                </div>
                            </div>
                        </div>

                    </main>
                </div>
            </div>

            <!-- MODALS -->

            <!-- Task Details Modal -->
            <div v-if="selectedTask" @click.self="selectedTask = null" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
                    <div class="p-8 border-b dark:border-slate-800 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/20">
                        <div>
                            <div class="flex items-center space-x-3 mb-2">
                                <span :class="priorityClass(selectedTask.priority)" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">{{ selectedTask.priority }}</span>
                                <span class="text-xs font-mono text-gray-400 tracking-tighter">ID: {{ selectedTask.id }}</span>
                            </div>
                            <h3 class="text-2xl font-black">{{ selectedTask.title }}</h3>
                        </div>
                        <button @click="selectedTask = null" class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-slate-800 hover:bg-red-50 hover:text-red-500 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-10 grid grid-cols-1 lg:grid-cols-12 gap-12">
                        <div class="lg:col-span-8 space-y-12">
                            <div>
                                <h4 class="font-black text-[10px] uppercase text-gray-400 mb-6 tracking-[0.2em]">Подробности заявки</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div v-for="f in settings.form_fields" :key="f.id" class="group">
                                        <div class="text-[10px] font-black text-blue-500 uppercase mb-2 group-hover:translate-x-1 transition-transform">{{ f.label }}</div>
                                        <div class="text-base text-gray-800 dark:text-gray-200 leading-relaxed font-medium bg-gray-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-transparent hover:border-blue-100 dark:hover:border-blue-900/30 transition-all">
                                            {{ selectedTask.custom_fields[f.id] || '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-black text-[10px] uppercase text-gray-400 mb-6 tracking-[0.2em]">Вложения</h4>
                                <div class="flex flex-wrap gap-4 mb-6">
                                    <div v-for="a in selectedTask.attachments" :key="a.path" class="bg-gray-50 dark:bg-slate-800 p-4 rounded-2xl border dark:border-slate-700 flex items-center group">
                                        <span class="text-2xl mr-3">📄</span>
                                        <div class="mr-6">
                                            <a :href="a.path" target="_blank" class="font-bold text-sm text-blue-600 hover:underline block truncate max-w-[150px]">{{ a.name }}</a>
                                            <div class="text-[10px] text-gray-400">{{ a.user }}</div>
                                        </div>
                                    </div>
                                    <label class="cursor-pointer border-2 border-dashed border-gray-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 p-6 rounded-2xl flex flex-col items-center justify-center transition-all group">
                                        <span class="text-2xl mb-1 group-hover:scale-110 transition-transform">➕</span>
                                        <span class="text-[10px] font-black uppercase text-gray-400">Загрузить</span>
                                        <input type="file" class="hidden" @change="uploadFile">
                                    </label>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-black text-[10px] uppercase text-gray-400 mb-6 tracking-[0.2em]">Обсуждение</h4>
                                <div class="space-y-6 mb-8">
                                    <div v-for="c in selectedTask.comments" :key="c.id" class="flex space-x-4">
                                        <div class="w-10 h-10 rounded-2xl bg-blue-500 flex-shrink-0 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20">
                                            {{ c.user_name.charAt(0) }}
                                        </div>
                                        <div class="flex-1 bg-gray-50 dark:bg-slate-800/50 p-5 rounded-2xl border dark:border-slate-800">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="font-black text-xs text-gray-800 dark:text-gray-200">{{ c.user_name }}</span>
                                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ formatDate(c.time) }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ c.text }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <textarea v-model="commentText" placeholder="Введите ваше сообщение..." class="w-full px-6 py-5 bg-white dark:bg-slate-800 border-2 border-gray-100 dark:border-slate-700 rounded-[2rem] outline-none focus:border-blue-500 transition-all min-h-[120px] shadow-sm"></textarea>
                                    <button @click="addComment" class="absolute bottom-4 right-4 bg-blue-600 text-white px-8 py-3 rounded-2xl font-black text-sm shadow-xl shadow-blue-500/30 hover:bg-blue-700 active:scale-95 transition-all">Отправить</button>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4 space-y-8">
                            <div class="bg-gray-50 dark:bg-slate-800/30 p-8 rounded-[2rem] border dark:border-slate-800">
                                <h4 class="font-black text-[10px] uppercase text-gray-400 mb-6 tracking-[0.2em]">SLA & Статус</h4>
                                <div class="space-y-6">
                                    <div>
                                        <label class="text-[10px] font-black text-gray-400 uppercase mb-2 block">Текущий статус</label>
                                        <select v-model="selectedTask.status" @change="updateStatus" class="w-full px-4 py-3 bg-white dark:bg-slate-800 rounded-xl border-2 border-transparent focus:border-blue-500 outline-none font-bold text-sm shadow-sm">
                                            <option v-for="s in statusOptions" :value="s">{{ s }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-gray-400 uppercase mb-2 block">Исполнитель</label>
                                        <select v-model="selectedTask.executor_id" @change="assignTask" :disabled="!isAdminOrHead" class="w-full px-4 py-3 bg-white dark:bg-slate-800 rounded-xl border-2 border-transparent focus:border-blue-500 outline-none font-bold text-sm shadow-sm disabled:opacity-50">
                                            <option :value="null">Не назначен</option>
                                            <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                    </div>
                                    <div class="p-5 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border dark:border-slate-700">
                                        <div class="text-[10px] font-black text-gray-400 uppercase mb-1">Срок выполнения</div>
                                        <div class="text-sm font-black" :class="selectedTask.is_overdue ? 'text-red-500' : 'text-gray-800 dark:text-gray-200'">{{ formatDate(selectedTask.deadline) }}</div>
                                        <div v-if="selectedTask.is_overdue" class="mt-1 text-[10px] text-red-500 font-black animate-pulse">СРОК ИСТЕК!</div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-8">
                                <h4 class="font-black text-[10px] uppercase text-gray-400 mb-6 tracking-[0.2em]">Журнал действий</h4>
                                <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-100 dark:before:bg-slate-800">
                                    <div v-for="h in selectedTask.history" :key="h.time" class="relative pl-8">
                                        <div class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-700 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                        </div>
                                        <div class="text-[10px] font-black text-gray-400 mb-1">{{ formatDate(h.time) }}</div>
                                        <div class="text-xs leading-relaxed"><span class="font-black text-gray-800 dark:text-gray-200">{{ h.user }}</span>: {{ h.action }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-300">
                    <div class="p-8 border-b dark:border-slate-800">
                        <h3 class="text-2xl font-black">Новая заявка</h3>
                    </div>
                    <div class="p-8 space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">Тема обращения</label>
                            <input v-model="newTask.title" placeholder="Кратко опишите проблему" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 border-2 border-transparent transition-all font-bold">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">Категория</label>
                                <select v-model="newTask.category" class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none font-bold text-sm outline-none">
                                    <option v-for="cat in settings.categories" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">Приоритет</label>
                                <select v-model="newTask.priority" class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none font-bold text-sm outline-none">
                                    <option v-for="p in settings.priorities" :value="p">{{ p }}</option>
                                </select>
                            </div>
                        </div>
                        <div v-for="f in settings.form_fields" :key="f.id">
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">{{ f.label }}</label>
                            <textarea v-if="f.type === 'textarea'" v-model="newTask.custom_fields[f.id]" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl h-32 border-none outline-none font-medium"></textarea>
                            <input v-else :type="f.type" v-model="newTask.custom_fields[f.id]" class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-800 rounded-2xl border-none outline-none font-medium">
                        </div>
                    </div>
                    <div class="p-8 bg-gray-50 dark:bg-slate-800/50 flex justify-end space-x-4">
                        <button @click="showCreateModal = false" class="px-6 py-3 text-gray-500 font-black text-sm uppercase tracking-widest">Отмена</button>
                        <button @click="createTask" class="px-10 py-4 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-500/20 hover:bg-blue-700 transition-all active:scale-95">Создать заявку</button>
                    </div>
                </div>
            </div>

            <!-- User Modal -->
            <div v-if="showUserModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
                 <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-10 w-full max-w-md shadow-2xl">
                     <h3 class="text-2xl font-black mb-8">{{ editUser.id ? 'Изменить' : 'Добавить' }} пользователя</h3>
                     <div class="space-y-6">
                         <input v-model="editUser.name" placeholder="ФИО сотрудника" class="w-full px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 font-bold outline-none focus:ring-2 focus:ring-blue-500">
                         <input v-model="editUser.username" placeholder="Логин (Email)" class="w-full px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 outline-none">
                         <input v-model="editUser.password" type="password" placeholder="Пароль" class="w-full px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 outline-none">
                         <select v-model="editUser.role" class="w-full px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 outline-none font-bold">
                             <option value="Administrator">Администратор</option>
                             <option value="Responsible Employee">Ответственный</option>
                             <option value="Head of Department">Начальник отдела</option>
                             <option value="Executor">Исполнитель</option>
                         </select>
                         <input v-model="editUser.department" placeholder="Департамент" class="w-full px-5 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 outline-none">
                     </div>
                     <div class="mt-10 flex justify-end space-x-4">
                         <button @click="showUserModal = false" class="px-6 py-2 text-gray-400 font-bold">Отмена</button>
                         <button @click="saveUser" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-500/20">Сохранить</button>
                     </div>
                 </div>
            </div>

        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    token: localStorage.getItem('token'),
                    user: JSON.parse(localStorage.getItem('user') || '{}'),
                    view: 'dashboard',
                    tasks: [],
                    usersList: [],
                    settings: { categories: [], priorities: [], form_fields: [], sla: {} },
                    stats: {},
                    loginForm: { username: '', password: '' },
                    showCreateModal: false,
                    showUserModal: false,
                    selectedTask: null,
                    selectedTasks: [],
                    commentText: '',
                    newTask: { title: '', priority: 'Medium', category: '', custom_fields: {} },
                    newField: { label: '', type: 'text' },
                    editUser: { name: '', username: '', role: 'Executor', department: '' },
                    mysql: { host: 'localhost', name: 'crm_db', user: 'root', pass: '' },
                    charts: {},
                    darkMode: localStorage.getItem('darkMode') === 'true',
                    taskLayout: 'kanban',
                    lang: localStorage.getItem('lang') || 'ru',
                    statusOptions: ['New', 'Assigned', 'In Work', 'Completed', 'Rejected'],
                    menuItems: [
                        { view: 'dashboard', icon: '📊' },
                        { view: 'tasks', icon: '📋' }
                    ],
                    adminMenuItems: [
                        { view: 'admin', icon: '👥' },
                        { view: 'forms', icon: '🛠' },
                        { view: 'settings', icon: '⚙️' }
                    ],
                    i18n: {
                        ru: {
                            login: 'Логин', password: 'Пароль', enter: 'Войти', forgot_password: 'Забыли пароль?',
                            dashboard: 'Дашборд', tasks: 'Заявки', admin: 'Персонал', settings: 'Система', forms: 'Поля',
                            create: 'Создать', export: 'Отчет', menu: 'Меню', admin_section: 'Администрирование',
                            logout: 'Выйти из системы', list_view: 'Список', kanban_view: 'Канбан', selected: 'выбрано',
                            assign_selected: 'Назначить исполнителя', id: 'ID', title: 'Заголовок', priority: 'Приоритет',
                            status: 'Статус', deadline: 'Дедлайн', executor: 'Исполнитель', overdue: 'Просрочено',
                            overdue_caps: 'ПРОСРОЧЕНО', delete: 'Удалить', field_name: 'Название поля', add: 'Добавить',
                            save_form: 'Сохранить конфигурацию', status_dist: 'По статусам', employee_load: 'Нагрузка'
                        },
                        en: {
                            login: 'Login', password: 'Password', enter: 'Sign In', forgot_password: 'Forgot password?',
                            dashboard: 'Dashboard', tasks: 'Tasks', admin: 'Staff', settings: 'System', forms: 'Fields',
                            create: 'Create', export: 'Export', menu: 'Menu', admin_section: 'Administration',
                            logout: 'Sign Out', list_view: 'List', kanban_view: 'Kanban', selected: 'selected',
                            assign_selected: 'Assign Executor', id: 'ID', title: 'Title', priority: 'Priority',
                            status: 'Status', deadline: 'Deadline', executor: 'Executor', overdue: 'Overdue',
                            overdue_caps: 'OVERDUE', delete: 'Delete', field_name: 'Field Name', add: 'Add',
                            save_form: 'Save Form Configuration', status_dist: 'By Status', employee_load: 'Load'
                        }
                    }
                }
            },
            watch: {
                view(nv) {
                    if (nv === 'dashboard') this.fetchStats();
                    else if (nv === 'admin') this.fetchUsers();
                    else if (nv === 'tasks') this.fetchTasks();
                },
                lang(nv) { localStorage.setItem('lang', nv); }
            },
            computed: {
                viewTitle() { return this.t(this.view); },
                executors() { return this.usersList.filter(u => u.role === 'Executor' || u.role === 'Administrator'); },
                isAdminOrHead() { return ['Administrator', 'Head of Department'].includes(this.user.role); },
                canCreateTask() { return ['Administrator', 'Responsible Employee'].includes(this.user.role); },
                statCards() {
                    return [
                        { label: this.t('tasks'), value: this.stats.total || 0, color: 'text-blue-500' },
                        { label: 'In Work', value: (this.stats.byStatus || {})['In Work'] || 0, color: 'text-yellow-500' },
                        { label: 'Completed', value: (this.stats.byStatus || {})['Completed'] || 0, color: 'text-green-500' },
                        { label: 'SLA (hrs)', value: this.stats.avgCompletionTime || 0, color: 'text-purple-500' }
                    ];
                }
            },
            mounted() { if (this.token) this.initApp(); },
            methods: {
                t(key) { return this.i18n[this.lang][key] || key; },
                toggleDarkMode() { this.darkMode = !this.darkMode; localStorage.setItem('darkMode', this.darkMode); },
                async initApp() {
                    await this.fetchSettings();
                    await this.fetchTasks();
                    await this.fetchStats();
                    if (this.user.role === 'Administrator') this.fetchUsers();
                    this.$nextTick(() => { this.initDraggable(); this.initDraggableFields(); });
                },
                async login() {
                    const res = await fetch('api/login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.loginForm) });
                    if (res.ok) {
                        const data = await res.json();
                        this.token = data.token; localStorage.setItem('token', this.token);
                        this.user = JSON.parse(atob(this.token.split('.')[1])); localStorage.setItem('user', JSON.stringify(this.user));
                        this.initApp();
                    } else alert('Error');
                },
                logout() { this.token = null; localStorage.clear(); location.reload(); },
                async fetchTasks() {
                    const res = await fetch('api/tasks.php', { headers: { 'Authorization': 'Bearer ' + this.token } });
                    this.tasks = await res.json();
                    this.$nextTick(() => this.initDraggable());
                },
                async fetchStats() {
                    const res = await fetch('api/analytics.php', { headers: { 'Authorization': 'Bearer ' + this.token } });
                    this.stats = await res.json();
                    this.$nextTick(() => this.renderCharts());
                },
                async fetchUsers() {
                    const res = await fetch('api/admin.php?action=users', { headers: { 'Authorization': 'Bearer ' + this.token } });
                    this.usersList = await res.json();
                },
                async fetchSettings() {
                    const res = await fetch('api/admin.php?action=settings', { headers: { 'Authorization': 'Bearer ' + this.token } });
                    const data = await res.json();
                    this.settings = { ...data, sla: data.sla || {}, form_fields: data.form_fields || [] };
                    this.settings.categories_raw = (data.categories || []).join(', ');
                    this.settings.priorities_raw = (data.priorities || []).join(', ');
                    this.$nextTick(() => this.initDraggableFields());
                },
                async createTask() {
                    const res = await fetch('api/tasks.php?action=create', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify(this.newTask) });
                    if (res.ok) { this.showCreateModal = false; this.fetchTasks(); this.newTask = { title: '', priority: 'Medium', category: this.settings.categories[0], custom_fields: {} }; }
                },
                openTask(t) { this.selectedTask = JSON.parse(JSON.stringify(t)); this.commentText = ''; },
                async addComment() {
                    if (!this.commentText) return;
                    await fetch('api/tasks.php?action=add_comment', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ id: this.selectedTask.id, comment: this.commentText }) });
                    this.fetchTasks().then(() => { this.selectedTask = JSON.parse(JSON.stringify(this.tasks.find(x => x.id === this.selectedTask.id))); });
                    this.commentText = '';
                },
                async updateStatus() { await fetch('api/tasks.php?action=update_status', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ id: this.selectedTask.id, status: this.selectedTask.status }) }); this.fetchTasks(); },
                async assignTask() { await fetch('api/tasks.php?action=assign', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ id: this.selectedTask.id, executor_id: this.selectedTask.executor_id }) }); this.fetchTasks(); },
                async uploadFile(e) { const file = e.target.files[0]; if (!file) return; const fd = new FormData(); fd.append('file', file); fd.append('task_id', this.selectedTask.id); await fetch('api/tasks.php?action=upload', { headers: { 'Authorization': 'Bearer ' + this.token }, method: 'POST', body: fd }); this.fetchTasks().then(() => this.selectedTask = JSON.parse(JSON.stringify(this.tasks.find(x => x.id === this.selectedTask.id)))); },
                openUserModal(u = null) { this.editUser = u ? { ...u, password: '' } : { name: '', username: '', role: 'Executor', department: '' }; this.showUserModal = true; },
                async saveUser() { await fetch('api/admin.php?action=save_user', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify(this.editUser) }); this.showUserModal = false; this.fetchUsers(); },
                async deleteUser(id) { if (confirm('Delete?')) await fetch('api/admin.php?action=delete_user', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); this.fetchUsers(); },
                addField() { if (this.newField.label) { this.settings.form_fields.push({ id: 'f' + Date.now(), label: this.newField.label, type: this.newField.type }); this.newField = { label: '', type: 'text' }; } },
                async saveSettings() {
                    const data = { ...this.settings, categories: this.settings.categories_raw.split(',').map(s => s.trim()), priorities: this.settings.priorities_raw.split(',').map(s => s.trim()) };
                    await fetch('api/admin.php?action=save_settings', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
                    this.fetchSettings(); alert('Saved');
                },
                async initMySQL() { const res = await fetch('api/admin.php?action=init_mysql', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ db: this.mysql }) }); if (res.ok) alert('MySQL Connected!'); else alert('MySQL Error'); },
                toggleSelectAll(e) { if (e.target.checked) this.selectedTasks = this.tasks.map(t => t.id); else this.selectedTasks = []; },
                async massAssign() { const execId = prompt('Enter Executor ID:'); if (!execId) return; for (let id of this.selectedTasks) await fetch('api/tasks.php?action=assign', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ id, executor_id: execId }) }); this.fetchTasks(); this.selectedTasks = []; },
                initDraggable() {
                    if (this.taskLayout !== 'kanban') return;
                    this.statusOptions.forEach(s => {
                        const el = document.getElementById('col-' + s);
                        if (el) {
                            new Sortable(el, {
                                group: 'tasks', animation: 150,
                                onEnd: async (evt) => {
                                    const tid = evt.item.getAttribute('data-id');
                                    const nst = evt.to.getAttribute('data-status');
                                    await fetch('api/tasks.php?action=update_status', { method: 'POST', headers: { 'Authorization': 'Bearer ' + this.token, 'Content-Type': 'application/json' }, body: JSON.stringify({ id: tid, status: nst }) });
                                    this.fetchTasks();
                                }
                            });
                        }
                    });
                },
                initDraggableFields() {
                    const el = document.getElementById('form-fields-sort');
                    if (el) new Sortable(el, { animation: 150 });
                },
                renderCharts() {
                    if (this.charts.status) this.charts.status.destroy(); if (this.charts.load) this.charts.load.destroy();
                    const sctx = document.getElementById('statusChart');
                    if (sctx) this.charts.status = new Chart(sctx, { type: 'doughnut', data: { labels: Object.keys(this.stats.byStatus || {}), datasets: [{ data: Object.values(this.stats.byStatus || {}), backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444'] }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
                    const lctx = document.getElementById('loadChart');
                    if (lctx) this.charts.load = new Chart(lctx, { type: 'bar', data: { labels: Object.keys(this.stats.employeeLoad || {}), datasets: [{ label: this.t('tasks'), data: Object.values(this.stats.employeeLoad || {}), backgroundColor: '#3b82f6' }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } } });
                },
                formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleString(this.lang === 'ru' ? 'ru-RU' : 'en-US', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); },
                priorityClass(p) { const map = { 'Low': 'bg-gray-100 text-gray-500', 'Medium': 'bg-blue-100 text-blue-600', 'High': 'bg-orange-100 text-orange-600', 'Urgent': 'bg-red-100 text-red-600', 'Низкий': 'bg-gray-100 text-gray-500', 'Средний': 'bg-blue-100 text-blue-600', 'Высокий': 'bg-orange-100 text-orange-600', 'Критический': 'bg-red-100 text-red-600' }; return map[p] || 'bg-gray-100'; },
                statusClass(s) { const map = { 'New': 'bg-blue-100 text-blue-700', 'Assigned': 'bg-purple-100 text-purple-700', 'In Work': 'bg-yellow-100 text-yellow-700', 'Completed': 'bg-green-100 text-green-700', 'Rejected': 'bg-red-100 text-red-700' }; return map[s] || 'bg-gray-100'; }
            }
        }).mount('#app');
    </script>
</body>
</html>
