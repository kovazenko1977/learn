const API_URL = 'api/index.php';
let currentUser = null;
let currentView = 'chats';
let activeChatUserId = null;
let pollInterval = null;
let lastMessageCount = 0;

const playNotificationSound = () => {
    const context = new (window.AudioContext || window.webkitAudioContext)();
    const playPop = (delay, freq, volume) => {
        const osc = context.createOscillator();
        const gain = context.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, context.currentTime + delay);
        gain.gain.setValueAtTime(volume, context.currentTime + delay);
        gain.gain.exponentialRampToValueAtTime(0.01, context.currentTime + delay + 0.1);
        osc.connect(gain);
        gain.connect(context.destination);
        osc.start(context.currentTime + delay);
        osc.stop(context.currentTime + delay + 0.1);
    };
    playPop(0, 1046, 0.1);
    playPop(0.08, 1318, 0.08);
};

function escapeHTML(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
    document.getElementById('header-username').innerText = currentUser.username;
    const navAdmin = document.getElementById('nav-admin');
    if (currentUser && currentUser.role === 'admin') {
        navAdmin.classList.remove('hidden');
    } else {
        navAdmin.classList.add('hidden');
    }
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
        case 'chats': title.innerText = 'Чаты'; renderUserList(); break;
        case 'tasks': title.innerText = 'Задачи'; renderTaskList(); break;
        case 'shopping': title.innerText = 'Покупки'; renderShoppingList(); break;
        case 'achievements': title.innerText = 'Награды'; renderAchievements(); break;
        case 'events': title.innerText = 'События'; renderEvents(); break;
        case 'settings': title.innerText = 'Настройки'; renderSettings(); break;
        case 'about': title.innerText = 'О Жанне'; renderAbout(); break;
        case 'admin': title.innerText = 'Панель Администратора'; renderAdmin(); break;
    }
}

