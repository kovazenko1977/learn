
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
        chat: "Чат"
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
        chat: "Chat"
    }
};

const roleTranslations = {
    'Administrator': 'Администратор',
    'Responsible Employee': 'Ответственный сотрудник',
    'Department Head': 'Начальник отдела',
    'Executor': 'Исполнитель (мастер, техник)'
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
        const groups = ref([]);

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

        const sendPing = async () => {
            if (!user.value) return;
            try {
                await api('users.php?ping=1', 'POST');
            } catch (e) {}
        };

        const activeNewAssignment = ref(null);

        const checkNewAssignments = () => {
            if (!user.value || user.value.role !== 'Executor') {
                activeNewAssignment.value = null;
                return;
            }
            const assignedTasks = tasks.value.filter(t => t.executor_id === user.value.id && t.status === 'assigned');
            if (assignedTasks.length === 0) {
                activeNewAssignment.value = null;
                return;
            }
            let seen = [];
            try {
                seen = JSON.parse(localStorage.getItem('seen_assigned_tasks') || '[]');
            } catch (e) {
                seen = [];
            }
            const unseen = assignedTasks.find(t => !seen.includes(t.id));
            if (unseen) {
                activeNewAssignment.value = unseen;
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification("BELHOS CRM: Вам назначена новая заявка", {
                        body: unseen.title,
                        icon: 'https://cdn-icons-png.flaticon.com/512/1040/1040243.png'
                    });
                } else if ('Notification' in window && Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }
            } else {
                activeNewAssignment.value = null;
            }
        };

        const dismissAssignment = (taskId) => {
            let seen = [];
            try {
                seen = JSON.parse(localStorage.getItem('seen_assigned_tasks') || '[]');
            } catch (e) {
                seen = [];
            }
            if (!seen.includes(taskId)) {
                seen.push(taskId);
                localStorage.setItem('seen_assigned_tasks', JSON.stringify(seen));
            }
            activeNewAssignment.value = null;
        };

        const viewAssignment = (task) => {
            dismissAssignment(task.id);
            view.value = 'tasks';
            localStorage.setItem('target_view_task_id', task.id);
        };

        const initData = async () => {
            if (!user.value) return;
            const [s, t, u, st, gr] = await Promise.all([
                api('settings.php'),
                api('tasks.php'),
                api('users.php'),
                (user.value.role === 'Administrator' || user.value.role === 'Department Head') ? api('analytics.php') : Promise.resolve(null),
                api('groups.php')
            ]);
            settings.value = s || {};
            tasks.value = t || [];
            users.value = u || [];
            stats.value = st;
            groups.value = gr || [];
            checkNewAssignments();
        };

        watch(tasks, () => {
            checkNewAssignments();
        }, { deep: true });

        watch(user, () => {
            checkNewAssignments();
        });

        onMounted(() => {
            if (darkMode.value) document.documentElement.classList.add('dark');
            if (user.value) {
                initData();
                sendPing();
            }
            setInterval(() => {
                if (user.value) {
                    sendPing();
                    initData();
                }
            }, 10000);
        });

        return {
            user, token, lang, darkMode, view, t, formatDuration, toggleDarkMode, toggleLang,
            login, logout, api, settings, tasks, users, stats, initData,
            activeNewAssignment, dismissAssignment, viewAssignment, roleTranslations, groups
        };
    },
    template: `
        <div :class="{ 'dark': darkMode }" class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors">
            <!-- Executor Notification Alert -->
            <div v-if="activeNewAssignment" class="fixed top-4 right-4 bg-yellow-50 dark:bg-yellow-950 border-l-4 border-yellow-500 p-4 rounded-xl shadow-2xl max-w-sm z-50 flex flex-col space-y-2 border border-yellow-200 dark:border-yellow-900">
                <div class="flex items-start space-x-3">
                    <div class="p-1.5 bg-yellow-100 dark:bg-yellow-900 rounded-lg text-yellow-600 dark:text-yellow-300">
                        <i class="fas fa-bell text-sm"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-gray-800 dark:text-white">Новое назначение!</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Вам назначена новая заявка: <strong class="text-gray-900 dark:text-yellow-200">{{ activeNewAssignment.title }}</strong></p>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 text-[10px] pt-1">
                    <button @click="dismissAssignment(activeNewAssignment.id)" class="text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition-all font-semibold">Позже</button>
                    <button @click="viewAssignment(activeNewAssignment)" class="bg-yellow-500 text-white px-3 py-1 rounded-md font-bold hover:bg-yellow-600 shadow-sm transition-all">Посмотреть</button>
                </div>
            </div>

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
                            <nav-link :active="view === 'users'" @click="view = 'users'">{{ t('users') }}</nav-link>
                            <nav-link :active="view === 'chat'" @click="view = 'chat'">{{ t('chat') }}</nav-link>
                            <nav-link v-if="user.role === 'Administrator'" :active="view === 'settings'" @click="view = 'settings'">{{ t('settings') }}</nav-link>
                            <nav-link :active="view === 'help'" @click="view = 'help'">{{ t('help') }}</nav-link>
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
                <main class="flex-1 overflow-auto p-4 md:p-6 pb-20 md:pb-6">
                    <dashboard-view v-if="view === 'dashboard'" :stats="stats" :t="t" :format-duration="formatDuration"></dashboard-view>
                    <tasks-view v-if="view === 'tasks'" :tasks="tasks" :users="users" :settings="settings" :user="user" :t="t" @refresh="initData" :api="api" @start-chat="view = 'chat'"></tasks-view>
                    <users-view v-if="view === 'users'" :users="users" :t="t" @refresh="initData" :api="api" :user="user" :groups="groups" @start-chat="view = 'chat'"></users-view>
                    <chat-view v-if="view === 'chat'" :user="user" :users="users" :t="t" :api="api"></chat-view>
                    <settings-view v-if="view === 'settings'" :settings="settings" :t="t" @refresh="initData" :api="api"></settings-view>
                    <profile-view v-if="view === 'profile'" :user="user" :t="t" @refresh="initData" :api="api"></profile-view>
                    <help-view v-if="view === 'help'" :t="t"></help-view>
                </main>

                <!-- Mobile Bottom Nav -->
                <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-around items-center py-2 px-1 z-40 shadow-lg no-print">
                    <button @click="view = 'dashboard'" :class="view === 'dashboard' ? 'text-primary' : 'text-gray-500 dark:text-gray-400'" class="flex-1 flex flex-col items-center">
                        <i class="fas fa-chart-pie text-lg"></i>
                        <span class="text-[9px] mt-0.5">Дашборд</span>
                    </button>
                    <button @click="view = 'tasks'" :class="view === 'tasks' ? 'text-primary' : 'text-gray-500 dark:text-gray-400'" class="flex-1 flex flex-col items-center">
                        <i class="fas fa-tasks text-lg"></i>
                        <span class="text-[9px] mt-0.5">Заявки</span>
                    </button>
                    <button @click="view = 'users'" :class="view === 'users' ? 'text-primary' : 'text-gray-500 dark:text-gray-400'" class="flex-1 flex flex-col items-center">
                        <i class="fas fa-users text-lg"></i>
                        <span class="text-[9px] mt-0.5">Контакты</span>
                    </button>
                    <button @click="view = 'chat'" :class="view === 'chat' ? 'text-primary' : 'text-gray-500 dark:text-gray-400'" class="flex-1 flex flex-col items-center relative">
                        <i class="fas fa-comments text-lg"></i>
                        <span class="text-[9px] mt-0.5">Чат</span>
                    </button>
                    <button v-if="user.role === 'Administrator'" @click="view = 'settings'" :class="view === 'settings' ? 'text-primary' : 'text-gray-500 dark:text-gray-400'" class="flex-1 flex flex-col items-center">
                        <i class="fas fa-cog text-lg"></i>
                        <span class="text-[9px] mt-0.5">Настройки</span>
                    </button>
                    <button @click="view = 'help'" :class="view === 'help' ? 'text-primary' : 'text-gray-500 dark:text-gray-400'" class="flex-1 flex flex-col items-center">
                        <i class="fas fa-graduation-cap text-lg"></i>
                        <span class="text-[9px] mt-0.5">Справка</span>
                    </button>
                </div>
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

        const getExecutorName = (executorId) => {
            const u = props.users.find(u => u.id === executorId);
            return u ? u.full_name : '';
        };

        onMounted(() => {
            const targetId = localStorage.getItem('target_view_task_id');
            if (targetId) {
                const found = props.tasks.find(t => t.id === targetId);
                if (found) {
                    selectedTask.value = found;
                }
                localStorage.removeItem('target_view_task_id');
            }
        });

        watch(() => props.tasks, (newTasks) => {
            const targetId = localStorage.getItem('target_view_task_id');
            if (targetId) {
                const found = newTasks.find(t => t.id === targetId);
                if (found) {
                    selectedTask.value = found;
                }
                localStorage.removeItem('target_view_task_id');
            }
        }, { deep: true });

        return { showForm, searchQuery, filterStatus, showArchive, filteredTasks, statusColors, updateStatus, selectedTask, onDragEnd, print, selectedTasks, bulkAssign, getExecutorName };
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

                                <div v-if="element.executor_id" class="mt-2 flex items-center space-x-1.5 text-[11px] text-primary bg-blue-50 dark:bg-gray-700 px-2 py-0.5 rounded-md font-semibold">
                                    <i class="fas fa-user-cog text-[10px]"></i>
                                    <span class="truncate">Исполнитель: {{ getExecutorName(element.executor_id) }}</span>
                                </div>

                                <div class="mt-3 flex justify-between items-center text-[10px] text-gray-400">
                                    <span>{{ element.created_at.split(' ')[0] }}</span>
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

        const isImage = (url) => {
            if (!url) return false;
            const ext = url.split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        };

        const openAttachment = (url) => {
            window.open(url, '_blank');
        };

        return { comment, saveComment, changeStatus, assign, isAdminOrHead, isImage, openAttachment };
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
                             <h4 class="font-bold mb-2 dark:text-gray-200">Вложения и Фото</h4>
                             <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div v-for="a in task.attachments" :key="a.url" class="border border-gray-200 dark:border-gray-700 rounded-md p-1 bg-white dark:bg-gray-800 flex flex-col justify-between shadow-sm">
                                    <div class="flex items-center justify-center p-1 bg-gray-50 dark:bg-gray-950 rounded mb-1 h-20 overflow-hidden" v-if="isImage(a.url)">
                                        <img :src="a.url" class="max-h-full max-w-full rounded object-contain cursor-pointer hover:scale-105 transition-all" @click="openAttachment(a.url)">
                                    </div>
                                    <div class="flex items-center justify-center p-1 bg-gray-50 dark:bg-gray-950 rounded mb-1 h-20" v-else>
                                        <i class="fas fa-file-alt text-2xl text-gray-400"></i>
                                    </div>
                                    <a :href="a.url" target="_blank" class="text-[11px] text-primary hover:underline text-center truncate block font-medium mt-1">
                                        <i class="fas fa-file-download mr-1"></i>{{ a.name }}
                                    </a>
                                </div>
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
    props: ['users', 't', 'api', 'user', 'groups'],
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
            email: '',
            banned: 0,
            group_id: ''
        });

        const isOnline = (lastSeen) => {
            if (!lastSeen) return false;
            const now = Math.floor(Date.now() / 1000);
            return (now - parseInt(lastSeen)) < 30; // Active in last 30 seconds
        };

        const formatLastSeen = (u) => {
            if (u.banned == 1) return 'Заблокирован';
            if (isOnline(u.last_seen)) return 'В сети';
            if (!u.last_seen || u.last_seen == 0) return 'Не в сети';
            const date = new Date(u.last_seen * 1000);
            return 'Был в сети: ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' ' + date.toLocaleDateString();
        };

        const edit = (u) => {
            editingUser.value = u;
            Object.assign(form, u);
            form.password = '';
            form.banned = u.banned ? 1 : 0;
            form.group_id = u.group_id || '';
            showForm.value = true;
        };

        const save = async () => {
            const url = editingUser.value ? `users.php?id=${editingUser.value.id}` : 'users.php';
            form.banned = form.banned ? 1 : 0;
            await props.api(url, 'POST', form);
            showForm.value = false;
            editingUser.value = null;
            emit('refresh');
        };

        const remove = async (id) => {
            if (confirm('Удалить пользователя?')) {
                await props.api(`users.php?id=${id}`, 'DELETE');
                emit('refresh');
            }
        };

        const toggleBan = async (u) => {
            const updated = { ...u, banned: u.banned == 1 ? 0 : 1 };
            await props.api(`users.php?id=${u.id}`, 'POST', updated);
            emit('refresh');
        };

        const startPrivateChat = (u) => {
            localStorage.setItem('chat_target_user_id', u.id);
            emit('start-chat');
        };

        const getGroupName = (groupId) => {
            if (!props.groups || !groupId) return '';
            const g = props.groups.find(g => g.id === groupId);
            return g ? g.name : '';
        };

        return { showForm, editingUser, roles, form, edit, save, remove, isOnline, formatLastSeen, toggleBan, startPrivateChat, roleTranslations, getGroupName };
    },
    template: `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold dark:text-white">Справочник пользователей и статусы</h2>
                    <p class="text-xs text-gray-500 mt-1">Всего зарегистрировано: {{ users.length }} сотрудников</p>
                </div>
                <button v-if="user.role === 'Administrator'" @click="editingUser = null; Object.assign(form, { username: '', password: '', full_name: '', role: 'Executor', department: '', email: '', banned: 0 }); showForm = true" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-blue-600">Добавить</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-gray-500 uppercase text-[11px] font-bold">
                        <tr>
                            <th class="px-6 py-3">Пользователь</th>
                            <th class="px-6 py-3">Роль / Отдел</th>
                            <th class="px-6 py-3">Статус в сети</th>
                            <th class="px-6 py-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 dark:hover:bg-gray-900 dark:text-gray-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900 text-primary dark:text-blue-300 flex items-center justify-center font-bold text-sm">
                                        {{ u.full_name ? u.full_name[0].toUpperCase() : u.username[0].toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800 dark:text-gray-100">{{ u.full_name || u.username }}</div>
                                        <div class="text-xs text-gray-400">@{{ u.username }} | {{ u.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': u.role === 'Administrator',
                                          'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': u.role === 'Department Head',
                                          'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': u.role === 'Executor',
                                          'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': u.role === 'Responsible Employee'
                                      }">
                                    {{ roleTranslations[u.role] || u.role }}
                                </span>
                                <div class="text-xs text-gray-500 mt-1">{{ u.department || 'Без отдела' }}</div>
                                <div v-if="u.group_id" class="text-xs text-primary font-bold mt-1.5"><i class="fas fa-shield-alt mr-1"></i>{{ getGroupName(u.group_id) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full"
                                          :class="{
                                              'bg-green-500 animate-pulse': u.banned != 1 && isOnline(u.last_seen),
                                              'bg-gray-400': u.banned != 1 && !isOnline(u.last_seen),
                                              'bg-red-500': u.banned == 1
                                          }"></span>
                                    <span class="text-xs" :class="u.banned == 1 ? 'text-red-500 font-bold' : 'text-gray-600 dark:text-gray-300'">
                                        {{ formatLastSeen(u) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <button v-if="u.id !== user.id" @click="startPrivateChat(u)" class="bg-blue-50 hover:bg-blue-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-primary dark:text-blue-300 px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center space-x-1 transition-all">
                                        <i class="fab fa-viber text-purple-500"></i>
                                        <span>Чат</span>
                                    </button>
                                    <template v-if="user.role === 'Administrator'">
                                        <button @click="edit(u)" class="text-blue-500 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded" title="Редактировать"><i class="fas fa-edit"></i></button>
                                        <button @click="toggleBan(u)" :class="u.banned == 1 ? 'text-green-500' : 'text-orange-500'" class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded" :title="u.banned == 1 ? 'Разблокировать' : 'Заблокировать'">
                                            <i class="fas" :class="u.banned == 1 ? 'fa-user-check' : 'fa-user-slash'"></i>
                                        </button>
                                        <button v-if="u.id !== user.id" @click="remove(u.id)" class="text-red-500 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded" title="Удалить"><i class="fas fa-trash"></i></button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add/Edit form Modal -->
            <div v-if="showForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-md">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">{{ editingUser ? 'Редактировать сотрудника' : 'Добавить сотрудника' }}</h3>
                    <form @submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Имя пользователя (Логин)</label>
                            <input v-model="form.username" placeholder="Логин для входа" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Пароль</label>
                            <input v-model="form.password" type="password" placeholder="Оставьте пустым, чтобы не менять" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" :required="!editingUser">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">ФИО сотрудника</label>
                            <input v-model="form.full_name" placeholder="ФИО" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Роль в системе</label>
                            <select v-model="form.role" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                                <option v-for="r in roles" :value="r">{{ roleTranslations[r] || r }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Группа прав доступа (необязательно)</label>
                            <select v-model="form.group_id" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                                <option value="">По умолчанию (согласно роли)</option>
                                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Отдел / Сектор</label>
                            <input v-model="form.department" placeholder="Например: IT, Бухгалтерия, Снабжение" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Электронная почта</label>
                            <input v-model="form.email" type="email" placeholder="email@example.com" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white">
                        </div>
                        <div v-if="editingUser">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" v-model="form.banned" :true-value="1" :false-value="0">
                                <span class="text-sm dark:text-white font-bold text-red-500 font-bold">Заблокировать доступ</span>
                            </label>
                        </div>
                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button" @click="showForm = false" class="px-4 py-2 text-gray-500">Отмена</button>
                            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg">Сохранить</button>
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
        const groups = ref([]);
        const showGroupForm = ref(false);
        const editingGroup = ref(null);

        const groupForm = reactive({
            name: '',
            permissions: {
                can_view_all_tasks: false,
                can_create_tasks: false,
                can_assign_executors: false,
                can_comment_tasks: false,
                can_edit_tasks: false,
                can_delete_tasks: false,
                can_access_chat: false,
                can_view_analytics: false
            }
        });

        const permissionLabels = {
            can_view_all_tasks: 'Просмотр всех заявок',
            can_create_tasks: 'Создание заявок',
            can_assign_executors: 'Назначение исполнителей',
            can_comment_tasks: 'Комментирование заявок',
            can_edit_tasks: 'Редактирование заявок',
            can_delete_tasks: 'Удаление заявок',
            can_access_chat: 'Использование чатов',
            can_view_analytics: 'Просмотр аналитики'
        };

        const loadGroups = async () => {
            const res = await props.api('groups.php');
            groups.value = res || [];
        };

        const editGroup = (g) => {
            editingGroup.value = g;
            groupForm.name = g.name;
            Object.keys(groupForm.permissions).forEach(k => {
                groupForm.permissions[k] = !!(g.permissions && g.permissions[k]);
            });
            showGroupForm.value = true;
        };

        const saveGroup = async () => {
            if (!groupForm.name.trim()) return;
            const payload = {
                name: groupForm.name,
                permissions: { ...groupForm.permissions }
            };
            if (editingGroup.value) {
                payload.id = editingGroup.value.id;
            }
            await props.api('groups.php', 'POST', payload);
            showGroupForm.value = false;
            editingGroup.value = null;
            loadGroups();
        };

        const removeGroup = async (id) => {
            if (confirm('Удалить эту группу пользователей?')) {
                await props.api(`groups.php?id=${id}`, 'DELETE');
                loadGroups();
            }
        };

        const save = async () => {
            await props.api('settings.php', 'POST', localSettings);
            alert('Settings saved');
            emit('refresh');
        };

        const addCategory = () => localSettings.categories.push('Новая категория');
        const addField = () => localSettings.form_fields.push({ id: 'f' + Date.now(), label: 'Новое поле', type: 'text', required: false });

        onMounted(() => {
            loadGroups();
        });

        return {
            localSettings, save, addCategory, addField,
            groups, showGroupForm, editingGroup, groupForm, permissionLabels,
            editGroup, saveGroup, removeGroup
        };
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

                    <!-- Groups & Permissions Section -->
                    <div class="border-t dark:border-gray-700 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold dark:text-gray-300">Группы сотрудников и права доступа</h3>
                            <button @click="editingGroup = null; groupForm.name = ''; Object.keys(groupForm.permissions).forEach(k => groupForm.permissions[k] = false); showGroupForm = true" type="button" class="bg-primary hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">+ Создать группу</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="g in groups" :key="g.id" class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl bg-gray-50 dark:bg-gray-900 flex flex-col justify-between space-y-4">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-bold text-sm text-gray-800 dark:text-white">{{ g.name }}</h4>
                                        <div class="flex space-x-1">
                                            <button @click="editGroup(g)" type="button" class="text-blue-500 hover:bg-gray-200 dark:hover:bg-gray-800 p-1 rounded text-xs"><i class="fas fa-edit"></i></button>
                                            <button @click="removeGroup(g.id)" type="button" class="text-red-500 hover:bg-gray-200 dark:hover:bg-gray-800 p-1 rounded text-xs"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        <span v-for="(val, key) in g.permissions" :key="key" v-show="val" class="bg-blue-100 dark:bg-blue-900 text-primary dark:text-blue-200 px-2 py-0.5 rounded text-[10px] font-semibold">
                                            {{ permissionLabels[key] || key }}
                                        </span>
                                        <span v-if="!g.permissions || Object.values(g.permissions).filter(Boolean).length === 0" class="text-xs text-gray-400 italic">Нет назначенных прав</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t dark:border-gray-700">
                    <button @click="save" class="bg-primary text-white px-6 py-2 rounded-md font-bold">Сохранить все настройки</button>
                </div>
            </div>

            <!-- Group Creation/Editing Modal -->
            <div v-if="showGroupForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-lg">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">{{ editingGroup ? 'Редактировать группу' : 'Создать новую группу' }}</h3>
                    <form @submit.prevent="saveGroup" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Название группы</label>
                            <input v-model="groupForm.name" placeholder="Например: Мастера, Менеджеры, Поддержка" class="w-full border p-2 rounded dark:bg-gray-700 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Назначить права доступа</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-1">
                                <label v-for="(label, key) in permissionLabels" :key="key" class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer">
                                    <input type="checkbox" v-model="groupForm.permissions[key]" class="rounded text-primary focus:ring-primary">
                                    <span class="text-xs dark:text-gray-200">{{ label }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2 pt-2 border-t dark:border-gray-700">
                            <button type="button" @click="showGroupForm = false" class="px-4 py-2 text-gray-500">Отмена</button>
                            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg font-bold">Сохранить группу</button>
                        </div>
                    </form>
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

app.component('chat-view', {
    props: ['user', 'users', 't', 'api'],
    setup(props) {
        const activeTarget = ref(null); // 'general' or user_id or null
        const messages = ref([]);
        const messageText = ref('');
        const searchQuery = ref('');
        const chatContainer = ref(null);
        const isUploading = ref(false);
        const attachedPhoto = ref(null);

        const filteredUsers = computed(() => {
            return props.users.filter(u => {
                if (u.id === props.user.id) return false;
                const search = searchQuery.value.toLowerCase();
                return (u.full_name || '').toLowerCase().includes(search) ||
                       (u.username || '').toLowerCase().includes(search);
            });
        });

        const activeTargetUser = computed(() => {
            if (activeTarget.value === 'general') return null;
            return props.users.find(u => u.id === activeTarget.value);
        });

        const isOnline = (lastSeen) => {
            if (!lastSeen) return false;
            const now = Math.floor(Date.now() / 1000);
            return (now - parseInt(lastSeen)) < 30;
        };

        const loadMessages = async () => {
            const res = await props.api(`chat.php?recipient_id=${activeTarget.value}`);
            messages.value = res || [];
            scrollToBottom();
        };

        const selectTarget = (targetId) => {
            activeTarget.value = targetId;
            attachedPhoto.value = null;
            loadMessages();
        };

        const scrollToBottom = () => {
            nextTick(() => {
                if (chatContainer.value) {
                    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                }
            });
        };

        const sendMessage = async () => {
            if (!messageText.value.trim() && !attachedPhoto.value) return;

            const payload = {
                recipient_id: activeTarget.value === 'general' ? null : activeTarget.value,
                message: messageText.value,
                attachment_url: attachedPhoto.value ? attachedPhoto.value.url : null,
                attachment_name: attachedPhoto.value ? attachedPhoto.value.name : null
            };

            await props.api('chat.php', 'POST', payload);
            messageText.value = '';
            attachedPhoto.value = null;
            await loadMessages();
        };

        const uploadChatPhoto = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            isUploading.value = true;
            const formData = new FormData();
            formData.append('file', file);
            try {
                const res = await fetch('api/uploads.php', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` },
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    attachedPhoto.value = { url: result.url, name: result.name };
                }
            } catch (err) {
                alert('Ошибка при загрузке фото');
            } finally {
                isUploading.value = false;
            }
        };

        const getSenderName = (senderId) => {
            const u = props.users.find(u => u.id === senderId);
            return u ? u.full_name : 'Сотрудник';
        };

        const openAttachment = (url) => {
            window.open(url, '_blank');
        };

        onMounted(() => {
            const targetId = localStorage.getItem('chat_target_user_id');
            if (targetId) {
                activeTarget.value = targetId;
                localStorage.removeItem('chat_target_user_id');
            }
            loadMessages();
            const interval = setInterval(() => {
                loadMessages();
            }, 3000);
        });

        return {
            activeTarget,
            messages,
            messageText,
            searchQuery,
            filteredUsers,
            activeTargetUser,
            isOnline,
            selectTarget,
            sendMessage,
            uploadChatPhoto,
            isUploading,
            attachedPhoto,
            getSenderName,
            chatContainer,
            openAttachment,
            roleTranslations
        };
    },
    template: `
        <div class="flex h-[calc(100vh-120px)] bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Sidebar (Contacts / Channels) -->
            <div :class="{'hidden md:flex': activeTarget !== null, 'flex w-full md:w-80': activeTarget === null, 'w-80': activeTarget !== null}"
                 class="border-r border-gray-100 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="relative">
                        <input v-model="searchQuery" type="text" placeholder="Поиск контактов..." class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-9 pr-4 py-2 text-xs focus:outline-none focus:border-primary dark:text-white">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    <div class="text-xs font-bold text-gray-400 px-3 py-1 uppercase tracking-wider">Каналы</div>

                    <button @click="selectTarget('general')"
                            :class="activeTarget === 'general' ? 'bg-primary text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-150 dark:hover:bg-gray-800'"
                            class="w-full text-left px-3 py-2.5 rounded-lg flex items-center space-x-3 transition-all">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-primary dark:text-blue-300 flex items-center justify-center">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-xs">Общий чат (Viber)</div>
                            <div class="text-[10px] opacity-75 truncate">Общие вопросы и объявления</div>
                        </div>
                    </button>

                    <div class="text-xs font-bold text-gray-400 px-3 py-2 uppercase tracking-wider mt-4">Личные переписки</div>

                    <button v-for="u in filteredUsers" :key="u.id"
                            @click="selectTarget(u.id)"
                            :class="activeTarget === u.id ? 'bg-primary text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
                            class="w-full text-left px-3 py-2 rounded-lg flex items-center space-x-3 transition-all">
                        <div class="relative">
                            <div class="w-8 h-8 rounded-full bg-blue-150 dark:bg-blue-900 text-blue-800 dark:text-blue-200 flex items-center justify-center font-bold text-xs uppercase">
                                {{ u.full_name ? u.full_name[0].toUpperCase() : u.username[0].toUpperCase() }}
                            </div>
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-900"
                                  :class="u.banned == 1 ? 'bg-red-500' : (isOnline(u.last_seen) ? 'bg-green-500' : 'bg-gray-400')"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-xs truncate">{{ u.full_name || u.username }}</div>
                            <div class="text-[10px] opacity-75 truncate">{{ roleTranslations[u.role] || u.role }} | {{ u.department || 'Без отдела' }}</div>
                        </div>
                    </button>

                    <div v-if="filteredUsers.length === 0" class="text-center py-4 text-xs text-gray-400">
                        Никого не найдено
                    </div>
                </div>
            </div>

            <!-- Empty Desktop Placeholder -->
            <div v-if="activeTarget === null" class="hidden md:flex flex-1 flex-col items-center justify-center text-gray-400 space-y-4 bg-gray-50 dark:bg-gray-950">
                <i class="fab fa-viber text-6xl opacity-35 text-primary"></i>
                <p class="text-xs font-semibold">Выберите контакт или канал для начала общения</p>
            </div>

            <div v-else :class="{'hidden md:flex': activeTarget === null, 'flex flex-1': activeTarget !== null}" class="flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900">
                    <div class="flex items-center space-x-3">
                        <!-- Back button for Mobile -->
                        <button @click="selectTarget(null)" class="md:hidden text-primary hover:text-blue-600 mr-2 p-1">
                            <i class="fas fa-chevron-left text-lg"></i>
                        </button>
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">
                            <i v-if="activeTarget === 'general'" class="fas fa-comments text-lg"></i>
                            <span v-else>{{ activeTargetUser ? activeTargetUser.full_name[0].toUpperCase() : '?' }}</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm dark:text-white">
                                {{ activeTarget === 'general' ? 'Общий корпоративный чат BELHOS' : (activeTargetUser ? activeTargetUser.full_name : 'Сотрудник') }}
                            </h3>
                            <div class="text-xs text-gray-500 flex items-center space-x-1.5">
                                <template v-if="activeTarget === 'general'">
                                    <span>Доступно всем сотрудникам компании</span>
                                </template>
                                <template v-else-if="activeTargetUser">
                                    <span class="w-2 h-2 rounded-full" :class="activeTargetUser.banned == 1 ? 'bg-red-500' : (isOnline(activeTargetUser.last_seen) ? 'bg-green-500' : 'bg-gray-400')"></span>
                                    <span>{{ activeTargetUser.banned == 1 ? 'Заблокирован' : (isOnline(activeTargetUser.last_seen) ? 'В сети' : 'Не в сети') }}</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50 dark:bg-gray-950">
                    <div v-for="m in messages" :key="m.id"
                         :class="m.sender_id === user.id ? 'justify-end' : 'justify-start'"
                         class="flex">
                        <div :class="m.sender_id === user.id ? 'bg-primary text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-bl-none'"
                             class="max-w-md p-3 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col space-y-1">

                            <span v-if="activeTarget === 'general' && m.sender_id !== user.id" class="text-[10px] font-bold text-indigo-500 dark:text-indigo-300">
                                {{ getSenderName(m.sender_id) }}
                            </span>

                            <p class="text-xs whitespace-pre-wrap leading-relaxed">{{ m.message }}</p>

                            <div v-if="m.attachment_url" class="pt-1">
                                <img :src="m.attachment_url" class="max-h-60 rounded-lg object-contain cursor-pointer hover:opacity-95 transition-all" @click="openAttachment(m.attachment_url)">
                            </div>

                            <span class="text-[9px] text-right self-end opacity-70">
                                {{ m.created_at ? m.created_at.split(' ')[1].slice(0,5) : '' }}
                            </span>
                        </div>
                    </div>

                    <div v-if="messages.length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 space-y-2">
                        <i class="fab fa-viber text-5xl opacity-40"></i>
                        <p class="text-xs">Сообщений пока нет. Напишите первым!</p>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900">
                    <div v-if="attachedPhoto" class="mb-2 p-2 bg-blue-50 dark:bg-blue-900 rounded-lg flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <img :src="attachedPhoto.url" class="w-10 h-10 rounded object-cover">
                            <span class="text-xs text-gray-700 dark:text-gray-200 truncate max-w-xs">{{ attachedPhoto.name }}</span>
                        </div>
                        <button @click="attachedPhoto = null" type="button" class="text-red-500 hover:text-red-700 text-sm p-1"><i class="fas fa-times-circle"></i></button>
                    </div>

                    <form @submit.prevent="sendMessage" class="flex items-center space-x-2">
                        <label class="cursor-pointer text-gray-400 hover:text-primary p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all relative">
                            <i class="fas fa-camera text-sm"></i>
                            <input type="file" accept="image/*" @change="uploadChatPhoto" class="hidden">
                            <span v-if="isUploading" class="absolute inset-0 bg-white dark:bg-gray-900 bg-opacity-75 flex items-center justify-center rounded-full">
                                <i class="fas fa-spinner animate-spin text-primary"></i>
                            </span>
                        </label>

                        <input v-model="messageText" type="text" placeholder="Напишите сообщение..." class="flex-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-xs focus:outline-none focus:border-primary dark:text-white" :required="!attachedPhoto">

                        <button type="submit" class="bg-primary hover:bg-blue-600 text-white p-2.5 rounded-full shadow-md transition-all flex items-center justify-center">
                            <i class="fas fa-paper-plane text-xs"></i>
                        </button>
                    </form>
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
