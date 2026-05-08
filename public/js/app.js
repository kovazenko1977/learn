let currentPin = '';
let currentTab = 'emails';
let emails = [];
let templates = [];

// --- Auth ---
function appendPin(num) {
    if (currentPin.length < 6) {
        currentPin += num;
        updatePinDots();
        if (currentPin.length === 6) {
            login();
        }
    }
}

function clearPin() {
    currentPin = currentPin.slice(0, -1);
    updatePinDots();
}

function updatePinDots() {
    for (let i = 0; i < 6; i++) {
        const dot = document.getElementById(`dot-${i}`);
        if (i < currentPin.length) {
            dot.classList.add('filled');
        } else {
            dot.classList.remove('filled');
        }
    }
}

async function login() {
    try {
        const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ pin: currentPin })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('login-screen').classList.add('hidden');
            initApp();
        } else {
            alert('Wrong PIN');
            currentPin = '';
            updatePinDots();
        }
    } catch (e) {
        console.error(e);
    }
}

async function checkAuth() {
    const res = await fetch('api/auth.php?action=check');
    const data = await res.json();
    if (data.logged_in) {
        document.getElementById('login-screen').classList.add('hidden');
        initApp();
    }
}

async function logout() {
    await fetch('api/auth.php?action=logout');
    location.reload();
}

// --- App Logic ---
function initApp() {
    switchTab('emails');
    loadSettings();

    // Handle pre-fill from URL
    const urlParams = new URLSearchParams(window.location.search);
    const prefill = urlParams.get('email') || urlParams.get('mailto');
    if (prefill) {
        showAddEmailModal(prefill);
        // Clear URL params without reloading to avoid multiple modals
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    document.getElementById('add-btn').onclick = () => {
        if (currentTab === 'emails') showAddEmailModal();
        if (currentTab === 'templates') showAddTemplateModal();
    };

    document.getElementById('save-settings-btn').onclick = saveSettings;
    document.getElementById('logout-btn').onclick = logout;
    document.getElementById('send-now-btn').onclick = sendMail;
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

    document.getElementById(`${tab}-tab`).classList.add('active');
    document.getElementById(`tab-${tab}`).classList.add('active');

    const addBtn = document.getElementById('add-btn');
    if (tab === 'emails' || tab === 'templates') {
        addBtn.classList.remove('hidden');
    } else {
        addBtn.classList.add('hidden');
    }

    const titleMap = {
        'emails': 'Emails',
        'templates': 'Templates',
        'send': 'Send Mail',
        'settings': 'Settings'
    };
    document.getElementById('page-title').innerText = titleMap[tab];

    if (tab === 'emails') loadEmails();
    if (tab === 'templates') loadTemplates();
    if (tab === 'send') prepareSendTab();
}

// --- Emails ---
async function loadEmails() {
    const res = await fetch('api/emails.php');
    emails = await res.json();
    renderEmails();
}

function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function renderEmails() {
    const list = document.getElementById('email-list');
    const search = document.getElementById('search-emails').value.toLowerCase();
    list.innerHTML = '';

    emails.filter(e => e.email.toLowerCase().includes(search) || e.note.toLowerCase().includes(search)).forEach(e => {
        const div = document.createElement('div');
        div.className = 'ios-item flex justify-between items-center';
        div.innerHTML = `
            <div class="flex flex-col">
                <span class="font-medium">${escapeHTML(e.email)}</span>
                <span class="text-xs text-gray-500">${escapeHTML(e.note || 'No note')}</span>
            </div>
            <button onclick="deleteEmail('${escapeHTML(e.email)}')" class="text-red-500"><i data-lucide="trash-2"></i></button>
        `;
        list.appendChild(div);
    });
    lucide.createIcons();
}

function showEditTemplateModal(t) {
    showModal(`
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4 text-center">Edit Template</h3>
            <input type="text" id="t-name" class="w-full p-2 bg-gray-100 rounded mb-2" value="${t.name}" placeholder="Template Name">
            <input type="text" id="t-subject" class="w-full p-2 bg-gray-100 rounded mb-2" value="${t.subject}" placeholder="Subject">
            <textarea id="t-body" class="w-full p-2 bg-gray-100 rounded mb-4 h-32" placeholder="Body">${t.body}</textarea>
            <div class="flex border-t">
                <button onclick="closeModal()" class="flex-1 py-3 text-[#007aff] border-r">Cancel</button>
                <button onclick="updateTemplate(${t.id})" class="flex-1 py-3 text-[#007aff] font-bold">Update</button>
            </div>
        </div>
    `);
}

async function updateTemplate(id) {
    const payload = {
        id: id,
        name: document.getElementById('t-name').value,
        subject: document.getElementById('t-subject').value,
        body: document.getElementById('t-body').value
    };
    await fetch('api/templates.php', {
        method: 'PUT',
        body: JSON.stringify(payload)
    });
    closeModal();
    loadTemplates();
}

document.getElementById('search-emails').oninput = renderEmails;

function showAddEmailModal(prefill = '') {
    showModal(`
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4 text-center">Add Email</h3>
            <input type="email" id="new-email" class="w-full p-3 bg-gray-100 rounded-xl outline-none mb-2" placeholder="email@example.com" value="${escapeHTML(prefill)}">
            <input type="text" id="new-note" class="w-full p-3 bg-gray-100 rounded-xl outline-none mb-4" placeholder="Note (who is this)">
            <div class="flex border-t">
                <button onclick="closeModal()" class="flex-1 py-3 text-[#007aff] font-medium border-r">Cancel</button>
                <button onclick="addEmail()" class="flex-1 py-3 text-[#007aff] font-bold">Add</button>
            </div>
        </div>
    `);
}

async function addEmail() {
    const email = document.getElementById('new-email').value;
    const note = document.getElementById('new-note').value;
    const res = await fetch('api/emails.php', {
        method: 'POST',
        body: JSON.stringify({ email, note })
    });
    const data = await res.json();
    if (data.success) {
        closeModal();
        loadEmails();
    } else {
        alert(data.message);
    }
}

async function deleteEmail(email) {
    if (confirm(`Delete ${email}?`)) {
        await fetch(`api/emails.php?email=${encodeURIComponent(email)}`, { method: 'DELETE' });
        loadEmails();
    }
}

// --- Templates ---
async function loadTemplates() {
    const res = await fetch('api/templates.php');
    templates = await res.json();
    renderTemplates();
}

function renderTemplates() {
    const list = document.getElementById('template-list');
    list.innerHTML = '';
    templates.forEach(t => {
        const div = document.createElement('div');
        div.className = 'ios-item flex justify-between items-center ios-btn-active';
        div.onclick = (e) => {
            if (e.target.closest('button')) return;
            showEditTemplateModal(t);
        };
        div.innerHTML = `
            <div>
                <div class="font-semibold">${escapeHTML(t.name)}</div>
                <div class="text-xs text-gray-500">${escapeHTML(t.subject)}</div>
            </div>
            <button onclick="deleteTemplate(${t.id})" class="text-red-500"><i data-lucide="trash-2"></i></button>
        `;
        list.appendChild(div);
    });
    lucide.createIcons();
}

function showAddTemplateModal() {
    showModal(`
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4 text-center">New Template</h3>
            <input type="text" id="t-name" class="w-full p-2 bg-gray-100 rounded mb-2" placeholder="Template Name">
            <input type="text" id="t-subject" class="w-full p-2 bg-gray-100 rounded mb-2" placeholder="Subject">
            <textarea id="t-body" class="w-full p-2 bg-gray-100 rounded mb-4 h-32" placeholder="Body"></textarea>
            <div class="flex border-t">
                <button onclick="closeModal()" class="flex-1 py-3 text-[#007aff] border-r">Cancel</button>
                <button onclick="saveTemplate()" class="flex-1 py-3 text-[#007aff] font-bold">Save</button>
            </div>
        </div>
    `);
}

async function saveTemplate() {
    const payload = {
        name: document.getElementById('t-name').value,
        subject: document.getElementById('t-subject').value,
        body: document.getElementById('t-body').value
    };
    await fetch('api/templates.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    closeModal();
    loadTemplates();
}

async function deleteTemplate(id) {
    if (confirm('Delete template?')) {
        await fetch(`api/templates.php?id=${id}`, { method: 'DELETE' });
        loadTemplates();
    }
}

// --- Send ---
function prepareSendTab() {
    const select = document.getElementById('send-to-select');
    select.innerHTML = '<option value="bulk">All in Database</option>';
    emails.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.email;
        opt.textContent = e.email + (e.note ? ` (${e.note})` : '');
        select.appendChild(opt);
    });

    const tSelect = document.getElementById('send-template-select');
    tSelect.innerHTML = '<option value="">No Template</option>';
    templates.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.name;
        tSelect.appendChild(opt);
    });

    tSelect.onchange = () => {
        const selected = templates.find(t => t.id == tSelect.value);
        if (selected) {
            document.getElementById('send-subject').value = selected.subject;
            document.getElementById('send-body').value = selected.body;
        }
    };
}

