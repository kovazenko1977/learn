
const translations = {
    ru: {
        login: "Вход",
        username: "Имя пользователя",
        password: "Пароль",
        submit: "Войти",
        dashboard: "Дашборд",
        tasks: "Заявки",
        users: "Пользователи",
        settings: "Настройки",
        profile: "Профиль",
        logout: "Выход",
        new_task: "Новая заявка",
        status_new: "Новая",
        status_assigned: "Назначена",
        status_in_work: "В работе",
        status_done: "Выполнено",
        status_rejected: "Отклонено",
        priority_low: "Низкий",
        priority_medium: "Средний",
        priority_high: "Высокий",
        priority_critical: "Критический",
        save: "Сохранить",
        cancel: "Отмена",
        delete: "Удалить",
        assign: "Назначить",
        categories: "Категории",
        history: "История",
        export: "Экспорт CSV"
    },
    en: {
        login: "Login",
        username: "Username",
        password: "Password",
        submit: "Sign In",
        dashboard: "Dashboard",
        tasks: "Tasks",
        users: "Users",
        settings: "Settings",
        profile: "Profile",
        logout: "Logout",
        new_task: "New Request",
        status_new: "New",
        status_assigned: "Assigned",
        status_in_work: "In Progress",
        status_done: "Done",
        status_rejected: "Rejected",
        priority_low: "Low",
        priority_medium: "Medium",
        priority_high: "High",
        priority_critical: "Critical",
        save: "Save",
        cancel: "Cancel",
        delete: "Delete",
        assign: "Assign",
        categories: "Categories",
        history: "History",
        export: "Export CSV"
    }
};

const { createApp, ref, reactive, onMounted, computed, watch, nextTick } = Vue;

