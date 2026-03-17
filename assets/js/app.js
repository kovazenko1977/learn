document.addEventListener('DOMContentLoaded', () => {
    const state = {
        user: null,
        view: 'chat',
        messages: [],
        tasks: [],
        achievements: [],
        shopping: [],
        settings: {
            theme: 'light',
            accentColor: '#3b82f6',
            font: "'Segoe UI', sans-serif",
            fontSize: '16px',
            sounds: true,
            push: false
        },
        users: [],
        currentTaskId: null,
        pollingInterval: null
    };

    // DOM Elements
    const screens = {
        login: document.getElementById('login-screen'),
        main: document.getElementById('main-screen')
    };

    const views = {
        chat: document.getElementById('chat-view'),
        tasks: document.getElementById('tasks-view'),
        achievements: document.getElementById('achievements-view'),
        shopping: document.getElementById('shopping-view'),
        settings: document.getElementById('settings-view')
    };

    const loginInput = document.getElementById('passcode');
    const loginBtn = document.getElementById('login-btn');
    const loginError = document.getElementById('login-error');

    const chatContainer = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const sendChatBtn = document.getElementById('send-chat-btn');
    const imageUpload = document.getElementById('image-upload');

    const taskList = document.getElementById('task-list');
    const newTaskBtn = document.getElementById('new-task-btn');
    const newTaskModal = document.getElementById('new-task-modal');
    const confirmNewTaskBtn = document.getElementById('confirm-new-task');
    const taskModal = document.getElementById('task-modal');
    const taskDetailContent = document.getElementById('task-detail-content');

    const shoppingList = document.getElementById('shopping-list');
    const shoppingInput = document.getElementById('shopping-input');
    const addShoppingBtn = document.getElementById('add-shopping-btn');

    const achievementsList = document.getElementById('achievements-list');
    const userManagementList = document.getElementById('user-management-list');
    const myNameInput = document.getElementById('setting-my-name');
    const saveProfileBtn = document.getElementById('save-profile-btn');
    const addUserNameInput = document.getElementById('add-user-name');
    const addUserPasscodeInput = document.getElementById('add-user-passcode');
    const addUserBtn = document.getElementById('add-user-btn');
    const installPwaBtn = document.getElementById('install-pwa-btn');

    const navItems = document.querySelectorAll('.nav-item[data-view]');
    const viewTitle = document.getElementById('view-title');
    const logoutBtn = document.getElementById('logout-btn');
    const logoutBtnSidebar = document.getElementById('logout-btn-sidebar');

    // --- Authentication ---
    async function checkAuth() {
        const resp = await fetch('api/auth.php?action=check');
        const data = await resp.json();
        if (data.logged_in) {
            state.user = data.user;
            showScreen('main');
            initApp();
        } else {
            showScreen('login');
        }
    }

    async function login() {
        const passcode = loginInput.value;
        if (!/^\d{6}$/.test(passcode)) {
            loginError.textContent = 'Введите 6 цифр';
            return;
        }

        const formData = new FormData();
        formData.append('passcode', passcode);

        const resp = await fetch('api/auth.php?action=login', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            state.user = data.user;
            showScreen('main');
            initApp();
        } else {
            loginError.textContent = data.error;
        }
    }

    async function logout() {
        await fetch('api/auth.php?action=logout');
        location.reload();
    }

    // --- Initialization ---
    async function initApp() {
        const preloader = document.getElementById('global-preloader');
        if (preloader) preloader.style.display = 'flex';

        document.getElementById('current-username').textContent = state.user.username;
        if (state.user.avatar) {
            document.getElementById('current-avatar').style.backgroundImage = `url(${state.user.avatar})`;
        }

        await loadSettings();
        applySettings();
        switchView(state.view);

        startPolling();

        if (preloader) {
            preloader.style.opacity = '0';
            setTimeout(() => preloader.style.display = 'none', 500);
        }
    }

    function showScreen(screenId) {
        Object.values(screens).forEach(s => s.classList.remove('active'));
        screens[screenId].classList.add('active');
    }

    function switchView(viewId) {
        state.view = viewId;
        Object.values(views).forEach(v => v.classList.remove('active'));
        views[viewId].classList.add('active');

        navItems.forEach(item => {
            item.classList.toggle('active', item.dataset.view === viewId);
        });

        const titles = {
            chat: 'Сообщения',
            tasks: 'Проекты',
            achievements: 'Рейтинг',
            shopping: 'Покупки',
            settings: 'Опции'
        };
        viewTitle.textContent = titles[viewId] || 'Приложение';

        if (viewId === 'chat') {
            loadMessages();
            setTimeout(() => chatContainer.scrollTop = chatContainer.scrollHeight, 100);
        } else if (viewId === 'tasks') {
            loadTasks();
        } else if (viewId === 'shopping') {
            loadShopping();
        } else if (viewId === 'achievements') {
            loadAchievements();
        } else if (viewId === 'settings') {
            loadUsers();
        }
    }

    // --- Chat ---
    async function loadMessages() {
        try {
            const resp = await fetch('api/chat.php?action=read');
            const messages = await resp.json();

            const isAtBottom = chatContainer.scrollHeight - chatContainer.scrollTop <= chatContainer.clientHeight + 100;

            if (JSON.stringify(messages) !== JSON.stringify(state.messages)) {
                const oldLength = state.messages.length;
                state.messages = messages;

                if (state.view === 'chat') {
                    renderMessages();
                    if (isAtBottom) {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }
                }

                if (oldLength > 0 && messages.length > oldLength) {
                    const lastMsg = messages[messages.length - 1];
                    if (lastMsg.user_id !== state.user.id) {
                        if (state.settings.sounds) playNotifySound();
                        showBrowserNotification(`Новое сообщение от ${lastMsg.username}`, lastMsg.message || 'Изображение');
                    }
                }
            }
        } catch (e) {
            console.error("Failed to load messages", e);
        }
    }

    function escapeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderMessages() {
        if (state.view !== 'chat') return;
        chatContainer.innerHTML = '';
        state.messages.forEach(msg => {
            const isOwn = msg.user_id === state.user.id;
            const div = document.createElement('div');
            div.className = `message ${isOwn ? 'sent' : 'received'}`;

            const time = new Date(msg.timestamp * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            let content = `
                <div class="msg-info">
                    ${isOwn ? '' : `<span>${escapeHTML(msg.username)}</span>`}
                    <span>${time}</span>
                </div>
                <div class="msg-content">
                    ${msg.message ? `<div class="msg-text">${escapeHTML(msg.message)}</div>` : ''}
                    ${msg.image ? `<img src="${msg.image}" class="msg-image" onclick="window.open('${msg.image}')">` : ''}
                </div>`;
            div.innerHTML = content;
            chatContainer.appendChild(div);
        });
    }

    async function sendMessage() {
        const text = chatInput.value.trim();
        const file = imageUpload.files[0];

        if (!text && !file) return;

        const formData = new FormData();
        formData.append('message', text);
        if (file) formData.append('image', file);

        chatInput.value = '';
        imageUpload.value = '';

        const resp = await fetch('api/chat.php?action=send', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            loadMessages();
        }
    }

    // --- Tasks ---
    async function loadTasks() {
        const resp = await fetch('api/tasks.php?action=list');
        state.tasks = await resp.json();
        renderTasks();
    }

    function renderTasks() {
        taskList.innerHTML = '';
        state.tasks.forEach(task => {
            const completedSubtasks = (task.subtasks || []).filter(s => s.completed).length;
            const totalSubtasks = (task.subtasks || []).length;
            const progress = totalSubtasks > 0 ? (completedSubtasks / totalSubtasks) * 100 : (task.completed ? 100 : 0);

            const div = document.createElement('div');
            div.className = 'task-card';
            div.innerHTML = `
                <h3>${escapeHTML(task.title)}</h3>
                <p class="task-desc">${escapeHTML(task.description || 'Нет описания')}</p>
                <div class="task-progress-bar">
                    <div class="task-progress-fill" style="width: ${progress}%"></div>
                </div>
                <div class="task-meta">
                    <span>${completedSubtasks}/${totalSubtasks} этапов</span>
                    <span>${escapeHTML(task.username)}</span>
                </div>
            `;
            div.onclick = () => openTaskDetails(task.id);
            taskList.appendChild(div);
        });
    }

    async function createNewTask() {
        const title = document.getElementById('new-task-title').value;
        const description = document.getElementById('new-task-desc').value;

        if (!title) return;

        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);

        const resp = await fetch('api/tasks.php?action=create', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            newTaskModal.classList.remove('active');
            document.getElementById('new-task-title').value = '';
            document.getElementById('new-task-desc').value = '';
            loadTasks();
        }
    }

    function openTaskDetails(taskId) {
        state.currentTaskId = taskId;
        renderTaskDetail();
        taskModal.classList.add('active');
    }

    function renderTaskDetail() {
        const task = state.tasks.find(t => t.id === state.currentTaskId);
        if (!task) return;

        taskDetailContent.innerHTML = `
            <h2>${escapeHTML(task.title)}</h2>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">${escapeHTML(task.description || '')}</p>

            <div class="subtask-section">
                <h3>Под-этапы</h3>
                <div class="subtask-list">
                    ${(task.subtasks || []).map(sub => `
                        <div class="subtask-item ${sub.completed ? 'completed' : ''}" onclick="toggleSubtask('${sub.id}')">
                            <i class="far ${sub.completed ? 'fa-check-square' : 'fa-square'}"></i>
                            <span>${escapeHTML(sub.text)}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="input-group" style="display: flex; gap: 10px;">
                    <input type="text" id="new-subtask-input" placeholder="Новый этап..." style="flex:1; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-tertiary); color: var(--text-primary);">
                    <button class="btn-primary" onclick="addSubtask()">Добавить</button>
                </div>
            </div>

            <div class="task-comments">
                <h3>Обсуждение</h3>
                <div id="comment-list">
                    ${(task.comments || []).map(c => `
                        <div class="comment-item">
                            <div class="comment-header">
                                <span>${escapeHTML(c.username)}</span>
                                <span>${new Date(c.timestamp * 1000).toLocaleString()}</span>
                            </div>
                            <div class="comment-body">${escapeHTML(c.text)}</div>
                        </div>
                    `).join('')}
                </div>
                <div class="input-group" style="display: flex; gap: 10px; margin-top: 15px;">
                    <input type="text" id="new-comment-input" placeholder="Написать комментарий..." style="flex:1; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-tertiary); color: var(--text-primary);">
                    <button class="btn-primary" onclick="addComment()">Отправить</button>
                </div>
            </div>
        `;
    }

    window.toggleSubtask = async (subtaskId) => {
        const formData = new FormData();
        formData.append('task_id', state.currentTaskId);
        formData.append('subtask_id', subtaskId);
        await fetch('api/tasks.php?action=toggle_subtask', { method: 'POST', body: formData });
        await loadTasks();
        renderTaskDetail();
    };

    window.addSubtask = async () => {
        const input = document.getElementById('new-subtask-input');
        const text = input.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append('task_id', state.currentTaskId);
        formData.append('text', text);
        await fetch('api/tasks.php?action=add_subtask', { method: 'POST', body: formData });
        await loadTasks();
        renderTaskDetail();
    };

    window.addComment = async () => {
        const input = document.getElementById('new-comment-input');
        const text = input.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append('task_id', state.currentTaskId);
        formData.append('comment', text);
        await fetch('api/tasks.php?action=add_comment', { method: 'POST', body: formData });
        await loadTasks();
        renderTaskDetail();
    };

    // --- Shopping List ---
    async function loadShopping() {
        const resp = await fetch('api/tasks.php?action=shopping_list');
        state.shopping = await resp.json();
        renderShopping();
    }

    function renderShopping() {
        shoppingList.innerHTML = '';
        state.shopping.forEach(item => {
            const div = document.createElement('div');
            div.className = `shopping-item ${item.checked ? 'checked' : ''}`;
            div.innerHTML = `
                <i class="far ${item.checked ? 'fa-check-circle' : 'fa-circle'}" onclick="toggleShopping('${item.id}')"></i>
                <span style="flex-grow:1">${escapeHTML(item.text)}</span>
                <i class="fas fa-trash-alt" style="color:var(--error-color); cursor:pointer" onclick="deleteShopping('${item.id}')"></i>
            `;
            shoppingList.appendChild(div);
        });
    }

    window.addShopping = async () => {
        const text = shoppingInput.value.trim();
        if (!text) return;
        const formData = new FormData();
        formData.append('text', text);
        await fetch('api/tasks.php?action=shopping_add', { method: 'POST', body: formData });
        shoppingInput.value = '';
        loadShopping();
    };

    window.toggleShopping = async (id) => {
        const formData = new FormData();
        formData.append('id', id);
        await fetch('api/tasks.php?action=shopping_toggle', { method: 'POST', body: formData });
        loadShopping();
    };

    window.deleteShopping = async (id) => {
        const formData = new FormData();
        formData.append('id', id);
        await fetch('api/tasks.php?action=shopping_delete', { method: 'POST', body: formData });
        loadShopping();
    };

    // --- Achievements ---
    async function loadAchievements() {
        const resp = await fetch('api/tasks.php?action=achievements');
        state.achievements = await resp.json();
        renderAchievements();
    }

    function renderAchievements() {
        achievementsList.innerHTML = '';
        state.achievements.forEach(ach => {
            const div = document.createElement('div');
            div.className = `achievement-card ${ach.earned ? 'earned' : 'locked'}`;
            const progress = Math.min(100, (ach.progress / ach.threshold) * 100);

            div.innerHTML = `
                <div class="achievement-icon" style="color: ${ach.earned ? '#f59e0b' : '#64748b'}">
                    <i class="fas ${ach.icon || 'fa-medal'}"></i>
                </div>
                <div class="achievement-info">
                    <h4>${escapeHTML(ach.title)} ${ach.earned ? '🏆' : ''}</h4>
                    <p>${escapeHTML(ach.description)}</p>
                    <div class="achievement-progress-container">
                        <div class="achievement-progress-bar" style="width: ${progress}%"></div>
                    </div>
                    <div class="achievement-meta">
                        <span>${ach.progress} / ${ach.threshold}</span>
                        <span class="role-badge">${ach.points} очков</span>
                    </div>
                </div>
            `;
            achievementsList.appendChild(div);
        });
    }

    // --- Settings & User Management ---
    async function loadUsers() {
        const resp = await fetch('api/auth.php?action=users');
        state.users = await resp.json();
        renderUsers();
    }

    function renderUsers() {
        userManagementList.innerHTML = '';
        state.users.forEach(u => {
            const div = document.createElement('div');
            div.className = 'user-list-item';
            div.innerHTML = `
                <div class="avatar" style="width:30px; height:30px; background-image:url(${u.avatar})"></div>
                <div style="flex-grow:1">
                    <div>${escapeHTML(u.username)}</div>
                    <div style="font-size:10px; color:var(--text-secondary)">Код: ${u.passcode}</div>
                </div>
                <div class="role-badge ${u.role === 'admin' ? 'admin' : ''}">${u.role || 'Участник'}</div>
            `;
            userManagementList.appendChild(div);
        });
    }

    async function loadSettings() {
        const resp = await fetch('api/settings.php?action=read');
        state.settings = await resp.json();

        myNameInput.value = state.user.username;

        // Update UI inputs
        document.getElementById('setting-theme').value = state.settings.theme || 'dark';
        document.getElementById('setting-accent').value = state.settings.accentColor || '#0078d4';
        document.getElementById('setting-font').value = state.settings.font || "'Segoe UI', sans-serif";
        document.getElementById('setting-font-size').value = state.settings.fontSize || '14px';
        document.getElementById('setting-sounds').checked = state.settings.sounds !== false;
        document.getElementById('setting-push').checked = state.settings.push || false;
    }

    async function saveProfile() {
        const username = myNameInput.value.trim();
        if (!username) return;

        const formData = new FormData();
        formData.append('username', username);

        const resp = await fetch('api/auth.php?action=update_profile', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            state.user = data.user;
            document.getElementById('current-username').textContent = state.user.username;
            alert('Профиль обновлен');
        }
    }

    async function addUser() {
        const username = addUserNameInput.value.trim();
        const passcode = addUserPasscodeInput.value.trim();

        if (!username || !/^\d{6}$/.test(passcode)) {
            alert('Введите корректные данные (имя и 6 цифр кода)');
            return;
        }

        const formData = new FormData();
        formData.append('username', username);
        formData.append('passcode', passcode);

        const resp = await fetch('api/auth.php?action=add_user', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            addUserNameInput.value = '';
            addUserPasscodeInput.value = '';
            loadUsers();
            alert('Участник добавлен');
        } else {
            alert(data.error);
        }
    }

    async function saveSettings() {
        state.settings = {
            theme: document.getElementById('setting-theme').value,
            accentColor: document.getElementById('setting-accent').value,
            font: document.getElementById('setting-font').value,
            fontSize: document.getElementById('setting-font-size').value,
            sounds: document.getElementById('setting-sounds').checked,
            push: document.getElementById('setting-push').checked
        };

        await fetch('api/settings.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(state.settings)
        });

        applySettings();
        alert('Настройки сохранены');
    }

    function applySettings() {
        document.body.className = `theme-${state.settings.theme}`;
        document.documentElement.style.setProperty('--accent-color', state.settings.accentColor);
        document.documentElement.style.setProperty('--font-family', state.settings.font);
        document.documentElement.style.setProperty('--font-size', state.settings.fontSize);
        document.documentElement.style.setProperty('--accent-hover', state.settings.accentColor + 'dd');
    }

    // --- Utilities ---
    async function requestNotificationPermission() {
        if (!("Notification" in window)) return;
        if (Notification.permission !== "granted" && Notification.permission !== "denied") {
            await Notification.requestPermission();
        }
    }

    function showBrowserNotification(title, body) {
        if (state.settings.push && Notification.permission === "granted" && document.hidden) {
            new Notification(title, { body, icon: 'assets/img/icon-192.png' });
        }
    }

    function startPolling() {
        if (state.pollingInterval) clearInterval(state.pollingInterval);
        state.pollingInterval = setInterval(() => {
            if (!state.user) return;

            // Always poll messages for notifications
            loadMessages();

            if (state.view === 'tasks') loadTasks();
            else if (state.view === 'shopping') loadShopping();
        }, 3000);
    }

    function playNotifySound() {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3');
        audio.play().catch(e => {});
    }

    // --- PWA Install ---
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const installSection = document.getElementById('install-section');
        if (installSection) installSection.style.display = 'block';
    });

    if (installPwaBtn) {
        installPwaBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') deferredPrompt = null;
            }
        });
    }

    // --- Event Listeners ---
    loginBtn.addEventListener('click', login);
    loginInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') login(); });

    if (logoutBtn) logoutBtn.addEventListener('click', logout);
    if (logoutBtnSidebar) logoutBtnSidebar.addEventListener('click', logout);

    navItems.forEach(item => {
        item.addEventListener('click', () => switchView(item.dataset.view));
    });

    sendChatBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

    newTaskBtn.addEventListener('click', () => newTaskModal.classList.add('active'));
    confirmNewTaskBtn.addEventListener('click', createNewTask);

    addShoppingBtn.addEventListener('click', window.addShopping);
    shoppingInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') window.addShopping(); });

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            newTaskModal.classList.remove('active');
            taskModal.classList.remove('active');
        });
    });

    document.getElementById('save-settings-btn').addEventListener('click', () => {
        saveSettings();
        if (state.settings.push) requestNotificationPermission();
    });
    saveProfileBtn.addEventListener('click', saveProfile);
    addUserBtn.addEventListener('click', addUser);

    // Initial check
    checkAuth();
});