async function sendMail() {
    const btn = document.getElementById('send-now-btn');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Sending...';
    btn.style.opacity = '0.5';

    const payload = {
        type: document.getElementById('send-to-select').value === 'bulk' ? 'bulk' : 'single',
        to: document.getElementById('send-to-select').value,
        subject: document.getElementById('send-subject').value,
        body: document.getElementById('send-body').value
    };

    try {
        const res = await fetch('api/send.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message || 'Sent!');
    } catch (e) {
        alert('Error sending mail');
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
        btn.style.opacity = '1';
    }
}

// --- Settings ---
async function loadSettings() {
    const res = await fetch('api/settings.php');
    const data = await res.json();
    document.getElementById('setting-interval').value = data.interval;
}

async function saveSettings() {
    const payload = {
        interval: document.getElementById('setting-interval').value,
        password: document.getElementById('setting-pin').value || undefined
    };
    await fetch('api/settings.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    alert('Settings saved');
    if (payload.password) logout();
}

// --- Helpers ---
function showModal(content) {
    document.getElementById('modal-content').innerHTML = content;
    document.getElementById('modal-backdrop').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-backdrop').classList.add('hidden');
}

function startVoiceInput(targetId) {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('Speech recognition not supported in this browser.');
        return;
    }
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new Recognition();
    recognition.lang = 'ru-RU'; // Support Russian as requested in prompt
    recognition.onresult = (event) => {
        const text = event.results[0][0].transcript;
        document.getElementById(targetId).value += text;
    };
    recognition.start();
}

// Start
checkAuth();
