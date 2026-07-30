
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
        export: "Экспорт CSV",
        help: "Справка и обучение",
        help_title: "Справка по системе BELHOS",
        training_title: "Обучение работе в программе",
        tools: "Инструменты"
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
        export: "Export CSV",
        help: "Help & Training",
        help_title: "BELHOS System Help",
        training_title: "Interactive Software Training",
        tools: "Logical Tools"
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
                        <h1 class="text-xl font-bold text-primary">BELHOS</h1>
                        <div class="hidden md:flex space-x-1">
                            <nav-link :active="view === 'dashboard'" @click="view = 'dashboard'">{{ t('dashboard') }}</nav-link>
                            <nav-link :active="view === 'tasks'" @click="view = 'tasks'">{{ t('tasks') }}</nav-link>
                            <nav-link v-if="user.role === 'Administrator'" :active="view === 'users'" @click="view = 'users'">{{ t('users') }}</nav-link>
                            <nav-link v-if="user.role === 'Administrator'" :active="view === 'settings'" @click="view = 'settings'">{{ t('settings') }}</nav-link>
                            <nav-link :active="view === 'help'" @click="view = 'help'">{{ t('help') }}</nav-link>
                            <nav-link :active="view === 'tools'" @click="view = 'tools'">{{ t('tools') }}</nav-link>
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
                    <help-view v-if="view === 'help'" :t="t"></help-view>
                    <tools-view v-if="view === 'tools'" :t="t"></tools-view>
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
                <h2 class="text-3xl font-bold text-primary">BELHOS</h2>
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

