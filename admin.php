<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Auth.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $user = Auth::authenticate();
    if (!$user || $user['role'] !== 'admin') {
        die('Unauthorized');
    }

    $tasks = Storage::read('tasks.json');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tasks_export_' . date('Y-m-d') . '.csv');

    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Дата', 'Отдел', 'Статус', 'Приоритет', 'Описание', 'Исполнитель', 'Создатель']);

    foreach ($tasks as $t) {
        fputcsv($output, [
            $t['id'],
            $t['created_at'],
            $t['department_id'],
            $t['status'],
            $t['priority'],
            $t['description'],
            $t['executor_id'] ?? '-',
            $t['created_by'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM PRO - Панель управления</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [v-cloak] { display: none; }
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .card { background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-item.active { background: rgba(99, 102, 241, 0.2); border-right: 3px solid #6366f1; color: #818cf8; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen">
    <div id="app" v-cloak>
        <!-- Login Overlay -->
        <div v-if="!token" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950 p-4">
            <div class="max-w-md w-full glass p-10 rounded-3xl shadow-2xl">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">CRM PRO</h1>
                    <p class="text-slate-400">Авторизация в системе ХОП</p>
                </div>
                <form @submit.prevent="login" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Логин</label>
                        <input v-model="loginForm.username" type="text" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Пароль</label>
                        <input v-model="loginForm.password" type="password" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-white" required>
                    </div>
                    <button type="submit" :disabled="loading" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-900/20 transition-all">
                        Войти в систему
                    </button>
                    <p v-if="error" class="text-red-400 text-center text-sm mt-4">{{ error }}</p>
                </form>
            </div>
        </div>

        <!-- Main Layout -->
        <div v-else class="flex min-h-screen">
            <!-- Sidebar -->
            <aside class="w-64 glass border-r border-slate-800 flex flex-col fixed inset-y-0">
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-10">
                        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">C</div>
                        <span class="text-xl font-bold text-white tracking-tight">CRM PRO</span>
                    </div>

                    <nav class="space-y-1">
                        <button v-for="item in menu" :key="item.id" @click="tab = item.id" :class="['sidebar-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl transition-all', tab === item.id ? 'active' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200']">
                            <i :data-lucide="item.icon" class="w-5 h-5"></i>
                            <span class="font-medium">{{ item.name }}</span>
                        </button>
                    </nav>
                </div>

                <div class="mt-auto p-6 border-t border-slate-800">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold border border-slate-700 uppercase">
                            {{ user.username[0] }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-white truncate">{{ user.full_name || user.username }}</p>
                            <p class="text-xs text-slate-500 capitalize">{{ user.role }}</p>
                        </div>
                    </div>
                    <button @click="logout" class="w-full flex items-center justify-center space-x-2 py-3 rounded-xl bg-slate-900 hover:bg-red-900/20 hover:text-red-400 text-slate-400 transition-all border border-slate-800">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="text-sm font-semibold">Выйти</span>
                    </button>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 ml-64 p-8">
                <!-- Header -->
                <header class="flex justify-between items-center mb-10">
                    <div>
                        <h2 class="text-3xl font-bold text-white">{{ currentMenuName }}</h2>
                        <p class="text-slate-500">Добро пожаловать в систему управления задачами</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="exportCSV" v-if="user.role === 'admin'" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center space-x-2 transition-all">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Экспорт CSV</span>
                        </button>
                        <div class="w-10 h-10 glass rounded-xl flex items-center justify-center text-slate-400 relative cursor-pointer">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-indigo-500 rounded-full ring-2 ring-slate-950"></span>
                        </div>
                    </div>
                </header>

                <!-- Dynamic Component / View -->
                <div v-if="tab === 'dashboard'">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                        <div class="card p-6 rounded-3xl" v-for="stat in stats" :key="stat.label">
                            <p class="text-slate-500 text-sm font-medium mb-1">{{ stat.label }}</p>
                            <p class="text-3xl font-bold text-white">{{ stat.value }}</p>
                            <div class="mt-4 flex items-center text-xs" :class="stat.trend > 0 ? 'text-green-400' : 'text-slate-400'">
                                <i :data-lucide="stat.trend > 0 ? 'trending-up' : 'minus'" class="w-3 h-3 mr-1"></i>
                                <span>{{ Math.abs(stat.trend) }}% за неделю</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="card p-8 rounded-3xl">
                            <h3 class="text-lg font-bold text-white mb-6">Заявки по отделам</h3>
                            <div class="space-y-4">
                                <div v-for="(count, dept) in deptStats" :key="dept" class="flex items-center">
                                    <span class="w-24 text-sm text-slate-400">{{ deptName(dept) }}</span>
                                    <div class="flex-1 h-2 bg-slate-900 rounded-full mx-4 overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full" :style="{ width: (count/tasks.length*100 || 0) + '%' }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-white">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card p-8 rounded-3xl">
                            <h3 class="text-lg font-bold text-white mb-6">Последняя активность</h3>
                            <div class="space-y-4">
                                <div v-for="t in tasks.slice(-5).reverse()" :key="t.id" class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-3">
                                        <div :class="['w-2 h-2 rounded-full', statusColor(t.status)]"></div>
                                        <span class="text-slate-300 font-medium">Заявка #{{ t.id }}</span>
                                    </div>
                                    <span class="text-slate-600 text-xs">{{ t.created_at.split(' ')[1] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'tasks'">
                    <div class="flex space-x-6 overflow-x-auto pb-6 min-h-[600px]">
                        <div v-for="status in statuses" :key="status.id" class="w-80 flex-shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-400 flex items-center">
                                    <span :class="['w-2 h-2 rounded-full mr-2', status.color]"></span>
                                    {{ status.name }}
                                    <span class="ml-2 bg-slate-900 px-2 py-0.5 rounded text-xs">{{ filteredTasks(status.id).length }}</span>
                                </h3>
                                <button class="text-slate-600 hover:text-slate-400"><i data-lucide="plus" class="w-4 h-4"></i></button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="task in filteredTasks(status.id)" :key="task.id" @click="openTask(task)" class="card p-5 rounded-2xl cursor-pointer hover:border-indigo-500/50 transition-all group">
                                    <div class="flex justify-between items-start mb-3">
                                        <span :class="['text-[10px] uppercase font-black px-2 py-0.5 rounded', priorityClass(task.priority)]">
                                            {{ task.priority }}
                                        </span>
                                        <span class="text-[10px] text-slate-600 font-mono">#{{ task.id }}</span>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-200 mb-3 group-hover:text-indigo-400 transition-colors">{{ task.description.substring(0, 60) }}...</p>
                                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-800">
                                        <div class="flex -space-x-2">
                                            <div v-if="task.executor_id" class="w-6 h-6 rounded-full bg-indigo-500 border-2 border-slate-900 text-[8px] flex items-center justify-center font-bold">EX</div>
                                            <div class="w-6 h-6 rounded-full bg-slate-800 border-2 border-slate-900 text-[8px] flex items-center justify-center font-bold">?</div>
                                        </div>
                                        <div class="flex items-center text-slate-500 text-[10px] space-x-2">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                            <span>{{ task.created_at.split(' ')[0] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'forms'">
                    <div class="card p-8 rounded-3xl">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-xl font-bold text-white">Конструктор полей</h3>
                            <button @click="addField" class="bg-indigo-600 px-4 py-2 rounded-xl text-sm font-bold">Добавить поле</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(field, index) in settings.form_fields" :key="index" class="flex items-center space-x-4 bg-slate-900/50 p-4 rounded-2xl border border-slate-800">
                                <input v-model="field.label" placeholder="Название поля" class="flex-1 bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                <select v-model="field.type" class="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm outline-none">
                                    <option value="text">Текст</option>
                                    <option value="number">Число</option>
                                    <option value="date">Дата</option>
                                    <option value="tel">Телефон</option>
                                </select>
                                <label class="flex items-center space-x-2 text-xs text-slate-500">
                                    <input type="checkbox" v-model="field.required">
                                    <span>Обязательно</span>
                                </label>
                                <button @click="removeField(index)" class="text-red-500 hover:text-red-400 p-2"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                        <button @click="saveSettings" class="mt-8 bg-slate-200 text-slate-900 px-6 py-3 rounded-xl font-bold hover:bg-white transition-all">Сохранить изменения</button>
                    </div>
                </div>

                <div v-if="tab === 'users'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-white">Управление персоналом</h3>
                        <button @click="openUserModal(null)" class="bg-indigo-600 px-4 py-2 rounded-xl text-sm font-bold">Добавить сотрудника</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="u in allUsers" :key="u.id" class="card p-6 rounded-3xl relative">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold border border-slate-700 uppercase">
                                    {{ u.username[0] }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-white">{{ u.full_name }}</h4>
                                    <p class="text-xs text-slate-500">@{{ u.username }} • {{ u.role }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button @click="openUserModal(u)" class="flex-1 bg-slate-900 hover:bg-slate-800 py-2 rounded-lg text-xs font-bold transition-all">Изменить</button>
                                <button @click="deleteUser(u.id)" class="px-3 bg-red-900/20 hover:bg-red-900/40 text-red-400 py-2 rounded-lg text-xs transition-all"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'settings'" class="max-w-2xl">
                    <div class="card p-8 rounded-3xl space-y-8">
                        <h3 class="text-xl font-bold text-white">Общие настройки</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase mb-2 block">Название системы</label>
                                <input v-model="settings.system_name" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 text-white">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase mb-2 block">Telegram Bot Token</label>
                                <input v-model="settings.telegram_bot_token" type="password" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 text-white">
                            </div>
                        </div>
                        <button @click="saveSettings" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 rounded-xl transition-all">
                            Сохранить конфигурацию
                        </button>
                    </div>

                    <div v-if="user.role === 'admin'" class="card p-8 rounded-3xl mt-8 space-y-8">
                        <h3 class="text-xl font-bold text-white">Обслуживание системы</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 bg-slate-900/50 rounded-2xl border border-slate-800">
                                <h4 class="text-sm font-bold text-white mb-4">Резервное копирование</h4>
                                <div class="space-y-3">
                                    <button @click="createBackup" class="w-full py-2 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 rounded-lg text-xs font-bold transition-all">Создать бэкап</button>
                                    <div class="max-h-32 overflow-y-auto space-y-2 mt-4">
                                        <div v-for="b in backups" :key="b" class="flex items-center justify-between text-[10px] bg-slate-950 p-2 rounded border border-slate-800">
                                            <span>{{ b }}</span>
                                            <button @click="restoreBackup(b)" class="text-green-400 hover:underline">Восстановить</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-900/50 rounded-2xl border border-slate-800">
                                <h4 class="text-sm font-bold text-white mb-4">Очистка данных</h4>
                                <div class="space-y-3">
                                    <button @click="clearAllData" class="w-full py-2 bg-red-900/20 hover:bg-red-900/30 text-red-400 rounded-lg text-xs font-bold transition-all">Удалить все заявки</button>
                                    <button @click="cleanupTemp" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-bold transition-all">Очистить вложения</button>

                                    <div class="pt-4 border-t border-slate-800">
                                        <label class="text-[10px] text-slate-500 uppercase block mb-2">Удалить за период</label>
                                        <div class="flex space-x-2">
                                            <input type="date" v-model="clearPeriod.start" class="bg-slate-950 text-[10px] p-1 rounded border border-slate-800 outline-none">
                                            <input type="date" v-model="clearPeriod.end" class="bg-slate-950 text-[10px] p-1 rounded border border-slate-800 outline-none">
                                            <button @click="clearDataPeriod" class="bg-red-900/20 text-red-400 p-1 rounded"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- User Modal -->
        <div v-if="userModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="max-w-md w-full glass p-8 rounded-3xl shadow-2xl">
                <h3 class="text-2xl font-bold text-white mb-6">{{ userModal.id ? 'Редактировать' : 'Новый сотрудник' }}</h3>
                <form @submit.prevent="saveUser" class="space-y-4">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">ФИО</label>
                        <input v-model="userModal.full_name" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Логин</label>
                        <input v-model="userModal.username" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Пароль {{ userModal.id ? '(оставьте пустым для сохранения)' : '' }}</label>
                        <input v-model="userModal.password" type="password" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none" :required="!userModal.id">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Роль</label>
                        <select v-model="userModal.role" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none">
                            <option value="employee">Сотрудник (Подача)</option>
                            <option value="executor">Исполнитель</option>
                            <option value="head">Нач. отдела</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    <div class="flex space-x-3 pt-4">
                        <button type="button" @click="userModal = null" class="flex-1 bg-slate-900 py-3 rounded-xl font-bold">Отмена</button>
                        <button type="submit" class="flex-1 bg-indigo-600 py-3 rounded-xl font-bold">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Task Modal -->
        <div v-if="selectedTask" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="max-w-4xl w-full glass rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                    <div class="flex items-center space-x-3">
                        <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase', priorityClass(selectedTask.priority)]">
                            {{ selectedTask.priority }}
                        </span>
                        <h3 class="text-xl font-bold text-white">Заявка #{{ selectedTask.id }}</h3>
                    </div>
                    <button @click="selectedTask = null" class="text-slate-500 hover:text-white p-2 transition-all"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="flex-1 overflow-y-auto p-8 grid grid-cols-3 gap-10">
                    <div class="col-span-2 space-y-8">
                        <div>
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Описание</h4>
                            <p class="text-slate-200 leading-relaxed">{{ selectedTask.description }}</p>
                        </div>
                        <div v-if="selectedTask.attachment" class="p-4 bg-slate-900 rounded-2xl border border-slate-800 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="image" class="text-indigo-400"></i>
                                <span class="text-sm font-medium text-slate-300">Вложение.jpg</span>
                            </div>
                            <a :href="'uploads/' + selectedTask.attachment" target="_blank" class="text-xs font-bold text-indigo-400 hover:underline">Открыть</a>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6">История изменений</h4>
                            <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[1px] before:bg-slate-800">
                                <div v-for="h in selectedTask.history" :key="h.at" class="relative pl-8">
                                    <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center">
                                        <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                    </div>
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-bold text-slate-300">{{ h.msg }}</p>
                                        <span class="text-[10px] text-slate-600">{{ h.at }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ h.user }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div class="card p-6 rounded-2xl border-indigo-500/10">
                             <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Управление</h4>
                             <div class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-600 block mb-2">Статус</label>
                                    <select v-model="selectedTask.status" @change="updateTask" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                        <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-600 block mb-2">Исполнитель</label>
                                    <select v-model="selectedTask.executor_id" @change="updateTask" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                        <option value="">Не назначен</option>
                                        <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                                    </select>
                                </div>
                             </div>
                        </div>
                        <div class="text-[10px] text-slate-600 bg-slate-900/50 p-4 rounded-xl">
                            <p>Создана: {{ selectedTask.created_at }}</p>
                            <p class="mt-1">Дедлайн: {{ selectedTask.deadline }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
