<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Auth.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $token = $_GET['token'] ?? null;
    $user = Auth::authenticate($token);
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
    <link id="google-font" rel="stylesheet">
    <style id="theme-style">
        :root {
            --primary: #6366f1;
            --bg-main: #0f172a;
            --bg-glass: rgba(15, 23, 42, 0.9);
            --bg-card: rgba(30, 41, 59, 0.5);
            --text-main: #e2e8f0;
            --text-dim: rgba(226, 232, 240, 0.6);
            --border-color: rgba(255, 255, 255, 0.1);
        }
        [v-cloak] { display: none; }
        body {
            font-family: v-bind('settings.font_family || "Inter"'), sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            overflow-x: hidden;
        }
        .glass { background: var(--bg-glass); backdrop-filter: blur(16px); border: 1px solid var(--border-color); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); backdrop-filter: blur(12px); box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.1); transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { box-shadow: 0 8px 30px 0 rgba(0, 0, 0, 0.2); }

        .sidebar-item { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; }
        .sidebar-item.active { background: color-mix(in srgb, var(--primary), transparent 85%); color: var(--primary); font-weight: 700; }
        .layout-sidebar-modern .sidebar-item.active { border-right: 4px solid var(--primary); transform: translateX(4px); }
        .layout-sidebar-compact .sidebar-item.active { background: var(--primary); color: white; border-radius: 12px; }

        .topbar-item { transition: all 0.2s; border-bottom: 2px solid transparent; }
        .topbar-item.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 700; }

        .text-dim { color: var(--text-dim); }
        .text-main { color: var(--text-main); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }

        .text-base-custom { font-size: 14px; }

        /* Layout Transitions */
        .layout-enter-active, .layout-leave-active { transition: opacity 0.3s, transform 0.3s; }
        .layout-enter-from, .layout-leave-to { opacity: 0; transform: translateY(10px); }
    </style>