app.component('tools-view', {
    props: ['t'],
    setup() {
        const search = ref('');
        const activeTool = ref(1);

        // Interactive States for 30 Helper Tools
        // 1. SLA Calc
        const slaHours = ref(24);
        const slaResult = computed(() => {
            const days = (slaHours.value / 24).toFixed(1);
            return `${days} дн.`;
        });
        // 2. Temp Convert
        const tempC = ref(25);
        const tempF = computed(() => (tempC.value * 9/5 + 32).toFixed(1));
        const tempK = computed(() => (parseFloat(tempC.value) + 273.15).toFixed(1));
        // 3. BMI Calc
        const weight = ref(70);
        const height = ref(175);
        const bmi = computed(() => {
            const hM = height.value / 100;
            return (weight.value / (hM * hM)).toFixed(1);
        });
        // 4. Currency Convert
        const byn = ref(10);
        const usdRate = 3.25;
        const rubRate = 0.035;
        const usdVal = computed(() => (byn.value / usdRate).toFixed(2));
        const rubVal = computed(() => (byn.value / rubRate).toFixed(2));
        // 5. Password Generator
        const passLen = ref(12);
        const generatedPass = ref('');
        const genPass = () => {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
            let res = '';
            for (let i = 0; i < passLen.value; i++) {
                res += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            generatedPass.value = res;
        };
        // 6. Pomodoro Timer
        const pomoTime = ref(1500); // 25 mins
        const pomoInterval = ref(null);
        const startPomo = () => {
            if (pomoInterval.value) return;
            pomoInterval.value = setInterval(() => {
                if (pomoTime.value > 0) pomoTime.value--;
                else stopPomo();
            }, 1000);
        };
        const stopPomo = () => {
            clearInterval(pomoInterval.value);
            pomoInterval.value = null;
        };
        const resetPomo = () => {
            stopPomo();
            pomoTime.value = 1500;
        };
        // 7. Stopwatch
        const swTime = ref(0);
        const swInterval = ref(null);
        const startSw = () => {
            if (swInterval.value) return;
            swInterval.value = setInterval(() => { swTime.value += 10; }, 10);
        };
        const stopSw = () => {
            clearInterval(swInterval.value);
            swInterval.value = null;
        };
        const resetSw = () => {
            stopSw();
            swTime.value = 0;
        };
        // 8. Water Tracker
        const waterLogged = ref(parseInt(localStorage.getItem('water_logged') || '0'));
        const addWater = () => {
            waterLogged.value += 250;
            localStorage.setItem('water_logged', waterLogged.value);
        };
        const resetWater = () => {
            waterLogged.value = 0;
            localStorage.setItem('water_logged', '0');
        };
        // 9. Text Analyzer
        const textToAnalyze = ref('');
        const textStats = computed(() => {
            const charCount = textToAnalyze.value.length;
            const wordCount = textToAnalyze.value.trim() ? textToAnalyze.value.trim().split(/\s+/).length : 0;
            return { charCount, wordCount };
        });
        // 10. Habit list
        const habits = ref(JSON.parse(localStorage.getItem('habits') || '[]'));
        const newHabit = ref('');
        const addHabit = () => {
            if (!newHabit.value.trim()) return;
            habits.value.push({ text: newHabit.value, done: false });
            newHabit.value = '';
            localStorage.setItem('habits', JSON.stringify(habits.value));
        };
        const toggleHabit = (idx) => {
            habits.value[idx].done = !habits.value[idx].done;
            localStorage.setItem('habits', JSON.stringify(habits.value));
        };
        const removeHabit = (idx) => {
            habits.value.splice(idx, 1);
            localStorage.setItem('habits', JSON.stringify(habits.value));
        };
        // 11. Expense Tracker
        const expenses = ref(JSON.parse(localStorage.getItem('expenses') || '[]'));
        const expenseName = ref('');
        const expenseAmount = ref(0);
        const addExpense = () => {
            if (!expenseName.value || expenseAmount.value <= 0) return;
            expenses.value.push({ name: expenseName.value, amount: expenseAmount.value });
            expenseName.value = '';
            expenseAmount.value = 0;
            localStorage.setItem('expenses', JSON.stringify(expenses.value));
        };
        const totalExpenses = computed(() => expenses.value.reduce((sum, e) => sum + parseFloat(e.amount), 0));
        // 12. Debts Tracker
        const debts = ref(JSON.parse(localStorage.getItem('debts') || '[]'));
        const debtName = ref('');
        const debtAmount = ref(0);
        const debtType = ref('взял'); // 'дал' or 'взял'
        const addDebt = () => {
            if (!debtName.value || debtAmount.value <= 0) return;
            debts.value.push({ name: debtName.value, amount: debtAmount.value, type: debtType.value });
            debtName.value = '';
            debtAmount.value = 0;
            localStorage.setItem('debts', JSON.stringify(debts.value));
        };
        const clearDebts = () => {
            debts.value = [];
            localStorage.setItem('debts', '[]');
        };
        // 13. Random Number
        const randMin = ref(1);
        const randMax = ref(100);
        const randRes = ref(null);
        const genRand = () => {
            randRes.value = Math.floor(Math.random() * (randMax.value - randMin.value + 1)) + parseInt(randMin.value);
        };
        // 14. Math Trainer
        const mathQ = ref('5 + 3');
        const mathAns = ref(8);
        const mathUserAns = ref('');
        const mathScore = ref(0);
        const checkMath = () => {
            if (parseInt(mathUserAns.value) === mathAns.value) {
                mathScore.value++;
                const a = Math.floor(Math.random() * 10) + 1;
                const b = Math.floor(Math.random() * 10) + 1;
                mathQ.value = `${a} * ${b}`;
                mathAns.value = a * b;
            } else {
                alert('Неверно! Попробуйте еще раз.');
            }
            mathUserAns.value = '';
        };
        // 15. Breathing Guide
        const breatheState = ref('Вдох'); // 'Вдох', 'Задержка', 'Выдох'
        const startBreathe = () => {
            let cycle = 0;
            setInterval(() => {
                cycle = (cycle + 1) % 3;
                breatheState.value = cycle === 0 ? 'Вдох' : (cycle === 1 ? 'Задержка' : 'Выдох');
            }, 4000);
        };
        // 16. Length Converter
        const lenMeters = ref(1);
        const lenKm = computed(() => (lenMeters.value / 1000).toFixed(4));
        const lenMiles = computed(() => (lenMeters.value * 0.000621371).toFixed(4));
        // 17. Weight Converter
        const weightKg = ref(1);
        const weightLbs = computed(() => (weightKg.value * 2.20462).toFixed(2));
        const weightOz = computed(() => (weightKg.value * 35.274).toFixed(2));
        // 18. Simulated QR Code
        const qrInput = ref('http://wes.by');
        const qrSim = computed(() => `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrInput.value)}`);
        // 19. Reaction Tester
        const reactColor = ref('bg-red-500');
        const reactText = ref('Ждите зеленого...');
        const reactTimer = ref(null);
        const reactStart = ref(0);
        const reactionTime = ref(null);
        const runReactTest = () => {
            reactColor.value = 'bg-red-500';
            reactText.value = 'Ждите зеленого...';
            reactionTime.value = null;
            const delay = Math.floor(Math.random() * 3000) + 2000;
            reactTimer.value = setTimeout(() => {
                reactColor.value = 'bg-green-500';
                reactText.value = 'КЛИКАЙ!';
                reactStart.value = Date.now();
            }, delay);
        };
        const reactClick = () => {
            if (reactColor.value === 'bg-green-500') {
                reactionTime.value = Date.now() - reactStart.value;
                reactText.value = `Время реакции: ${reactionTime.value} мс!`;
                reactColor.value = 'bg-blue-500';
                clearTimeout(reactTimer.value);
            } else {
                reactText.value = 'Слишком рано!';
                clearTimeout(reactTimer.value);
            }
        };
        // 20. VAT Calc
        const vatPrice = ref(100);
        const vatRate = ref(20);
        const vatVal = computed(() => (vatPrice.value * (vatRate.value / 100)).toFixed(2));
        const vatTotal = computed(() => (parseFloat(vatPrice.value) + parseFloat(vatVal.value)).toFixed(2));
        // 21. Mood Log
        const currentMood = ref('Neutral');
        const moodLogs = ref(JSON.parse(localStorage.getItem('moods') || '[]'));
        const logMood = () => {
            moodLogs.value.push({ date: new Date().toLocaleDateString(), mood: currentMood.value });
            localStorage.setItem('moods', JSON.stringify(moodLogs.value));
        };
        // 22. Color Converter
        const rColor = ref(255);
        const gColor = ref(0);
        const bColor = ref(0);
        const rgbToHex = computed(() => {
            const toHex = (c) => {
                const hex = Math.min(255, Math.max(0, parseInt(c))).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            };
            return '#' + toHex(rColor.value) + toHex(gColor.value) + toHex(bColor.value);
        });
        // 23. Cigarette Tracker
        const cigCount = ref(parseInt(localStorage.getItem('cig_count') || '0'));
        const cigPrice = ref(5.0); // Packet price BYN
        const cigCountInPack = ref(20);
        const addCig = () => { cigCount.value++; localStorage.setItem('cig_count', cigCount.value); };
        const cigMoneyWaste = computed(() => ((cigCount.value / cigCountInPack.value) * cigPrice.value).toFixed(2));
        // 24. Smart Notepad
        const noteText = ref(localStorage.getItem('smart_note') || '');
        const saveNote = () => { localStorage.setItem('smart_note', noteText.value); alert('Заметка сохранена!'); };
        // 25. Hash Generator
        const hashText = ref('BELHOS');
        const hashRes = computed(() => {
            let hash = 0;
            for (let i = 0; i < hashText.value.length; i++) {
                const char = hashText.value.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash).toString(16);
        });
        // 26. Tip Calc
        const billAmount = ref(50);
        const tipPercent = ref(10);
        const tipVal = computed(() => (billAmount.value * (tipPercent.value / 100)).toFixed(2));
        // 27. Pulse Simulator
        const pulseRate = ref(75);
        const simulatedBeats = ref([]);
        const simBeat = () => {
            simulatedBeats.value.push(Date.now());
            if (simulatedBeats.value.length > 5) simulatedBeats.value.shift();
        };
        // 28. Age Calculator
        const birthDate = ref('2000-01-01');
        const calculatedAge = computed(() => {
            if (!birthDate.value) return 0;
            const diff = Date.now() - new Date(birthDate.value).getTime();
            return Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25));
        });
        // 29. Timezone Converter
        const selectedTz = ref('GMT');
        const currentTzTime = computed(() => {
            const date = new Date();
            if (selectedTz.value === 'EST') return new Date(date.getTime() - 5*3600*1000).toLocaleTimeString();
            if (selectedTz.value === 'MSK') return new Date(date.getTime() + 3*3600*1000).toLocaleTimeString();
            return date.toLocaleTimeString();
        });
        // 30. White Noise Simulator
        const noisePlaying = ref(false);
        const noiseContext = ref(null);
        const toggleNoise = () => {
            noisePlaying.value = !noisePlaying.value;
            if (noisePlaying.value) {
                // Synthesize white noise via Web Audio API
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                noiseContext.value = new AudioContext();
                const bufferSize = 2 * noiseContext.value.sampleRate,
                noiseBuffer = noiseContext.value.createBuffer(1, bufferSize, noiseContext.value.sampleRate),
                output = noiseBuffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    output[i] = Math.random() * 2 - 1;
                }
                const whiteNoise = noiseContext.value.createBufferSource();
                whiteNoise.buffer = noiseBuffer;
                whiteNoise.loop = true;
                whiteNoise.connect(noiseContext.value.destination);
                whiteNoise.start();
                noiseContext.value.node = whiteNoise;
            } else {
                if (noiseContext.value) {
                    noiseContext.value.node.stop();
                    noiseContext.value.close();
                }
            }
        };

        const allToolsList = [
            { id: 1, name: "Калькулятор SLA", desc: "Расчет срока выполнения" },
            { id: 2, name: "Конвертер температур", desc: "Шкалы Цельсия, Фаренгейта, Кельвина" },
            { id: 3, name: "Калькулятор ИМТ", desc: "Индекс массы тела" },
            { id: 4, name: "Конвертер валют", desc: "Курсы BYN, USD, RUB" },
            { id: 5, name: "Генератор паролей", desc: "Безопасные случайные пароли" },
            { id: 6, name: "Помодоро таймер", desc: "Таймер продуктивности 25 минут" },
            { id: 7, name: "Секундомер", desc: "Высокоточный секундомер" },
            { id: 8, name: "Водный трекер", desc: "Учет дневного потребления воды" },
            { id: 9, name: "Анализатор текста", desc: "Подсчет слов и знаков" },
            { id: 10, name: "Каталог привычек", desc: "Отслеживание полезных привычек" },
            { id: 11, name: "Учет расходов", desc: "Планировщик бюджета" },
            { id: 12, name: "Трекер долгов", desc: "Логирование заемных средств" },
            { id: 13, name: "Случайные числа", desc: "Генерация случайных значений" },
            { id: 14, name: "Мат-тренажер", desc: "Логические примеры" },
            { id: 15, name: "Дыхательный гид", desc: "Медитативные циклы" },
            { id: 16, name: "Конвертер длины", desc: "Метры, мили, километры" },
            { id: 17, name: "Конвертер веса", desc: "Килограммы, фунты, унции" },
            { id: 18, name: "Генератор QR", desc: "Симуляция ссылок QR" },
            { id: 19, name: "Тест реакции", desc: "Измерение скорости реакции" },
            { id: 20, name: "Калькулятор НДС", desc: "Выделение и начисление НДС" },
            { id: 21, name: "Лог настроения", desc: "Ежедневный дневник" },
            { id: 22, name: "Цветовой RGB-Hex", desc: "Конвертер цвета" },
            { id: 23, name: "Борьба с курением", desc: "Счетчик и финансовый урон" },
            { id: 24, name: "Умный блокнот", desc: "Быстрое сохранение заметок" },
            { id: 25, name: "Хэш-генератор", desc: "Генерация HEX хэшей" },
            { id: 26, name: "Калькулятор чаевых", desc: "Расчет процента" },
            { id: 27, name: "Симулятор пульса", desc: "Запись биений сердца" },
            { id: 28, name: "Калькулятор возраста", desc: "Расчет в годах" },
            { id: 29, name: "Конвертер зон времени", desc: "Конвертер часовых поясов" },
            { id: 30, name: "Белый шум", desc: "Звуковой фон концентрации" }
        ];

        const filteredTools = computed(() => {
            return allToolsList.filter(t => t.name.toLowerCase().includes(search.value.toLowerCase()) || t.desc.toLowerCase().includes(search.value.toLowerCase()));
        });

        onMounted(() => {
            startBreathe();
        });

        return {
            search, activeTool, filteredTools, allToolsList,
            slaHours, slaResult, tempC, tempF, tempK, weight, height, bmi,
            byn, usdRate, rubRate, usdVal, rubVal, passLen, generatedPass, genPass,
            pomoTime, pomoInterval, startPomo, stopPomo, resetPomo, swTime, startSw, stopSw, resetSw,
            waterLogged, addWater, resetWater, textToAnalyze, textStats, habits, newHabit, addHabit, toggleHabit, removeHabit,
            expenses, expenseName, expenseAmount, addExpense, totalExpenses, debts, debtName, debtAmount, debtType, addDebt, clearDebts,
            randMin, randMax, randRes, genRand, mathQ, mathAns, mathUserAns, mathScore, checkMath, breatheState,
            lenMeters, lenKm, lenMiles, weightKg, weightLbs, weightOz, qrInput, qrSim,
            reactColor, reactText, reactionTime, runReactTest, reactClick, vatPrice, vatRate, vatVal, vatTotal,
            currentMood, logMood, moodLogs, rColor, gColor, bColor, rgbToHex, cigCount, cigPrice, cigCountInPack, addCig, cigMoneyWaste,
            noteText, saveNote, hashText, hashRes, billAmount, tipPercent, tipVal, pulseRate, simBeat, simulatedBeats,
            birthDate, calculatedAge, selectedTz, currentTzTime, noisePlaying, toggleNoise
        };
    },
    template: `
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Panel: Tools list -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm space-y-4">
                <h3 class="font-bold text-lg text-primary">Полнофункциональный набор (30 логических утилит)</h3>
                <input v-model="search" placeholder="Поиск утилит..." class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white text-sm mb-2">
                <div class="space-y-1 max-h-[60vh] overflow-y-auto">
                    <button v-for="t in filteredTools" :key="t.id" @click="activeTool = t.id" :class="['w-full text-left px-3 py-2 rounded text-xs transition-colors flex flex-col', activeTool === t.id ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-300']">
                        <span class="font-bold">{{ t.id }}. {{ t.name }}</span>
                        <span class="opacity-75">{{ t.desc }}</span>
                    </button>
                </div>
            </div>

            <!-- Main Panel: Active utility details -->
            <div class="lg:col-span-3 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm min-h-[450px] flex flex-col justify-between">
                <div>
                    <!-- Render Active Tool -->
                    <div v-if="activeTool === 1" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">1. Калькулятор SLA (срок выполнения)</h4>
                        <label class="block text-xs dark:text-gray-300">Введите время SLA (в часах):</label>
                        <input type="number" v-model.number="slaHours" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm">Результат в днях: <strong class="text-primary">{{ slaResult }}</strong></div>
                    </div>

                    <div v-if="activeTool === 2" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">2. Конвертер температур</h4>
                        <input type="number" v-model="tempC" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm space-y-1">
                            <div>Шкала Фаренгейта: <strong>{{ tempF }} °F</strong></div>
                            <div>Шкала Кельвина: <strong>{{ tempK }} K</strong></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 3" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">3. Интерактивный калькулятор ИМТ</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs">Вес (кг):</label>
                                <input type="number" v-model="weight" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                            </div>
                            <div>
                                <label class="block text-xs">Рост (см):</label>
                                <input type="number" v-model="height" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                            </div>
                        </div>
                        <div class="text-sm">Ваш индекс массы тела (ИМТ): <strong class="text-primary">{{ bmi }}</strong></div>
                    </div>

                    <div v-if="activeTool === 4" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">4. Конвертер валют</h4>
                        <label class="block text-xs">BYN (Белорусский рубль):</label>
                        <input type="number" v-model="byn" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm space-y-1">
                            <div>Эквивалент USD (курс {{ usdRate }}): <strong>{{ usdVal }} $</strong></div>
                            <div>Эквивалент RUB (курс {{ rubRate }}): <strong>{{ rubVal }} ₽</strong></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 5" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">5. Генератор паролей</h4>
                        <label class="block text-xs">Длина пароля:</label>
                        <input type="number" v-model="passLen" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <button @click="genPass" class="bg-primary text-white px-4 py-2 rounded-lg block">Сгенерировать</button>
                        <div v-if="generatedPass" class="p-2 bg-gray-50 dark:bg-gray-900 border rounded font-mono select-all">{{ generatedPass }}</div>
                    </div>

                    <div v-if="activeTool === 6" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">6. Таймер Помодоро</h4>
                        <div class="text-4xl font-mono text-center">{{ Math.floor(pomoTime/60) }}:{{ (pomoTime%60).toString().padStart(2, '0') }}</div>
                        <div class="flex justify-center space-x-2">
                            <button @click="startPomo" class="bg-green-500 text-white px-4 py-2 rounded">Старт</button>
                            <button @click="stopPomo" class="bg-yellow-500 text-white px-4 py-2 rounded">Пауза</button>
                            <button @click="resetPomo" class="bg-red-500 text-white px-4 py-2 rounded">Сброс</button>
                        </div>
                    </div>

                    <div v-if="activeTool === 7" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">7. Секундомер</h4>
                        <div class="text-4xl font-mono text-center">{{ (swTime/1000).toFixed(2) }} сек</div>
                        <div class="flex justify-center space-x-2">
                            <button @click="startSw" class="bg-green-500 text-white px-4 py-2 rounded">Старт</button>
                            <button @click="stopSw" class="bg-yellow-500 text-white px-4 py-2 rounded">Пауза</button>
                            <button @click="resetSw" class="bg-red-500 text-white px-4 py-2 rounded">Сброс</button>
                        </div>
                    </div>

                    <div v-if="activeTool === 8" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">8. Водный трекер</h4>
                        <div class="text-2xl font-bold text-center text-blue-500">{{ waterLogged }} мл / 2000 мл</div>
                        <div class="flex justify-center space-x-2">
                            <button @click="addWater" class="bg-blue-500 text-white px-4 py-2 rounded">+250 мл</button>
                            <button @click="resetWater" class="bg-red-500 text-white px-4 py-2 rounded">Сброс</button>
                        </div>
                    </div>

                    <div v-if="activeTool === 9" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">9. Анализатор текста</h4>
                        <textarea v-model="textToAnalyze" rows="4" placeholder="Введите ваш текст..." class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white"></textarea>
                        <div class="text-sm space-y-1">
                            <div>Символов: <strong>{{ textStats.charCount }}</strong></div>
                            <div>Слов: <strong>{{ textStats.wordCount }}</strong></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 10" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">10. Список полезных привычек</h4>
                        <div class="flex space-x-2">
                            <input v-model="newHabit" placeholder="Новая привычка..." class="flex-1 border p-2 rounded dark:bg-gray-700 dark:text-white">
                            <button @click="addHabit" class="bg-primary text-white px-4 py-2 rounded">Добавить</button>
                        </div>
                        <div class="space-y-1 max-h-[200px] overflow-y-auto">
                            <div v-for="(h, idx) in habits" :key="idx" class="flex justify-between items-center p-2 border rounded dark:border-gray-700">
                                <span :class="{ 'line-through text-gray-400': h.done }">{{ h.text }}</span>
                                <div class="space-x-1">
                                    <button @click="toggleHabit(idx)" class="text-green-500 text-xs"><i class="fas fa-check"></i></button>
                                    <button @click="removeHabit(idx)" class="text-red-500 text-xs"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTool === 11" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">11. Учет и планировщик расходов</h4>
                        <div class="flex space-x-2">
                            <input v-model="expenseName" placeholder="Название расхода..." class="border p-2 rounded dark:bg-gray-700 dark:text-white flex-1">
                            <input type="number" v-model="expenseAmount" placeholder="Сумма" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-24">
                            <button @click="addExpense" class="bg-primary text-white px-4 py-2 rounded">Добавить</button>
                        </div>
                        <div class="text-sm">Всего потрачено: <strong class="text-red-500">{{ totalExpenses }} BYN</strong></div>
                    </div>

                    <div v-if="activeTool === 12" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">12. Список долгов и займов</h4>
                        <div class="flex space-x-2">
                            <input v-model="debtName" placeholder="ФИО должника..." class="border p-2 rounded dark:bg-gray-700 dark:text-white flex-1">
                            <input type="number" v-model="debtAmount" placeholder="Сумма" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-24">
                            <select v-model="debtType" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                                <option value="взял">Взял</option>
                                <option value="дал">Дал</option>
                            </select>
                            <button @click="addDebt" class="bg-primary text-white px-4 py-2 rounded">Записать</button>
                        </div>
                        <div class="space-y-1">
                            <div v-for="d in debts" class="text-xs p-1.5 border rounded flex justify-between">
                                <span>{{ d.name }}</span>
                                <span class="font-bold" :class="d.type === 'дал' ? 'text-green-500' : 'text-red-500'">{{ d.type }} {{ d.amount }} BYN</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTool === 13" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">13. Генератор случайных чисел</h4>
                        <div class="flex space-x-2">
                            <input type="number" v-model="randMin" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-20">
                            <span>-</span>
                            <input type="number" v-model="randMax" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-20">
                            <button @click="genRand" class="bg-primary text-white px-4 py-2 rounded">Генерация</button>
                        </div>
                        <div v-if="randRes !== null" class="text-3xl font-bold text-center text-primary">{{ randRes }}</div>
                    </div>

                    <div v-if="activeTool === 14" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">14. Математический тренажер</h4>
                        <div class="text-lg font-bold text-center">Сколько будет: {{ mathQ }} ?</div>
                        <div class="flex space-x-2 justify-center">
                            <input type="number" v-model="mathUserAns" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-24">
                            <button @click="checkMath" class="bg-primary text-white px-4 py-2 rounded">Проверить</button>
                        </div>
                        <div class="text-sm text-center">Очки: <strong>{{ mathScore }}</strong></div>
                    </div>

                    <div v-if="activeTool === 15" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">15. Дыхательный таймер</h4>
                        <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-xl font-bold text-blue-700 mx-auto animate-pulse">
                            {{ breatheState }}
                        </div>
                        <p class="text-xs text-center text-gray-500">Помогает расслабиться и восстановить ритм дыхания. Циклы меняются каждые 4 секунды.</p>
                    </div>

                    <div v-if="activeTool === 16" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">16. Конвертер длины</h4>
                        <label class="block text-xs">Метры:</label>
                        <input type="number" v-model="lenMeters" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm space-y-1">
                            <div>Километры: <strong>{{ lenKm }} км</strong></div>
                            <div>Мили: <strong>{{ lenMiles }} миль</strong></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 17" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">17. Конвертер веса</h4>
                        <label class="block text-xs">Килограммы:</label>
                        <input type="number" v-model="weightKg" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm space-y-1">
                            <div>Фунты: <strong>{{ weightLbs }} lbs</strong></div>
                            <div>Унции: <strong>{{ weightOz }} oz</strong></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 18" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">18. Имитатор QR-кода</h4>
                        <input v-model="qrInput" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="flex justify-center">
                            <img :src="qrSim" class="border p-2 rounded bg-white shadow-sm" alt="QR-код">
                        </div>
                    </div>

                    <div v-if="activeTool === 19" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">19. Тестер скорости реакции</h4>
                        <div @click="reactClick" :class="[reactColor, 'h-32 rounded-xl flex items-center justify-center text-white font-bold text-lg cursor-pointer transition-all select-none']">
                            {{ reactText }}
                        </div>
                        <button @click="runReactTest" class="bg-primary text-white px-4 py-2 rounded block mx-auto">Начать тест</button>
                    </div>

                    <div v-if="activeTool === 20" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">20. Калькулятор НДС</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs">Сумма (без НДС):</label>
                                <input type="number" v-model="vatPrice" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                            </div>
                            <div>
                                <label class="block text-xs">Ставка (%):</label>
                                <input type="number" v-model="vatRate" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                            </div>
                        </div>
                        <div class="text-sm space-y-1">
                            <div>НДС составит: <strong>{{ vatVal }} BYN</strong></div>
                            <div>Итого с НДС: <strong>{{ vatTotal }} BYN</strong></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 21" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">21. Логгер настроения</h4>
                        <div class="flex space-x-2">
                            <select v-model="currentMood" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                                <option value="Happy">Отлично 😊</option>
                                <option value="Neutral">Нормально 😐</option>
                                <option value="Sad">Грустно 😔</option>
                            </select>
                            <button @click="logMood" class="bg-primary text-white px-4 py-2 rounded">Записать</button>
                        </div>
                        <div class="text-xs space-y-1">
                            <div v-for="m in moodLogs" class="p-1 border rounded dark:border-gray-700">{{ m.date }}: {{ m.mood }}</div>
                        </div>
                    </div>

                    <div v-if="activeTool === 22" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">22. Цветовой RGB-Hex конвертер</h4>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" v-model="rColor" placeholder="R" class="border p-1 rounded dark:bg-gray-700 text-xs">
                            <input type="number" v-model="gColor" placeholder="G" class="border p-1 rounded dark:bg-gray-700 text-xs">
                            <input type="number" v-model="bColor" placeholder="B" class="border p-1 rounded dark:bg-gray-700 text-xs">
                        </div>
                        <div class="text-sm flex items-center space-x-4">
                            <span>Код цвета: <strong>{{ rgbToHex }}</strong></span>
                            <div class="w-8 h-8 rounded border shadow" :style="{ backgroundColor: rgbToHex }"></div>
                        </div>
                    </div>

                    <div v-if="activeTool === 23" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">23. Борьба с курением</h4>
                        <div class="text-center font-bold">Выкурено сигарет: {{ cigCount }} шт.</div>
                        <div class="flex justify-center space-x-2">
                            <button @click="addCig" class="bg-red-500 text-white px-4 py-2 rounded">Выкурил сигарету 🚬</button>
                        </div>
                        <div class="text-sm text-center text-red-400 font-bold">Потрачено денег впустую: {{ cigMoneyWaste }} BYN</div>
                    </div>

                    <div v-if="activeTool === 24" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">24. Умный блокнот быстрых записей</h4>
                        <textarea v-model="noteText" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" rows="4"></textarea>
                        <button @click="saveNote" class="bg-primary text-white px-4 py-2 rounded block">Сохранить</button>
                    </div>

                    <div v-if="activeTool === 25" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">25. Хэш-генератор</h4>
                        <input v-model="hashText" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm">HEX Хэш: <strong class="text-primary font-mono">{{ hashRes }}</strong></div>
                    </div>

                    <div v-if="activeTool === 26" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">26. Калькулятор чаевых</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs">Счет (BYN):</label>
                                <input type="number" v-model="billAmount" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                            </div>
                            <div>
                                <label class="block text-xs">Чаевые (%):</label>
                                <input type="number" v-model="tipPercent" class="border p-2 rounded dark:bg-gray-700 dark:text-white w-full">
                            </div>
                        </div>
                        <div class="text-sm">Сумма чаевых: <strong class="text-green-500">{{ tipVal }} BYN</strong></div>
                    </div>

                    <div v-if="activeTool === 27" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">27. Симулятор пульса</h4>
                        <button @click="simBeat" class="bg-red-500 text-white px-4 py-2 rounded animate-bounce"><i class="fas fa-heart mr-2"></i>Стук сердца</button>
                        <div class="text-xs">Записанные удары: {{ simulatedBeats.length }} ударов.</div>
                    </div>

                    <div v-if="activeTool === 28" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">28. Калькулятор точного возраста</h4>
                        <input type="date" v-model="birthDate" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                        <div class="text-sm">Возраст в годах: <strong>{{ calculatedAge }} лет</strong></div>
                    </div>

                    <div v-if="activeTool === 29" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">29. Конвертер часовых поясов</h4>
                        <select v-model="selectedTz" class="border p-2 rounded dark:bg-gray-700 dark:text-white">
                            <option value="EST">Нью-Йорк (EST)</option>
                            <option value="MSK">Минск/Москва (MSK)</option>
                            <option value="GMT">Лондон (GMT)</option>
                        </select>
                        <div class="text-sm">Время в зоне {{ selectedTz }}: <strong>{{ currentTzTime }}</strong></div>
                    </div>

                    <div v-if="activeTool === 30" class="space-y-4">
                        <h4 class="text-xl font-bold text-primary">30. Генератор белого шума</h4>
                        <button @click="toggleNoise" class="bg-primary text-white px-6 py-2 rounded-lg font-bold">
                            {{ noisePlaying ? 'Остановить фоновый шум' : 'Запустить белый шум' }}
                        </button>
                        <p class="text-xs text-gray-400">Синтезирует акустические волны белого шума для полной концентрации в офисе.</p>
                    </div>
                </div>

                <div class="border-t pt-4 mt-6 flex justify-between text-xs text-gray-400">
                    <span>Служебная логика BELHOS CRM</span>
                    <span>30/30 функций утилит</span>
                </div>
            </div>
        </div>
    `
});

