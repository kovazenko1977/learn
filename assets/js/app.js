const API_URL = 'api/index.php';
let currentUser = null;
let currentView = 'chats';
let activeChatUserId = null;
let pollInterval = null;

// App Initialization
document.addEventListener('DOMContentLoaded', async () => {
    initAuth();
    initNavigation();
    await checkAuth();
});

function initAuth() {
    const loginTab = document.getElementById('login-tab');
    const registerTab = document.getElementById('register-tab');
    const authBtn = document.getElementById('auth-btn');
    let mode = 'login';

    loginTab.onclick = () => { mode = 'login'; loginTab.classList.add('active'); registerTab.classList.remove('active'); authBtn.innerText = 'Войти'; };
    registerTab.onclick = () => { mode = 'register'; registerTab.classList.add('active'); loginTab.classList.remove('active'); authBtn.innerText = 'Зарегистрироваться'; };

    authBtn.onclick = async () => {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        if (!username || !password) return alert('Заполните все поля');

        const response = await fetch(`${API_URL}?action=${mode}`, {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        const result = await response.json();

        if (result.success) {
            if (mode === 'register') {
                alert('Регистрация успешна! Теперь войдите.');
                loginTab.click();
            } else {
                currentUser = result.user;
                showMainView();
            }
        } else {
            alert(result.message);
        }
    };

    document.getElementById('logout-btn').onclick = async () => {
        await fetch(`${API_URL}?action=logout`);
        currentUser = null;
        showAuthView();
    };
}

async function checkAuth() {
    const response = await fetch(`${API_URL}?action=get_current_user`);
    const result = await response.json();
    if (result.success && result.user) {
        currentUser = result.user;
        showMainView();
    } else {
        showAuthView();
    }
}

function showAuthView() {
    document.getElementById('auth-view').classList.add('active');
    document.getElementById('main-view').classList.remove('active');
    stopPolling();
}

function showMainView() {
    document.getElementById('auth-view').classList.remove('active');
    document.getElementById('main-view').classList.add('active');
    renderView(currentView);
}

function initNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.onclick = () => {
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            currentView = item.dataset.view;
            renderView(currentView);
        };
    });
}

function renderView(view) {
    const container = document.getElementById('view-container');
    const title = document.getElementById('view-title');
    container.innerHTML = '';
    stopPolling();

    switch (view) {
        case 'chats':
            title.innerText = 'Чаты';
            renderUserList();
            break;
        case 'tasks':
            title.innerText = 'Задачи';
            renderTaskList();
            break;
        case 'settings':
            title.innerText = 'Настройки';
            renderSettings();
            break;
    }
}

// Messenger Logic
async function renderUserList() {
    const container = document.getElementById('view-container');
    const response = await fetch(`${API_URL}?action=list_users`);
    const result = await response.json();

    if (result.success) {
        result.users.forEach(user => {
            if (user.id === currentUser.id) return;
            const div = document.createElement('div');
            div.className = 'user-item';
            div.innerHTML = `
                <div class="avatar">${user.username[0].toUpperCase()}</div>
                <div class="user-info">
                    <strong>${user.username}</strong>
                </div>
            `;
            div.onclick = () => openChat(user);
            container.appendChild(div);
        });
    }
}

async function openChat(user) {
    activeChatUserId = user.id;
    const container = document.getElementById('view-container');
    document.getElementById('view-title').innerText = user.username;

    container.innerHTML = `
        <div id="chat-messages" style="display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto; padding-bottom: 80px;"></div>
        <div class="chat-input-area" style="position: fixed; bottom: calc(var(--safe-area-inset-bottom) + 60px); left: 0; right: 0; background: white;">
            <button id="upload-btn" class="icon-btn">📷</button>
            <input type="file" id="image-input" hidden accept="image/*">
            <input type="text" id="msg-input" placeholder="Сообщение...">
            <button id="send-btn" class="icon-btn">🚀</button>
        </div>
    `;

    document.getElementById('send-btn').onclick = sendMessage;
    document.getElementById('msg-input').onkeypress = (e) => { if(e.key === 'Enter') sendMessage(); };
    document.getElementById('upload-btn').onclick = () => document.getElementById('image-input').click();
    document.getElementById('image-input').onchange = handleImageUpload;

    await fetchChatHistory();
    startPolling(fetchChatHistory);
}

async function handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);

    const res = await fetch('api/upload.php', {
        method: 'POST',
        body: formData
    });
    const result = await res.json();

    if (result.success) {
        const response = await fetch(`${API_URL}?action=send_message`, {
            method: 'POST',
            body: JSON.stringify({ to_id: activeChatUserId, message: '', image: result.url })
        });
        if ((await response.json()).success) {
            fetchChatHistory();
        }
    } else {
        alert(result.message);
    }
}

async function sendMessage() {
    const input = document.getElementById('msg-input');
    const message = input.value.trim();
    if (!message) return;

    const response = await fetch(`${API_URL}?action=send_message`, {
        method: 'POST',
        body: JSON.stringify({ to_id: activeChatUserId, message })
    });

    if ((await response.json()).success) {
        input.value = '';
        fetchChatHistory();
    }
}