function renderAbout() {
    const container = document.getElementById('view-container');
    container.innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <div style="font-size: 80px; margin-bottom: 20px;">❤️</div>
            <h1 style="font-size: 32px; margin-bottom: 10px;">Жанна</h1>
            <p style="font-size: 18px; line-height: 1.6; color: #666;">
                Разработана и посвящается моей любимой жене Жанне 2026г.
            </p>
            <div style="margin-top: 40px; font-size: 14px; opacity: 0.5;">
                Версия 5.0 "Ultimate Edition"
            </div>
        </div>
    `;
}

// Shopping Logic
async function renderShoppingList() {
    const container = document.getElementById('view-container');
    container.innerHTML = `
        <div id="shopping-container" style="padding-bottom: 250px;"></div>
        <button id="clear-shop-btn" style="width: 100%; padding: 18px; margin-top: 20px; border-radius: 20px; border: none; background: #f2f2f7; color: #ff3b30; font-weight: 600;">Очистить купленное</button>
        <div class="chat-input-area">
            <input type="text" id="shop-input" placeholder="Что купить?">
            <button id="add-shop-btn" class="icon-btn">➕</button>
        </div>
    `;
    document.getElementById('add-shop-btn').onclick = addShoppingItem;
    document.getElementById('shop-input').onkeypress = (e) => { if(e.key === 'Enter') addShoppingItem(); };
    document.getElementById('clear-shop-btn').onclick = clearShoppingList;
    await fetchShoppingList();
}

async function addShoppingItem() {
    const input = document.getElementById('shop-input');
    const text = input.value.trim();
    if (!text) return;
    await fetch(`${API_URL}?action=add_shopping_item`, { method: 'POST', body: JSON.stringify({ text }) });
    input.value = '';
    fetchShoppingList();
}

async function fetchShoppingList() {
    const response = await fetch(`${API_URL}?action=get_shopping_list`);
    const result = await response.json();
    if (result.success) {
        const box = document.getElementById('shopping-container');
        box.innerHTML = result.items.map(item => `
            <div class="task-item ${item.completed ? 'completed' : ''}">
                <div class="checkbox ${item.completed ? 'checked' : ''}" onclick="toggleShoppingItem('${item.id}')"></div>
                <span style="flex: 1;" onclick="toggleShoppingItem('${item.id}')">${escapeHTML(item.text)}</span>
                ${(item.user_id === currentUser.id || currentUser.role === 'admin') ? `
                    <button onclick="deleteShoppingItem('${item.id}')" style="background:none; border:none; font-size: 18px; padding: 5px;">🗑️</button>
                ` : ''}
            </div>
        `).join('');
    }
}

async function deleteShoppingItem(id) {
    if (!confirm('Удалить этот пункт?')) return;
    const res = await fetch(`${API_URL}?action=delete_shopping_item`, { method: 'POST', body: JSON.stringify({ id }) });
    if ((await res.json()).success) fetchShoppingList();
}

async function toggleShoppingItem(id) {
    await fetch(`${API_URL}?action=toggle_shopping_item`, { method: 'POST', body: JSON.stringify({ id }) });
    fetchShoppingList();
}

async function clearShoppingList() {
    if (!confirm('Очистить все выполненные пункты?')) return;
    await fetch(`${API_URL}?action=clear_shopping_list`, { method: 'POST' });
    fetchShoppingList();
}

// Contacts Logic
async function renderUserList() {
    const container = document.getElementById('view-container');
    container.innerHTML = `
        <div style="background: white; padding: 10px 15px; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; z-index: 10; display: flex; gap: 10px;">
            <input type="text" id="user-search" placeholder="Поиск..." style="padding: 10px 15px; border-radius: 10px; font-size: 14px; background: #f0f0f5; flex: 1;">
            <button id="create-group-btn" class="icon-btn">👥</button>
        </div>
        <div id="groups-box"></div>
        <div id="users-box"></div>
    `;

    document.getElementById('create-group-btn').onclick = showCreateGroupModal;

    const [uRes, gRes] = await Promise.all([
        fetch(`${API_URL}?action=list_users`),
        fetch(`${API_URL}?action=list_groups`)
    ]);
    const uResult = await uRes.json();
    const gResult = await gRes.json();

    if (uResult.success) {
        const render = (filter = '') => {
            const gBox = document.getElementById('groups-box');
            const uBox = document.getElementById('users-box');
            gBox.innerHTML = ''; uBox.innerHTML = '';

            // Saved Messages
            if (!filter || 'избранное'.includes(filter.toLowerCase())) {
                const div = document.createElement('div');
                div.className = 'user-item';
                div.innerHTML = `<div class="avatar" style="background: #5d5d5d;">🔖</div><div class="user-info"><strong>Избранное</strong><div style="font-size: 12px; opacity: 0.6;">Ваши заметки</div></div>`;
                div.onclick = () => openChat({ id: currentUser.id, username: 'Избранное' });
                gBox.appendChild(div);
            }

            if (gResult.success) {
                gResult.groups.forEach(group => {
                    if (filter && !group.title.toLowerCase().includes(filter.toLowerCase())) return;
                    const div = document.createElement('div');
                    div.className = 'user-item';
                    div.innerHTML = `<div class="avatar" style="background: var(--primary-color);">👥</div><div class="user-info"><strong>${escapeHTML(group.title)}</strong><div style="font-size: 12px; opacity: 0.6;">Группа (${group.participants.length} уч.)</div></div>`;
                    div.onclick = () => openChat({ id: group.id, username: group.title, isGroup: true });
                    gBox.appendChild(div);
                });
            }

            uResult.users.forEach(user => {
                if (user.id === currentUser.id) return;
                if (filter && !user.username.toLowerCase().includes(filter.toLowerCase())) return;
                const isOnline = (Date.now() / 1000 - user.last_seen) < 60;
                const div = document.createElement('div');
                div.className = 'user-item';
                div.innerHTML = `
                    <div style="position: relative;">
                        <div class="avatar" style="background: #bdbdbd;">${user.avatar ? `<img src="${user.avatar}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">` : escapeHTML(user.username[0].toUpperCase())}</div>
                        ${isOnline ? '<div style="position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background: #4cd964; border: 2px solid white; border-radius: 50%;"></div>' : ''}
                    </div>
                    <div class="user-info" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between;"><strong>${escapeHTML(user.username)}</strong><span style="font-size: 11px; color: ${isOnline ? '#4cd964' : '#9e9e9e'};">${isOnline ? 'в сети' : 'не в сети'}</span></div>
                        <div style="font-size: 13px; color: #757575; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHTML(user.status || 'Привет!')}</div>
                    </div>
                `;
                div.onclick = () => openChat(user);
                uBox.appendChild(div);
            });
        };
        render();
        document.getElementById('user-search').oninput = (e) => render(e.target.value);
    }
}

async function showCreateGroupModal() {
    const res = await fetch(`${API_URL}?action=list_users`);
    const result = await res.json();
    const users = result.users.filter(u => u.id !== currentUser.id);
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    overlay.classList.remove('hidden');
    content.innerHTML = `
        <h3>Создать группу</h3>
        <input type="text" id="group-title" placeholder="Название группы" style="margin: 15px 0;">
        <div style="max-height: 200px; overflow-y: auto;">
            ${users.map(u => `<label style="display: flex; align-items: center; gap: 10px; padding: 8px 0;"><input type="checkbox" class="group-user-select" value="${u.id}" style="width: auto;"> ${u.username}</label>`).join('')}
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button id="save-group-btn" style="flex: 1; padding: 12px; border-radius: 10px; background: var(--primary-color); color: white; border: none;">Создать</button>
            <button onclick="document.getElementById('modal-overlay').classList.add('hidden')" style="flex: 1; padding: 12px; border-radius: 10px; background: #eee; border: none;">Отмена</button>
        </div>
    `;
    document.getElementById('save-group-btn').onclick = async () => {
        const title = document.getElementById('group-title').value;
        const participant_ids = Array.from(document.querySelectorAll('.group-user-select:checked')).map(cb => cb.value);
        if (!title) return alert('Введите название');
        const res = await fetch(`${API_URL}?action=create_group`, { method: 'POST', body: JSON.stringify({ title, participant_ids }) });
        if ((await res.json()).success) { overlay.classList.add('hidden'); renderUserList(); }
    };
}

// Messenger Main Logic
async function openChat(user) {
    activeChatUserId = user.id;
    lastMessageCount = 0;
    const container = document.getElementById('view-container');
    document.getElementById('view-title').innerText = user.username;

    container.innerHTML = `
        <div id="chat-header-actions" style="background: white; padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; gap: 10px; align-items: center;">
            <input type="text" id="chat-search" placeholder="Поиск в чате..." style="padding: 5px 12px; border-radius: 8px; font-size: 13px; background: #f2f2f7; flex: 1;">
            <button id="mute-btn" class="icon-btn" style="width:30px; height:30px; font-size:16px;">🔔</button>
        </div>
        <div id="typing-indicator" style="background: rgba(255,255,255,0.8); padding: 5px 15px; font-size: 11px; font-style: italic; color: var(--primary-color); display: none;">Печатает...</div>
        <div id="chat-messages" style="display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto; padding-bottom: 250px; background: var(--chat-bg);"></div>
        <div class="chat-input-area">
            <button id="more-btn" class="icon-btn">➕</button>
            <input type="file" id="file-input" hidden>
            <input type="text" id="msg-input" placeholder="Сообщение...">
            <button id="voice-btn" class="icon-btn">🎤</button>
            <button id="send-btn" class="icon-btn">🚀</button>
            <div id="more-menu" class="hidden" style="position: absolute; bottom: 100%; left: 0; background: white; border: 1px solid #ddd; border-radius: 12px; padding: 10px; display: flex; gap: 10px; box-shadow: 0 -5px 15px rgba(0,0,0,0.1);">
                <button onclick="document.getElementById('file-input').accept='image/*'; document.getElementById('file-input').click()" class="icon-btn">📷</button>
                <button onclick="document.getElementById('file-input').accept='*/*'; document.getElementById('file-input').click()" class="icon-btn">📄</button>
                <button onclick="shareLocation()" class="icon-btn">📍</button>
            </div>
        </div>
    `;

    document.getElementById('send-btn').onclick = sendMessage;
    document.getElementById('msg-input').onkeypress = (e) => {
        if(e.key === 'Enter') sendMessage();
        else reportTyping();
    };
    document.getElementById('more-btn').onclick = () => document.getElementById('more-menu').classList.toggle('hidden');
    document.getElementById('file-input').onchange = handleFileUpload;
    document.getElementById('voice-btn').onmousedown = startVoiceRecording;
    document.getElementById('voice-btn').onmouseup = stopVoiceRecording;
    document.getElementById('voice-btn').ontouchstart = (e) => { e.preventDefault(); startVoiceRecording(); };
    document.getElementById('voice-btn').ontouchend = (e) => { e.preventDefault(); stopVoiceRecording(); };

    const mutes = JSON.parse(localStorage.getItem('chat_mutes') || '{}');
    const muteBtn = document.getElementById('mute-btn');
    if (mutes[activeChatUserId]) muteBtn.innerText = '🔕';
    muteBtn.onclick = () => {
        const mutes = JSON.parse(localStorage.getItem('chat_mutes') || '{}');
        if (mutes[activeChatUserId]) delete mutes[activeChatUserId];
        else mutes[activeChatUserId] = true;
        localStorage.setItem('chat_mutes', JSON.stringify(mutes));
        muteBtn.innerText = mutes[activeChatUserId] ? '🔕' : '🔔';
    };

    document.getElementById('chat-search').oninput = (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.chat-bubble').forEach(bubble => {
            const visible = !term || bubble.innerText.toLowerCase().includes(term);
            bubble.style.display = visible ? 'block' : 'none';
        });
    };

    await fetchChatHistory();
    startPolling(fetchChatHistory);
}

async function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    const isImage = file.type.startsWith('image/');
    const formData = new FormData();
    formData.append(isImage ? 'image' : 'file', file);
    document.getElementById('more-menu').classList.add('hidden');
    const res = await fetch('api/upload.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) {
        const body = { to_id: activeChatUserId, message: '' };
        if (isImage) body.image = result.url; else body.file = result.url;
        await fetch(`${API_URL}?action=send_message`, { method: 'POST', body: JSON.stringify(body) });
        fetchChatHistory();
    }
}

function shareLocation() {
    document.getElementById('more-menu').classList.add('hidden');
    if (!navigator.geolocation) return alert('Геолокация не поддерживается');
    navigator.geolocation.getCurrentPosition(async (pos) => {
        const location = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        await fetch(`${API_URL}?action=send_message`, { method: 'POST', body: JSON.stringify({ to_id: activeChatUserId, message: '', location }) });
        fetchChatHistory();
    });
}

let mediaRecorder;
let audioChunks = [];
async function startVoiceRecording() {
    const btn = document.getElementById('voice-btn');
    btn.style.background = '#ff3b30'; btn.style.color = 'white';
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        mediaRecorder.ondataavailable = (e) => audioChunks.push(e.data);
        mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const formData = new FormData();
            formData.append('audio', audioBlob, 'voice.webm');
            const res = await fetch('api/upload.php', { method: 'POST', body: formData });
            const result = await res.json();
            if (result.success) {
                await fetch(`${API_URL}?action=send_message`, { method: 'POST', body: JSON.stringify({ to_id: activeChatUserId, message: '', voice: result.url }) });
                fetchChatHistory();
            }
        };
        mediaRecorder.start();
    } catch (e) { alert('Доступ к микрофону запрещен'); }
}

function stopVoiceRecording() {
    const btn = document.getElementById('voice-btn');
    btn.style.background = ''; btn.style.color = '';
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(t => t.stop());
    }
}

let typingTimer;
function reportTyping() {
    clearTimeout(typingTimer);
    fetch(`${API_URL}?action=update_profile`, { method: 'POST', body: JSON.stringify({ typing_in: activeChatUserId }) });
    typingTimer = setTimeout(() => {
        fetch(`${API_URL}?action=update_profile`, { method: 'POST', body: JSON.stringify({ typing_in: null }) });
    }, 3000);
}

async function sendMessage() {
    const input = document.getElementById('msg-input');
    const message = input.value.trim();
    if (!message && !window.replyToMsgId) return;
    const body = { to_id: activeChatUserId, message };
    if (window.replyToMsgId) {
        body.reply_to = window.replyToMsgId;
        window.replyToMsgId = null;
        document.getElementById('reply-preview')?.remove();
    }
    const response = await fetch(`${API_URL}?action=send_message`, { method: 'POST', body: JSON.stringify(body) });
    if ((await response.json()).success) { input.value = ''; fetchChatHistory(); }
}

async function fetchChatHistory() {
    if (!activeChatUserId) return;
    const action = activeChatUserId.startsWith('group_') ? 'group_id' : 'with_id';
    const response = await fetch(`${API_URL}?action=get_chat_history&${action}=${activeChatUserId}`);
    const result = await response.json();

    const usersRes = await fetch(`${API_URL}?action=list_users`);
    const usersResult = await usersRes.json();
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator && usersResult.success) {
        const isTyping = usersResult.users.some(u => u.typing_in === currentUser.id && u.id === activeChatUserId);
        typingIndicator.style.display = isTyping ? 'block' : 'none';
    }

    if (result.success) {
        if (result.history.length > lastMessageCount) {
            const lastMsg = result.history[result.history.length - 1];
            const mutes = JSON.parse(localStorage.getItem('chat_mutes') || '{}');
            if (lastMsg.from !== currentUser.id && lastMessageCount > 0 && !mutes[activeChatUserId]) playNotificationSound();
            lastMessageCount = result.history.length;
        }

        const chatBox = document.getElementById('chat-messages');
        if (!chatBox) return;

        const pinnedMsg = result.history.find(m => m.pinned);
        let pinnedHtml = '';
        if (pinnedMsg) {
            pinnedHtml = `<div style="background: white; padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; display: flex; align-items: center; gap: 10px;"><span>📌</span><div style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHTML(pinnedMsg.message || 'Вложение')}</div></div>`;
        }

        chatBox.innerHTML = pinnedHtml + result.history.map(msg => `
            <div class="chat-bubble ${msg.from === currentUser.id ? 'sent' : 'received'}" style="position: relative; margin-bottom: 12px;" onclick="showMessageMenu('${msg.id}', '${msg.from}', \`${escapeHTML(msg.message).replace(/`/g, '\\`')}\`)">
                ${currentUser.role === 'admin' ? `<button onclick="event.stopPropagation(); adminDeleteMessage('${msg.id}')" style="position: absolute; top: -10px; right: -10px; background: #ff3b30; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 10px; cursor: pointer; z-index: 10;">×</button>` : ''}
                ${msg.reply_to ? `<div style="background: rgba(0,0,0,0.05); padding: 5px 8px; border-radius: 8px; margin-bottom: 5px; font-size: 12px; border-left: 3px solid var(--primary-color);">Ответ...</div>` : ''}
                ${msg.image ? `<img src="${escapeHTML(msg.image)}" style="max-width: 100%; border-radius: 10px; margin-bottom: 5px;">` : ''}
                ${msg.voice ? `<audio src="${escapeHTML(msg.voice)}" controls style="width:100%; max-width:200px; height:30px; margin-bottom:5px;"></audio>` : ''}
                ${msg.file ? `<div style="display:flex; align-items:center; gap:10px; background:rgba(0,0,0,0.05); padding:10px; border-radius:10px;">📄 <a href="${escapeHTML(msg.file)}" target="_blank" style="color:inherit; font-size:13px; text-decoration:none;">Открыть файл</a></div>` : ''}
                ${msg.location ? `<div style="padding:5px;"><a href="https://www.google.com/maps?q=${msg.location.lat},${msg.location.lng}" target="_blank" style="color:var(--primary-color); text-decoration:none;">📍 Местоположение</a></div>` : ''}
                ${msg.message ? `<div>${escapeHTML(msg.message)}</div>` : ''}
                ${msg.reactions ? `<div style="display:flex; gap:3px; margin-top:5px;">${Object.values(msg.reactions).map(r => `<span style="background:rgba(0,0,0,0.05); padding:2px 5px; border-radius:8px; font-size:12px;">${r}</span>`).join('')}</div>` : ''}
                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 4px; font-size: 10px; opacity: 0.6; margin-top: 4px; color: ${msg.from === currentUser.id ? '#6c63ff' : '#757575'};">
                    ${msg.starred_by && msg.starred_by.includes(currentUser.id) ? '<span>⭐️</span>' : ''}
                    ${msg.edited ? '<span style="font-style: italic;">ред. </span>' : ''}
                    <span>${new Date(msg.timestamp * 1000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    ${msg.from === currentUser.id ? (msg.read ? ' <span style="font-weight:bold; color: #7360f2; font-size: 12px;">✓✓</span>' : ' <span style="font-size: 12px;">✓</span>') : ''}
                </div>
            </div>
        `).join('');
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

function showMessageMenu(id, fromId, text) {
    const isMine = fromId === currentUser.id;
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    overlay.classList.remove('hidden');
    content.innerHTML = `
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 10px; padding: 10px; background: #f2f2f7; border-radius: 12px; justify-content: space-around; font-size: 24px;">
                <span onclick="toggleReaction('${id}', '👍')">👍</span><span onclick="toggleReaction('${id}', '❤️')">❤️</span><span onclick="toggleReaction('${id}', '😂')">😂</span><span onclick="toggleReaction('${id}', '😮')">😮</span><span onclick="toggleReaction('${id}', '😢')">😢</span><span onclick="toggleReaction('${id}', '🔥')">🔥</span>
            </div>
            <button onclick="replyToMessage('${id}', \`${text}\`)" style="padding: 15px; border-radius: 12px; border: none; background: #f2f2f7; text-align: left;">↩️ Ответить</button>
            <button onclick="toggleStar('${id}')" style="padding: 15px; border-radius: 12px; border: none; background: #f2f2f7; text-align: left;">⭐️ Избранное</button>
            <button onclick="togglePin('${id}')" style="padding: 15px; border-radius: 12px; border: none; background: #f2f2f7; text-align: left;">📌 Закрепить/Открепить</button>
            <button onclick="forwardMessage(\`${text}\`)" style="padding: 15px; border-radius: 12px; border: none; background: #f2f2f7; text-align: left;">➡️ Переслать</button>
            ${isMine ? `<button onclick="editMessagePrompt('${id}', \`${text}\`)" style="padding: 15px; border-radius: 12px; border: none; background: #f2f2f7; text-align: left;">✏️ Редактировать</button><button onclick="deleteMessageEveryone('${id}')" style="padding: 15px; border-radius: 12px; border: none; background: #fff1f0; color: #ff4d4f; text-align: left;">🗑️ Удалить у всех</button>` : ''}
            <button onclick="document.getElementById('modal-overlay').classList.add('hidden')" style="padding: 15px; border-radius: 12px; border: none; background: #eee; margin-top: 10px;">Отмена</button>
        </div>
    `;
}

function replyToMessage(id, text) {
    window.replyToMsgId = id; document.getElementById('modal-overlay').classList.add('hidden');
    const inputArea = document.querySelector('.chat-input-area');
    let preview = document.getElementById('reply-preview');
    if (!preview) {
        preview = document.createElement('div'); preview.id = 'reply-preview';
        preview.style.cssText = 'position: absolute; bottom: 100%; left: 0; right: 0; background: #f8f8f8; padding: 8px 15px; border-top: 1px solid #ddd; font-size: 12px; display: flex; justify-content: space-between; align-items: center;';
        inputArea.appendChild(preview);
    }
    preview.innerHTML = `<div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;"><strong>Ответ на:</strong> ${text}</div><button onclick="window.replyToMsgId=null; this.parentElement.remove()" style="border:none; background:none; font-size: 16px;">×</button>`;
    document.getElementById('msg-input').focus();
}

async function forwardMessage(text) {
    document.getElementById('modal-overlay').classList.add('hidden');
    const res = await fetch(`${API_URL}?action=list_users`);
    const result = await res.json();
    const users = result.users.filter(u => u.id !== currentUser.id);
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    overlay.classList.remove('hidden');
    content.innerHTML = `<h3>Переслать</h3><div style="max-height: 300px; overflow-y: auto;">${users.map(u => `<div onclick="doForward('${u.id}', \`${text}\`)" style="padding: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px;"><div class="avatar" style="width:30px; height:30px; font-size:12px;">${escapeHTML(u.username[0].toUpperCase())}</div>${escapeHTML(u.username)}</div>`).join('')}</div><button onclick="document.getElementById('modal-overlay').classList.add('hidden')" style="width:100%; padding: 12px; margin-top: 10px; border-radius: 10px; border:none; background:#eee;">Отмена</button>`;
}

async function doForward(toId, text) {
    await fetch(`${API_URL}?action=send_message`, { method: 'POST', body: JSON.stringify({ to_id: toId, message: `[Переслано]: ${text}` }) });
    document.getElementById('modal-overlay').classList.add('hidden');
}

async function editMessagePrompt(id, oldText) {
    document.getElementById('modal-overlay').classList.add('hidden');
    const newText = prompt('Редактировать:', oldText);
    if (!newText || newText === oldText) return;
    await fetch(`${API_URL}?action=edit_message`, { method: 'POST', body: JSON.stringify({ id, message: newText.trim() }) });
    fetchChatHistory();
}

async function toggleReaction(id, reaction) {
    await fetch(`${API_URL}?action=toggle_reaction`, { method: 'POST', body: JSON.stringify({ id, reaction }) });
    document.getElementById('modal-overlay').classList.add('hidden');
    fetchChatHistory();
}

async function togglePin(id) {
    await fetch(`${API_URL}?action=toggle_pin`, { method: 'POST', body: JSON.stringify({ id }) });
    document.getElementById('modal-overlay').classList.add('hidden');
    fetchChatHistory();
}

async function toggleStar(id) {
    await fetch(`${API_URL}?action=toggle_star`, { method: 'POST', body: JSON.stringify({ id }) });
    document.getElementById('modal-overlay').classList.add('hidden');
    fetchChatHistory();
}

async function deleteMessageEveryone(id) {
    document.getElementById('modal-overlay').classList.add('hidden');
    if (!confirm('Удалить для всех?')) return;
    await fetch(`${API_URL}?action=delete_message_everyone`, { method: 'POST', body: JSON.stringify({ id }) });
    fetchChatHistory();
}

function startPolling(fn) {
    stopPolling();
    pollInterval = setInterval(fn, 3500);
    document.addEventListener('visibilitychange', () => {
        stopPolling();
        pollInterval = setInterval(fn, document.hidden ? 15000 : 3500);
    });
}
function stopPolling() { if (pollInterval) clearInterval(pollInterval); }

// Task Management Logic
async function renderTaskList() {
    const container = document.getElementById('view-container');
    container.innerHTML = `<button id="new-list-btn" style="width: 100%; padding: 15px; border-radius: 15px; border: 2px dashed #ccc; background: none; margin-bottom: 20px;">+ Создать список задач</button><div id="task-lists-container"></div>`;
    document.getElementById('new-list-btn').onclick = showCreateListModal;
    const response = await fetch(`${API_URL}?action=get_task_lists`);
    const result = await response.json();
    if (result.success) {
        const box = document.getElementById('task-lists-container');
        result.lists.forEach(list => {
            const div = document.createElement('div');
            div.className = 'list-item';
            div.innerHTML = `<div style="flex: 1;"><strong>${escapeHTML(list.title)}</strong><div style="font-size: 12px; opacity: 0.6;">Задач: ${list.tasks.length}</div></div><span>➡️</span>`;
            div.onclick = () => openTaskList(list.id);
            box.appendChild(div);
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
    content.innerHTML = `<h3>Новый список задач</h3><input type="text" id="list-title" placeholder="Название" style="margin: 15px 0;"><div style="max-height: 200px; overflow-y: auto;">${users.map(u => `<label style="display: flex; align-items: center; gap: 10px; padding: 8px 0;"><input type="checkbox" class="user-select" value="${u.id}" style="width: auto;"> ${u.username}</label>`).join('')}</div><div style="display: flex; gap: 10px; margin-top: 20px;"><button id="save-list-btn" style="flex: 1; padding: 12px; border-radius: 10px; background: var(--primary-color); color: white; border: none;">Создать</button><button id="cancel-modal" style="flex: 1; padding: 12px; border-radius: 10px; background: #eee; border: none;">Отмена</button></div>`;
    document.getElementById('cancel-modal').onclick = () => overlay.classList.add('hidden');
    document.getElementById('save-list-btn').onclick = async () => {
        const title = document.getElementById('list-title').value;
        const participant_ids = Array.from(document.querySelectorAll('.user-select:checked')).map(cb => cb.value);
        if (!title) return alert('Введите название');
        const res = await fetch(`${API_URL}?action=create_task_list`, { method: 'POST', body: JSON.stringify({ title, participant_ids }) });
        if ((await res.json()).success) { overlay.classList.add('hidden'); renderTaskList(); }
    };
}

async function openTaskList(listId) {
    const response = await fetch(`${API_URL}?action=get_task_lists`);
    const result = await response.json();
    const list = result.lists.find(l => l.id === listId);
    if (!list) return;
    const container = document.getElementById('view-container');
    document.getElementById('view-title').innerText = list.title;
    container.innerHTML = `<div id="tasks-container" style="margin-bottom: 250px;">${list.tasks.map(task => `<div class="task-item ${task.completed ? 'completed' : ''}"><div class="checkbox ${task.completed ? 'checked' : ''}" onclick="toggleTask('${list.id}', '${task.id}')"></div><span style="flex: 1;">${escapeHTML(task.text)}</span>${(task.author_id === currentUser.id || currentUser.role === 'admin') ? `${task.author_id === currentUser.id ? `<button onclick="editTaskPrompt('${list.id}', '${task.id}', '${escapeHTML(task.text).replace(/'/g, "\\'")}')" style="background:none; border:none; font-size: 18px;">✏️</button>` : ''}<button onclick="deleteTaskAdmin('${list.id}', '${task.id}')" style="background:none; border:none; font-size: 18px;">🗑️</button>` : ''}</div>`).join('')}</div><div class="chat-input-area"><input type="text" id="task-input" placeholder="Добавить задачу..."><button id="add-task-btn" class="icon-btn">➕</button></div>`;
    document.getElementById('add-task-btn').onclick = () => addTask(list.id);
    document.getElementById('task-input').onkeypress = (e) => { if(e.key === 'Enter') addTask(list.id); };
}

async function addTask(listId) {
    const input = document.getElementById('task-input');
    const text = input.value.trim();
    if (!text) return;
    const res = await fetch(`${API_URL}?action=add_task`, { method: 'POST', body: JSON.stringify({ list_id: listId, text }) });
    if ((await res.json()).success) { input.value = ''; openTaskList(listId); }
}

async function editTaskPrompt(listId, taskId, oldText) {
    const newText = prompt('Редактировать:', oldText);
    if (!newText || newText === oldText) return;
    const res = await fetch(`${API_URL}?action=edit_task`, { method: 'POST', body: JSON.stringify({ list_id: listId, task_id: taskId, text: newText.trim() }) });
    if ((await res.json()).success) openTaskList(listId);
}

async function toggleTask(listId, taskId) {
    const res = await fetch(`${API_URL}?action=toggle_task`, { method: 'POST', body: JSON.stringify({ list_id: listId, task_id: taskId }) });
    if ((await res.json()).success) openTaskList(listId);
}

async function deleteTaskAdmin(listId, taskId) {
    if (!confirm('Удалить?')) return;
    const action = currentUser.role === 'admin' ? 'admin_delete_task' : 'delete_task';
    const res = await fetch(`${API_URL}?action=${action}`, { method: 'POST', body: JSON.stringify({ list_id: listId, task_id: taskId }) });
    if ((await res.json()).success) openTaskList(listId);
}

// Settings and Apply Logic
function renderSettings() {
    const container = document.getElementById('view-container');
    const settings = JSON.parse(localStorage.getItem('family_settings') || '{"theme":"light","fontSize":"16","accent":"#7360f2","chatBg":"#f0eff5"}');
    container.innerHTML = `
        <div style="background: #ffffff; padding: 20px; border-radius: 20px; margin-bottom: 20px; text-align: center;">
            <div style="position: relative; display: inline-block;">
                <div id="profile-avatar" class="avatar" style="width: 100px; height: 100px; font-size: 40px; border-radius: 50%; background: #bdbdbd; margin: 0 auto;">${currentUser.avatar ? `<img src="${currentUser.avatar}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">` : escapeHTML(currentUser.username[0].toUpperCase())}</div>
                <button id="change-avatar-btn" style="position: absolute; bottom: 0; right: 0; background: var(--primary-color); border: none; border-radius: 50%; width: 32px; height: 32px; color: white; cursor: pointer;">📷</button>
                <input type="file" id="avatar-input" hidden accept="image/*">
            </div>
            <h2 style="margin-top: 10px;">${escapeHTML(currentUser.username)}</h2>
        </div>
        <div style="background: var(--secondary-color); padding: 20px; border-radius: 20px;">
            <h3>Персонализация</h3>
            <div style="margin-top: 15px;"><label>Тема:</label><select id="theme-select"><option value="light" ${settings.theme === 'light' ? 'selected' : ''}>Светлая</option><option value="dark" ${settings.theme === 'dark' ? 'selected' : ''}>Темная</option></select></div>
            <div style="margin-top: 15px;"><label>Размер шрифта: <span id="font-val">${settings.fontSize}</span>px</label><input type="range" id="font-size" min="12" max="24" value="${settings.fontSize}"></div>
            <div style="margin-top: 15px;"><label>Акцентный цвет:</label><input type="color" id="accent-color" value="${settings.accent}"></div>
            <div style="margin-top: 15px;"><label>Фон чата:</label><select id="chat-bg-select"><option value="#f0eff5" ${settings.chatBg === '#f0eff5' ? 'selected' : ''}>Стандартный</option><option value="#e5ddd5" ${settings.chatBg === '#e5ddd5' ? 'selected' : ''}>WhatsApp</option><option value="#34003d" ${settings.chatBg === '#34003d' ? 'selected' : ''}>Deep Purple</option><option value="#001219" ${settings.chatBg === '#001219' ? 'selected' : ''}>Midnight</option></select></div>
            <div style="margin-top: 15px;"><label>Статус:</label><input type="text" id="status-input" value="${currentUser.status || ''}"></div>
            <div style="margin-top: 15px;"><label>О себе:</label><textarea id="bio-input" style="height: 80px;">${currentUser.bio || ''}</textarea></div>
        </div>
        <div style="margin-top: 20px; background: var(--secondary-color); padding: 20px; border-radius: 20px;">
            <button id="save-settings" style="width: 100%; padding: 15px; border-radius: 15px; background: var(--primary-color); color: white; border: none; font-weight: bold; margin-bottom: 15px;">Сохранить</button>
            <button id="manual-install" style="width: 100%; padding: 15px; border-radius: 15px; background: #28a745; color: white; border: none; font-weight: bold;">Установить</button>
        </div>
    `;

    document.getElementById('font-size').oninput = (e) => document.getElementById('font-val').innerText = e.target.value;
    document.getElementById('save-settings').onclick = async () => {
        const newSettings = { theme: document.getElementById('theme-select').value, fontSize: document.getElementById('font-size').value, accent: document.getElementById('accent-color').value, chatBg: document.getElementById('chat-bg-select').value };
        const status = document.getElementById('status-input').value;
        const bio = document.getElementById('bio-input').value;
        localStorage.setItem('family_settings', JSON.stringify(newSettings));
        applySettings(newSettings);
        await fetch(`${API_URL}?action=update_profile`, { method: 'POST', body: JSON.stringify({ status, bio }) });
        currentUser.status = status; currentUser.bio = bio; alert('Настройки сохранены');
    };
    document.getElementById('manual-install').onclick = () => { if (typeof triggerInstall === 'function') triggerInstall(); };
    document.getElementById('change-avatar-btn').onclick = () => document.getElementById('avatar-input').click();
    document.getElementById('avatar-input').onchange = async (e) => {
        const file = e.target.files[0]; if (!file) return;
        const formData = new FormData(); formData.append('image', file);
        const res = await fetch('api/upload.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            await fetch(`${API_URL}?action=update_profile`, { method: 'POST', body: JSON.stringify({ avatar: result.url }) });
            currentUser.avatar = result.url; renderSettings();
        }
    };

    const extra = document.createElement('div');
    extra.style.cssText = 'margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;';
    extra.innerHTML = `<button id="view-achievements-btn" style="padding: 15px; border-radius: 15px; background: #f2f2f7; border: none; font-weight: 600;">🏆 Награды</button><button id="view-about-btn" style="padding: 15px; border-radius: 15px; background: #f2f2f7; border: none; font-weight: 600;">❤️ О Жанне</button>`;
    container.appendChild(extra);
    document.getElementById('view-achievements-btn').onclick = () => { currentView = 'achievements'; renderView('achievements'); };
    document.getElementById('view-about-btn').onclick = () => { currentView = 'about'; renderView('about'); };
}

function applySettings(settings) {
    if (!settings) settings = JSON.parse(localStorage.getItem('family_settings') || '{"theme":"light","fontSize":"16","accent":"#7360f2","chatBg":"#f0eff5"}');
    const root = document.documentElement;
    root.style.setProperty('--font-size', settings.fontSize + 'px');
    root.style.setProperty('--accent-color', settings.accent);
    root.style.setProperty('--chat-bg', settings.chatBg || '#f0eff5');
    if (settings.theme === 'dark') {
        root.style.setProperty('--bg-color', '#000000'); root.style.setProperty('--text-color', '#ffffff'); root.style.setProperty('--secondary-color', '#1c1c1e'); root.style.setProperty('--primary-color', '#7360f2');
    } else {
        root.style.setProperty('--bg-color', '#ffffff'); root.style.setProperty('--text-color', '#000000'); root.style.setProperty('--secondary-color', '#f8f8f8'); root.style.setProperty('--primary-color', '#000000');
    }
}
applySettings();

async function renderAchievements() {
    const container = document.getElementById('view-container');
    const response = await fetch(`${API_URL}?action=get_achievements`);
    const result = await response.json();
    if (result.success) {
        container.innerHTML = `<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">${Object.entries(result.metadata).map(([id, meta]) => `<div style="background: var(--secondary-color); padding: 20px; border-radius: 20px; text-align: center; opacity: ${result.my_achievements.includes(id) ? 1 : 0.3}"><div style="font-size: 40px; margin-bottom: 10px;">${meta.icon}</div><strong>${meta.name}</strong></div>`).join('')}</div>`;
    }
}

// Events Logic
async function renderEvents() {
    const container = document.getElementById('view-container');
    container.innerHTML = `<button id="new-event-btn" style="width: 100%; padding: 15px; border-radius: 15px; border: 2px dashed #ccc; background: none; margin-bottom: 20px;">+ Добавить событие</button><div id="events-container"></div>`;
    document.getElementById('new-event-btn').onclick = showCreateEventModal;
    await fetchEvents();
}

async function showCreateEventModal() {
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    overlay.classList.remove('hidden');
    content.innerHTML = `<h3>Новое событие</h3><input type="text" id="event-title" placeholder="Название" style="margin-top: 15px;"><input type="date" id="event-date" style="margin-top: 10px;"><textarea id="event-desc" placeholder="Описание" style="width: 100%; padding: 12px; border-radius: 10px; margin-top: 10px; border: 1px solid #ddd;"></textarea><div style="display: flex; gap: 10px; margin-top: 20px;"><button id="save-event-btn" style="flex: 1; padding: 12px; border-radius: 10px; background: var(--primary-color); color: white; border: none;">Сохранить</button><button id="cancel-event" style="flex: 1; padding: 12px; border-radius: 10px; background: #eee; border: none;">Отмена</button></div>`;
    document.getElementById('cancel-event').onclick = () => overlay.classList.add('hidden');
    document.getElementById('save-event-btn').onclick = async () => {
        const title = document.getElementById('event-title').value; const date = document.getElementById('event-date').value; const description = document.getElementById('event-desc').value;
        if (!title || !date) return alert('Заполните поля');
        await fetch(`${API_URL}?action=add_event`, { method: 'POST', body: JSON.stringify({ title, date, description }) });
        overlay.classList.add('hidden'); renderEvents();
    };
}

async function fetchEvents() {
    const response = await fetch(`${API_URL}?action=get_events`);
    const result = await response.json();
    if (result.success) {
        const box = document.getElementById('events-container');
        box.innerHTML = result.events.map(e => `<div style="background: var(--secondary-color); padding: 15px; border-radius: 20px; margin-bottom: 12px;"><div style="display: flex; justify-content: space-between;"><strong>${escapeHTML(e.title)}</strong><span style="color: var(--accent-color); font-weight: bold;">${new Date(e.date).toLocaleDateString()}</span></div><div style="font-size: 14px; margin-top: 5px; color: #666;">${escapeHTML(e.description)}</div><button onclick="deleteEvent('${e.id}')" style="margin-top: 10px; background: none; border: none; font-size: 12px; color: #ff3b30;">Удалить</button></div>`).join('');
    }
}

async function deleteEvent(id) { if (!confirm('Удалить?')) return; await fetch(`${API_URL}?action=delete_event`, { method: 'POST', body: JSON.stringify({ id }) }); fetchEvents(); }

// Admin panel Logic
async function renderAdmin() {
    const container = document.getElementById('view-container');
    const response = await fetch(`${API_URL}?action=admin_get_summary`);
    const result = await response.json();
    if (!result.success) { container.innerHTML = '<p>Доступ запрещен</p>'; return; }
    container.innerHTML = `
        <div style="background: var(--secondary-color); padding: 20px; border-radius: 20px; margin-bottom: 20px;">
            <h3>Статистика</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;"><div style="background: white; padding: 15px; border-radius: 12px; text-align: center;"><div style="font-size: 24px; font-weight: bold;">${result.summary.users}</div><div style="font-size: 12px; opacity: 0.6;">Юзеров</div></div><div style="background: white; padding: 15px; border-radius: 12px; text-align: center;"><div style="font-size: 24px; font-weight: bold;">${result.summary.tasks}</div><div style="font-size: 12px; opacity: 0.6;">Списков</div></div></div>
        </div>
        <div style="margin-bottom: 20px;"><h3>Пользователи</h3><div id="admin-user-list"></div></div>
        <div style="margin-bottom: 20px; padding-bottom: 300px;"><h3>Сообщения</h3><div id="admin-messages-list"></div></div>
    `;
    const msgBox = document.getElementById('admin-messages-list');
    msgBox.innerHTML = result.recent_messages.map(m => `<div style="background: white; padding: 12px; border-radius: 12px; margin-bottom: 10px; font-size: 13px;"><div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><strong>${escapeHTML(m.from)} ➡️ ${escapeHTML(m.to)}</strong><button onclick="adminDeleteMessage('${m.id}')" style="color: #ff3b30; background: none; border: none; font-size: 11px;">Удалить</button></div><div>${escapeHTML(m.message || '[Вложение]')}</div><div style="font-size: 10px; opacity: 0.5; margin-top: 5px;">${new Date(m.timestamp * 1000).toLocaleString()}</div></div>`).join('');
    const uRes = await fetch(`${API_URL}?action=list_users`);
    const uList = await uRes.json();
    const uBox = document.getElementById('admin-user-list');
    uBox.innerHTML = uList.users.map(u => `<div class="user-item"><div style="flex: 1;"><strong>${escapeHTML(u.username)}</strong><div style="font-size: 11px; opacity: 0.5;">ID: ${u.id}</div></div>${u.username !== 'admin' ? `<button onclick="adminDeleteUser('${u.id}')" style="background: #ff3b30; color: white; border: none; padding: 5px 10px; border-radius: 8px; font-size: 12px;">Удалить</button>` : ''}</div>`).join('');
}

async function adminDeleteUser(id) { if (!confirm('Удалить?')) return; await fetch(`${API_URL}?action=admin_delete_user`, { method: 'POST', body: JSON.stringify({ id }) }); renderAdmin(); }
async function adminDeleteMessage(id) { if (!confirm('Удалить?')) return; await fetch(`${API_URL}?action=admin_delete_message`, { method: 'POST', body: JSON.stringify({ id }) }); if (currentView === 'admin') renderAdmin(); else fetchChatHistory(); }