app.component('help-view', {
    props: ['t'],
    setup() {
        const activeTab = ref('help'); // 'help' or 'training'
        const simStep = ref(1);
        const simRole = ref('Responsible Employee');
        const testUser = ref('Иван Петров');
        const simTaskTitle = ref('');
        const simTaskDesc = ref('');
        const simStatus = ref('new');
        const simAnswers = reactive({ q1: '', q2: '' });
        const simResult = ref('');
        const completedTraining = ref(false);

        const resetSimulator = () => {
            simStep.value = 1;
            simTaskTitle.value = '';
            simTaskDesc.value = '';
            simStatus.value = 'new';
            simAnswers.q1 = '';
            simAnswers.q2 = '';
            simResult.value = '';
            completedTraining.value = false;
        };

        const checkQuiz = () => {
            if (simAnswers.q1 === 'Administrator' && simAnswers.q2 === 'yes') {
                simResult.value = 'success';
                completedTraining.value = true;
                simStep.value = 4;
            } else {
                simResult.value = 'fail';
            }
        };

        return {
            activeTab,
            simStep,
            simRole,
            testUser,
            simTaskTitle,
            simTaskDesc,
            simStatus,
            simAnswers,
            simResult,
            completedTraining,
            resetSimulator,
            checkQuiz
        };
    },
    template: `
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Navigation Tab -->
            <div class="flex space-x-4 border-b pb-4 dark:border-gray-700">
                <button @click="activeTab = 'help'" :class="['px-4 py-2 font-bold rounded-lg transition-colors', activeTab === 'help' ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800']">
                    <i class="fas fa-book-open mr-2"></i>Справка
                </button>
                <button @click="activeTab = 'training'" :class="['px-4 py-2 font-bold rounded-lg transition-colors', activeTab === 'training' ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800']">
                    <i class="fas fa-graduation-cap mr-2"></i>Интерактивное обучение
                </button>
            </div>

            <!-- Reference Manual View -->
            <div v-if="activeTab === 'help'" class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm space-y-8">
                <div>
                    <h2 class="text-2xl font-bold text-primary mb-4">{{ t('help_title') }}</h2>
                    <p class="text-gray-600 dark:text-gray-300">
                        Добро пожаловать в справочную систему **BELHOS** — CRM-платформы для автоматизации создания, распределения и контроля выполнения заявок на ремонт и обслуживание.
                    </p>
                </div>

                <div class="border-t pt-6 dark:border-gray-700 space-y-4">
                    <h3 class="text-xl font-bold dark:text-white"><i class="fas fa-user-shield text-blue-500 mr-2"></i>1. Роли и права пользователей</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <span class="font-bold text-blue-600 dark:text-blue-400">Администратор</span>
                            <p class="text-xs text-gray-500 mt-1">Полный доступ: управление пользователями, категориями, приоритетами, SLA-контроль, конструктор форм и переключение баз данных.</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <span class="font-bold text-yellow-600 dark:text-yellow-400">Начальник отдела</span>
                            <p class="text-xs text-gray-500 mt-1">Курирование: просмотр всех заявок, назначение ответственных исполнителей (вручную и через drag-and-drop), смена статусов и комментирование.</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <span class="font-bold text-green-600 dark:text-green-400">Ответственный сотрудник</span>
                            <p class="text-xs text-gray-500 mt-1">Создатель: регистрация заявок, заполнение настраиваемых полей конструктора, прикрепление фото и файлов.</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">Исполнитель (мастер, техник)</span>
                            <p class="text-xs text-gray-500 mt-1">Реализатор: просмотр назначенных ему задач, изменение статуса (В работе / Выполнено) и добавление отчетов-комментариев.</p>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-6 dark:border-gray-700 space-y-4">
                    <h3 class="text-xl font-bold dark:text-white"><i class="fas fa-tasks text-yellow-500 mr-2"></i>2. Жизненный цикл заявки</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Каждая заявка последовательно проходит следующие стадии:
                    </p>
                    <ul class="list-disc pl-6 text-sm text-gray-600 dark:text-gray-300 space-y-2">
                        <li><strong>Новая (new):</strong> Заявка создана ответственным лицом, но исполнитель еще не назначен.</li>
                        <li><strong>Назначена (assigned):</strong> Начальник отдела выбрал техника для выполнения.</li>
                        <li><strong>В работе (in_work):</strong> Техник приступил к выполнению задачи.</li>
                        <li><strong>Выполнено (done):</strong> Мастер завершил работу и прикрепил отчет. Заявка отправляется в архив.</li>
                        <li><strong>Отклонено (rejected):</strong> Заявка закрыта по объективным причинам.</li>
                    </ul>
                </div>

                <div class="border-t pt-6 dark:border-gray-700 space-y-4">
                    <h3 class="text-xl font-bold dark:text-white"><i class="fas fa-database text-green-500 mr-2"></i>3. Смена режима хранения (JSON ↔ MySQL)</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Система поддерживает гибридное хранилище. Администратор может переключить режим в настройках:
                    </p>
                    <ul class="list-disc pl-6 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li><strong>Режим JSON:</strong> Отличная скорость и переносимость, не требует настройки СУБД (данные в файлах JSON).</li>
                        <li><strong>Режим MySQL:</strong> Промышленная база данных. При переходе на MySQL система автоматически сгенерирует все таблицы и базовые роли.</li>
                    </ul>
                </div>

                <div class="border-t pt-6 dark:border-gray-700 space-y-4">
                    <h3 class="text-xl font-bold dark:text-white"><i class="fas fa-tools text-purple-500 mr-2"></i>4. Дополнительные возможности</h3>
                    <ul class="list-disc pl-6 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li><strong>Конструктор форм:</strong> Администратор может динамически менять поля (текст, номер, дата, телефон) при создании заявок с помощью удобного drag-and-drop перетаскивания.</li>
                        <li><strong>Массовое назначение:</strong> Быстрое назначение одного мастера на множество выбранных заявок.</li>
                        <li><strong>SLA контроль:</strong> Автоматический расчет планового дедлайна выполнения в зависимости от выбранной категории срочности.</li>
                    </ul>
                </div>
            </div>

            <!-- Interactive Training Simulator -->
            <div v-if="activeTab === 'training'" class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm space-y-6">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-primary mb-2">{{ t('training_title') }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">Пройдите практическое интерактивное руководство по работе в системе</p>
                </div>

                <!-- Step 1: Role Selection -->
                <div v-if="simStep === 1" class="space-y-4">
                    <h3 class="text-lg font-bold dark:text-white text-center">Шаг 1: Выберите вашу роль для симуляции обучения</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <button @click="simRole = 'Responsible Employee'; simStep = 2" class="p-6 border rounded-xl hover:border-primary text-center dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-900 transition-all">
                            <i class="fas fa-edit text-2xl text-green-500 mb-2"></i>
                            <div class="font-bold dark:text-white">Сотрудник</div>
                            <div class="text-[10px] text-gray-400 mt-1">Создание новых заявок</div>
                        </button>
                        <button @click="simRole = 'Department Head'; simStep = 2" class="p-6 border rounded-xl hover:border-primary text-center dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-900 transition-all">
                            <i class="fas fa-users-cog text-2xl text-yellow-500 mb-2"></i>
                            <div class="font-bold dark:text-white">Начальник отдела</div>
                            <div class="text-[10px] text-gray-400 mt-1">Распределение исполнителей</div>
                        </button>
                        <button @click="simRole = 'Executor'; simStep = 2" class="p-6 border rounded-xl hover:border-primary text-center dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-900 transition-all">
                            <i class="fas fa-wrench text-2xl text-indigo-500 mb-2"></i>
                            <div class="font-bold dark:text-white">Исполнитель</div>
                            <div class="text-[10px] text-gray-400 mt-1">Выполнение работ</div>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Interactive simulator actions -->
                <div v-if="simStep === 2" class="space-y-4">
                    <div class="flex justify-between items-center bg-blue-50 dark:bg-blue-950 p-3 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                        <span>Симуляция роли: <strong>{{ simRole }}</strong></span>
                        <button @click="simStep = 1" class="text-xs underline hover:text-blue-800">Изменить</button>
                    </div>

                    <!-- Simulator Content based on Role -->
                    <div v-if="simRole === 'Responsible Employee'" class="space-y-4">
                        <p class="text-sm dark:text-gray-300">Ваша задача — заполнить поля ниже, чтобы создать тестовую заявку на починку лифта.</p>
                        <div class="space-y-2 max-w-md">
                            <input v-model="simTaskTitle" placeholder="Введите название (например: Поломка лифта)" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                            <textarea v-model="simTaskDesc" placeholder="Введите описание проблемы" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" rows="2"></textarea>
                            <button @click="simStep = 3" :disabled="!simTaskTitle" class="bg-primary text-white px-4 py-2 rounded-lg disabled:opacity-50">Отправить заявку</button>
                        </div>
                    </div>

                    <div v-if="simRole === 'Department Head'" class="space-y-4">
                        <p class="text-sm dark:text-gray-300">Симуляция распределения: перетащите задачу или назначьте исполнителя из списка ниже.</p>
                        <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-900 space-y-3">
                            <div class="p-3 bg-white dark:bg-gray-800 shadow rounded flex justify-between items-center">
                                <div>
                                    <div class="font-bold dark:text-white">Сломался бойлер в корпусе 2</div>
                                    <div class="text-xs text-red-500">Дедлайн: сегодня</div>
                                </div>
                                <select @change="simStep = 3" class="border p-1 rounded dark:bg-gray-700 dark:text-white text-xs">
                                    <option>Выберите...</option>
                                    <option>Техник Иван</option>
                                    <option>Электрик Сергей</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="simRole === 'Executor'" class="space-y-4">
                        <p class="text-sm dark:text-gray-300">Вам назначена заявка «Замена светильника». Поменяйте ее статус на «В работе» или «Выполнено».</p>
                        <div class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-900 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="font-bold dark:text-white">Замена светильника</span>
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">Текущий статус: {{ simStatus }}</span>
                            </div>
                            <div class="flex space-x-2">
                                <button @click="simStatus = 'in_work'; simStep = 3" class="bg-yellow-500 text-white px-3 py-1 text-xs rounded">В работу</button>
                                <button @click="simStatus = 'done'; simStep = 3" class="bg-green-500 text-white px-3 py-1 text-xs rounded">Выполнить</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Interactive Quiz -->
                <div v-if="simStep === 3" class="space-y-6">
                    <h3 class="text-lg font-bold dark:text-white text-center">Финальный тест знаний</h3>
                    <p class="text-sm text-gray-500 text-center">Чтобы подтвердить завершение обучения, ответьте на два вопроса:</p>

                    <div class="space-y-4 max-w-md mx-auto">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300">1. Какая роль имеет доступ к полным настройкам и СУБД?</label>
                            <select v-model="simAnswers.q1" class="mt-1 block w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                                <option value="">Выберите...</option>
                                <option value="Executor">Исполнитель</option>
                                <option value="Department Head">Начальник отдела</option>
                                <option value="Administrator">Администратор</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300">2. Генерирует ли СУБД MySQL схемы таблиц автоматически при переходе?</label>
                            <select v-model="simAnswers.q2" class="mt-1 block w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                                <option value="">Выберите...</option>
                                <option value="yes">Да, автоматически</option>
                                <option value="no">Нет, нужно создавать руками</option>
                            </select>
                        </div>

                        <div class="pt-4 flex justify-between">
                            <button @click="simStep = 2" class="text-sm text-gray-500">Назад</button>
                            <button @click="checkQuiz" class="bg-primary text-white px-6 py-2 rounded-lg">Проверить ответы</button>
                        </div>

                        <!-- Failure display -->
                        <div v-if="simResult === 'fail'" class="p-3 bg-red-100 text-red-800 text-sm rounded text-center">
                            Ответы неверны. Попробуйте еще раз!
                        </div>
                    </div>
                </div>

                <!-- Step 4: Finished simulation -->
                <div v-if="simStep === 4" class="text-center space-y-4">
                    <div class="inline-flex w-16 h-16 bg-green-100 text-green-600 rounded-full items-center justify-center text-2xl mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-xl font-bold dark:text-white">Поздравляем с завершением обучения!</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto">Вы успешно освоили ключевые принципы работы, ролевые особенности и прошли квалификационное тестирование.</p>
                    <div class="pt-4 space-x-2">
                        <button @click="resetSimulator" class="bg-primary text-white px-6 py-2 rounded-lg">Начать заново</button>
                    </div>
                </div>
            </div>
        </div>
    `
});

app.mount('#app');