const App = {
    setup() {
        const user = ref(JSON.parse(localStorage.getItem('user')) || null);
        const token = ref(localStorage.getItem('token') || '');
        const lang = ref(localStorage.getItem('lang') || 'ru');
        const darkMode = ref(localStorage.getItem('darkMode') === 'true');
        const view = ref(user.value ? 'dashboard' : 'login');
        const settings = ref({});
        const tasks = ref([]);
        const users = ref([]);
        const stats = ref(null);

        const t = (key) => translations[lang.value][key] || key;

        const formatDuration = (seconds) => {
            if (!seconds) return '-';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            return lang.value === 'ru' ? `${h}ч ${m}м` : `${h}h ${m}m`;
        };

        const toggleDarkMode = () => {
            darkMode.value = !darkMode.value;
            localStorage.setItem('darkMode', darkMode.value);
            document.documentElement.classList.toggle('dark', darkMode.value);
        };

        const toggleLang = () => {
            lang.value = lang.value === 'ru' ? 'en' : 'ru';
            localStorage.setItem('lang', lang.value);
        };

        const api = async (endpoint, method = 'GET', body = null) => {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}`
                }
            };
            if (body) options.body = JSON.stringify(body);
            const res = await fetch(`api/${endpoint}`, options);
            if (res.status === 401 && view.value !== 'login') {
                logout();
                return;
            }
            if (res.headers.get('Content-Type')?.includes('csv')) return res.blob();
            return res.json();
        };

        const login = async (credentials) => {
            const res = await api('auth.php?action=login', 'POST', credentials);
            if (res?.success) {
                user.value = res.user;
                token.value = res.token;
                localStorage.setItem('user', JSON.stringify(res.user));
                localStorage.setItem('token', res.token);
                view.value = 'dashboard';
                initData();
            } else {
                alert(res?.message || 'Login failed');
            }
        };

        const logout = () => {
            user.value = null;
            token.value = '';
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            view.value = 'login';
        };

        const initData = async () => {
            if (!user.value) return;
            const [s, t, u, st] = await Promise.all([
                api('settings.php'),
                api('tasks.php'),
                user.value.role === 'Administrator' ? api('users.php') : Promise.resolve([]),
                (user.value.role === 'Administrator' || user.value.role === 'Department Head') ? api('analytics.php') : Promise.resolve(null)
            ]);
            settings.value = s || {};
            tasks.value = t || [];
            users.value = u || [];
            stats.value = st;
        };

        onMounted(() => {
            if (darkMode.value) document.documentElement.classList.add('dark');
            if (user.value) initData();
        });

        return {
            user, token, lang, darkMode, view, t, formatDuration, toggleDarkMode, toggleLang,
            login, logout, api, settings, tasks, users, stats, initData
        };
    },
    template: `
        <div :class="{ 'dark': darkMode }" class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors">
            <!-- Login View -->
            <div v-if="view === 'login'" class="flex items-center justify-center min-h-screen">
                <login-form @login="login" :t="t"></login-form>
            </div>

            <!-- Main Layout -->
            <div v-else class="flex flex-col h-screen">
                <!-- Top Nav -->
                <nav class="bg-white dark:bg-gray-800 shadow-sm px-6 py-3 flex justify-between items-center no-print">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-xl font-bold text-primary">Sanatorium 2.0</h1>
                        <div class="hidden md:flex space-x-1">
                            <nav-link :active="view === 'dashboard'" @click="view = 'dashboard'">{{ t('dashboard') }}</nav-link>
                            <nav-link :active="view === 'tasks'" @click="view = 'tasks'">{{ t('tasks') }}</nav-link>
                            <nav-link v-if="user.role === 'Administrator'" :active="view === 'users'" @click="view = 'users'">{{ t('users') }}</nav-link>
                            <nav-link v-if="user.role === 'Administrator'" :active="view === 'settings'" @click="view = 'settings'">{{ t('settings') }}</nav-link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="toggleDarkMode" class="text-gray-500 hover:text-primary"><i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i></button>
                        <button @click="toggleLang" class="text-sm font-medium uppercase">{{ lang }}</button>
                        <div class="relative group">
                            <button class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white text-xs">{{ user.username[0].toUpperCase() }}</div>
                                <span class="hidden md:block dark:text-gray-200">{{ user.full_name }}</span>
                            </button>
                            <div class="absolute right-0 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-md mt-2 py-1 hidden group-hover:block z-50">
                                <button @click="view = 'profile'" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">{{ t('profile') }}</button>
                                <button @click="logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">{{ t('logout') }}</button>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <main class="flex-1 overflow-auto p-6">
                    <dashboard-view v-if="view === 'dashboard'" :stats="stats" :t="t" :format-duration="formatDuration"></dashboard-view>
                    <tasks-view v-if="view === 'tasks'" :tasks="tasks" :users="users" :settings="settings" :user="user" :t="t" @refresh="initData" :api="api"></tasks-view>
                    <users-view v-if="view === 'users'" :users="users" :t="t" @refresh="initData" :api="api"></users-view>
                    <settings-view v-if="view === 'settings'" :settings="settings" :t="t" @refresh="initData" :api="api"></settings-view>
                    <profile-view v-if="view === 'profile'" :user="user" :t="t" @refresh="initData" :api="api"></profile-view>
                </main>
            </div>
        </div>
    `
};

const app = createApp(App);
app.component('draggable', vuedraggable);

// Components will be added here
app.component('nav-link', {
    props: ['active'],
    template: `<button :class="['px-3 py-2 rounded-md text-sm font-medium transition-colors', active ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-primary']"><slot></slot></button>`
});

app.component('login-form', {
    props: ['t'],
    setup(props, { emit }) {
        const mode = ref('login');
        const form = reactive({ username: '', password: '', email: '', token: '' });

        const submit = () => {
            if (mode.value === 'login') emit('login', { username: form.username, password: form.password });
            else if (mode.value === 'recover') recover();
            else reset();
        };

        const recover = async () => {
            const res = await fetch('api/auth.php?action=recover', {
                method: 'POST',
                body: JSON.stringify({ email: form.email })
            }).then(r => r.json());
            alert(res.message);
            if (res.success) {
                form.token = res.debug_token; // For demo purposes
                mode.value = 'reset';
            }
        };

        const reset = async () => {
            const res = await fetch('api/auth.php?action=reset', {
                method: 'POST',
                body: JSON.stringify({ token: form.token, password: form.password })
            }).then(r => r.json());
            alert(res.message);
            if (res.success) mode.value = 'login';
        };

        return { mode, form, submit };
    },
    template: `
        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-xl w-full max-w-md">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-primary">Sanatorium 2.0</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Service CRM Portal</p>
            </div>

            <form v-if="mode === 'login'" @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('username') }}</label>
                    <input v-model="form.username" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm p-2 dark:bg-gray-700 dark:text-white" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('password') }}</label>
                    <input v-model="form.password" type="password" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm p-2 dark:bg-gray-700 dark:text-white" required>
                </div>
                <button type="submit" class="w-full bg-primary text-white font-bold py-2 px-4 rounded-md hover:bg-blue-600 transition-colors">{{ t('submit') }}</button>
                <button type="button" @click="mode = 'recover'" class="w-full text-sm text-primary hover:underline">Забыли пароль?</button>
            </form>

            <form v-if="mode === 'recover'" @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ваш Email</label>
                    <input v-model="form.email" type="email" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm p-2 dark:bg-gray-700 dark:text-white" required>
                </div>
                <button type="submit" class="w-full bg-primary text-white font-bold py-2 px-4 rounded-md hover:bg-blue-600 transition-colors">Восстановить</button>
                <button type="button" @click="mode = 'login'" class="w-full text-sm text-gray-500 hover:underline">Вернуться к входу</button>
            </form>

            <form v-if="mode === 'reset'" @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Токен из письма</label>
                    <input v-model="form.token" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm p-2 dark:bg-gray-700 dark:text-white" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Новый пароль</label>
                    <input v-model="form.password" type="password" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm p-2 dark:bg-gray-700 dark:text-white" required>
                </div>
                <button type="submit" class="w-full bg-primary text-white font-bold py-2 px-4 rounded-md hover:bg-blue-600 transition-colors">Сохранить</button>
            </form>
        </div>
    `
});

app.component('dashboard-view', {
    props: ['stats', 't', 'formatDuration'],
    template: `
        <div>
            <h2 class="text-2xl font-bold mb-6 dark:text-white">{{ t('dashboard') }}</h2>
            <div v-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Всего заявок</div>
                    <div class="text-3xl font-bold dark:text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">В работе</div>
                    <div class="text-3xl font-bold dark:text-white">{{ stats.by_status.in_work + stats.by_status.assigned }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Выполнено</div>
                    <div class="text-3xl font-bold dark:text-white">{{ stats.by_status.done }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-purple-500">
                    <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Среднее время</div>
                    <div class="text-2xl font-bold dark:text-white">{{ formatDuration(stats.avg_done_time) }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Статусы заявок</h3>
                    <div class="space-y-4">
                        <div v-for="(val, key) in stats?.by_status" :key="key" class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('status_' + key) }}</span>
                            <div class="flex-1 mx-4 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" :style="{ width: (val/stats.total*100) + '%' }"></div>
                            </div>
                            <span class="font-bold dark:text-white">{{ val }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Нагрузка по исполнителям</h3>
                    <div v-if="stats?.by_executor" class="space-y-4">
                        <div v-for="(val, id) in stats.by_executor" :key="id" class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ID: {{ id }}</span>
                            <span class="font-bold dark:text-white">{{ val }} задач</span>
                        </div>
                    </div>
                    <div v-else class="text-gray-400 italic">Нет данных по исполнителям</div>
                </div>
            </div>
        </div>
    `
});

app.component('tasks-view', {
    props: ['tasks', 'users', 'settings', 'user', 't', 'api'],
    setup(props, { emit }) {
        const showForm = ref(false);
        const searchQuery = ref('');
        const filterStatus = ref('');
        const showArchive = ref(false);
        const selectedTask = ref(null);
        const selectedTasks = ref([]);

        const filteredTasks = computed(() => {
            return props.tasks.filter(t => {
                const isArchived = ['done', 'rejected'].includes(t.status);
                if (showArchive.value && !isArchived) return false;
                if (!showArchive.value && isArchived) return false;

                const matchSearch = t.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                                    t.description.toLowerCase().includes(searchQuery.value.toLowerCase());
                const matchStatus = filterStatus.value ? t.status === filterStatus.value : true;
                return matchSearch && matchStatus;
            });
        });

        const statusColors = {
            new: 'bg-blue-100 text-blue-800',
            assigned: 'bg-yellow-100 text-yellow-800',
            in_work: 'bg-indigo-100 text-indigo-800',
            done: 'bg-green-100 text-green-800',
            rejected: 'bg-red-100 text-red-800'
        };

        const updateStatus = async (task, status) => {
            await props.api(`tasks.php?id=${task.id}`, 'POST', { status });
            emit('refresh');
        };

        const onDragEnd = async (evt, status) => {
             const task = evt.item._value;
             if (task.status !== status) {
                await updateStatus(task, status);
             }
        };

        const print = () => window.print();

        const bulkAssign = async (executorId) => {
            if (!executorId) return;
            const promises = selectedTasks.value.map(id => props.api(`tasks.php?id=${id}`, 'POST', { executor_id: executorId, status: 'assigned' }));
            await Promise.all(promises);
            selectedTasks.value = [];
            emit('refresh');
        };

        return { showForm, searchQuery, filterStatus, showArchive, filteredTasks, statusColors, updateStatus, selectedTask, onDragEnd, print, selectedTasks, bulkAssign };
    },
    template: `
        <div>
            <div v-if="selectedTasks.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white dark:bg-gray-800 shadow-2xl rounded-full px-6 py-3 flex items-center space-x-6 z-50 border border-primary no-print">
                <span class="text-sm font-bold dark:text-white">Выбрано: {{ selectedTasks.length }}</span>
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500">Назначить:</span>
                    <select @change="bulkAssign($event.target.value)" class="text-sm border border-gray-300 dark:border-gray-600 rounded-md p-1 dark:bg-gray-700 dark:text-white">
                        <option value="">Выбрать...</option>
                        <option v-for="u in users.filter(u => u.role === 'Executor')" :key="u.id" :value="u.id">{{ u.full_name }}</option>
                    </select>
                </div>
                <button @click="selectedTasks = []" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>

            <div class="flex justify-between items-center mb-6 no-print">
                <h2 class="text-2xl font-bold dark:text-white">{{ t('tasks') }}</h2>
                <div class="flex space-x-2">
                    <button @click="showForm = true" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        <i class="fas fa-plus mr-2"></i>{{ t('new_task') }}
                    </button>
                    <button @click="print" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">
                         <i class="fas fa-print mr-2"></i>PDF
                    </button>
                    <a href="api/analytics.php?action=export" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">
                        <i class="fas fa-download mr-2"></i>{{ t('export') }}
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-4 no-print">
                <input v-model="searchQuery" type="text" placeholder="Поиск..." class="border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white flex-1 min-w-[200px]">
                <select v-model="filterStatus" class="border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                    <option value="">Все статусы</option>
                    <option v-for="s in (showArchive ? ['done', 'rejected'] : ['new', 'assigned', 'in_work'])" :value="s">{{ t('status_' + s) }}</option>
                </select>
                <button @click="showArchive = !showArchive" :class="['px-4 py-2 rounded-md transition-colors', showArchive ? 'bg-purple-500 text-white' : 'bg-gray-100 dark:bg-gray-700 dark:text-gray-200']">
                    <i class="fas" :class="showArchive ? 'fa-box-open' : 'fa-archive'"></i> {{ showArchive ? 'Текущие' : 'Архив' }}
                </button>
            </div>

            <!-- Kanban Board -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8 overflow-x-auto">
                <div v-for="status in (showArchive ? ['done', 'rejected'] : ['new', 'assigned', 'in_work'])" :key="status" class="bg-gray-100 dark:bg-gray-900 p-3 rounded-lg min-w-[250px] kanban-column">
                    <h3 class="font-bold mb-4 flex justify-between dark:text-gray-200">
                        {{ t('status_' + status) }}
                        <span class="bg-gray-200 dark:bg-gray-700 px-2 rounded-full text-xs flex items-center">{{ tasks.filter(t => t.status === status).length }}</span>
                    </h3>
                    <draggable :list="tasks.filter(t => t.status === status)"
                               group="tasks"
                               item-key="id"
                               class="space-y-3 min-h-[300px]"
                               @add="onDragEnd($event, status)">
                        <template #item="{element}">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-md shadow-sm border-l-4 cursor-pointer hover:shadow-md transition-shadow relative"
                                 :class="statusColors[element.status]"
                                 @click="selectedTask = element">
                                <div class="absolute top-2 right-2 no-print" @click.stop>
                                    <input type="checkbox" :value="element.id" v-model="selectedTasks" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                                </div>
                                <div class="font-semibold text-gray-800 dark:text-white truncate pr-6">{{ element.title }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ element.category }} | {{ element.priority }}</div>
                                <div class="mt-3 flex justify-between items-center text-[10px] text-gray-400">
                                    <span>{{ element.created_at.split(' ')[0] }}</span>
                                    <div v-if="element.executor_id" class="w-5 h-5 bg-primary rounded-full flex items-center justify-center text-white text-[8px]">{{ element.executor_id[0] }}</div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <div class="print-only">
                <h1 class="text-3xl font-bold mb-8">Отчёт по заявкам</h1>
                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Заголовок</th>
                            <th class="border p-2">Статус</th>
                            <th class="border p-2">Приоритет</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in tasks" :key="t.id">
                            <td class="border p-2 text-xs">{{ t.id.substr(0,8) }}</td>
                            <td class="border p-2">{{ t.title }}</td>
                            <td class="border p-2">{{ t.status }}</td>
                            <td class="border p-2">{{ t.priority }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <task-modal v-if="selectedTask" :task="selectedTask" :users="users" :user="user" :t="t" :api="api" @close="selectedTask = null" @refresh="$emit('refresh')"></task-modal>
            <task-form v-if="showForm" :settings="settings" :t="t" :api="api" @close="showForm = false" @refresh="$emit('refresh')"></task-form>
        </div>
    `
});

app.component('task-form', {
    props: ['settings', 't', 'api'],
    setup(props, { emit }) {
        const form = reactive({
            title: '',
            description: '',
            category: props.settings.categories[0],
            priority: Object.keys(props.settings.priorities)[1],
            custom_fields: {},
            attachments: []
        });

        const uploadFile = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            const res = await fetch('api/uploads.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` },
                body: formData
            });
            const result = await res.json();
            if (result.success) {
                form.attachments.push({ url: result.url, name: result.name });
            }
        };

        const submit = async () => {
            const res = await props.api('tasks.php', 'POST', form);
            if (res?.success) {
                emit('refresh');
                emit('close');
            }
        };

        return { form, submit };
    },
    template: `
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-lg">
                <h3 class="text-xl font-bold mb-4 dark:text-white">{{ t('new_task') }}</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300">Заголовок</label>
                        <input v-model="form.title" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300">{{ t('categories') }}</label>
                            <select v-model="form.category" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                                <option v-for="c in settings.categories" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300">Приоритет</label>
                            <select v-model="form.priority" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                                <option v-for="(h, p) in settings.priorities" :value="p">{{ p }}</option>
                            </select>
                        </div>
                    </div>
                    <div v-for="field in settings.form_fields" :key="field.id">
                        <label class="block text-sm font-medium dark:text-gray-300">{{ field.label }}</label>
                        <input v-model="form.custom_fields[field.id]" :type="field.type" :required="field.required" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300">Описание</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white" required></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300">Прикрепить файлы</label>
                        <input type="file" @change="uploadFile" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-600">
                        <div class="flex flex-wrap gap-2 mt-2">
                            <div v-for="(a, i) in form.attachments" :key="i" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded flex items-center dark:text-gray-200">
                                {{ a.name }}
                                <button @click="form.attachments.splice(i,1)" type="button" class="ml-2 text-red-500"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" @click="$emit('close')" class="px-4 py-2 text-gray-500 hover:text-gray-700">{{ t('cancel') }}</button>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-blue-600">{{ t('save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    `
});

