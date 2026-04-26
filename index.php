<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [v-cloak] { display: none; }
        .kanban-column { min-height: 500px; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .dark .glass { background: rgba(15, 23, 42, 0.7); }
        .task-card.selected { border-color: #2563eb; ring: 2px; ring-color: #2563eb; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 transition-colors duration-200" :class="{ 'dark bg-slate-950 text-slate-100': darkMode }">
    <div id="app" v-cloak>
        <!-- Login Screen -->
        <div v-if="!user" class="min-h-screen flex items-center justify-center bg-slate-100 dark:bg-slate-900 p-4">
            <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-xl w-full max-w-md">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Service CRM</h1>
                    <p class="text-slate-500 dark:text-slate-400">{{ t('login_title') }}</p>
                </div>
                <form @submit.prevent="login">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">{{ t('username') }}</label>
                        <input v-model="loginForm.username" type="text" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600 outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-1">{{ t('password') }}</label>
                        <input v-model="loginForm.password" type="password" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600 outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button type="submit" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition-colors">
                        {{ loading ? t('loading') : t('login_btn') }}
                    </button>
                    <div class="mt-4 text-center">
                        <button type="button" @click="forgotPassword" class="text-sm text-blue-600 hover:underline">{{ t('forgot_password') }}</button>
                    </div>
                    <p v-if="error" class="mt-4 text-red-500 text-center text-sm">{{ error }}</p>
                </form>
                <div class="mt-8 flex justify-center space-x-2">
                    <button @click="lang = 'ru'" :class="['text-xs', lang === 'ru' ? 'font-bold' : '']">RU</button>
                    <span class="text-slate-300">|</span>
                    <button @click="lang = 'en'" :class="['text-xs', lang === 'en' ? 'font-bold' : '']">EN</button>
                </div>
            </div>
        </div>

        <!-- Main Layout -->
        <div v-else class="min-h-screen flex">
            <!-- Sidebar -->
            <aside class="w-64 bg-white dark:bg-slate-900 border-r dark:border-slate-800 flex flex-col shrink-0">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-blue-600">Service CRM</h2>
                </div>
                <nav class="flex-1 px-4 space-y-1">
                    <a v-for="item in menuItems" :key="item.id" @click="activeTab = item.id"
                       :class="['flex items-center px-4 py-3 rounded-xl cursor-pointer transition-colors', activeTab === item.id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400']">
                        <span class="mr-3" v-html="item.icon"></span>
                        {{ t(item.id) }}
                    </a>
                </nav>
                <div class="p-4 border-t dark:border-slate-800">
                    <div class="flex items-center space-x-3 mb-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 p-2 rounded-lg" @click="activeTab = 'profile'">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                            {{ user.username[0].toUpperCase() }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-semibold truncate w-32">{{ user.full_name }}</p>
                            <p class="text-xs text-slate-500">{{ roles[user.role] }}</p>
                        </div>
                    </div>
                    <button @click="logout" class="w-full flex items-center justify-center px-4 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 rounded-lg transition-colors">
                        {{ t('logout') }}
                    </button>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-auto bg-slate-50 dark:bg-slate-950 p-8">
                <!-- Header -->
                <header class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-bold">{{ t(activeTab) }}</h1>
                        <p class="text-slate-500 dark:text-slate-400">{{ t('header_subtitle') }}</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="exportCSV" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm border dark:border-slate-700 hover:text-blue-600" title="CSV Export">
                            CSV
                        </button>
                        <button @click="exportPDF" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm border dark:border-slate-700 hover:text-blue-600" title="PDF Report">
                            PDF
                        </button>
                        <button @click="darkMode = !darkMode" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm border dark:border-slate-700">
                            <span v-if="!darkMode">🌙</span>
                            <span v-else>☀️</span>
                        </button>
                        <button v-if="canCreateTask" @click="showCreateModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl shadow-lg shadow-blue-500/20 transition-all">
                            + {{ t('new_task') }}
                        </button>
                    </div>
                </header>

                <!-- Search & Filters -->
                <div v-if="activeTab === 'dashboard'" class="mb-6 flex flex-wrap gap-4 items-center bg-white dark:bg-slate-800 p-4 rounded-2xl border dark:border-slate-700">
                    <div class="relative flex-1 min-w-[200px]">
                        <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                        <input v-model="filters.search" @input="fetchTasks" type="text" :placeholder="t('search_placeholder')" class="w-full pl-10 pr-4 py-2 rounded-xl border dark:bg-slate-700 dark:border-slate-600 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <select v-model="filters.priority" @change="fetchTasks" class="px-4 py-2 rounded-xl border dark:bg-slate-700 dark:border-slate-600">
                        <option value="">{{ t('all_priorities') }}</option>
                        <option v-for="p in settings.priorities" :key="p.id" :value="p.id">{{ p.label }}</option>
                    </select>
                    <div v-if="selectedTasks.length > 0" class="flex items-center space-x-2 bg-blue-50 dark:bg-blue-900/30 px-4 py-2 rounded-xl">
                        <span class="text-sm font-bold text-blue-600">{{ t('selected') }}: {{ selectedTasks.length }}</span>
                        <button @click="showMassAssign = true" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm">{{ t('assign_all') }}</button>
                        <button @click="selectedTasks = []" class="text-slate-400">✕</button>
                    </div>
                </div>

                <!-- Kanban Board -->
                <div v-if="activeTab === 'dashboard'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div v-for="status in settings.statuses" :key="status.id" class="flex flex-col space-y-4">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="font-semibold text-slate-700 dark:text-slate-300 flex items-center">
                                <span :class="['w-2 h-2 rounded-full mr-2', 'bg-' + (status.color || 'slate') + '-500']"></span>
                                {{ status.label }}
                            </h3>
                            <span class="bg-slate-200 dark:bg-slate-800 text-xs px-2 py-1 rounded-full font-medium">
                                {{ tasksByStatus[status.id]?.length || 0 }}
                            </span>
                        </div>
                        <div class="kanban-column space-y-4 p-2 rounded-2xl bg-slate-100/50 dark:bg-slate-900/50 border-2 border-dashed border-transparent hover:border-blue-500/30 transition-colors"
                             @dragover.prevent @drop="dropToStatus($event, status.id)">
                            <div v-for="task in tasksByStatus[status.id]" :key="task.id"
                                 draggable="true" @dragstart="dragTask($event, task)"
                                 @click="openTask(task)"
                                 :class="['task-card bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border dark:border-slate-700 cursor-pointer hover:shadow-md transition-all group relative', selectedTasks.includes(task.id) ? 'border-blue-500 ring-2 ring-blue-500/20' : '']">
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <input type="checkbox" :value="task.id" v-model="selectedTasks" @click.stop>
                                </div>
                                <div class="flex justify-between items-start mb-2">
                                    <span :class="['text-[10px] uppercase font-bold px-2 py-0.5 rounded-full', priorityClass(task.priority)]">
                                        {{ getPriorityLabel(task.priority) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">#{{ task.id.substring(0, 6) }}</span>
                                </div>
                                <h4 class="font-semibold text-sm mb-2 group-hover:text-blue-600 transition-colors">{{ task.title }}</h4>
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex -space-x-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-500 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[10px] text-white" :title="task.creator_name">
                                            {{ task.creator_name[0] }}
                                        </div>
                                        <div v-if="task.executor_name" class="w-6 h-6 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[10px] text-white" :title="'Исполнитель: ' + task.executor_name">
                                            {{ task.executor_name[0] }}
                                        </div>
                                    </div>
                                    <div class="flex items-center text-slate-400 text-[10px]">
                                        <span class="mr-2">💬 {{ task.comments?.length || 0 }}</span>
                                        <span>📅 {{ formatDate(task.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Tab -->
                <div v-if="activeTab === 'users'" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border dark:border-slate-700 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b dark:border-slate-700">
                                <th class="px-6 py-4 font-semibold text-sm">{{ t('user') }}</th>
                                <th class="px-6 py-4 font-semibold text-sm">{{ t('role') }}</th>
                                <th class="px-6 py-4 font-semibold text-sm">{{ t('department') }}</th>
                                <th class="px-6 py-4 font-semibold text-sm">{{ t('actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in allUsers" :key="u.id" class="border-b dark:border-slate-700 last:border-0">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center mr-3 font-bold text-xs">
                                            {{ u.username[0].toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm">{{ u.full_name }}</p>
                                            <p class="text-xs text-slate-500">@{{ u.username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 px-2 py-1 rounded-lg">
                                        {{ roles[u.role] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">{{ u.department }}</td>
                                <td class="px-6 py-4">
                                    <button class="text-slate-400 hover:text-blue-600 transition-colors">✏️</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Profile Tab -->
                <div v-if="activeTab === 'profile'" class="max-w-2xl">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border dark:border-slate-700">
                        <h3 class="text-xl font-bold mb-6">{{ t('profile_settings') }}</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium mb-1">{{ t('full_name') }}</label>
                                <input v-model="profileForm.full_name" type="text" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">{{ t('new_password') }}</label>
                                <input v-model="profileForm.password" type="password" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600">
                            </div>
                            <button @click="updateProfile" class="bg-blue-600 text-white px-8 py-2 rounded-xl">{{ t('save') }}</button>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div v-if="activeTab === 'settings'" class="max-w-4xl">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border dark:border-slate-700 space-y-8">
                        <div>
                            <h3 class="text-lg font-bold mb-4">{{ t('basic_settings') }}</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium mb-1">{{ t('company_name') }}</label>
                                    <input v-model="settings.company_name" type="text" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">{{ t('storage_mode') }}</label>
                                    <select v-model="settings.storage_mode" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600 outline-none">
                                        <option value="json">JSON</option>
                                        <option value="mysql">MySQL</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold mb-4">{{ t('form_constructor') }}</h3>
                            <div class="space-y-4">
                                <div v-for="(field, index) in settings.form_fields" :key="index" class="flex items-center space-x-4 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border dark:border-slate-700">
                                    <div class="flex-1 grid grid-cols-3 gap-4">
                                        <input v-model="field.label" placeholder="Label" class="px-3 py-1.5 rounded-lg border text-sm">
                                        <select v-model="field.type" class="px-3 py-1.5 rounded-lg border text-sm">
                                            <option value="text">Text</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="number">Number</option>
                                            <option value="date">Date</option>
                                        </select>
                                        <div class="flex items-center">
                                            <input type="checkbox" v-model="field.required" :id="'req-'+index" class="mr-2">
                                            <label :for="'req-'+index" class="text-sm">{{ t('required') }}</label>
                                        </div>
                                    </div>
                                    <button @click="settings.form_fields.splice(index, 1)" class="text-red-500">🗑️</button>
                                </div>
                                <button @click="settings.form_fields.push({label:'', type:'text', required:false})" class="text-blue-600 text-sm font-medium">+ {{ t('add_field') }}</button>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button @click="saveSettings" class="bg-blue-600 text-white px-8 py-2 rounded-xl shadow-lg shadow-blue-500/20">{{ t('save_all') }}</button>
                        </div>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div v-if="activeTab === 'analytics'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border dark:border-slate-700">
                            <p class="text-slate-500 text-sm mb-1">{{ t('total_tasks') }}</p>
                            <h3 class="text-2xl font-bold">{{ analyticsData.total_tasks }}</h3>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border dark:border-slate-700">
                            <p class="text-slate-500 text-sm mb-1">{{ t('avg_time') }} (h)</p>
                            <h3 class="text-2xl font-bold">{{ analyticsData.avg_execution_time }}</h3>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border dark:border-slate-700">
                            <p class="text-slate-500 text-sm mb-1">SLA</p>
                            <h3 class="text-2xl font-bold" :class="analyticsData.sla_compliance < 90 ? 'text-red-500' : 'text-emerald-500'">{{ analyticsData.sla_compliance }}%</h3>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border dark:border-slate-700">
                            <p class="text-slate-500 text-sm mb-1">{{ t('active_tasks') }}</p>
                            <h3 class="text-2xl font-bold">{{ (analyticsData.status_distribution?.new || 0) + (analyticsData.status_distribution?.assigned || 0) + (analyticsData.status_distribution?.in_work || 0) }}</h3>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border dark:border-slate-700 h-[400px]">
                            <h3 class="font-bold mb-4">{{ t('tasks_by_status') }}</h3>
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border dark:border-slate-700 h-[400px]">
                            <h3 class="font-bold mb-4">{{ t('load_on_executors') }}</h3>
                            <canvas id="loadChart"></canvas>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create Task Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
                <div class="px-8 py-6 border-b dark:border-slate-700 flex justify-between items-center">
                    <h2 class="text-xl font-bold">{{ t('new_task') }}</h2>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ t('title') }}</label>
                        <input v-model="newTask.title" type="text" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600" placeholder="">
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ t('category') }}</label>
                            <select v-model="newTask.category" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600">
                                <option v-for="c in settings.categories" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ t('priority') }}</label>
                            <select v-model="newTask.priority" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600">
                                <option v-for="p in settings.priorities" :key="p.id" :value="p.id">{{ p.label }}</option>
                            </select>
                        </div>
                    </div>
                    <!-- Dynamic fields -->
                    <div v-for="field in settings.form_fields" :key="field.label">
                        <label class="block text-sm font-medium mb-1">{{ field.label }} {{ field.required ? '*' : '' }}</label>
                        <textarea v-if="field.type === 'textarea'" v-model="newTask.custom_fields[field.label]" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600" rows="3"></textarea>
                        <input v-else :type="field.type" v-model="newTask.custom_fields[field.label]" class="w-full px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600">
                    </div>
                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ t('attachments') }}</label>
                        <input type="file" @change="handleFileUpload($event)" class="text-sm">
                        <div class="mt-2 flex flex-wrap gap-2">
                            <div v-for="file in newTask.attachments" :key="file.path" class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-xs flex items-center">
                                {{ file.name }}
                                <button @click="newTask.attachments = newTask.attachments.filter(f => f !== file)" class="ml-2 text-red-500">✕</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end space-x-4">
                    <button @click="showCreateModal = false" class="px-6 py-2 text-slate-600">{{ t('cancel') }}</button>
                    <button @click="createTask" class="bg-blue-600 text-white px-8 py-2 rounded-xl">{{ t('create') }}</button>
                </div>
            </div>
        </div>

        <!-- Task Detail Modal -->
        <div v-if="openedTask" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openedTask = null"></div>
            <div class="relative bg-white dark:bg-slate-800 w-full max-w-4xl h-[90vh] rounded-2xl shadow-2xl flex flex-col">
                <div class="px-8 py-6 border-b dark:border-slate-700 flex justify-between items-center">
                    <div>
                        <span :class="['text-[10px] uppercase font-bold px-2 py-1 rounded-full mr-3', priorityClass(openedTask.priority)]">
                            {{ getPriorityLabel(openedTask.priority) }}
                        </span>
                        <h2 class="text-xl font-bold inline">#{{ openedTask.id.substring(0, 8) }} {{ openedTask.title }}</h2>
                    </div>
                    <button @click="openedTask = null" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="flex-1 flex overflow-hidden">
                    <!-- Left: Info -->
                    <div class="flex-1 p-8 overflow-y-auto border-r dark:border-slate-700">
                        <div class="mb-8">
                            <h3 class="text-sm font-bold uppercase text-slate-400 mb-4">{{ t('history') }}</h3>
                            <div class="space-y-2">
                                <div v-for="h in openedTask.history" :key="h.date" class="text-xs flex items-start">
                                    <span class="text-slate-400 w-32 shrink-0">{{ formatDate(h.date) }}</span>
                                    <span class="font-semibold w-32 shrink-0">{{ h.user }}:</span>
                                    <span>{{ h.action }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-8 mb-8">
                            <div>
                                <h3 class="text-sm font-bold uppercase text-slate-400 mb-2">{{ t('creator') }}</h3>
                                <p>{{ openedTask.creator_name }} ({{ openedTask.department }})</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold uppercase text-slate-400 mb-2">{{ t('executor') }}</h3>
                                <p v-if="openedTask.executor_name">{{ openedTask.executor_name }}</p>
                                <p v-else class="text-slate-400 italic">{{ t('not_assigned') }}</p>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div v-if="openedTask.attachments?.length > 0" class="mb-8">
                            <h3 class="text-sm font-bold uppercase text-slate-400 mb-4">{{ t('attachments') }}</h3>
                            <div class="flex flex-wrap gap-4">
                                <a v-for="file in openedTask.attachments" :key="file.path" :href="file.path" target="_blank" class="flex items-center p-2 border rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <span class="mr-2">📎</span>
                                    <span class="text-sm">{{ file.name }}</span>
                                </a>
                            </div>
                        </div>

                        <div v-if="Object.keys(openedTask.custom_fields || {}).length > 0" class="mb-8">
                            <h3 class="text-sm font-bold uppercase text-slate-400 mb-4">{{ t('custom_fields') }}</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="(val, key) in openedTask.custom_fields" :key="key">
                                    <p class="text-xs text-slate-500">{{ key }}</p>
                                    <p class="font-medium">{{ val }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment (Admin/Head) -->
                        <div v-if="canAssign(openedTask)" class="mt-12 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                            <h3 class="font-bold mb-4">{{ t('assign_executor') }}</h3>
                            <div class="flex space-x-4">
                                <select v-model="assigneeId" class="flex-1 px-4 py-2 rounded-lg border dark:bg-slate-700 dark:border-slate-600">
                                    <option v-for="u in executors" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                                </select>
                                <button @click="assignTask" class="bg-blue-600 text-white px-6 py-2 rounded-lg">{{ t('assign') }}</button>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Comments/Timeline -->
                    <div class="w-80 p-8 bg-slate-50 dark:bg-slate-900/50 flex flex-col">
                        <h3 class="font-bold mb-6">{{ t('comments') }}</h3>
                        <div class="flex-1 overflow-y-auto space-y-4 mb-6">
                            <div v-for="c in openedTask.comments" :key="c.date" class="bg-white dark:bg-slate-800 p-3 rounded-xl shadow-sm text-sm">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold">{{ c.user }}</span>
                                    <span class="text-[10px] text-slate-400">{{ formatDate(c.date) }}</span>
                                </div>
                                <p>{{ c.text }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <textarea v-model="commentText" class="w-full p-3 text-sm rounded-xl border dark:bg-slate-700 dark:border-slate-600 outline-none" rows="3" :placeholder="t('add_comment')"></textarea>
                            <button @click="addComment" class="w-full bg-blue-600 text-white py-2 rounded-xl text-sm font-bold">{{ t('send') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp, ref, onMounted, computed, watch, nextTick } = Vue;

        const translations = {
            ru: {
                login_title: 'Вход в систему', username: 'Логин', password: 'Пароль', login_btn: 'Войти', loading: 'Загрузка...', forgot_password: 'Забыли пароль?',
                dashboard: 'Заявки', analytics: 'Аналитика', users: 'Пользователи', settings: 'Настройки', profile: 'Профиль', logout: 'Выйти',
                header_subtitle: 'Управление заявками и процессами', new_task: 'Новая заявка', search_placeholder: 'Поиск...', all_priorities: 'Все приоритеты',
                selected: 'Выбрано', assign_all: 'Назначить всех', user: 'Пользователь', role: 'Роль', department: 'Отдел', actions: 'Действия',
                profile_settings: 'Настройки профиля', full_name: 'ФИО', new_password: 'Новый пароль', save: 'Сохранить',
                basic_settings: 'Основные настройки', company_name: 'Название компании', storage_mode: 'Режим хранения', form_constructor: 'Конструктор форм',
                required: 'Обязательное', add_field: 'Добавить поле', save_all: 'Сохранить всё', total_tasks: 'Всего заявок', avg_time: 'Среднее время',
                active_tasks: 'Активных заявок', tasks_by_status: 'Заявки по статусам', load_on_executors: 'Нагрузка на исполнителей',
                title: 'Заголовок', category: 'Категория', priority: 'Приоритет', cancel: 'Отмена', create: 'Создать', attachments: 'Приложения',
                history: 'История', creator: 'Создатель', executor: 'Исполнитель', not_assigned: 'Не назначен', custom_fields: 'Доп. поля',
                assign_executor: 'Назначить исполнителя', assign: 'Назначить', comments: 'Комментарии', add_comment: 'Написать комментарий...', send: 'Отправить'
            },
            en: {
                login_title: 'Login to System', username: 'Username', password: 'Password', login_btn: 'Login', loading: 'Loading...', forgot_password: 'Forgot Password?',
                dashboard: 'Tasks', analytics: 'Analytics', users: 'Users', settings: 'Settings', profile: 'Profile', logout: 'Logout',
                header_subtitle: 'Manage tasks and processes', new_task: 'New Task', search_placeholder: 'Search...', all_priorities: 'All priorities',
                selected: 'Selected', assign_all: 'Assign all', user: 'User', role: 'Role', department: 'Department', actions: 'Actions',
                profile_settings: 'Profile Settings', full_name: 'Full Name', new_password: 'New Password', save: 'Save',
                basic_settings: 'Basic Settings', company_name: 'Company Name', storage_mode: 'Storage Mode', form_constructor: 'Form Constructor',
                required: 'Required', add_field: 'Add field', save_all: 'Save all', total_tasks: 'Total Tasks', avg_time: 'Avg Time',
                active_tasks: 'Active Tasks', tasks_by_status: 'Tasks by Status', load_on_executors: 'Load on Executors',
                title: 'Title', category: 'Category', priority: 'Priority', cancel: 'Cancel', create: 'Create', attachments: 'Attachments',
                history: 'History', creator: 'Creator', executor: 'Executor', not_assigned: 'Not assigned', custom_fields: 'Custom Fields',
                assign_executor: 'Assign Executor', assign: 'Assign', comments: 'Comments', add_comment: 'Add comment...', send: 'Send'
            }
        };

        createApp({
            setup() {
                const user = ref(JSON.parse(localStorage.getItem('user')));
                const token = ref(localStorage.getItem('token'));
                const darkMode = ref(localStorage.getItem('darkMode') === 'true');
                const lang = ref(localStorage.getItem('lang') || 'ru');
                const loading = ref(false);
                const error = ref('');
                const activeTab = ref('dashboard');
                const showCreateModal = ref(false);
                const showMassAssign = ref(false);
                const openedTask = ref(null);
                const tasks = ref([]);
                const allUsers = ref([]);
                const selectedTasks = ref([]);
                const massAssigneeId = ref(null);
                const analyticsData = ref({});
                const profileForm = ref({ full_name: user.value?.full_name || '', password: '' });
                const filters = ref({ search: '', status: '', priority: '' });
                const settings = ref({
                    company_name: 'Service CRM',
                    categories: [],
                    priorities: [],
                    statuses: [],
                    form_fields: []
                });

                const loginForm = ref({ username: '', password: '' });
                const newTask = ref({ title: '', category: '', priority: 'medium', custom_fields: {}, attachments: [] });
                const commentText = ref('');
                const assigneeId = ref(null);

                let statusChart = null;
                let loadChart = null;

                const t = (key) => translations[lang.value][key] || key;
                watch(lang, (val) => localStorage.setItem('lang', val));

                const roles = { admin: 'Администратор', employee: 'Сотрудник', head: 'Начальник отдела', executor: 'Исполнитель' };

                const menuItems = computed(() => {
                    const items = [{ id: 'dashboard', label: 'Заявки', icon: '📋' }];
                    if (user.value?.role === 'admin' || user.value?.role === 'head') items.push({ id: 'analytics', label: 'Аналитика', icon: '📊' });
                    if (user.value?.role === 'admin') {
                        items.push({ id: 'users', label: 'Пользователи', icon: '👥' });
                        items.push({ id: 'settings', label: 'Настройки', icon: '⚙️' });
                    }
                    return items;
                });

                const tasksByStatus = computed(() => {
                    const groups = {};
                    tasks.value.forEach(t => { if (!groups[t.status]) groups[t.status] = []; groups[t.status].push(t); });
                    return groups;
                });

                const executors = computed(() => allUsers.value.filter(u => u.role === 'executor'));
                const canCreateTask = computed(() => user.value?.role === 'employee' || user.value?.role === 'admin');

                watch(darkMode, (val) => localStorage.setItem('darkMode', val));
                watch(activeTab, (val) => { if (val === 'analytics') fetchAnalytics().then(() => nextTick(initCharts)); });

                const api = axios.create({ baseURL: 'api/' });
                api.interceptors.request.use(config => { if (token.value) config.headers.Authorization = `Bearer ${token.value}`; return config; });

                const login = async () => {
                    loading.value = true; error.value = '';
                    try {
                        const res = await api.post('auth.php?action=login', loginForm.value);
                        user.value = res.data.user; token.value = res.data.token;
                        localStorage.setItem('user', JSON.stringify(user.value)); localStorage.setItem('token', token.value);
                        profileForm.value.full_name = user.value.full_name;
                        init();
                    } catch (e) { error.value = 'Invalid credentials'; } finally { loading.value = false; }
                };

                const logout = () => { user.value = null; token.value = null; localStorage.removeItem('user'); localStorage.removeItem('token'); };

                const fetchTasks = async () => {
                    try {
                        const params = new URLSearchParams({ action: 'list', ...filters.value });
                        const res = await api.get('tasks.php?' + params.toString());
                        tasks.value = res.data;
                    } catch (e) {}
                };

                const fetchData = async () => {
                    try {
                        fetchTasks();
                        const sRes = await api.get('settings.php');
                        settings.value = sRes.data;
                        if (user.value.role === 'admin' || user.value.role === 'head') {
                            const uRes = await api.get('users.php?action=list');
                            allUsers.value = uRes.data;
                        }
                    } catch (e) {}
                };

                const fetchAnalytics = async () => {
                    try {
                        const res = await api.get('analytics.php');
                        analyticsData.value = res.data;
                    } catch (e) {}
                };

                const handleFileUpload = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    const formData = new FormData();
                    formData.append('file', file);
                    try {
                        const res = await api.post('uploads.php', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
                        newTask.value.attachments.push({ path: res.data.path, name: res.data.name });
                    } catch (e) { alert('Upload failed'); }
                };

                const createTask = async () => {
                    try {
                        await api.post('tasks.php?action=create', { ...newTask.value, description: newTask.value.title });
                        showCreateModal.value = false;
                        newTask.value = { title: '', category: '', priority: 'medium', custom_fields: {}, attachments: [] };
                        fetchTasks();
                    } catch (e) {}
                };

                const openTask = (task) => { openedTask.value = task; assigneeId.value = task.executor_id; };

                const addComment = async () => {
                    if (!commentText.value) return;
                    try {
                        await api.post('tasks.php?action=add_comment', { id: openedTask.value.id, text: commentText.value });
                        commentText.value = '';
                        fetchTasks().then(() => { openedTask.value = tasks.value.find(t => t.id === openedTask.value.id); });
                    } catch (e) {}
                };

                const assignTask = async () => {
                    const executor = executors.value.find(u => u.id === assigneeId.value);
                    try {
                        await api.post('tasks.php?action=assign', { id: openedTask.value.id, executor_id: assigneeId.value, executor_name: executor.full_name });
                        fetchTasks().then(() => { openedTask.value = tasks.value.find(t => t.id === openedTask.value.id); });
                    } catch (e) {}
                };

                const updateProfile = async () => {
                    try {
                        await api.post('users.php?action=update_profile', profileForm.value);
                        alert('Profile updated');
                        const res = await api.get('auth.php?action=me');
                        user.value = res.data; localStorage.setItem('user', JSON.stringify(user.value));
                    } catch (e) {}
                };

                const exportCSV = () => { window.location.href = 'api/export.php?token=' + token.value; };
                const exportPDF = () => { window.open('api/report.php?token=' + token.value, '_blank'); };

                const forgotPassword = () => { alert('Please contact system administrator to reset your password.'); };

                const dragTask = (e, task) => { e.dataTransfer.setData('taskId', task.id); };
                const dropToStatus = async (e, statusId) => {
                    const taskId = e.dataTransfer.getData('taskId');
                    try { await api.post('tasks.php?action=update_status', { id: taskId, status: statusId }); fetchTasks(); } catch (e) {}
                };

                const canAssign = (task) => (user.value.role === 'admin' || (user.value.role === 'head' && task.department === user.value.department));
                const priorityClass = (p) => {
                    const classes = { low: 'bg-blue-100 text-blue-600', medium: 'bg-yellow-100 text-yellow-600', high: 'bg-orange-100 text-orange-600', critical: 'bg-red-100 text-red-600' };
                    return classes[p] || 'bg-slate-100';
                };
                const getPriorityLabel = (p) => settings.value.priorities.find(pr => pr.id === p)?.label || p;
                const formatDate = (dateStr) => {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                };

                const saveSettings = async () => { try { await api.post('settings.php', settings.value); alert('Settings saved'); } catch (e) {} };

                const initCharts = () => {
                    if (statusChart) statusChart.destroy(); if (loadChart) loadChart.destroy();
                    const ctxStatus = document.getElementById('statusChart').getContext('2d');
                    statusChart = new Chart(ctxStatus, {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(analyticsData.value.status_distribution).map(s => settings.value.statuses.find(st => st.id === s)?.label || s),
                            datasets: [{ data: Object.values(analyticsData.value.status_distribution), backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#64748b'] }]
                        },
                        options: { maintainAspectRatio: false }
                    });
                    const ctxLoad = document.getElementById('loadChart').getContext('2d');
                    loadChart = new Chart(ctxLoad, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(analyticsData.value.load_by_executor),
                            datasets: [{ label: 'Tasks', data: Object.values(analyticsData.value.load_by_executor), backgroundColor: '#3b82f6' }]
                        },
                        options: { maintainAspectRatio: false }
                    });
                };

                const init = () => { if (user.value) fetchData(); };
                onMounted(init);

                return {
                    user, token, darkMode, lang, loading, error, activeTab, menuItems, roles, t,
                    loginForm, login, logout, tasksByStatus, settings, showCreateModal, newTask, createTask,
                    priorityClass, getPriorityLabel, formatDate, openTask, openedTask, commentText, addComment,
                    executors, assigneeId, assignTask, canAssign, dragTask, dropToStatus, allUsers, canCreateTask,
                    saveSettings, filters, fetchTasks, selectedTasks, showMassAssign, massAssigneeId,
                    analyticsData, profileForm, updateProfile, exportCSV, exportPDF, handleFileUpload, forgotPassword
                };
            }
        }).mount('#app');
    </script>
</body>
</html>