async function fetchChatHistory() {
    if (!activeChatUserId) return;
    const response = await fetch(`${API_URL}?action=get_chat_history&with_id=${activeChatUserId}`);
    const result = await response.json();
    if (result.success) {
        const chatBox = document.getElementById('chat-messages');
        if (!chatBox) return;

        chatBox.innerHTML = result.history.map(msg => `
            <div class="chat-bubble ${msg.from === currentUser.id ? 'sent' : 'received'}">
                ${msg.image ? `<img src="${msg.image}" style="max-width: 100%; border-radius: 10px; margin-bottom: 5px;">` : ''}
                ${msg.message ? `<div>${msg.message}</div>` : ''}
                <div style="font-size: 10px; opacity: 0.6; text-align: right; margin-top: 4px;">
                    ${new Date(msg.timestamp * 1000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    ${msg.from === currentUser.id ? (msg.read ? ' ✓✓' : ' ✓') : ''}
                </div>
            </div>
        `).join('');
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

function startPolling(fn) {
    stopPolling();
    pollInterval = setInterval(fn, 3000);
}

function stopPolling() {
    if (pollInterval) clearInterval(pollInterval);
}

// Task Management Logic
async function renderTaskList() {
    const container = document.getElementById('view-container');
    container.innerHTML = `
        <button id="new-list-btn" style="width: 100%; padding: 15px; border-radius: 15px; border: 2px dashed #ccc; background: none; margin-bottom: 20px;">+ Создать список задач</button>
        <div id="task-lists-container"></div>
    `;

    document.getElementById('new-list-btn').onclick = showCreateListModal;

    const response = await fetch(`${API_URL}?action=get_task_lists`);
    const result = await response.json();

    if (result.success) {
        const listsBox = document.getElementById('task-lists-container');
        result.lists.forEach(list => {
            const div = document.createElement('div');
            div.className = 'list-item';
            div.innerHTML = `
                <div style="flex: 1;">
                    <strong>${list.title}</strong>
                    <div style="font-size: 12px; opacity: 0.6;">Задач: ${list.tasks.length}</div>
                </div>
                <span>➡️</span>
            `;
            div.onclick = () => openTaskList(list.id);
            listsBox.appendChild(div);
        });
    }
}

async function showCreateListModal() {
    const response = await fetch(`${API_URL}?action=list_users`);
    const result = await response.json();
    const users = result.users.filter(u => u.id !== currentUser.id);

    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    overlay.classList.remove('hidden');

    content.innerHTML = `
        <h3>Новый список задач</h3>
        <input type="text" id="list-title" placeholder="Название списка" style="margin: 15px 0;">
        <p>Выберите участников:</p>
        <div style="max-height: 200px; overflow-y: auto; margin: 10px 0;">
            ${users.map(u => `
                <label style="display: flex; align-items: center; gap: 10px; padding: 8px 0;">
                    <input type="checkbox" class="user-select" value="${u.id}" style="width: auto;"> ${u.username}
                </label>
            `).join('')}
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button id="save-list-btn" style="flex: 1; padding: 12px; border-radius: 10px; background: var(--primary-color); color: white; border: none;">Создать</button>
            <button id="cancel-modal" style="flex: 1; padding: 12px; border-radius: 10px; background: #eee; border: none;">Отмена</button>
        </div>
    `;

    document.getElementById('cancel-modal').onclick = () => overlay.classList.add('hidden');
    document.getElementById('save-list-btn').onclick = async () => {
        const title = document.getElementById('list-title').value;
        const participant_ids = Array.from(document.querySelectorAll('.user-select:checked')).map(cb => cb.value);
        if (!title) return alert('Введите название');

        const res = await fetch(`${API_URL}?action=create_task_list`, {
            method: 'POST',
            body: JSON.stringify({ title, participant_ids })
        });
        if ((await res.json()).success) {
            overlay.classList.add('hidden');
            renderTaskList();
        }
    };
}

async function openTaskList(listId) {
    const response = await fetch(`${API_URL}?action=get_task_lists`);
    const result = await response.json();
    const list = result.lists.find(l => l.id === listId);
    if (!list) return;

    const container = document.getElementById('view-container');
    document.getElementById('view-title').innerText = list.title;

    container.innerHTML = `
        <div id="tasks-container" style="margin-bottom: 80px;">
            ${list.tasks.map(task => `
                <div class="task-item ${task.completed ? 'completed' : ''}">
                    <div class="checkbox ${task.completed ? 'checked' : ''}" onclick="toggleTask('${list.id}', '${task.id}')"></div>
                    <span style="flex: 1;">${task.text}</span>
                    ${task.author_id === currentUser.id ? `
                        <button onclick="editTaskPrompt('${list.id}', '${task.id}', '${task.text.replace(/'/g, "\\'")}')" style="background:none; border:none; font-size: 18px;">✏️</button>
                        <button onclick="deleteTask('${list.id}', '${task.id}')" style="background:none; border:none; font-size: 18px;">🗑️</button>
                    ` : ''}
                </div>
            `).join('')}
        </div>
        <div class="chat-input-area" style="position: fixed; bottom: calc(var(--safe-area-inset-bottom) + 60px); left: 0; right: 0; background: white;">
            <input type="text" id="task-input" placeholder="Добавить задачу...">
            <button id="add-task-btn" class="icon-btn">➕</button>
        </div>
    `;

    document.getElementById('add-task-btn').onclick = () => addTask(list.id);
    document.getElementById('task-input').onkeypress = (e) => { if(e.key === 'Enter') addTask(list.id); };
}

async function addTask(listId) {
    const input = document.getElementById('task-input');
    const text = input.value.trim();
    if (!text) return;

    const res = await fetch(`${API_URL}?action=add_task`, {
        method: 'POST',
        body: JSON.stringify({ list_id: listId, text })
    });
    if ((await res.json()).success) {
        input.value = '';
        openTaskList(listId);
    }
}

async function editTaskPrompt(listId, taskId, oldText) {
    const newText = prompt('Редактировать задачу:', oldText);
    if (newText === null || newText.trim() === '' || newText === oldText) return;

    const res = await fetch(`${API_URL}?action=edit_task`, {
        method: 'POST',
        body: JSON.stringify({ list_id: listId, task_id: taskId, text: newText.trim() })
    });
    if ((await res.json()).success) {
        openTaskList(listId);
    }
}

async function toggleTask(listId, taskId) {
    const res = await fetch(`${API_URL}?action=toggle_task`, {
        method: 'POST',
        body: JSON.stringify({ list_id: listId, task_id: taskId })
    });
    if ((await res.json()).success) {
        openTaskList(listId);
    }
}

async function deleteTask(listId, taskId) {
    if (!confirm('Удалить задачу?')) return;
    const res = await fetch(`${API_URL}?action=delete_task`, {
        method: 'POST',
        body: JSON.stringify({ list_id: listId, task_id: taskId })
    });
    if ((await res.json()).success) {
        openTaskList(listId);
    }
}

function renderSettings() {
    const container = document.getElementById('view-container');
    const settings = JSON.parse(localStorage.getItem('family_settings') || '{"theme":"light","fontSize":"16","accent":"#007aff"}');

    container.innerHTML = `
        <div style="background: var(--secondary-color); padding: 20px; border-radius: 20px;">
            <h3>Персонализация</h3>

            <div style="margin-top: 20px;">
                <label>Тема оформления:</label>
                <select id="theme-select" style="width: 100%; padding: 10px; border-radius: 10px; margin-top: 5px;">
                    <option value="light" ${settings.theme === 'light' ? 'selected' : ''}>Светлая</option>
                    <option value="dark" ${settings.theme === 'dark' ? 'selected' : ''}>Темная</option>
                </select>
            </div>

            <div style="margin-top: 20px;">
                <label>Размер шрифта: <span id="font-val">${settings.fontSize}</span>px</label>
                <input type="range" id="font-size" min="12" max="24" value="${settings.fontSize}" style="width: 100%;">
            </div>

            <div style="margin-top: 20px;">
                <label>Акцентный цвет:</label>
                <input type="color" id="accent-color" value="${settings.accent}" style="width: 100%; height: 40px; border: none; padding: 0; background: none; margin-top: 5px;">
            </div>
        </div>

        <div style="margin-top: 20px; background: var(--secondary-color); padding: 20px; border-radius: 20px;">
            <button id="save-settings" style="width: 100%; padding: 15px; border-radius: 15px; background: var(--primary-color); color: white; border: none; font-weight: bold;">Сохранить настройки</button>
        </div>
    `;

    document.getElementById('font-size').oninput = (e) => {
        document.getElementById('font-val').innerText = e.target.value;
    };

    document.getElementById('save-settings').onclick = () => {
        const newSettings = {
            theme: document.getElementById('theme-select').value,
            fontSize: document.getElementById('font-size').value,
            accent: document.getElementById('accent-color').value
        };
        localStorage.setItem('family_settings', JSON.stringify(newSettings));
        applySettings(newSettings);
        alert('Настройки сохранены');
    };
}

function applySettings(settings) {
    if (!settings) settings = JSON.parse(localStorage.getItem('family_settings') || '{"theme":"light","fontSize":"16","accent":"#007aff"}');

    const root = document.documentElement;
    root.style.setProperty('--font-size', settings.fontSize + 'px');
    root.style.setProperty('--accent-color', settings.accent);

    if (settings.theme === 'dark') {
        root.style.setProperty('--bg-color', '#000000');
        root.style.setProperty('--text-color', '#ffffff');
        root.style.setProperty('--secondary-color', '#1c1c1e');
        root.style.setProperty('--primary-color', '#ffffff');
    } else {
        root.style.setProperty('--bg-color', '#ffffff');
        root.style.setProperty('--text-color', '#000000');
        root.style.setProperty('--secondary-color', '#f8f8f8');
        root.style.setProperty('--primary-color', '#000000');
    }
}

// Global apply on load
applySettings();