app.component('task-modal', {
    props: ['task', 'users', 'user', 't', 'api'],
    setup(props, { emit }) {
        const comment = ref('');
        const isAdminOrHead = computed(() => ['Administrator', 'Department Head'].includes(props.user.role));

        const saveComment = async () => {
            if (!comment.value) return;
            await props.api(`tasks.php?id=${props.task.id}`, 'POST', { content: comment.value });
            comment.value = '';
            emit('refresh');
        };

        const changeStatus = async (s) => {
            await props.api(`tasks.php?id=${props.task.id}`, 'POST', { status: s });
            emit('refresh');
        };

        const assign = async (uid) => {
            await props.api(`tasks.php?id=${props.task.id}`, 'POST', { executor_id: uid, status: 'assigned' });
            emit('refresh');
        };

        return { comment, saveComment, changeStatus, assign, isAdminOrHead };
    },
    template: `
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-bold dark:text-white">{{ task.title }}</h3>
                        <p class="text-sm text-gray-500">{{ task.category }} | {{ task.priority }} | {{ task.status }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <h4 class="font-bold mb-2 dark:text-gray-200">Описание</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ task.description }}</p>
                        </div>
                        <div v-if="task.attachments && task.attachments.length" class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                             <h4 class="font-bold mb-2 dark:text-gray-200">Вложения</h4>
                             <div class="flex flex-wrap gap-2">
                                <a v-for="a in task.attachments" :key="a.url" :href="a.url" target="_blank" class="text-sm text-primary hover:underline bg-white dark:bg-gray-800 px-3 py-1 rounded shadow-sm">
                                    <i class="fas fa-file-download mr-1"></i>{{ a.name }}
                                </a>
                             </div>
                        </div>
                        <div v-if="task.custom_fields && Object.keys(task.custom_fields).length" class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                             <h4 class="font-bold mb-2 dark:text-gray-200">Дополнительные поля</h4>
                             <div v-for="(v, k) in task.custom_fields" :key="k" class="text-sm">
                                <span class="font-medium dark:text-gray-400">{{ k }}:</span> <span class="dark:text-white">{{ v }}</span>
                             </div>
                        </div>

                        <div>
                            <h4 class="font-bold mb-4 dark:text-gray-200">Комментарии</h4>
                            <div class="space-y-3 mb-4">
                                <div v-for="c in task.comments" :key="c.id" class="bg-white dark:bg-gray-800 p-3 rounded-md shadow-sm border border-gray-100 dark:border-gray-700">
                                    <div class="flex justify-between text-[10px] text-gray-400 mb-1">
                                        <span>User ID: {{ c.user_id }}</span>
                                        <span>{{ c.created_at }}</span>
                                    </div>
                                    <p class="text-sm dark:text-gray-200">{{ c.content }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <input v-model="comment" type="text" placeholder="Ваш комментарий..." class="flex-1 border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                                <button @click="saveComment" class="bg-primary text-white px-4 py-2 rounded-md"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold mb-2 dark:text-gray-200">Управление</h4>
                            <div class="space-y-2">
                                <button v-if="task.status !== 'done'" @click="changeStatus('done')" class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600">Завершить</button>
                                <button v-if="task.status === 'new'" @click="changeStatus('in_work')" class="w-full bg-indigo-500 text-white py-2 rounded-md hover:bg-indigo-600">В работу</button>
                                <button v-if="task.status !== 'rejected'" @click="changeStatus('rejected')" class="w-full bg-red-100 text-red-600 py-2 rounded-md hover:bg-red-200">Отклонить</button>
                            </div>
                        </div>

                        <div v-if="isAdminOrHead">
                            <h4 class="font-bold mb-2 dark:text-gray-200">Назначить исполнителя</h4>
                            <select @change="assign($event.target.value)" class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                                <option value="">Выбрать...</option>
                                <option v-for="u in users.filter(u => u.role === 'Executor')" :key="u.id" :value="u.id" :selected="task.executor_id === u.id">{{ u.full_name }}</option>
                            </select>
                        </div>

                        <div class="text-[10px] text-gray-400 space-y-1">
                            <div>Создано: {{ task.created_at }}</div>
                            <div class="text-red-400 font-bold">Дедлайн: {{ task.deadline }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
});

app.component('users-view', {
    props: ['users', 't', 'api'],
    setup(props, { emit }) {
        const showForm = ref(false);
        const editingUser = ref(null);
        const roles = ['Administrator', 'Responsible Employee', 'Department Head', 'Executor'];

        const form = reactive({
            username: '',
            password: '',
            full_name: '',
            role: 'Executor',
            department: '',
            email: ''
        });

        const edit = (u) => {
            editingUser.value = u;
            Object.assign(form, u);
            form.password = '';
            showForm.value = true;
        };

        const save = async () => {
            const url = editingUser.value ? `users.php?id=${editingUser.value.id}` : 'users.php';
            await props.api(url, 'POST', form);
            showForm.value = false;
            editingUser.value = null;
            emit('refresh');
        };

        const remove = async (id) => {
            if (confirm('Delete user?')) {
                await props.api(`users.php?id=${id}`, 'DELETE');
                emit('refresh');
            }
        };

        return { showForm, editingUser, roles, form, edit, save, remove };
    },
    template: `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-bold dark:text-white">{{ t('users') }}</h2>
                <button @click="editingUser = null; showForm = true" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-blue-600">Добавить</button>
            </div>
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-900 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Department</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 dark:hover:bg-gray-900 dark:text-gray-200">
                        <td class="px-6 py-4">
                            <div class="font-bold">{{ u.full_name }}</div>
                            <div class="text-xs text-gray-400">@{{ u.username }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ u.role }}</td>
                        <td class="px-6 py-4 text-sm">{{ u.department }}</td>
                        <td class="px-6 py-4">
                            <button @click="edit(u)" class="text-blue-500 mr-3"><i class="fas fa-edit"></i></button>
                            <button @click="remove(u.id)" class="text-red-500"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="showForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-md">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">{{ editingUser ? 'Edit' : 'Add' }} User</h3>
                    <form @submit.prevent="save" class="space-y-4">
                        <input v-model="form.username" placeholder="Username" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" required>
                        <input v-model="form.password" type="password" placeholder="Password" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" :required="!editingUser">
                        <input v-model="form.full_name" placeholder="Full Name" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" required>
                        <select v-model="form.role" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                            <option v-for="r in roles" :value="r">{{ r }}</option>
                        </select>
                        <input v-model="form.department" placeholder="Department" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <input v-model="form.email" type="email" placeholder="Email" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="flex justify-end space-x-2">
                            <button type="button" @click="showForm = false" class="text-gray-500">Cancel</button>
                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `
});

app.component('settings-view', {
    props: ['settings', 't', 'api'],
    setup(props, { emit }) {
        const localSettings = reactive(JSON.parse(JSON.stringify(props.settings)));

        const save = async () => {
            await props.api('settings.php', 'POST', localSettings);
            alert('Settings saved');
            emit('refresh');
        };

        const addCategory = () => localSettings.categories.push('Новая категория');
        const addField = () => localSettings.form_fields.push({ id: 'f' + Date.now(), label: 'Новое поле', type: 'text', required: false });

        return { localSettings, save, addCategory, addField };
    },
    template: `
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
                <h2 class="text-xl font-bold mb-6 dark:text-white">{{ t('settings') }}</h2>
                <div class="space-y-8">
                    <div>
                        <h3 class="font-bold mb-4 dark:text-gray-300">Режим хранения</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <select v-model="localSettings.storage_mode" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                                    <option value="json">JSON (Локальные файлы)</option>
                                    <option value="mysql">MySQL (База данных)</option>
                                </select>
                            </div>
                            <div v-if="localSettings.storage_mode === 'mysql'" class="space-y-2 border p-4 rounded bg-gray-50 dark:bg-gray-900">
                                <input v-model="localSettings.mysql.host" placeholder="Host" class="w-full border p-1 rounded text-sm dark:bg-gray-700 dark:text-white">
                                <input v-model="localSettings.mysql.dbname" placeholder="DB Name" class="w-full border p-1 rounded text-sm dark:bg-gray-700 dark:text-white">
                                <input v-model="localSettings.mysql.user" placeholder="User" class="w-full border p-1 rounded text-sm dark:bg-gray-700 dark:text-white">
                                <input v-model="localSettings.mysql.pass" type="password" placeholder="Pass" class="w-full border p-1 rounded text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold mb-4 dark:text-gray-300">Категории заявок</h3>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <div v-for="(c, i) in localSettings.categories" :key="i" class="flex items-center bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded">
                                <input v-model="localSettings.categories[i]" class="bg-transparent border-none focus:ring-0 text-sm dark:text-white">
                                <button @click="localSettings.categories.splice(i,1)" class="text-red-500 ml-2"><i class="fas fa-times"></i></button>
                            </div>
                            <button @click="addCategory" class="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm font-bold">+</button>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold mb-4 dark:text-gray-300">Конструктор полей формы</h3>
                        <div class="space-y-2">
                            <draggable v-model="localSettings.form_fields" item-key="id" handle=".fa-grip-lines">
                                <template #item="{element, index}">
                                    <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-900 p-2 rounded mb-2">
                                        <i class="fas fa-grip-lines text-gray-400 cursor-move"></i>
                                        <input v-model="element.label" class="flex-1 border-none bg-transparent dark:text-white" placeholder="Название поля">
                                        <select v-model="element.type" class="text-xs border rounded p-1 dark:bg-gray-700 dark:text-white">
                                            <option value="text">Текст</option>
                                            <option value="tel">Телефон</option>
                                            <option value="number">Число</option>
                                            <option value="date">Дата</option>
                                        </select>
                                        <label class="flex items-center text-xs dark:text-gray-400">
                                            <input type="checkbox" v-model="element.required" class="mr-1"> Обяз.
                                        </label>
                                        <button @click="localSettings.form_fields.splice(index,1)" class="text-red-500"><i class="fas fa-trash"></i></button>
                                    </div>
                                </template>
                            </draggable>
                            <button @click="addField" class="text-primary text-sm font-bold">+ Добавить поле</button>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold mb-4 dark:text-gray-300">Уведомления</h3>
                        <div class="space-y-4 border p-4 rounded bg-gray-50 dark:bg-gray-900">
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center dark:text-gray-200">
                                    <input type="checkbox" v-model="localSettings.notifications.email" class="mr-2"> Email
                                </label>
                                <label class="flex items-center dark:text-gray-200">
                                    <input type="checkbox" v-model="localSettings.notifications.telegram" class="mr-2"> Telegram
                                </label>
                            </div>
                            <div v-if="!localSettings.notifications.triggers" class="hidden">{{ localSettings.notifications.triggers = { created: true, assignment: true, status_change: true } }}</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t dark:border-gray-700">
                                <label class="flex items-center text-sm dark:text-gray-300">
                                    <input type="checkbox" v-model="localSettings.notifications.triggers.created" class="mr-2"> Новая заявка
                                </label>
                                <label class="flex items-center text-sm dark:text-gray-300">
                                    <input type="checkbox" v-model="localSettings.notifications.triggers.assignment" class="mr-2"> Назначение
                                </label>
                                <label class="flex items-center text-sm dark:text-gray-300">
                                    <input type="checkbox" v-model="localSettings.notifications.triggers.status_change" class="mr-2"> Смена статуса
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold mb-4 dark:text-gray-300">SLA (Сроки в часах)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="(h, p) in localSettings.priorities" :key="p">
                                <label class="block text-xs text-gray-500">{{ p }}</label>
                                <input v-model.number="localSettings.priorities[p]" type="number" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t dark:border-gray-700">
                    <button @click="save" class="bg-primary text-white px-6 py-2 rounded-md font-bold">Сохранить все настройки</button>
                </div>
            </div>
        </div>
    `
});

app.component('profile-view', {
    props: ['user', 't', 'api'],
    setup(props, { emit }) {
        const form = reactive({
            full_name: props.user.full_name,
            email: props.user.email,
            telegram_id: props.user.telegram_id || '',
            password: ''
        });

        const save = async () => {
            const res = await props.api('auth.php?action=profile', 'POST', form);
            if (res?.success) {
                alert('Profile updated');
                emit('refresh');
            }
        };

        return { form, save };
    },
    template: `
        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm">
            <h2 class="text-2xl font-bold mb-6 dark:text-white">{{ t('profile') }}</h2>
            <form @submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">ФИО</label>
                    <input v-model="form.full_name" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Email</label>
                    <input v-model="form.email" type="email" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Telegram ID</label>
                    <input v-model="form.telegram_id" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Новый пароль (оставьте пустым, чтобы не менять)</label>
                    <input v-model="form.password" type="password" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 dark:bg-gray-700 dark:text-white">
                </div>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-blue-600">{{ t('save') }}</button>
            </form>
        </div>
    `
});

app.mount('#app');
