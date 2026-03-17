document.addEventListener('DOMContentLoaded', () => {
    const state = {
        user: null,
        view: 'chat',
        messages: [],
        tasks: [],
        settings: {
            theme: 'dark',
            accentColor: '#0078d4',
            font: "'Segoe UI', sans-serif",
            fontSize: '14px',
            sounds: true,
            push: false
        },
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

    const navItems = document.querySelectorAll('.nav-item[data-view]');
    const viewTitle = document.getElementById('view-title');

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
        document.getElementById('current-username').textContent = state.user.username;
        if (state.user.avatar) {
            document.getElementById('current-avatar').style.backgroundImage = `url(${state.user.avatar})`;
        }

        await loadSettings();
        applySettings();
        switchView(state.view);

        startPolling();
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

        const titles = { chat: 'Чат', tasks: 'Задачи', settings: 'Настройки' };
        viewTitle.textContent = titles[viewId];

        if (viewId === 'chat') {
            loadMessages();
            setTimeout(() => chatContainer.scrollTop = chatContainer.scrollHeight, 100);
        } else if (viewId === 'tasks') {
            loadTasks();
        }
    }

    // --- Chat ---
    async function loadMessages() {
        const resp = await fetch('api/chat.php?action=read');
        const messages = await resp.json();

        const isAtBottom = chatContainer.scrollHeight - chatContainer.scrollTop <= chatContainer.clientHeight + 100;

        if (JSON.stringify(messages) !== JSON.stringify(state.messages)) {
            const oldLength = state.messages.length;
            state.messages = messages;
            renderMessages();

            if (isAtBottom) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }

            if (oldLength > 0 && messages.length > oldLength && state.view !== 'chat' && state.settings.sounds) {
                playNotifySound();
            }
        }
    }

    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderMessages() {
        chatContainer.innerHTML = '';
        state.messages.forEach(msg => {
            const isOwn = msg.user_id === state.user.id;
            const div = document.createElement('div');
            div.className = `message ${isOwn ? 'own' : ''}`;

            let content = `<div class="msg-meta">${escapeHTML(msg.username)} • ${new Date(msg.timestamp * 1000).toLocaleTimeString()}</div>
                           <div class="msg-bubble">
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
            const completedSubtasks = task.subtasks.filter(s => s.completed).length;
            const totalSubtasks = task.subtasks.length;
            const progress = totalSubtasks > 0 ? (completedSubtasks / totalSubtasks) * 100 : 0;

            const div = document.createElement('div');
            div.className = 'task-card';
            div.innerHTML = `
                <h3>${task.title}</h3>
                <p>${task.description || 'Нет описания'}</p>
                <div class="task-progress">
                    <div class="progress-bar" style="width: ${progress}%"></div>
                </div>
                <div class="task-meta">
                    <span>${completedSubtasks}/${totalSubtasks} этапов</span>
                    <span>${task.username}</span>
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
                    ${task.subtasks.map(sub => `
                        <div class="subtask-item ${sub.completed ? 'completed' : ''}" onclick="toggleSubtask('${sub.id}')">
                            <i class="far ${sub.completed ? 'fa-check-square' : 'fa-square'}"></i>
                            <span>${escapeHTML(sub.text)}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="input-group" style="display: flex; gap: 10px;">
                    <input type="text" id="new-subtask-input" placeholder="Новый этап..." style="flex:1; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg-tertiary); color: var(--text-primary);">
                    <button class="btn-primary" onclick="addSubtask()">Добавить</button>
                </div>
            </div>

            <div class="task-comments">
                <h3>Обсуждение</h3>
                <div id="comment-list">
                    ${task.comments.map(c => `
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
                    <input type="text" id="new-comment-input" placeholder="Написать комментарий..." style="flex:1; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg-tertiary); color: var(--text-primary);">
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

    // --- Settings ---
    async function loadSettings() {
        const resp = await fetch('api/settings.php?action=read');
        state.settings = await resp.json();

        // Update UI inputs
        document.getElementById('setting-theme').value = state.settings.theme || 'dark';
        document.getElementById('setting-accent').value = state.settings.accentColor || '#0078d4';
        document.getElementById('setting-font').value = state.settings.font || "'Segoe UI', sans-serif";
        document.getElementById('setting-font-size').value = state.settings.fontSize || '14px';
        document.getElementById('setting-sounds').checked = state.settings.sounds !== false;
        document.getElementById('setting-push').checked = state.settings.push || false;
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

        // Calculate hover color (simpler version)
        document.documentElement.style.setProperty('--accent-hover', state.settings.accentColor + 'dd');
    }

    // --- Utilities ---
    function startPolling() {
        if (state.pollingInterval) clearInterval(state.pollingInterval);
        state.pollingInterval = setInterval(() => {
            if (state.view === 'chat') loadMessages();
            if (state.view === 'tasks') loadTasks();
        }, 3000);
    }

    function playNotifySound() {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3');
        audio.play().catch(e => {});
    }

    // --- Event Listeners ---
    loginBtn.addEventListener('click', login);
    loginInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') login(); });

    document.getElementById('logout-btn').addEventListener('click', logout);

    navItems.forEach(item => {
        item.addEventListener('click', () => switchView(item.dataset.view));
    });

    sendChatBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

    newTaskBtn.addEventListener('click', () => newTaskModal.classList.add('active'));
    confirmNewTaskBtn.addEventListener('click', createNewTask);

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            newTaskModal.classList.remove('active');
            taskModal.classList.remove('active');
        });
    });

    document.getElementById('save-settings-btn').addEventListener('click', saveSettings);

    // Initial check
    checkAuth();
});
