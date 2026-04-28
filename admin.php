<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Service CRM PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-slate-50 font-[Inter] h-screen flex flex-col overflow-hidden">
    <div id="app" class="flex flex-1 overflow-hidden" v-if="user && user.username">
        <!-- Sidebar -->
        <aside :class="showMobileSidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col transition-transform md:translate-x-0 md:static">
            <div class="p-6 text-white font-bold text-xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg"></div>
                    CRM PRO
                </div>
                <button @click="showMobileSidebar = false" class="md:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 space-y-2">
                <button @click="tab = 'dashboard'; showMobileSidebar = false" :class="tab === 'dashboard' ? 'bg-slate-800 text-white' : ''" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Дашборд
                </button>
                <button @click="tab = 'kanban'; showMobileSidebar = false" :class="tab === 'kanban' ? 'bg-slate-800 text-white' : ''" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 012 2h2a2 2 0 012-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 012 2h2a2 2 0 012-2V7a2 2 0 01-2-2h-2a2 2 0 01-2 2"></path></svg>
                    Канбан
                </button>
                <button v-if="isAdmin" @click="tab = 'users'; showMobileSidebar = false" :class="tab === 'users' ? 'bg-slate-800 text-white' : ''" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Сотрудники
                </button>
                <button v-if="isAdmin" @click="tab = 'settings'; showMobileSidebar = false" :class="tab === 'settings' ? 'bg-slate-800 text-white' : ''" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Настройки
                </button>
            </nav>
            <div class="p-6 mt-auto border-t border-slate-800">
                <div class="flex items-center gap-3 mb-4">
                    <div v-if="user && user.username" class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold">
                        {{ user.username[0].toUpperCase() }}
                    </div>
                    <div v-if="user" class="overflow-hidden">
                        <div class="text-sm font-medium text-white truncate">{{ user.full_name }}</div>
                        <div class="text-xs text-slate-500 truncate">{{ user.role }}</div>
                    </div>
                </div>
                <button @click="logout" class="w-full text-left text-sm text-slate-500 hover:text-white transition">Выйти</button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col bg-slate-50 overflow-hidden relative">
            <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-8 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="showMobileSidebar = true" class="md:hidden p-2 text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-lg font-semibold text-slate-800">
                        <span v-if="tab === 'dashboard'">Аналитика</span>
                        <span v-if="tab === 'kanban'">Задачи</span>
                        <span v-if="tab === 'users'">Персонал</span>
                        <span v-if="tab === 'settings'">Настройки</span>
                    </h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative hidden md:block" v-if="tab === 'kanban'">
                        <input v-model="searchQuery" type="text" placeholder="Поиск задач..." class="pl-10 pr-4 py-2 bg-slate-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-auto p-4 md:p-8">
                <!-- Dashboard -->
                <div v-if="tab === 'dashboard'" class="space-y-6 md:space-y-8">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                        <div class="bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-slate-100">
                            <div class="text-slate-400 text-[10px] md:text-sm font-medium mb-1 uppercase tracking-wider">Всего</div>
                            <div class="text-xl md:text-3xl font-bold text-slate-800">{{ stats.total }}</div>
                        </div>
                        <div class="bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-slate-100">
                            <div class="text-slate-400 text-[10px] md:text-sm font-medium mb-1 uppercase tracking-wider">В работе</div>
                            <div class="text-xl md:text-3xl font-bold text-blue-600">{{ stats.in_work }}</div>
                        </div>
                        <div class="bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-slate-100">
                            <div class="text-slate-400 text-[10px] md:text-sm font-medium mb-1 uppercase tracking-wider">Готово</div>
                            <div class="text-xl md:text-3xl font-bold text-green-600">{{ stats.completed }}</div>
                        </div>
                        <div class="bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-slate-100">
                            <div class="text-slate-400 text-[10px] md:text-sm font-medium mb-1 uppercase tracking-wider">SLA!</div>
                            <div class="text-xl md:text-3xl font-bold text-red-600">{{ stats.overdue }}</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <h3 class="font-bold text-slate-800 mb-4">Последние действия</h3>
                        <div class="space-y-4">
                            <div v-for="t in tasks.slice(0, 5)" :key="t.id" class="flex items-center gap-4 text-sm">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" :class="t.status === 'Completed' ? 'bg-green-500' : 'bg-blue-500'"></div>
                                <div class="flex-1 text-slate-600 truncate">Заявка <span class="font-medium text-slate-800">#{{ t.id }}</span>: {{ t.title }}</div>
                                <div class="text-[10px] text-slate-400 whitespace-nowrap">{{ t.created_at.split(' ')[0] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kanban -->
                <div v-if="tab === 'kanban'" class="h-full flex flex-col md:flex-row gap-6 overflow-x-auto pb-4">
                    <div v-for="status in ['New', 'Assigned', 'In Work', 'Completed']" :key="status" class="flex-shrink-0 w-full md:w-80 flex flex-col gap-4">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="font-bold text-slate-700 uppercase text-xs tracking-wider">{{ status }}</h3>
                            <span class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full text-[10px] font-bold">{{ tasks.filter(t => t.status === status).length }}</span>
                        </div>
                        <div class="flex-1 space-y-4">
                            <div v-for="task in filteredTasks.filter(t => t.status === status)" :key="task.id"
                                 @click="selectedTask = task; showTaskModal = true"
                                 draggable="true" @dragstart="onDragStart($event, task)"
                                 class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 hover:border-blue-400 transition cursor-pointer active:scale-95">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase" :style="{ backgroundColor: settings.priorities.find(p => p.id === task.priority)?.color + '20', color: settings.priorities.find(p => p.id === task.priority)?.color }">
                                        {{ settings.priorities.find(p => p.id === task.priority)?.label }}
                                    </span>
                                    <span v-if="task.sla_status === 'overdue'" class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold uppercase">SLA!</span>
                                </div>
                                <div class="text-sm font-semibold text-slate-800 mb-1">{{ task.title }}</div>
                                <div class="text-xs text-slate-500 mb-3 line-clamp-2">{{ task.description }}</div>
                                <div class="flex items-center justify-between border-t border-slate-50 pt-3">
                                    <div class="text-[10px] text-slate-400">{{ task.created_by_name }}</div>
                                    <div class="text-[10px] font-medium text-slate-600 bg-slate-50 px-2 py-1 rounded">{{ task.category }}</div>
                                </div>
                            </div>
                            <div class="h-10 border-2 border-dashed border-slate-200 rounded-xl" @dragover.prevent @drop="onDrop($event, status)"></div>
                        </div>
                    </div>
                </div>

                <!-- Users -->
                <div v-if="tab === 'users'" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-4 md:p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <h3 class="font-bold text-slate-800">Персонал</h3>
                        <button @click="userForm = { role: 'Executor', department: settings.departments[0] }; showUserModal = true" class="w-full md:w-auto bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition">Добавить</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm min-w-[600px]">
                            <thead class="bg-slate-50 text-slate-400 font-medium">
                                <tr>
                                    <th class="px-6 py-4">ФИО / Логин</th>
                                    <th class="px-6 py-4">Роль</th>
                                    <th class="px-6 py-4">Отдел</th>
                                    <th class="px-6 py-4">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-800">{{ u.full_name }}</div>
                                        <div class="text-xs text-slate-400">@{{ u.username }}</div>
                                    </td>
                                    <td class="px-6 py-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-medium">{{ u.role }}</span></td>
                                    <td class="px-6 py-4 text-slate-500">{{ u.department }}</td>
                                    <td class="px-6 py-4">
                                        <button @click="userForm = {...u}; showUserModal = true" class="text-blue-500 hover:underline mr-3">Изменить</button>
                                        <button @click="deleteUser(u.id)" class="text-red-400 hover:underline">Удалить</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Settings -->
                <div v-if="tab === 'settings'" class="max-w-4xl space-y-8 pb-10">
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="font-bold text-slate-800 mb-6 text-lg">Конфигурация</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                            <div class="space-y-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Название системы</span>
                                    <input v-model="settings.app_name" class="mt-1 w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Хранилище</span>
                                    <select v-model="settings.storage_mode" class="mt-1 w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none">
                                        <option value="json">JSON Файлы</option>
                                        <option value="mysql">MySQL БД</option>
                                    </select>
                                </label>
                            </div>
                            <div class="space-y-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Тема</span>
                                    <select v-model="settings.current_theme" class="mt-1 w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none">
                                        <option v-for="t in settings.themes" :key="t.key" :value="t.key">{{ t.name }}</option>
                                    </select>
                                </label>
                                <div class="pt-2">
                                    <button @click="saveSettings" class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition shadow-lg">Сохранить</button>
                                </div>
                            </div>
                        </div>

                        <!-- Form Constructor -->
                        <div class="mt-10 border-t border-slate-100 pt-8">
                            <h4 class="font-bold text-slate-800 mb-4">Конструктор полей заявки</h4>
                            <div class="space-y-4 mb-6">
                                <div v-for="(field, index) in settings.form_fields" :key="index" class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <div class="flex-1 grid grid-cols-2 gap-4">
                                        <input v-model="field.label" placeholder="Название поля" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                                        <select v-model="field.type" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                                            <option value="text">Текст (строка)</option>
                                            <option value="textarea">Текст (многострочный)</option>
                                            <option value="number">Число</option>
                                            <option value="date">Дата</option>
                                        </select>
                                    </div>
                                    <button @click="settings.form_fields.splice(index, 1)" class="p-2 text-red-400 hover:bg-red-50 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button @click="settings.form_fields.push({ id: 'field_'+Date.now(), label: 'Новое поле', type: 'text', required: false })" class="text-blue-600 text-sm font-bold flex items-center gap-2 hover:underline">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Добавить поле
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modals -->
        <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-0 md:p-4">
             <div class="bg-white w-full max-w-2xl md:rounded-3xl overflow-hidden shadow-2xl flex flex-col h-full md:h-auto max-h-screen md:max-h-[90vh]">
                 <div class="p-6 md:p-8 border-b border-slate-100 flex justify-between items-start">
                     <div>
                         <div class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">#{{ selectedTask.id.slice(0,8) }}</div>
                         <h2 class="text-xl md:text-2xl font-bold text-slate-800">{{ selectedTask.title }}</h2>
                     </div>
                     <button @click="showTaskModal = false" class="p-2 text-slate-400 hover:text-slate-600">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                     </button>
                 </div>
                 <div class="flex-1 overflow-auto p-6 md:p-8 space-y-6">
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                         <div>
                             <div class="text-slate-400 mb-1 uppercase text-[10px] font-bold tracking-wider">Статус</div>
                             <select v-model="selectedTask.status" @change="updateTaskStatus(selectedTask, selectedTask.status)" class="w-full border border-slate-200 rounded-xl p-3 font-medium outline-none focus:ring-2 focus:ring-blue-500">
                                 <option v-for="s in ['New', 'Assigned', 'In Work', 'Completed', 'Rejected']" :key="s" :value="s">{{ s }}</option>
                             </select>
                         </div>
                         <div>
                             <div class="text-slate-400 mb-1 uppercase text-[10px] font-bold tracking-wider">Исполнитель</div>
                             <div class="p-3 bg-slate-50 rounded-xl font-medium border border-slate-100">{{ selectedTask.executor_name || 'Не назначен' }}</div>
                         </div>
                     </div>
                     <div>
                         <div class="text-slate-400 mb-2 uppercase text-[10px] font-bold tracking-wider">Описание</div>
                         <div class="bg-slate-50 p-4 rounded-2xl text-slate-700 leading-relaxed border border-slate-100 text-sm">{{ selectedTask.description }}</div>
                     </div>
                     <div>
                         <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                             Комментарии
                             <span class="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded-full">{{ selectedTask.comments.length }}</span>
                         </h4>
                         <div class="space-y-4 mb-6">
                             <div v-for="c in selectedTask.comments" :key="c.created_at" class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                 <div class="flex justify-between items-center mb-1">
                                     <span class="font-bold text-xs text-slate-800">{{ c.user_name }}</span>
                                     <span class="text-[10px] text-slate-400">{{ c.created_at.split(' ')[1].slice(0,5) }}</span>
                                 </div>
                                 <p class="text-xs text-slate-600">{{ c.text }}</p>
                             </div>
                         </div>
                         <div class="flex gap-2">
                             <input v-model="commentText" @keyup.enter="addComment" placeholder="Ваш комментарий..." class="flex-1 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                             <button @click="addComment" class="bg-blue-600 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 active:scale-95 transition">
                                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                             </button>
                         </div>
                     </div>
                 </div>
                 <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                     <a :href="'api/report.php?id='+selectedTask.id" target="_blank" class="text-blue-600 text-xs font-bold hover:underline">Открыть отчет для печати</a>
                 </div>
             </div>
        </div>

        <!-- User Modal -->
        <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
             <div class="bg-white w-full max-w-md rounded-3xl p-6 md:p-8 shadow-2xl">
                 <h2 class="text-xl md:text-2xl font-bold text-slate-800 mb-6">Сотрудник</h2>
                 <div class="space-y-4">
                     <input v-model="userForm.username" placeholder="Логин" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                     <input v-model="userForm.full_name" placeholder="ФИО" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                     <input v-model="userForm.password" type="password" placeholder="Пароль" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                     <select v-model="userForm.role" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none">
                         <option value="Administrator">Администратор</option>
                         <option value="Responsible Employee">Ответственный</option>
                         <option value="Head of Department">Начальник отдела</option>
                         <option value="Executor">Исполнитель</option>
                     </select>
                     <select v-model="userForm.department" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none">
                         <option v-for="d in settings.departments" :key="d" :value="d">{{ d }}</option>
                     </select>
                     <div class="flex gap-4 pt-4">
                         <button @click="showUserModal = false" class="flex-1 bg-slate-100 text-slate-600 font-bold py-3 rounded-xl active:scale-95 transition">Отмена</button>
                         <button @click="saveUser" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-200 active:scale-95 transition">Готово</button>
                     </div>
                 </div>
             </div>
        </div>
    </div>
    <div v-else class="flex h-screen w-screen items-center justify-center bg-slate-900">
         <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>