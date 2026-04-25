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
        <!-- Login Overlay -->
        <div v-if="!user" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="max-w-md w-full card">
                <h2 class="text-2xl font-bold mb-6">Вход в систему</h2>
                <form @submit.prevent="login" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Логин</label>
                        <input v-model="loginForm.username" type="text" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Пароль</label>
                        <input v-model="loginForm.password" type="password" class="input-field" required>
                    </div>
                    <button type="submit" class="w-full btn-primary py-3">Войти</button>
                    <p v-if="loginError" class="text-red-500 text-sm text-center">{{ loginError }}</p>
                </form>
            </div>
        </div>

        <template v-else>
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
                <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <span class="text-xl font-black tracking-tighter">CRM<span class="text-blue-600">PRO</span></span>
                        <nav class="hidden md:flex gap-4">
                            <button @click="tab = 'tasks'" :class="tab === 'tasks' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">Заявки</button>
                            <button v-if="isAdmin" @click="tab = 'users'" :class="tab === 'users' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">Пользователи</button>
                            <button v-if="isAdmin" @click="tab = 'settings'" :class="tab === 'settings' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">Настройки</button>
                            <button @click="tab = 'analytics'" :class="tab === 'analytics' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">Аналитика</button>
                            <button @click="tab = 'profile'" :class="tab === 'profile' ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900'" class="px-3 py-2 text-sm transition-all">Профиль</button>
                        </nav>
                    </div>
                    <div class="flex items-center gap-4">
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
            </header>

            <main class="max-w-7xl mx-auto px-4 py-8">
                <!-- Task Management -->
                <div v-if="tab === 'tasks'" class="space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between gap-4">
                        <h2 class="text-2xl font-bold">Список заявок</h2>
                        <div class="flex gap-2">
                            <button @click="exportCSV" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold hover:bg-slate-50">Экспорт CSV</button>
                            <a href="index.php" class="btn-primary flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Создать
                            </a>
                        </div>
                    </div>

                    <!-- Kanban / List view -->
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                        <div v-for="status in statuses" :key="status.id" class="space-y-4">
                            <div class="flex items-center justify-between px-2">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ status.label }}</h3>
                                <span class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full text-xs">{{ filteredTasks(status.id).length }}</span>
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
                                class="bg-slate-100/50 rounded-2xl p-2 min-h-[500px] space-y-3 transition-colors"
                                @dragover.prevent
                                @drop="onDrop($event, status.id)"
                            >
                                <div
                                    v-for="task in filteredTasks(status.id)"
                                    :key="task.id"
                                    class="card !p-4 cursor-grab active:cursor-grabbing hover:ring-2 hover:ring-blue-500 transition-all relative"
                                    draggable="true"
                                    @dragstart="onDragStart($event, task)"
                                    @click="openTask(task)"
                                >
                                    <input v-if="canAssign" type="checkbox" v-model="selectedTaskIds" :value="task.id" @click.stop class="absolute top-4 right-4 w-4 h-4 rounded border-slate-300">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="badge" :class="'status-' + task.status">#{{ task.id }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ formatDate(task.created_at) }}</span>
                                    </div>
                                    <h4 class="font-bold text-sm mb-1 line-clamp-2">{{ task.title }}</h4>
                                    <p class="text-xs text-slate-500 line-clamp-2 mb-3">{{ task.description }}</p>
                                    <div class="flex items-center justify-between mt-auto">
                                        <div class="flex -space-x-2">
                                            <div class="w-6 h-6 rounded-full bg-slate-300 border-2 border-white flex items-center justify-center text-[10px] font-bold text-white uppercase" :title="task.category">{{ task.category[0] }}</div>
                                        </div>
                                        <span class="text-[10px] font-bold" :class="priorityColor(task.priority)">{{ task.priority.toUpperCase() }}</span>
                                    </div>

                                    <div v-if="canAssign" class="mt-3 pt-3 border-t border-slate-100">
                                        <select
                                            :value="task.executor_id"
                                            @change="assignTask(task, $event.target.value)"
                                            class="w-full text-[10px] bg-slate-50 border-none rounded-lg py-1 px-2 focus:ring-1 focus:ring-blue-500"
                                        >
                                            <option :value="null">Без исполнителя</option>
                                            <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                                        </select>
                                    </div>
                                    <div v-else-if="task.executor_name" class="mt-2 text-[10px] text-slate-400 italic">
                                        Исполнитель: {{ task.executor_name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div v-if="tab === 'settings'" class="max-w-4xl space-y-8">
                     <div class="card">
                         <h3 class="text-xl font-bold mb-6">Конструктор форм</h3>
                         <div class="space-y-4">
                             <div v-for="(field, index) in settings.form_fields" :key="index" class="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                 <div class="flex-1">
                                     <input v-model="field.label" class="input-field mb-2" placeholder="Название поля">
                                     <select v-model="field.type" class="input-field">
                                         <option value="text">Текст</option>
                                         <option value="number">Число</option>
                                         <option value="date">Дата</option>
                                         <option value="checkbox">Галочка</option>
                                     </select>
                                 </div>
                                 <button @click="settings.form_fields.splice(index, 1)" class="text-red-500 p-2">✕</button>
                             </div>
                             <button @click="settings.form_fields.push({id: Date.now(), label: '', type: 'text', required: false})" class="w-full border-2 border-dashed border-slate-300 rounded-xl py-4 text-slate-500 hover:border-blue-500 hover:text-blue-500 transition-all">
                                 + Добавить поле
                             </button>
                         </div>
                     </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
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
        </template>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