</head>
<body class="min-h-screen transition-colors duration-500 text-base-custom">
    <!-- Boot Loader -->
    <div id="boot-loader" class="fixed inset-0 z-[100] bg-[#0f172a] flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="w-24 h-24 relative mb-8">
            <div class="absolute inset-0 border-4 border-indigo-500/20 rounded-2xl"></div>
            <div class="absolute inset-0 border-4 border-indigo-500 rounded-2xl animate-spin [animation-duration:3s]"></div>
            <div class="absolute inset-0 flex items-center justify-center text-indigo-500 font-black text-2xl">C</div>
        </div>
        <div class="text-center">
            <h2 class="text-white font-bold tracking-[0.2em] uppercase mb-2">Service CRM PRO</h2>
            <div class="flex items-center justify-center space-x-1">
                <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"></span>
                <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
            </div>
            <p class="text-slate-500 text-[10px] mt-4 uppercase font-bold tracking-widest">Загрузка системы...</p>
        </div>
        <div class="absolute bottom-10 left-0 right-0 px-20">
            <div class="h-1 bg-slate-900 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 w-0 animate-[loading_3s_linear_forwards]"></div>
            </div>
        </div>
        <style>
            @keyframes loading { from { width: 0%; } to { width: 100%; } }
        </style>
    </div>

    <div id="app" v-cloak>
        <!-- Login Overlay -->
        <div v-if="!token" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950 p-4">
            <div class="max-w-md w-full glass p-10 rounded-3xl shadow-2xl">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-main mb-2">CRM PRO</h1>
                    <p class="text-dim">Авторизация в системе ХОП</p>
                </div>
                <form @submit.prevent="login" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-dim mb-2">Логин</label>
                        <input v-model="loginForm.username" type="text" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-main" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dim mb-2">Пароль</label>
                        <input v-model="loginForm.password" type="password" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-main" required>
                    </div>
                    <button type="submit" :disabled="loading" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-900/20 transition-all">
                        Войти в систему
                    </button>
                    <p v-if="error" class="text-red-400 text-center text-sm mt-4">{{ error }}</p>
                </form>
            </div>
        </div>

        <!-- Main Layout -->
        <div v-else :class="['min-h-screen transition-all duration-300', 'layout-' + (settings.active_layout || 'sidebar-modern')]">

            <!-- 1. Sidebar Modern -->
            <aside v-if="settings.active_layout === 'sidebar-modern'" class="w-64 glass border-r border-slate-800 flex flex-col fixed inset-y-0 z-40">
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-10">
                        <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black shadow-lg shadow-indigo-500/20">C</div>
                        <span class="text-xl font-black text-main tracking-tighter">CRM <span class="text-indigo-500">PRO</span></span>
                    </div>
                    <nav class="space-y-1">
                        <button v-for="item in menu" :key="item.id" @click="tab = item.id" :class="['sidebar-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl transition-all', tab === item.id ? 'active' : 'text-slate-400 hover:bg-slate-900/50 hover:text-slate-200']">
                            <i :data-lucide="item.icon" class="w-5 h-5"></i>
                            <span class="font-bold text-sm">{{ item.name }}</span>
                        </button>
                    </nav>
                </div>
                <div class="mt-auto p-6 border-t border-slate-800/50 bg-slate-950/20">
                    <div class="flex items-center space-x-3 mb-6" v-if="user && user.username">
                        <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold border border-slate-700 uppercase">{{ user.username[0] }}</div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-main truncate">{{ user.full_name || user.username }}</p>
                            <p class="text-[10px] text-dim uppercase font-black tracking-widest">{{ user.role }}</p>
                        </div>
                    </div>
                    <button @click="logout" class="w-full flex items-center justify-center space-x-2 py-3 rounded-xl bg-slate-900 hover:bg-red-900/20 hover:text-red-400 text-dim transition-all border border-slate-800">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="text-xs font-black uppercase">Выход</span>
                    </button>
                </div>
            </aside>

            <!-- 2. Sidebar Compact -->
            <aside v-if="settings.active_layout === 'sidebar-compact'" class="w-20 glass border-r border-slate-800 flex flex-col fixed inset-y-0 z-40 items-center py-6">
                <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black mb-10 shadow-lg shadow-indigo-500/20">C</div>
                <nav class="space-y-4 flex-1">
                    <button v-for="item in menu" :key="item.id" @click="tab = item.id" :class="['sidebar-item w-12 h-12 flex items-center justify-center transition-all group relative', tab === item.id ? 'active' : 'text-slate-500 hover:text-slate-200']" :title="item.name">
                        <i :data-lucide="item.icon" class="w-6 h-6"></i>
                        <div class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 shadow-xl border border-slate-700 font-bold uppercase tracking-widest">{{ item.name }}</div>
                    </button>
                </nav>
                <div class="mt-auto space-y-4">
                    <button @click="logout" class="w-12 h-12 flex items-center justify-center rounded-xl bg-slate-900 hover:bg-red-900/20 hover:text-red-400 text-dim transition-all border border-slate-800" title="Выйти">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </div>
            </aside>

            <!-- 3. Topbar Pro -->
            <header v-if="settings.active_layout === 'topbar-pro'" class="h-20 glass border-b border-slate-800 fixed top-0 inset-x-0 z-40 flex items-center px-8">
                <div class="flex items-center space-x-3 mr-12">
                    <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black shadow-lg shadow-indigo-500/20">C</div>
                    <span class="text-xl font-black text-main tracking-tighter">CRM <span class="text-indigo-500">PRO</span></span>
                </div>
                <nav class="flex space-x-8 h-full">
                    <button v-for="item in menu" :key="item.id" @click="tab = item.id" :class="['topbar-item px-2 flex items-center space-x-2 h-full transition-all', tab === item.id ? 'active' : 'text-slate-400 hover:text-slate-200']">
                        <i :data-lucide="item.icon" class="w-4 h-4"></i>
                        <span class="font-bold text-sm">{{ item.name }}</span>
                    </button>
                </nav>
                <div class="ml-auto flex items-center space-x-6">
                    <div class="flex items-center space-x-3" v-if="user && user.username">
                        <div class="text-right hidden md:block">
                            <p class="text-sm font-bold text-main leading-none">{{ user.full_name || user.username }}</p>
                            <p class="text-[9px] text-dim uppercase font-black tracking-widest mt-1">{{ user.role }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400 font-bold border border-slate-700 uppercase">{{ user.username[0] }}</div>
                    </div>
                    <button @click="logout" class="p-2.5 rounded-xl bg-slate-900 hover:bg-red-900/20 hover:text-red-400 text-dim transition-all border border-slate-800" title="Выйти">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <main :class="['flex-1 p-8 transition-all duration-300',
                          settings.active_layout === 'sidebar-modern' ? 'ml-64' : '',
                          settings.active_layout === 'sidebar-compact' ? 'ml-20' : '',
                          settings.active_layout === 'topbar-pro' ? 'mt-20' : '']">
                <!-- Header -->
                <header class="flex justify-between items-center mb-10">
                    <div class="flex-1 mr-8">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-3xl font-bold text-main">{{ currentMenuName }}</h2>
                            <div class="group relative inline-block">
                                <i data-lucide="help-circle" class="w-4 h-4 text-slate-600 cursor-help"></i>
                                <div class="absolute left-full ml-2 top-0 hidden group-hover:block w-48 p-2 bg-slate-800 text-[10px] rounded shadow-xl z-50">
                                    {{ tab === 'dashboard' ? 'Здесь отображается общая статистика и аналитика по всем заявкам.' : '' }}
                                    {{ tab === 'tasks' ? 'Используйте Kanban-доску для управления статусами. Нажмите на карточку для деталей. Иконка микрофона позволяет вводить текст голосом.' : '' }}
                                    {{ tab === 'forms' ? 'Создавайте шаблоны полей, которые будут отображаться при подаче заявки.' : '' }}
                                    {{ tab === 'users' ? 'Управление доступом сотрудников к системе.' : '' }}
                                    {{ tab === 'settings' ? 'Настройка визуального стиля, шрифтов и системных параметров.' : '' }}
                                </div>
                            </div>
                        </div>
                        <p class="text-dim">Система ХОП PRO • {{ user.role === 'admin' ? 'Полный доступ' : 'Доступ ограничен' }}</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="exportCSV" v-if="user.role === 'admin'" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center space-x-2 transition-all text-main">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Экспорт CSV</span>
                        </button>
                            <div v-if="tab === 'tasks'" class="relative flex items-center space-x-3">
                                <div v-if="settings.maintenance_mode" class="flex items-center space-x-2 bg-red-900/20 px-3 py-1.5 rounded-full border border-red-500/20">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                    <span class="text-[9px] text-red-400 uppercase font-black">Maintenance</span>
                                </div>
                                <div class="flex items-center space-x-2 bg-slate-900 px-3 py-1.5 rounded-full border border-slate-800">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    <span class="text-[9px] text-slate-500 uppercase font-black">Auto-Sync</span>
                                </div>
                                <div class="relative">
                                    <i data-lucide="search" class="absolute left-3 top-3 w-4 h-4 text-slate-500"></i>
                                    <input v-model="searchQuery" placeholder="Поиск по ID или тексту..." class="bg-slate-900 border border-slate-800 pl-10 pr-4 py-2 rounded-xl text-sm outline-none focus:ring-1 focus:ring-indigo-500 w-64">
                                    <button @click="startVoice('searchQuery')" class="absolute right-3 top-2.5 text-slate-500 hover:text-indigo-400"><i data-lucide="mic" class="w-4 h-4"></i></button>
                                </div>
                        </div>
                        <div class="w-10 h-10 glass rounded-xl flex items-center justify-center text-slate-400 relative cursor-pointer">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-indigo-500 rounded-full ring-2 ring-slate-950"></span>
                        </div>
                    </div>
                </header>

                <!-- Views -->
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
                    <div class="card p-4 rounded-2xl mb-6 flex flex-wrap gap-4 items-center">
                        <select v-model="taskFilter.department_id" class="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white outline-none">
                            <option value="">Все отделы</option>
                            <option v-for="d in settings.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                        <select v-model="taskFilter.priority" class="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white outline-none">
                            <option value="">Все приоритеты</option>
                            <option value="low">Низкий</option>
                            <option value="medium">Средний</option>
                            <option value="high">Срочно</option>
                        </select>
                        <div class="flex items-center space-x-2">
                            <input type="date" v-model="taskFilter.date_start" class="bg-slate-900 border border-slate-800 rounded-lg px-2 py-2 text-xs text-white outline-none">
                            <span class="text-slate-600">-</span>
                            <input type="date" v-model="taskFilter.date_end" class="bg-slate-900 border border-slate-800 rounded-lg px-2 py-2 text-xs text-white outline-none">
                        </div>
                        <button @click="taskFilter = {department_id:'', priority:'', date_start:'', date_end:''}" class="text-xs text-slate-500 hover:text-white">Сбросить</button>

                        <div class="ml-auto flex items-center bg-slate-900/50 p-1 rounded-xl border border-slate-800">
                            <button @click="viewMode = 'kanban'" :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', viewMode === 'kanban' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-300']">
                                <i data-lucide="kanban" class="w-3.5 h-3.5 inline mr-1"></i> Канбан
                            </button>
                            <button @click="viewMode = 'table'" :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', viewMode === 'table' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-300']">
                                <i data-lucide="table" class="w-3.5 h-3.5 inline mr-1"></i> Таблица
                            </button>
                        </div>
                    </div>

                    <!-- Kanban View -->
                    <div v-if="viewMode === 'kanban'" class="flex space-x-6 overflow-x-auto pb-6 min-h-[600px]">
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
                                    <!-- Premium Task Card -->
                                    <div v-for="task in filteredTasks(status.id)" :key="task.id" @click="openTask(task)"
                                         class="card p-5 rounded-3xl cursor-pointer hover:scale-[1.02] active:scale-[0.98] transition-all group relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/5 blur-2xl -mr-12 -mt-12 group-hover:bg-indigo-500/10 transition-all"></div>

                                        <div class="flex justify-between items-start mb-4 relative z-10">
                                            <span :class="['text-[9px] uppercase font-black px-2.5 py-1 rounded-full border', priorityClass(task.priority)]">
                                            {{ task.priority }}
                                        </span>
                                            <span class="text-[9px] text-dim font-mono bg-slate-900/50 px-2 py-0.5 rounded">#{{ task.id }}</span>
                                    </div>

                                        <p class="text-sm font-bold text-main mb-4 leading-relaxed line-clamp-2 group-hover:text-indigo-400 transition-colors relative z-10">
                                            {{ task.description }}
                                        </p>

                                        <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-800/50 relative z-10">
                                            <div class="flex items-center space-x-2">
                                                <div v-if="task.executor_id" class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-[8px] font-black text-white shadow-lg shadow-indigo-500/20">
                                                    {{ executors.find(e => e.id == task.executor_id)?.full_name[0] || 'EX' }}
                                                </div>
                                                <div v-else class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-dim">
                                                    <i data-lucide="user" class="w-3 h-3"></i>
                                                </div>
                                                <span class="text-[9px] text-dim font-bold uppercase tracking-tighter">{{ deptName(task.department_id) }}</span>
                                        </div>
                                            <div class="text-right">
                                                <p class="text-[8px] text-dim font-black uppercase">{{ task.created_at.split(' ')[0] }}</p>
                                                <p class="text-[8px] text-indigo-500 font-bold mt-0.5">{{ task.created_at.split(' ')[1] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div v-if="viewMode === 'table'" class="card rounded-3xl overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900/50 border-b border-slate-800">
                                    <th class="p-4 text-[10px] font-black uppercase text-dim tracking-widest">ID</th>
                                    <th class="p-4 text-[10px] font-black uppercase text-dim tracking-widest">Дата</th>
                                    <th class="p-4 text-[10px] font-black uppercase text-dim tracking-widest">Статус</th>
                                    <th class="p-4 text-[10px] font-black uppercase text-dim tracking-widest">Приоритет</th>
                                    <th class="p-4 text-[10px] font-black uppercase text-dim tracking-widest">Отдел</th>
                                    <th class="p-4 text-[10px] font-black uppercase text-dim tracking-widest">Описание</th>
                                    <th class="p-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="task in filteredTasksTable" :key="task.id" @click="openTask(task)" class="border-b border-slate-800/50 hover:bg-slate-800/20 cursor-pointer transition-colors group">
                                    <td class="p-4 font-mono text-xs text-dim">#{{ task.id }}</td>
                                    <td class="p-4 text-xs text-main">{{ task.created_at }}</td>
                                    <td class="p-4">
                                        <div class="flex items-center space-x-2">
                                            <div :class="['w-2 h-2 rounded-full', statusColor(task.status)]"></div>
                                            <span class="text-xs font-bold text-main uppercase">{{ task.status }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span :class="['text-[10px] uppercase font-black px-2 py-0.5 rounded', priorityClass(task.priority)]">
                                            {{ task.priority }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-main">{{ deptName(task.department_id) }}</td>
                                    <td class="p-4 text-xs text-main truncate max-w-xs">{{ task.description }}</td>
                                    <td class="p-4 text-right">
                                        <button class="text-slate-600 group-hover:text-indigo-400 transition-colors">
                                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="!tasks.length" class="p-20 text-center text-dim italic">Нет заявок по заданным фильтрам</div>
                    </div>
                </div>

                <div v-if="tab === 'forms'">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 card p-8 rounded-3xl">
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
                                    <option value="textarea">Многострочный текст</option>
                                    <option value="checkbox">Галочка</option>
                                </select>
                                <label class="flex items-center space-x-2 text-xs text-slate-500">
                                    <input type="checkbox" v-model="field.required">
                                    <span>Обязательно</span>
                                </label>
                                <button @click="removeField(index)" class="text-red-500 hover:text-red-400 p-2"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                        <div class="mt-8 pt-8 border-t border-slate-800 flex items-center justify-between">
                            <button @click="saveSettings" class="bg-slate-200 text-slate-900 px-6 py-3 rounded-xl font-bold hover:bg-white transition-all">Сохранить изменения</button>
                            <div class="flex items-center space-x-2">
                                <input v-model="templateName" placeholder="Имя шаблона" class="bg-slate-900 border border-slate-800 px-3 py-3 rounded-xl text-sm outline-none">
                                <button @click="saveFormAsTemplate" class="bg-indigo-600/20 text-indigo-400 px-4 py-3 rounded-xl text-sm font-bold">Сохранить как шаблон</button>
                            </div>
                        </div>
                    </div>
                    <div class="card p-8 rounded-3xl">
                        <h3 class="text-lg font-bold text-white mb-6">Готовые шаблоны</h3>
                        <div class="space-y-3">
                            <div v-for="tpl in settings.form_templates" :key="tpl.id" class="p-4 bg-slate-900/50 border border-slate-800 rounded-2xl flex items-center justify-between group hover:border-indigo-500/50 transition-all">
                                <span class="text-sm font-medium text-main">{{ tpl.name }}</span>
                                <div class="flex items-center space-x-2">
                                    <button @click="applyTemplate(tpl)" class="text-[10px] font-black text-indigo-400 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all">Применить</button>
                                    <button @click="deleteTemplate(tpl.id)" class="text-red-500/50 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                            <p v-if="!settings.form_templates?.length" class="text-xs text-slate-600 italic">Шаблонов пока нет</p>
                        </div>
                    </div>
                    </div>
                </div>

                <div v-if="tab === 'departments'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <h3 class="text-xl font-bold text-white">Управление отделами</h3>
                            <input v-model="deptSearch" placeholder="Поиск отдела..." class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-xs outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <button @click="openDeptModal(null)" class="bg-indigo-600 px-4 py-2 rounded-xl text-sm font-bold">Новый отдел</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="d in filteredDepts" :key="d.id" class="card p-6 rounded-3xl relative">
                            <h4 class="font-bold text-white text-lg mb-2">{{ d.name }}</h4>
                            <p class="text-xs text-slate-500 mb-4">{{ d.description || 'Нет описания' }}</p>
                            <div class="flex space-x-2">
                                <button @click="openDeptModal(d)" class="flex-1 bg-slate-900 hover:bg-slate-800 py-2 rounded-lg text-xs font-bold transition-all">Изменить</button>
                                <button @click="deleteDept(d.id)" class="px-3 bg-red-900/20 hover:bg-red-900/40 text-red-400 py-2 rounded-lg text-xs transition-all"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'users'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <h3 class="text-xl font-bold text-white">Управление персоналом</h3>
                            <input v-model="userSearch" placeholder="Поиск по имени или логину..." class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-xs outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <button @click="openUserModal(null)" class="bg-indigo-600 px-4 py-2 rounded-xl text-sm font-bold">Добавить сотрудника</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="u in filteredUsers" :key="u.id" class="card p-6 rounded-3xl relative">
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

                <div v-if="tab === 'profile'" class="max-w-2xl">
                    <div class="card p-8 rounded-3xl">
                        <h3 class="text-xl font-bold text-white mb-6">Мой профиль</h3>
                        <form @submit.prevent="updateProfile" class="space-y-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">ФИО</label>
                                <input v-model="user.full_name" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Новый пароль</label>
                                <input v-model="user.password" type="password" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none" placeholder="Оставьте пустым, чтобы не менять">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 py-3 rounded-xl font-bold mt-4">Обновить данные</button>
                        </form>
                    </div>
                </div>

                <div v-if="tab === 'settings'" class="space-y-8 pb-20">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <!-- Left Column: UI Customization -->
                        <div class="lg:col-span-8 space-y-8">
                            <!-- 1. Layout Selection -->
                            <div class="card p-8 rounded-[2.5rem]">
                                <div class="flex items-center space-x-3 mb-8">
                                    <div class="w-10 h-10 bg-indigo-600/10 text-indigo-500 rounded-2xl flex items-center justify-center">
                                        <i data-lucide="layout" class="w-5 h-5"></i>
                                    </div>
                                    <h3 class="text-xl font-black text-white">Шаблон интерфейса</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div v-for="l in layouts" :key="l.id"
                                         @click="settings.active_layout = l.id"
                                         class="group cursor-pointer relative">
                                        <div :class="['aspect-[4/3] rounded-2xl border-2 transition-all overflow-hidden bg-slate-900 flex flex-col', settings.active_layout === l.id ? 'border-indigo-500 ring-4 ring-indigo-500/10' : 'border-slate-800 hover:border-slate-600']">
                                            <!-- Mini Preview Graphic -->
                                            <div class="flex-1 p-2 flex flex-col">
                                                <div v-if="l.id === 'sidebar-modern'" class="flex flex-1 gap-2">
                                                    <div class="w-4 bg-slate-800 rounded"></div>
                                                    <div class="flex-1 bg-slate-800/30 rounded"></div>
                                                </div>
                                                <div v-if="l.id === 'sidebar-compact'" class="flex flex-1 gap-2">
                                                    <div class="w-2 bg-slate-800 rounded"></div>
                                                    <div class="flex-1 bg-slate-800/30 rounded"></div>
                                                </div>
                                                <div v-if="l.id === 'topbar-pro'" class="flex flex-col flex-1 gap-2">
                                                    <div class="h-3 bg-slate-800 rounded w-full"></div>
                                                    <div class="flex-1 bg-slate-800/30 rounded w-full"></div>
                                                </div>
                                            </div>
                                            <div class="bg-slate-950 p-3 flex items-center justify-between">
                                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">{{ l.name }}</span>
                                                <div v-if="settings.active_layout === l.id" class="w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                                    <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Themes Selection -->
                            <div class="card p-8 rounded-[2.5rem]">
                                <div class="flex items-center space-x-3 mb-8">
                                    <div class="w-10 h-10 bg-amber-500/10 text-amber-500 rounded-2xl flex items-center justify-center">
                                        <i data-lucide="palette" class="w-5 h-5"></i>
                                    </div>
                                    <h3 class="text-xl font-black text-white">Цветовая палитра</h3>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div v-for="t in themes" :key="t.id"
                                         @click="settings.active_theme = t.id"
                                         class="p-4 rounded-3xl border-2 cursor-pointer transition-all flex flex-col items-center space-y-3 group"
                                         :class="settings.active_theme === t.id ? 'border-indigo-500 bg-indigo-500/5' : 'border-slate-800 hover:border-slate-700 bg-slate-900/20'">
                                        <div class="w-12 h-12 rounded-2xl shadow-2xl flex items-center justify-center transition-transform group-hover:scale-110"
                                             :style="{ backgroundColor: t.colors.bgMain, border: '1px solid ' + t.colors.border }">
                                             <div class="w-5 h-5 rounded-lg shadow-inner" :style="{ backgroundColor: t.colors.primary }"></div>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-tighter text-center text-slate-500 group-hover:text-white">{{ t.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. System Preferences -->
                            <div class="card p-8 rounded-[2.5rem]">
                                <div class="flex items-center space-x-3 mb-8">
                                    <div class="w-10 h-10 bg-emerald-500/10 text-emerald-500 rounded-2xl flex items-center justify-center">
                                        <i data-lucide="sliders" class="w-5 h-5"></i>
                                    </div>
                                    <h3 class="text-xl font-black text-white">Системные параметры</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-6">
                                        <div>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 block">Название CRM</label>
                                            <input v-model="settings.system_name" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-sm font-bold text-white focus:border-indigo-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 block">Telegram Bot API Key</label>
                                            <div class="relative">
                                                <input v-model="settings.telegram_bot_token" type="password" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-sm font-bold text-white focus:border-indigo-500 outline-none transition-all">
                                                <i data-lucide="lock" class="absolute right-5 top-4.5 w-4 h-4 text-slate-600"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 block">Рабочее время</label>
                                            <div class="flex items-center space-x-4">
                                                <input v-model="settings.work_start" type="time" class="flex-1 bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-sm font-bold text-white outline-none">
                                                <span class="text-slate-700 font-black">—</span>
                                                <input v-model="settings.work_end" type="time" class="flex-1 bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-sm font-bold text-white outline-none">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 block">Типографика</label>
                                            <div class="grid grid-cols-2 gap-4">
                                                <select v-model="settings.font_family" class="bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-xs font-bold text-white outline-none">
                                                    <option v-for="f in ['Inter', 'Roboto', 'Montserrat', 'Ubuntu', 'Raleway']" :key="f" :value="f">{{ f }}</option>
                                                </select>
                                                <select v-model="settings.font_size" class="bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-xs font-bold text-white outline-none">
                                                    <option value="12px">XS (12)</option>
                                                    <option value="14px">MD (14)</option>
                                                    <option value="16px">LG (16)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 block">Приветствие (Портал)</label>
                                            <input v-model="settings.welcome_text" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-sm font-bold text-white focus:border-indigo-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 block">Интервал синхронизации</label>
                                            <div class="relative">
                                                <input v-model="settings.refresh_interval" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-sm font-bold text-white outline-none">
                                                <span class="absolute right-5 top-4.5 text-[10px] font-black text-slate-600 uppercase">СЕК.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Quick Actions & Status -->
                        <div class="lg:col-span-4 space-y-8">
                            <div class="card p-8 rounded-[2.5rem] bg-indigo-600 shadow-2xl shadow-indigo-500/20 text-white">
                                <h4 class="text-lg font-black mb-6">Применить изменения</h4>
                                <p class="text-indigo-100 text-xs leading-relaxed mb-8 opacity-80">Все визуальные и системные настройки будут применены мгновенно для всех пользователей системы ХОП.</p>
                                <button @click="saveSettings" class="w-full bg-white text-indigo-600 hover:bg-indigo-50 py-4 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-xl">
                                    Сохранить всё
                                </button>
                                <button @click="generateDemo" class="w-full bg-indigo-500/50 hover:bg-indigo-400/50 text-white py-3 rounded-2xl font-bold text-[10px] uppercase tracking-widest mt-4 transition-all">
                                    Заполнить ДЕМО
                                </button>
                            </div>

                            <div class="card p-8 rounded-[2.5rem] border-red-500/20">
                                <h4 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-6">Безопасность</h4>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-4 bg-slate-950 rounded-2xl border border-slate-800">
                                        <div>
                                            <p class="text-xs font-black text-white uppercase">Maintenance Mode</p>
                                            <p class="text-[9px] text-slate-600">Вход только для админов</p>
                                        </div>
                                        <div @click="settings.maintenance_mode = !settings.maintenance_mode" :class="['w-12 h-6 rounded-full p-1 cursor-pointer transition-all', settings.maintenance_mode ? 'bg-red-500' : 'bg-slate-800']">
                                            <div :class="['w-4 h-4 bg-white rounded-full transition-all', settings.maintenance_mode ? 'translate-x-6' : '']"></div>
                                        </div>
                                    </div>
                                    <button @click="cleanupTemp" class="w-full py-3 rounded-2xl bg-slate-900 border border-slate-800 text-slate-400 text-[10px] font-black uppercase hover:bg-slate-800 transition-all">
                                        Очистить вложения
                                    </button>
                                </div>
                            </div>

                            <div class="card p-8 rounded-[2.5rem] bg-slate-900/50">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Backups</h4>
                                    <button @click="createBackup" class="text-indigo-400 hover:text-indigo-300 text-[10px] font-black uppercase tracking-tighter">Создать +</button>
                                </div>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="b in backups" :key="b" class="flex items-center justify-between p-3 bg-slate-950/50 rounded-xl border border-slate-800/50 group">
                                        <span class="text-[9px] font-bold text-slate-500 truncate w-24">{{ b }}</span>
                                        <button @click="restoreBackup(b)" class="text-[9px] font-black text-indigo-500 uppercase opacity-0 group-hover:opacity-100 transition-all">Restore</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

        <!-- Dept Modal -->
        <div v-if="deptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="max-w-md w-full glass p-8 rounded-3xl shadow-2xl">
                <h3 class="text-2xl font-bold text-white mb-6">{{ deptModal.id ? 'Редактировать отдел' : 'Новый отдел' }}</h3>
                <form @submit.prevent="saveDept" class="space-y-4">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Название</label>
                        <input v-model="deptModal.name" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Описание</label>
                        <textarea v-model="deptModal.description" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none" rows="3"></textarea>
                    </div>
                    <div class="flex space-x-3 pt-4">
                        <button type="button" @click="deptModal = null" class="flex-1 bg-slate-900 py-3 rounded-xl font-bold">Отмена</button>
                        <button type="submit" class="flex-1 bg-indigo-600 py-3 rounded-xl font-bold">Сохранить</button>
                    </div>
                </form>
            </div>
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
                    <div v-if="userModal.role === 'head' || userModal.role === 'executor'">
                        <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Отдел</label>
                        <select v-model="userModal.department_id" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-white outline-none">
                            <option v-for="d in settings.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
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
            <div class="max-w-5xl w-full glass rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-3">
                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase', priorityClass(selectedTask.priority)]">
                                {{ selectedTask.priority }}
                            </span>
                            <h3 class="text-xl font-bold text-white">Заявка #{{ selectedTask.id }}</h3>
                        </div>
                        <nav class="flex space-x-4">
                            <button @click="taskModalTab = 'details'" :class="['text-xs font-bold pb-2 border-b-2 transition-all', taskModalTab === 'details' ? 'border-indigo-500 text-white' : 'border-transparent text-slate-500']">ДЕТАЛИ</button>
                            <button @click="taskModalTab = 'chat'" :class="['text-xs font-bold pb-2 border-b-2 transition-all', taskModalTab === 'chat' ? 'border-indigo-500 text-white' : 'border-transparent text-slate-500']">ЧАТ С ЗАКАЗЧИКОМ</button>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="printTask(selectedTask.id)" class="text-slate-500 hover:text-indigo-400 p-2 transition-all" title="Печать отчета"><i data-lucide="printer" class="w-5 h-5"></i></button>
                        <button @click="selectedTask = false" class="text-slate-500 hover:text-white p-2 transition-all"><i data-lucide="x" class="w-6 h-6"></i></button>
                    </div>
                </div>
                <div class="flex-1 overflow-hidden flex">
                    <!-- Left Sidebar (Status Controls) -->
                    <div class="w-72 border-r border-slate-800 p-6 bg-slate-900/20 overflow-y-auto">
                        <h4 class="text-[10px] font-black text-slate-600 uppercase tracking-widest mb-6">Статус и Исполнитель</h4>
                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 block mb-2">Назначить исполнителя</label>
                                <select v-model="selectedTask.executor_id" @change="assignExecutor" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                                    <option value="">Не назначен</option>
                                    <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                                </select>
                            </div>
                            <div class="pt-6 border-t border-slate-800">
                                <label class="text-[10px] font-bold text-slate-500 block mb-2">Сменить статус</label>
                                <div class="space-y-2">
                                    <button v-for="s in statuses" :key="s.id" @click="updateTaskStatus(s.id)" :class="['w-full text-left px-3 py-2 rounded-lg text-xs font-bold transition-all border', selectedTask.status === s.id ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700']">
                                        {{ s.name }}
                                    </button>
                                </div>
                            </div>
                            <div class="relative">
                                <label class="text-[10px] font-bold text-slate-500 block mb-2">Обязательный комментарий</label>
                                <textarea v-model="statusComment" placeholder="Опишите причину смены статуса..." class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white outline-none focus:border-indigo-500 h-24"></textarea>
                                <button @click="startVoice('statusComment')" class="absolute right-3 bottom-10 text-slate-600 hover:text-indigo-400"><i data-lucide="mic" class="w-4 h-4"></i></button>
                                <p class="text-[9px] text-slate-600 mt-2 italic">* При смене статуса на 'Выполнено' или 'Отклонено' комментарий обязателен.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Modal Area -->
                    <div class="flex-1 overflow-y-auto p-8">
                        <div v-if="taskModalTab === 'details'" class="space-y-8">
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Описание проблемы</h4>
                                    <p class="text-slate-200 leading-relaxed bg-slate-900/50 p-4 rounded-2xl border border-slate-800">{{ selectedTask.description }}</p>
                                </div>
                                <div v-if="selectedTask.attachments?.length || selectedTask.attachment" class="space-y-3">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Прикрепленные файлы</h4>

                                    <!-- Legacy Single Attachment -->
                                    <div v-if="selectedTask.attachment" class="p-3 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
                                        <span class="text-xs text-slate-400 truncate flex-1 mr-4">{{ selectedTask.attachment }}</span>
                                        <a :href="'uploads/' + selectedTask.attachment" target="_blank" class="text-xs font-bold text-indigo-400 hover:underline">Открыть</a>
                                    </div>

                                    <!-- New Multi Attachments -->
                                    <div v-for="file in selectedTask.attachments" :key="file.name" class="p-3 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
                                        <div class="flex items-center space-x-2 truncate flex-1 mr-4">
                                            <i data-lucide="file" class="w-3 h-3 text-slate-500"></i>
                                            <span class="text-xs text-slate-300 truncate">{{ file.original || file.name }}</span>
                                        </div>
                                        <a :href="'uploads/' + file.name" target="_blank" class="text-xs font-bold text-indigo-400 hover:underline">Открыть</a>
                                    </div>

                                    <!-- Preview images -->
                                    <div class="grid grid-cols-2 gap-2 mt-4">
                                        <template v-for="file in selectedTask.attachments">
                                            <img v-if="['jpg','jpeg','png','gif'].includes(file.name.split('.').pop().toLowerCase())"
                                                 :src="'uploads/' + file.name"
                                                 class="rounded-lg border border-slate-800 h-24 w-full object-cover cursor-pointer hover:opacity-80 transition-all">
                                        </template>
                                        <img v-if="selectedTask.attachment && ['jpg','jpeg','png','gif'].includes(selectedTask.attachment.split('.').pop().toLowerCase())"
                                             :src="'uploads/' + selectedTask.attachment"
                                             class="rounded-lg border border-slate-800 h-24 w-full object-cover">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6">Журнал событий (Audit Trail)</h4>
                                <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[1px] before:bg-slate-800">
                                    <div v-for="h in selectedTask.history" :key="h.at" class="relative pl-8">
                                        <div class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center z-10">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                        </div>
                                        <div class="bg-slate-900/30 p-4 rounded-xl border border-slate-800/50">
                                            <div class="flex items-center justify-between mb-1">
                                                <p class="text-sm font-bold text-slate-200">{{ h.msg }}</p>
                                                <span class="text-[10px] text-slate-600 font-mono">{{ h.at }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-4 h-4 rounded-full bg-slate-800 text-[8px] flex items-center justify-center text-slate-400">{{ h.user[0] }}</div>
                                                <p class="text-[10px] text-slate-500">{{ h.user }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="taskModalTab === 'chat'" class="h-full flex flex-col">
                            <div class="flex-1 space-y-4 mb-6 overflow-y-auto">
                                <div v-for="msg in selectedTask.messages" :key="msg.at" :class="['flex flex-col', msg.user_id == user.id ? 'items-end' : 'items-start']">
                                    <div :class="['max-w-[80%] p-4 rounded-2xl text-sm shadow-sm', msg.user_id == user.id ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-slate-800 text-slate-200 rounded-tl-none border border-slate-700']">
                                        {{ msg.text }}
                                    </div>
                                    <span class="text-[9px] text-slate-600 mt-1 uppercase font-bold">{{ msg.user }} • {{ msg.at.split(' ')[1] }}</span>
                                </div>
                                <div v-if="!selectedTask.messages?.length" class="h-full flex flex-col items-center justify-center text-slate-600">
                                    <i data-lucide="message-square" class="w-12 h-12 mb-4 opacity-20"></i>
                                    <p class="text-sm italic">Сообщений пока нет. Начните диалог с заказчиком.</p>
                                </div>
                            </div>
                            <div class="pt-6 border-t border-slate-800 flex space-x-4">
                                <div class="relative flex-1">
                                    <input v-model="chatMessage" @keyup.enter="sendChatMessage" placeholder="Введите сообщение..." class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white outline-none focus:ring-1 focus:ring-indigo-500">
                                    <button @click="startVoice('chatMessage')" class="absolute right-3 top-3 text-slate-500 hover:text-indigo-400"><i data-lucide="mic" class="w-4 h-4"></i></button>
                                </div>
                                <button @click="sendChatMessage" class="bg-indigo-600 hover:bg-indigo-500 p-3 rounded-xl transition-all shadow-lg shadow-indigo-900/20">
                                    <i data-lucide="send" class="w-5 h-5"></i>
                                </button>
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
