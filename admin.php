<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM - Панель управления</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen transition-colors duration-300">
    <div id="app" v-cloak>
        <!-- Login Overlay -->
        <div v-if="!user" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a] p-4">
            <div class="absolute inset-0 z-0">
                <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/20 blur-[120px] rounded-full"></div>
                <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/20 blur-[120px] rounded-full"></div>
            </div>
            <div class="relative z-10 max-w-md w-full">
                <div v-if="!showForgotModal" class="card !bg-white/10 !backdrop-blur-2xl !border-white/10 !shadow-2xl !p-10 !rounded-[2.5rem]">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-black text-white mb-2">CRM Вход</h2>
                        <p class="text-slate-400 text-sm">Управление заявками</p>
                    </div>
                    <form @submit.prevent="login" class="space-y-6">
                        <input v-model="loginForm.username" type="text" placeholder="Логин" class="input-field !bg-white/5 !text-white !border-white/10 focus:!border-white/30" required>
                        <input v-model="loginForm.password" type="password" placeholder="Пароль" class="input-field !bg-white/5 !text-white !border-white/10 focus:!border-white/30" required>
                        <p v-if="loginError" class="text-red-400 text-xs font-bold text-center">{{ loginError }}</p>
                        <button type="submit" class="w-full btn-primary !py-4 shadow-xl shadow-blue-500/20">Войти</button>
                    </form>
                    <button @click="showForgotModal = true" class="w-full mt-6 text-slate-400 text-xs font-bold hover:text-white transition-colors">Забыли пароль?</button>
                </div>

                <div v-if="showForgotModal" class="card !bg-white/10 !backdrop-blur-2xl !border-white/10 !shadow-2xl !p-10 !rounded-[2.5rem]">
                    <div class="text-center mb-10">
                        <h2 class="text-2xl font-black text-white mb-2">Восстановление</h2>
                    </div>
                    <div v-if="!forgotSuccess" class="space-y-6">
                        <input v-model="forgotForm.username" type="text" placeholder="Ваш логин" class="input-field !bg-white/5 !text-white !border-white/10 focus:!border-white/30" required>
                        <button @click="forgotPassword" class="w-full btn-primary !py-4">Сбросить пароль</button>
                    </div>
                    <div v-else class="text-center space-y-6">
                        <p class="text-white text-sm">{{ forgotMessage }}</p>
                        <button @click="showForgotModal = false; forgotSuccess = false" class="btn-primary !py-2 !px-6">Назад ко входу</button>
                    </div>
                    <button v-if="!forgotSuccess" @click="showForgotModal = false" class="w-full mt-6 text-slate-400 text-xs font-bold hover:text-white transition-colors">Назад ко входу</button>
                </div>
            </div>
        </div>

        <!-- Main Interface -->
        <div v-if="user" class="flex flex-col h-screen">
            <!-- Sidebar / Header -->
            <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-8">
                    <div class="text-xl font-black text-blue-600">CRM PRO</div>
                    <nav class="hidden md:flex items-center gap-4">
                        <button @click="tab = 'tasks'" :class="tab === 'tasks' ? 'text-blue-600' : 'text-slate-500 dark:text-slate-400'" class="font-bold text-sm">{{ t.tasks }}</button>
                        <button @click="tab = 'kanban'" :class="tab === 'kanban' ? 'text-blue-600' : 'text-slate-500 dark:text-slate-400'" class="font-bold text-sm">{{ t.kanban }}</button>
                        <button v-if="user.role === 'admin' || user.role === 'head'" @click="tab = 'analytics'" :class="tab === 'analytics' ? 'text-blue-600' : 'text-slate-500 dark:text-slate-400'" class="font-bold text-sm">{{ t.analytics }}</button>
                        <button v-if="user.role === 'admin'" @click="tab = 'constructor'" :class="tab === 'constructor' ? 'text-blue-600' : 'text-slate-500 dark:text-slate-400'" class="font-bold text-sm">{{ t.constructor }}</button>
                        <button v-if="user.role === 'admin'" @click="tab = 'settings'" :class="tab === 'settings' ? 'text-blue-600' : 'text-slate-500 dark:text-slate-400'" class="font-bold text-sm">{{ t.settings }}</button>
                        <button @click="tab = 'profile'" :class="tab === 'profile' ? 'text-blue-600' : 'text-slate-500 dark:text-slate-400'" class="font-bold text-sm">{{ t.profile }}</button>
                    </nav>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center bg-slate-100 dark:bg-slate-700 rounded-xl p-1">
                        <button @click="toggleLang" class="px-3 py-1 text-[10px] font-black uppercase rounded-lg transition-all" :class="lang === 'ru' ? 'bg-white dark:bg-slate-600 shadow-sm' : ''">RU</button>
                        <button @click="toggleLang" class="px-3 py-1 text-[10px] font-black uppercase rounded-lg transition-all" :class="lang === 'en' ? 'bg-white dark:bg-slate-600 shadow-sm' : ''">EN</button>
                    </div>
                    <button @click="toggleDarkMode" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-slate-400">
                        <svg v-if="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" /></svg>
                    </button>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ user.full_name || user.username }}</div>
                            <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ user.role }}</div>
                        </div>
                        <button @click="logout" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg></button>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-auto p-8 bg-slate-50 dark:bg-slate-900">
                <!-- Tasks List -->
                <div v-if="tab === 'tasks'" class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ t.taskList }}</h2>
                            <div v-if="selectedTaskIds.length > 0" class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 px-4 py-2 rounded-xl">
                                <span class="text-xs font-bold text-blue-600">{{ t.selected }}: {{ selectedTaskIds.length }}</span>
                                <select @change="massAssign($event.target.value)" class="bg-transparent text-xs font-black text-blue-700 dark:text-blue-400 outline-none">
                                    <option value="">{{ t.assign }}</option>
                                    <option v-for="u in usersList.filter(u => u.role === 'executor')" :value="u.id">{{ u.full_name || u.username }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <input v-model="searchQuery" type="text" :placeholder="t.search" class="input-field !py-2 !w-64 dark:!bg-slate-800 dark:!text-white dark:!border-slate-700">
                            <select v-model="filterPriority" class="input-field !py-2 !w-40 dark:!bg-slate-800 dark:!text-white dark:!border-slate-700">
                                <option value="">{{ t.allPriorities }}</option>
                                <option v-for="p in settings.priorities" :value="p">{{ p }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="card overflow-hidden !p-0 dark:bg-slate-800 dark:border-slate-700">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                    <th class="px-6 py-4 w-10"></th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-400">{{ t.id }}</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-400">{{ t.title }}</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-400">{{ t.status }}</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-400">{{ t.priority }}</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-400">{{ t.executor }}</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="task in filteredTasks" class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer" @click="selectTask(task)">
                                    <td class="px-6 py-4" @click.stop>
                                        <input type="checkbox" :checked="selectedTaskIds.includes(task.id)" @change="toggleTaskSelection(task.id)" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-400">#{{ task.id }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ task.title }}</div>
                                        <div class="text-xs text-slate-500">{{ task.category }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="'badge badge-' + task.status">{{ getStatusLabel(task.status) }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div :class="getPriorityClass(task.priority)" class="w-2 h-2 rounded-full"></div>
                                            <span class="text-sm font-medium dark:text-slate-300">{{ task.priority }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">{{ getUserName(task.executor_id) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kanban Board -->
                <div v-if="tab === 'kanban'" class="h-full flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ t.kanbanBoard }}</h2>
                    </div>
                    <div class="flex-1 flex gap-6 overflow-x-auto pb-4">
                        <div v-for="status in statuses" class="flex-shrink-0 w-80 flex flex-col gap-4" @dragover.prevent @drop="onDrop(status.id)">
                            <div class="flex items-center justify-between px-2">
                                <h3 class="font-black text-slate-400 text-xs uppercase tracking-widest">{{ t[status.id] }}</h3>
                                <span class="bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 text-[10px] font-bold px-2 py-1 rounded-full">{{ tasks.filter(t => t.status === status.id).length }}</span>
                            </div>
                            <div class="flex-1 bg-slate-100/50 dark:bg-slate-800/50 rounded-[2rem] p-4 space-y-4 overflow-y-auto scrollbar-hide">
                                <div v-for="task in tasks.filter(t => t.status === status.id)" draggable="true" @dragstart="onDragStart(task)" class="card !p-4 !rounded-2xl shadow-sm hover:shadow-md cursor-grab active:cursor-grabbing dark:bg-slate-800 dark:border-slate-700" @click="selectTask(task)">
                                    <div class="flex items-center justify-between mb-2">
                                        <span :class="getPriorityClass(task.priority)" class="w-8 h-1 rounded-full"></span>
                                        <span class="text-[10px] font-bold text-slate-400">#{{ task.id }}</span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ task.title }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ task.description }}</div>
                                    <div class="mt-4 flex items-center justify-between">
                                        <div class="flex -space-x-2">
                                            <div class="w-6 h-6 rounded-full bg-blue-500 border-2 border-white dark:border-slate-700 flex items-center justify-center text-[8px] font-bold text-white">{{ getUserName(task.executor_id).charAt(0) }}</div>
                                        </div>
                                        <div v-if="task.deadline" class="text-[10px] font-bold text-rose-500 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ new Date(task.deadline).toLocaleDateString() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics -->
                <div v-if="tab === 'analytics' && stats" class="space-y-8">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ t.analytics }}</h2>
                        <div class="flex gap-2">
                            <button @click="window.print()" class="btn-primary !bg-slate-800 hover:!bg-slate-900 !py-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                Печать PDF
                            </button>
                            <button @click="exportData" class="btn-primary !py-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Экспорт в CSV
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="card dark:bg-slate-800 dark:border-slate-700">
                            <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">{{ t.allTasks }}</div>
                            <div class="text-3xl font-black text-slate-900 dark:text-white">{{ tasks.length }}</div>
                        </div>
                        <div class="card border-emerald-200 bg-emerald-50/30 dark:bg-emerald-900/10 dark:border-emerald-900/30">
                            <div class="text-xs font-black text-emerald-600 uppercase tracking-widest mb-2">{{ t.completed }}</div>
                            <div class="text-3xl font-black text-emerald-700 dark:text-emerald-500">{{ stats.by_status.completed }}</div>
                        </div>
                        <div class="card border-rose-200 bg-rose-50/30 dark:bg-rose-900/10 dark:border-rose-900/30">
                            <div class="text-xs font-black text-rose-600 uppercase tracking-widest mb-2">{{ t.slaBreaches }}</div>
                            <div class="text-3xl font-black text-rose-700 dark:text-rose-500">{{ stats.sla_breaches }}</div>
                        </div>
                        <div class="card dark:bg-slate-800 dark:border-slate-700">
                            <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">{{ t.avgTime }}</div>
                            <div class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.avg_completion_time }}ч</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="card dark:bg-slate-800 dark:border-slate-700">
                            <h3 class="font-black text-slate-900 dark:text-white mb-6">{{ t.userLoad }}</h3>
                            <div class="space-y-4">
                                <div v-for="load in stats.user_load" class="flex items-center gap-4">
                                    <div class="w-24 text-sm font-bold text-slate-600 dark:text-slate-400 truncate">{{ load.name }}</div>
                                    <div class="flex-1 bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                        <div class="bg-blue-600 h-full" :style="{ width: (load.count / (tasks.length || 1) * 100) + '%' }"></div>
                                    </div>
                                    <div class="text-sm font-black text-slate-900 dark:text-white">{{ load.count }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card dark:bg-slate-800 dark:border-slate-700">
                            <h3 class="font-black text-slate-900 dark:text-white mb-6">{{ t.taskStatuses }}</h3>
                            <div class="flex items-end justify-between h-48 px-4">
                                <div v-for="status in statuses" class="flex flex-col items-center gap-2">
                                    <div class="w-8 bg-blue-100 dark:bg-blue-900/30 rounded-t-lg transition-all" :style="{ height: (stats.by_status[status.id] / (tasks.length || 1) * 100 || 5) + '%' }"></div>
                                    <span class="text-[8px] font-black uppercase text-slate-400">{{ t[status.id] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Constructor -->
                <div v-if="tab === 'constructor'" class="max-w-3xl mx-auto space-y-8">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ t.fieldConstructor }}</h2>
                        <button @click="addField" class="btn-primary !py-2">{{ t.addField }}</button>
                    </div>
                    <div class="space-y-4">
                        <div v-for="(field, index) in settings.form_fields" :key="field.id"
                             draggable="true"
                             @dragstart="onFieldDragStart(index)"
                             @dragover.prevent
                             @drop="onFieldDrop(index)"
                             class="card !p-6 flex items-center gap-6 dark:bg-slate-800 dark:border-slate-700 cursor-move hover:border-blue-400 transition-all">
                            <div class="text-slate-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                            </div>
                            <div class="flex-1 grid grid-cols-3 gap-4">
                                <input v-model="field.label" placeholder="Название поля" class="input-field !py-2 dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                <select v-model="field.type" class="input-field !py-2 dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                    <option value="text">Текст</option>
                                    <option value="number">Число</option>
                                    <option value="date">Дата</option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" v-model="field.required" class="rounded border-slate-300 dark:border-slate-600 text-blue-600">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Обязательное</span>
                                </div>
                            </div>
                            <button @click="removeField(index)" class="text-rose-400 hover:text-rose-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </div>
                    </div>
                    <button @click="saveSettings" class="w-full btn-primary !py-4">{{ t.saveForm }}</button>
                </div>

                <!-- Profile Section -->
                <div v-if="tab === 'profile'" class="max-w-2xl mx-auto space-y-8">
                    <div class="card dark:bg-slate-800 dark:border-slate-700 !p-10 !rounded-[2.5rem]">
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-8">{{ t.profile }}</h3>
                        <form @submit.prevent="updateProfile" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">ФИО</label>
                                <input v-model="profileForm.full_name" type="text" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600" required>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Текущий пароль (для смены)</label>
                                <input v-model="profileForm.current_password" type="password" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Новый пароль</label>
                                <input v-model="profileForm.new_password" type="password" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                            </div>
                            <button type="submit" class="w-full btn-primary !py-4 !rounded-2xl shadow-xl shadow-blue-500/10">{{ t.save }}</button>
                        </form>
                    </div>
                </div>

                <!-- Settings -->
                <div v-if="tab === 'settings'" class="max-w-4xl mx-auto space-y-8 pb-12">
                    <div class="card dark:bg-slate-800 dark:border-slate-700">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-8">{{ t.systemSettings }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-8">
                                <div class="space-y-4">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">{{ t.storageMode }}</label>
                                    <select v-model="settings.storage_mode" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                        <option value="json">JSON Файлы</option>
                                        <option value="mysql">MySQL База данных</option>
                                    </select>
                                </div>
                                <div v-if="settings.storage_mode === 'mysql'" class="space-y-4">
                                    <input v-model="settings.mysql.host" placeholder="Хост" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                    <input v-model="settings.mysql.db" placeholder="БД" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                    <input v-model="settings.mysql.user" placeholder="Пользователь" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                    <input v-model="settings.mysql.pass" type="password" placeholder="Пароль" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Уведомления</label>
                                    <div class="flex items-center gap-4 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                                        <input type="checkbox" v-model="settings.notifications_email" id="notifyEmail" class="rounded border-slate-300">
                                        <label for="notifyEmail" class="text-xs font-bold text-slate-600 dark:text-slate-300">Включить Email</label>
                                    </div>
                                    <input v-model="settings.admin_email" placeholder="Email Администратора" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                    <div class="flex items-center gap-4 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                                        <input type="checkbox" v-model="settings.notifications_telegram" id="notifyTelegram" class="rounded border-slate-300">
                                        <label for="notifyTelegram" class="text-xs font-bold text-slate-600 dark:text-slate-300">Включить Telegram</label>
                                    </div>
                                    <input v-model="settings.telegram_bot_token" placeholder="Telegram Bot Token" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                    <input v-model="settings.telegram_chat_id" placeholder="Telegram Chat ID" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                </div>
                            </div>

                            <div class="space-y-8">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Категории</label>
                                        <button @click="settings.categories.push('')" class="text-blue-600 text-xs font-bold">+ Добавить</button>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="(cat, index) in settings.categories" class="flex gap-2">
                                            <input v-model="settings.categories[index]" class="input-field !py-2 dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                            <button @click="settings.categories.splice(index, 1)" class="text-rose-400">×</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">SLA Правила (часы)</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="p in settings.priorities" class="space-y-1">
                                            <label class="text-[10px] font-black uppercase text-slate-400">{{ p }}</label>
                                            <input v-model.number="settings.sla_rules[p]" type="number" class="input-field !py-2 dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button @click="saveSettings" class="mt-8 btn-primary">{{ t.save }}</button>
                    </div>

                    <div class="card dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ t.userManagement }}</h3>
                            <button @click="showUserModal = true" class="btn-primary !py-2 !text-sm">{{ t.addUser }}</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="u in usersList" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700 rounded-2xl">
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ u.full_name || u.username }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ u.role }} • {{ u.username }}</div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="p-2 hover:bg-white dark:hover:bg-slate-600 rounded-xl text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Task Detail Modal -->
            <div v-if="selectedTask" class="fixed inset-0 z-50 flex items-center justify-end bg-slate-900/40 backdrop-blur-sm" @click.self="selectedTask = null">
                <div class="w-full max-w-xl h-full bg-white dark:bg-slate-800 shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
                    <div class="p-8 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">{{ t.tasks }} #{{ selectedTask.id }}</div>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ selectedTask.title }}</h2>
                        </div>
                        <button @click="selectedTask = null" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl text-slate-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>

                    <div class="flex-1 overflow-auto p-8 space-y-8">
                        <div>
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">{{ t.description }}</h4>
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">{{ selectedTask.description }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-700 rounded-2xl">
                                <div class="text-[10px] font-black text-slate-400 uppercase mb-1">{{ t.status }}</div>
                                <select :value="selectedTask.status" @change="updateTaskStatus(selectedTask, $event.target.value)" class="bg-transparent font-bold text-sm outline-none w-full dark:text-white">
                                    <option v-for="s in statuses" :value="s.id">{{ t[s.id] }}</option>
                                </select>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-slate-700 rounded-2xl">
                                <div class="text-[10px] font-black text-slate-400 uppercase mb-1">{{ t.executor }}</div>
                                <select :value="selectedTask.executor_id" @change="assignTask(selectedTask, $event.target.value)" class="bg-transparent font-bold text-sm outline-none w-full dark:text-white">
                                    <option value="">{{ t.notAssigned }}</option>
                                    <option v-for="u in usersList.filter(u => u.role === 'executor')" :value="u.id">{{ u.full_name || u.username }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">{{ t.comments }}</h4>
                            <div class="space-y-4 mb-6">
                                <div v-for="c in comments" class="flex gap-4">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center text-xs font-bold dark:text-white">{{ c.user_name.charAt(0) }}</div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ c.user_name }}</span>
                                            <span class="text-[10px] text-slate-400">{{ new Date(c.created_at).toLocaleString() }}</span>
                                        </div>
                                        <div class="text-sm text-slate-600 dark:text-slate-400">{{ c.text }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <input v-model="newComment" type="text" placeholder="Ваш комментарий..." class="input-field !py-2 dark:!bg-slate-700 dark:!text-white dark:!border-slate-600" @keyup.enter="addComment">
                                <button @click="addComment" class="btn-primary !py-2 !px-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg></button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">{{ t.history }}</h4>
                            <div class="space-y-3">
                                <div v-for="h in history" class="flex items-center gap-3 text-xs">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ h.user_name }}</span>
                                    <span class="text-slate-400">{{ t.changedStatus }}</span>
                                    <span class="font-black text-blue-600 dark:text-blue-400">{{ getStatusLabel(h.new_status) }}</span>
                                    <span class="text-slate-400 ml-auto">{{ new Date(h.created_at).toLocaleTimeString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Modal -->
            <div v-if="showUserModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                <div class="card w-full max-w-md !p-10 !rounded-[2.5rem] dark:bg-slate-800 dark:border-slate-700">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-8">{{ t.addUser }}</h3>
                    <form @submit.prevent="registerUser" class="space-y-4">
                        <input v-model="userForm.username" placeholder="Логин" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600" required>
                        <input v-model="userForm.password" type="password" placeholder="Пароль" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600" required>
                        <input v-model="userForm.full_name" placeholder="ФИО" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600" required>
                        <select v-model="userForm.role" class="input-field dark:!bg-slate-700 dark:!text-white dark:!border-slate-600">
                            <option value="executor">Исполнитель</option>
                            <option value="head">Начальник отдела</option>
                            <option value="responsible">Ответственный</option>
                            <option value="admin">Администратор</option>
                        </select>
                        <div class="flex gap-4 mt-8">
                            <button type="button" @click="showUserModal = false" class="flex-1 px-6 py-4 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-slate-500">Отмена</button>
                            <button type="submit" class="flex-1 btn-primary !py-4">Создать</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
