<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Service CRM PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .kanban-col { min-height: 500px; }
        [v-cloak] { display: none; }
        .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-900 text-slate-200">
    <div id="app" v-cloak class="min-h-screen flex flex-col">
        <!-- Header -->
        <header v-if="token" class="h-16 border-b border-slate-800 flex items-center justify-between px-6 bg-slate-900 sticky top-0 z-40">
            <div class="flex items-center space-x-4">
                <span class="text-xl font-bold text-white">Service <span class="text-blue-500">CRM PRO</span></span>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-medium text-white">{{ user.full_name }}</p>
                    <p class="text-xs text-slate-500">{{ user.role }}</p>
                </div>
                <button @click="logout" class="bg-slate-800 hover:bg-slate-700 p-2 rounded-lg text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                </button>
            </div>
        </header>

        <div v-if="token" class="flex flex-1 overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-64 border-r border-slate-800 flex flex-col p-4 space-y-2 hidden lg:flex">
                <button v-for="tab in availableTabs" @click="activeTab = tab.id" :class="['flex items-center space-x-3 px-4 py-2.5 rounded-xl transition-all', activeTab === tab.id ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-slate-800 text-slate-400']">
                    <span v-html="tab.icon"></span>
                    <span class="font-medium">{{ tab.name }}</span>
                </button>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-slate-950/50 p-6">
                <div v-if="activeTab === 'dashboard'">
                    <h2 class="text-2xl font-bold mb-6">Аналитика</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                            <p class="text-slate-500 text-sm mb-1">Всего заявок</p>
                            <p class="text-3xl font-bold">{{ stats.total }}</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl border-l-4 border-l-blue-500">
                            <p class="text-slate-500 text-sm mb-1">Новых</p>
                            <p class="text-3xl font-bold">{{ stats.by_status?.New || 0 }}</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl border-l-4 border-l-green-500">
                            <p class="text-slate-500 text-sm mb-1">SLA Соблюдено</p>
                            <p class="text-3xl font-bold text-green-400">{{ stats.sla?.on_time }}</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl border-l-4 border-l-red-500">
                            <p class="text-slate-500 text-sm mb-1">SLA Просрочено</p>
                            <p class="text-3xl font-bold text-red-400">{{ stats.sla?.overdue }}</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl border-l-4 border-l-purple-500">
                            <p class="text-slate-500 text-sm mb-1">Среднее время (ч)</p>
                            <p class="text-3xl font-bold text-purple-400">{{ stats.avg_completion_time }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                            <h3 class="text-lg font-semibold mb-4 text-white">Распределение по статусам</h3>
                            <div class="space-y-4">
                                <div v-for="(count, status) in stats.by_status" class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>{{ statusTranslations[status] }}</span>
                                        <span class="font-bold">{{ count }}</span>
                                    </div>
                                    <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                                        <div :class="['h-full transition-all', statusColor(status)]" :style="{ width: (count / stats.total * 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                            <h3 class="text-lg font-semibold mb-4 text-white">Загрузка сотрудников</h3>
                            <div class="space-y-4">
                                <div v-for="emp in stats.workload" class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>{{ emp.name }}</span>
                                        <span class="font-bold">{{ emp.count }} задач</span>
                                    </div>
                                    <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 transition-all" :style="{ width: (emp.count / Math.max(...stats.workload.map(e => e.count), 1) * 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'tasks'">
                    <h2 class="text-2xl font-bold mb-6">Список всех заявок</h2>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase">
                                <tr>
                                    <th class="px-6 py-3">ID</th>
                                    <th class="px-6 py-3">Создана</th>
                                    <th class="px-6 py-3">Тема</th>
                                    <th class="px-6 py-3">Приоритет</th>
                                    <th class="px-6 py-3">Статус</th>
                                    <th class="px-6 py-3">Исполнитель</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-for="task in filteredTasks" @click="viewTask(task)" class="hover:bg-slate-800/30 cursor-pointer transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs">{{ task.id }}</td>
                                    <td class="px-6 py-4 text-sm">{{ formatDate(task.created_at) }}</td>
                                    <td class="px-6 py-4 text-sm">{{ task.fields.f_subject }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', priorityClass(task.priority)]">{{ task.priority }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="text-slate-300">{{ statusTranslations[task.status] }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <select @click.stop @change="assignTask(task.id, $event.target.value)" class="bg-slate-800 border border-slate-700 rounded px-2 py-1 outline-none text-xs">
                                            <option value="">Не назначен</option>
                                            <option v-for="u in usersList" :value="u.id" :selected="task.assigned_to == u.id">{{ u.full_name }}</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="activeTab === 'users'">
                    <h2 class="text-2xl font-bold mb-6">Пользователи</h2>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                        <div class="space-y-4">
                            <div v-for="u in usersList" class="flex items-center justify-between p-4 bg-slate-800 rounded-xl">
                                <div>
                                    <p class="font-bold text-white">{{ u.full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ u.username }} • {{ u.role }}</p>
                                </div>
                                <button class="text-blue-400 hover:text-blue-300 text-sm">Редактировать</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'forms'">
                    <h2 class="text-2xl font-bold mb-6">Конструктор форм</h2>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                        <div class="space-y-4 mb-6">
                            <div v-for="(field, index) in settings.form_fields" class="flex items-center space-x-4 p-4 bg-slate-800 rounded-xl">
                                <span class="bg-slate-700 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold">{{ index + 1 }}</span>
                                <div class="flex-1">
                                    <p class="font-bold text-white">{{ field.label }}</p>
                                    <p class="text-xs text-slate-500">{{ field.type }} • {{ field.required ? 'Обязательно' : 'Опционально' }}</p>
                                </div>
                                <button @click="removeField(index)" class="text-red-400 hover:text-red-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <button @click="addField" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-lg font-medium transition-all">+ Добавить поле</button>
                        <button @click="saveSettings" class="ml-4 bg-green-600 hover:bg-green-500 text-white px-6 py-2 rounded-lg font-medium transition-all">Сохранить всё</button>
                    </div>
                </div>

                <div v-if="activeTab === 'kanban'">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold">Канбан-доска</h2>
                        <div class="flex space-x-2">
                            <input v-model="search" type="text" placeholder="Поиск..." class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-1.5 outline-none text-sm">
                        </div>
                    </div>

                    <div class="flex space-x-6 overflow-x-auto pb-4">
                        <div v-for="status in ['New', 'In Work', 'Completed', 'Rejected']" class="flex-shrink-0 w-80">
                            <div class="flex items-center justify-between mb-4 px-2">
                                <h3 class="font-semibold text-slate-400 uppercase tracking-wider text-xs">{{ statusTranslations[status] }}</h3>
                                <span class="bg-slate-800 text-slate-400 px-2 py-0.5 rounded text-xs">{{ filteredTasks.filter(t => t.status === status).length }}</span>
                            </div>
                            <div @dragover.prevent @drop="dropTask($event, status)" class="kanban-col space-y-4 p-3 bg-slate-900/30 rounded-2xl border border-dashed border-slate-800">
                                <div v-for="task in filteredTasks.filter(t => t.status === status)" :key="task.id" draggable="true" @dragstart="dragTask($event, task)" @click="viewTask(task)" class="bg-slate-800 border border-slate-700 p-4 rounded-xl shadow-sm cursor-move hover:border-slate-500 transition-all">
                                    <div class="flex justify-between items-start mb-2">
                                        <span :class="['text-[10px] px-2 py-0.5 rounded uppercase font-bold', priorityClass(task.priority)]">{{ task.priority }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono">#{{ task.id }}</span>
                                    </div>
                                    <h4 class="font-medium text-white mb-1">{{ task.fields.f_subject || 'Без темы' }}</h4>
                                    <p class="text-xs text-slate-400 line-clamp-2 mb-3">{{ task.fields.f_description }}</p>
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex -space-x-2">
                                            <div class="w-6 h-6 rounded-full bg-blue-600 border border-slate-800 flex items-center justify-center text-[10px] font-bold">
                                                {{ task.creator?.[0] }}
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-slate-500">{{ formatDate(task.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Task Modal -->
        <div v-if="selectedTask" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white">{{ selectedTask.fields.f_subject || 'Заявка' }}</h3>
                        <p class="text-sm text-slate-500">ID #{{ selectedTask.id }} • {{ formatDate(selectedTask.created_at) }}</p>
                    </div>
                    <button @click="selectedTask = null" class="text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex flex-1 overflow-hidden">
                    <div class="w-2/3 overflow-y-auto p-6 space-y-6">
                        <div>
                            <h4 class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Описание</h4>
                            <p class="text-slate-200">{{ selectedTask.fields.f_description }}</p>
                        </div>

                        <div>
                            <h4 class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-4">Комментарии</h4>
                            <div class="space-y-4 mb-6">
                                <div v-for="c in selectedTask.comments" class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/50">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-bold text-blue-400">{{ c.by }}</span>
                                        <span class="text-[10px] text-slate-500">{{ formatDate(c.at) }}</span>
                                    </div>
                                    <p class="text-sm text-slate-300">{{ c.text }}</p>
                                </div>
                                <div v-if="!selectedTask.comments?.length" class="text-center py-4 text-slate-500 text-sm italic">Комментариев пока нет</div>
                            </div>
                            <div class="flex space-x-2">
                                <input v-model="newComment" @keyup.enter="addComment" type="text" placeholder="Напишите комментарий..." class="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                <button @click="addComment" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="w-1/3 border-l border-slate-800 bg-slate-950/30 p-6 space-y-6 overflow-y-auto">
                        <div>
                            <h4 class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-3">Статус и Приоритет</h4>
                            <div class="space-y-3">
                                <select v-model="selectedTask.status" @change="updateTaskStatus(selectedTask)" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm outline-none">
                                    <option v-for="(name, code) in statusTranslations" :value="code">{{ name }}</option>
                                </select>
                                <div :class="['w-full text-center py-2 rounded-lg text-xs font-bold uppercase', priorityClass(selectedTask.priority)]">
                                    {{ selectedTask.priority }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-3">Дедлайн (SLA)</h4>
                            <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/50">
                                <p class="text-sm font-medium text-white mb-1">{{ formatDate(selectedTask.deadline) }}</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-3">История активности</h4>
                            <div class="space-y-3">
                                <div v-for="h in selectedTask.history" class="relative pl-4 border-l border-slate-800">
                                    <div class="absolute -left-1.5 top-1.5 w-3 h-3 rounded-full bg-slate-700 border-2 border-slate-900"></div>
                                    <p class="text-xs text-slate-300 leading-tight">{{ h.msg }}</p>
                                    <p class="text-[9px] text-slate-500 mt-1">{{ formatDate(h.at) }} • {{ h.by }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
