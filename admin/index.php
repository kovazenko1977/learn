<?php
require_once '../includes/AuthManager.php';
AuthManager::check();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления Санаторием</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #7360f2; --bg: #f8f9fa; --card-bg: #ffffff; --text: #333; --border: #ddd; }
        body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg); margin: 0; display: flex; height: 100vh; color: var(--text); }

        .sidebar { width: 260px; background: white; border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 20px; }
        .sidebar h2 { font-size: 1.2rem; margin-bottom: 2rem; color: var(--primary); }
        .nav-item { padding: 12px; margin-bottom: 8px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; transition: 0.2s; }
        .nav-item:hover { background: #f0f0f0; }
        .nav-item.active { background: var(--primary); color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }

        .main { flex: 1; overflow-y: auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #eee; color: #333; }
        .btn-danger { background: #ff4d4d; color: white; }

        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .form-list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .form-list-item:last-child { border-bottom: none; }

        .editor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .field-item { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #eee; position: relative; }
        .field-controls { position: absolute; top: 10px; right: 10px; display: flex; gap: 10px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="number"], input[type="password"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }

        .preview-container { border: 2px dashed #ccc; border-radius: 12px; padding: 20px; background: #fff; min-height: 200px; }
        .hidden { display: none; }

        /* Mailing Specific */
        .mailing-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card h3 { margin: 0; color: #666; font-size: 0.9rem; }
        .stat-card p { margin: 10px 0 0; font-size: 1.5rem; font-weight: bold; color: var(--primary); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { color: #666; font-size: 0.8rem; text-transform: uppercase; }

        .progress-bar { width: 100%; background: #eee; border-radius: 10px; height: 10px; margin-top: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary); width: 0%; transition: 0.3s; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Санаторий Контроль</h2>
        <div class="nav-item active" onclick="showSection('forms')"><i class="fas fa-list"></i> Формы бронирования</div>
        <div class="nav-item" onclick="showSection('contacts')"><i class="fas fa-users"></i> Контакты (Рассылка)</div>
        <div class="nav-item" onclick="showSection('campaigns')"><i class="fas fa-paper-plane"></i> Кампании</div>
        <div class="nav-item" onclick="showSection('settings')"><i class="fas fa-cog"></i> Настройки</div>
        <div class="nav-item" style="margin-top: auto;" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Выйти</div>
    </div>

    <div class="main">
        <!-- Forms List Section -->
        <section id="section-forms">
            <div class="header">
                <h1>Список форм бронирования</h1>
                <button class="btn btn-primary" onclick="createNewForm()"><i class="fas fa-plus"></i> Новая форма</button>
            </div>
            <div class="card" id="forms-container"></div>
        </section>

        <!-- Contacts Management Section -->
        <section id="section-contacts" class="hidden">
            <div class="header">
                <h1>База email-адресов</h1>
                <div>
                    <button class="btn btn-secondary" onclick="exportContacts()"><i class="fas fa-download"></i> Экспорт</button>
                    <button class="btn btn-primary" onclick="showAddContactModal()"><i class="fas fa-plus"></i> Добавить контакт</button>
                </div>
            </div>
            <div class="card">
                <div style="display:flex; gap:10px; margin-bottom:20px;">
                    <input type="text" id="contact-search" placeholder="Поиск по email или имени..." oninput="renderContacts()">
                    <select id="filter-group" onchange="renderContacts()">
                        <option value="">Все группы</option>
                    </select>
                </div>
                <table id="contacts-table">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Группа</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="contacts-list"></tbody>
                </table>
            </div>
        </section>

        <!-- Campaigns Section -->
        <section id="section-campaigns" class="hidden">
            <div class="header">
                <h1>Массовые рассылки</h1>
                <button class="btn btn-primary" onclick="createNewCampaign()"><i class="fas fa-plus"></i> Создать кампанию</button>
            </div>
            <div class="mailing-stats">
                <div class="stat-card"><h3>Всего контактов</h3><p id="stat-total-contacts">0</p></div>
                <div class="stat-card"><h3>Кампаний</h3><p id="stat-total-campaigns">0</p></div>
                <div class="stat-card"><h3>Писем отправлено</h3><p id="stat-total-sent">0</p></div>
                <div class="stat-card"><h3>Активные сессии</h3><p id="stat-active">0</p></div>
            </div>
            <div id="campaigns-container"></div>
        </section>

        <!-- Form Editor (Shared) -->
        <section id="section-editor" class="hidden">
            <div class="header">
                <h1 id="editor-title">Редактирование</h1>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-secondary" onclick="cancelEditor()">Отмена</button>
                    <button class="btn btn-primary" id="save-btn">Сохранить</button>
                </div>
            </div>
            <div id="editor-content"></div>
        </section>

        <!-- Settings Section -->
        <section id="section-settings" class="hidden">
            <div class="header"><h1>Глобальные настройки</h1></div>
            <div class="card">
                <h3>SMTP Сервер</h3>
                <div class="input-group"><label>SMTP Хост</label><input type="text" id="smtp_host"></div>
                <div class="input-group"><label>SMTP Порт</label><input type="number" id="smtp_port"></div>
                <div class="input-group"><label>Пользователь</label><input type="text" id="smtp_user"></div>
                <div class="input-group"><label>Пароль</label><input type="password" id="smtp_pass"></div>

                <h3 style="margin-top:30px;">Безопасность</h3>
                <div class="input-group"><label>Код доступа администратора</label><input type="text" id="admin_passcode"></div>

                <h3 style="margin-top:30px;">Настройки рассылки</h3>
                <div class="input-group"><label>Имя отправителя</label><input type="text" id="mailing_from_name" value="Mailing Service"></div>
                <div class="input-group"><label>Email для ответа (Reply-To)</label><input type="email" id="mailing_reply_to"></div>
                <div class="input-group"><label>Интервал между письмами (мс)</label><input type="number" id="mailing_interval" value="2000"></div>
                <div class="input-group"><label>Размер пачки (писем)</label><input type="number" id="mailing_batch" value="10"></div>

                <button class="btn btn-primary" onclick="saveSettings()">Сохранить настройки</button>
            </div>
        </section>
    </div>

    <!-- Modals (Simple implementation) -->
    <div id="modal-contact" class="hidden" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:1000;">
        <div class="card" style="width:400px;">
            <h3>Добавить контакт</h3>
            <div class="input-group"><label>Имя</label><input type="text" id="new-contact-name"></div>
            <div class="input-group"><label>Email</label><input type="email" id="new-contact-email"></div>
            <div class="input-group"><label>Группа</label><input type="text" id="new-contact-group"></div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button class="btn btn-secondary" style="flex:1" onclick="document.getElementById('modal-contact').classList.add('hidden'); document.getElementById('modal-contact').style.display='none';">Отмена</button>
                <button class="btn btn-primary" style="flex:1" onclick="saveNewContact()">Добавить</button>
            </div>
        </div>
    </div>

    <script>
        let state = {
            forms: [],
            contacts: [],
            campaigns: [],
            settings: {},
            currentView: 'forms'
        };

        async function init() {
            await Promise.all([
                loadData('forms'),
                loadData('mailing_contacts', 'contacts'),
                loadData('mailing_campaigns', 'campaigns'),
                loadData('settings')
            ]);
            showSection('forms');
            updateStats();
        }

        async function loadData(type, stateKey = null) {
            const resp = await fetch(`../api/config.php?action=get&type=${type}`);
            const data = await resp.json();
            state[stateKey || type] = data;
        }

        function showSection(section) {
            document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
            document.getElementById('section-' + section).classList.remove('hidden');
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            state.currentView = section;

            // Highlight nav
            const navs = Array.from(document.querySelectorAll('.nav-item'));
            const indexMap = { 'forms': 0, 'contacts': 1, 'campaigns': 2, 'settings': 3 };
            if (navs[indexMap[section]]) navs[indexMap[section]].classList.add('active');

            if (section === 'forms') renderFormsList();
            if (section === 'contacts') renderContacts();
            if (section === 'campaigns') renderCampaigns();
            if (section === 'settings') fillSettings();
        }

        /* --- FORMS --- */
        function renderFormsList() {
            const container = document.getElementById('forms-container');
            if (state.forms.length === 0) {
                container.innerHTML = '<div style="padding:20px;text-align:center;color:#666;">Нет созданных форм.</div>';
                return;
            }
            container.innerHTML = state.forms.map(f => `
                <div class="form-list-item">
                    <div><strong>${f.name}</strong><br><small>ID: ${f.id}</small></div>
                    <div>
                        <button class="btn btn-secondary" onclick="editForm('${f.id}')"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger" onclick="deleteForm('${f.id}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }

        /* --- CONTACTS --- */
        function renderContacts() {
            const list = document.getElementById('contacts-list');
            const search = document.getElementById('contact-search').value.toLowerCase();
            const groupFilter = document.getElementById('filter-group').value;

            // Unique groups for filter
            const groups = [...new Set(state.contacts.map(c => c.group))].filter(g => g);
            const filterEl = document.getElementById('filter-group');
            const currentFilter = filterEl.value;
            filterEl.innerHTML = '<option value="">Все группы</option>' + groups.map(g => `<option value="${g}" ${g===currentFilter?'selected':''}>${g}</option>`).join('');

            const filtered = state.contacts.filter(c =>
                (c.email.toLowerCase().includes(search) || c.name.toLowerCase().includes(search)) &&
                (!groupFilter || c.group === groupFilter)
            );

            list.innerHTML = filtered.map((c, i) => `
                <tr>
                    <td>${c.name}</td>
                    <td>${c.email}</td>
                    <td><span style="background:#eee; padding:2px 8px; border-radius:10px; font-size:0.8rem;">${c.group || 'Без группы'}</span></td>
                    <td><button class="btn btn-danger" style="padding:5px 10px;" onclick="deleteContact(${i})"><i class="fas fa-trash"></i></button></td>
                </tr>
            `).join('');
        }

        function showAddContactModal() {
            const modal = document.getElementById('modal-contact');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
        async function saveNewContact() {
            const contact = {
                name: document.getElementById('new-contact-name').value,
                email: document.getElementById('new-contact-email').value,
                group: document.getElementById('new-contact-group').value
            };
            if (!contact.email) return alert('Email обязателен');
            state.contacts.push(contact);
            await saveToStorage('mailing_contacts', state.contacts);
            const modal = document.getElementById('modal-contact');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            renderContacts();
            updateStats();
        }

        async function deleteContact(index) {
            if (!confirm('Удалить контакт?')) return;
            state.contacts.splice(index, 1);
            await saveToStorage('mailing_contacts', state.contacts);
            renderContacts();
            updateStats();
        }

        /* --- SETTINGS --- */
        function fillSettings() {
            const s = state.settings;
            document.getElementById('smtp_host').value = s.smtp_host || '';
            document.getElementById('smtp_port').value = s.smtp_port || '';
            document.getElementById('smtp_user').value = s.smtp_user || '';
            document.getElementById('smtp_pass').value = s.smtp_pass || '';
            document.getElementById('admin_passcode').value = s.admin_passcode || '123456';
            document.getElementById('mailing_interval').value = s.mailing_interval || 2000;
            document.getElementById('mailing_batch').value = s.mailing_batch || 10;
            document.getElementById('mailing_from_name').value = s.mailing_from_name || 'Mailing Service';
            document.getElementById('mailing_reply_to').value = s.mailing_reply_to || '';
        }

        async function saveSettings() {
            state.settings = {
                smtp_host: document.getElementById('smtp_host').value,
                smtp_port: document.getElementById('smtp_port').value,
                smtp_user: document.getElementById('smtp_user').value,
                smtp_pass: document.getElementById('smtp_pass').value,
                admin_passcode: document.getElementById('admin_passcode').value,
                mailing_interval: document.getElementById('mailing_interval').value,
                mailing_batch: document.getElementById('mailing_batch').value,
                mailing_from_name: document.getElementById('mailing_from_name').value,
                mailing_reply_to: document.getElementById('mailing_reply_to').value,
            };
            await saveToStorage('settings', state.settings);
            alert('Настройки сохранены');
        }

        async function saveToStorage(type, data) {
            await fetch(`../api/config.php?action=save&type=${type}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        }

        async function updateStats() {
            document.getElementById('stat-total-contacts').textContent = state.contacts.length;
            document.getElementById('stat-total-campaigns').textContent = state.campaigns.length;

            const resp = await fetch('../api/config.php?action=get&type=mailing_history');
            const history = await resp.json();
            document.getElementById('stat-total-sent').textContent = history.length;
        }

        function renderCampaigns() {
            const container = document.getElementById('campaigns-container');
            if (state.campaigns.length === 0) {
                container.innerHTML = '<div style="padding:20px;text-align:center;color:#666;">Нет созданных кампаний.</div>';
                return;
            }
            container.innerHTML = state.campaigns.map(c => `
                <div class="card campaign-card" id="campaign-${c.id}">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong>${c.subject}</strong><br>
                            <small>Получателей: ${c.recipient_count} | Группа: ${c.group || 'Все'}</small>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button class="btn btn-primary" id="btn-start-${c.id}" onclick="startMailing('${c.id}')">Запустить</button>
                            <button class="btn btn-danger" onclick="deleteCampaign('${c.id}')"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div id="progress-${c.id}" class="progress-bar hidden"><div class="progress-fill"></div></div>
                    <div id="status-${c.id}" style="margin-top:10px; font-size:0.8rem; color:#666;"></div>
                </div>
            `).join('');
        }

        let currentAttachments = [];
        function createNewCampaign() {
            const editor = document.getElementById('editor-content');
            currentAttachments = [];
            editor.innerHTML = `
                <div class="card">
                    <div class="input-group"><label>Тема письма</label><input type="text" id="camp-subject"></div>
                    <div class="input-group"><label>Текст письма (HTML)</label><textarea id="camp-message" style="height:200px;"></textarea></div>
                    <div class="input-group">
                        <label>Группа получателей</label>
                        <select id="camp-group">
                            <option value="">Все контакты</option>
                            ${[...new Set(state.contacts.map(c => c.group))].map(g => `<option value="${g}">${g}</option>`).join('')}
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Вложения</label>
                        <input type="file" id="camp-file" multiple onchange="uploadFiles()">
                        <div id="attachments-list" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px;"></div>
                    </div>
                </div>
            `;
            document.getElementById('save-btn').onclick = saveCampaign;
            showSection('editor');
        }

        async function uploadFiles() {
            const files = document.getElementById('camp-file').files;
            for (let file of files) {
                const formData = new FormData();
                formData.append('file', file);
                const resp = await fetch('../api/upload.php', { method: 'POST', body: formData });
                const res = await resp.json();
                if (res.success) {
                    currentAttachments.push({ filename: res.filename, original_name: res.original_name });
                    renderAttachments();
                }
            }
        }

        function renderAttachments() {
            const list = document.getElementById('attachments-list');
            list.innerHTML = currentAttachments.map((a, i) => `
                <div style="background:#eee; padding:5px 10px; border-radius:10px; font-size:0.8rem; display:flex; align-items:center; gap:5px;">
                    ${a.original_name} <i class="fas fa-times" style="cursor:pointer;" onclick="currentAttachments.splice(${i},1); renderAttachments();"></i>
                </div>
            `).join('');
        }

        async function saveCampaign() {
            const group = document.getElementById('camp-group').value;
            const recipients = group ? state.contacts.filter(c => c.group === group) : state.contacts;
            const campaign = {
                id: 'camp_' + Date.now(),
                subject: document.getElementById('camp-subject').value,
                message: document.getElementById('camp-message').value,
                group: group,
                recipient_count: recipients.length,
                attachments: currentAttachments
            };
            state.campaigns.push(campaign);
            await saveToStorage('mailing_campaigns', state.campaigns);
            showSection('campaigns');
            updateStats();
        }

        async function deleteCampaign(id) {
            if (!confirm('Удалить кампанию?')) return;
            state.campaigns = state.campaigns.filter(c => c.id !== id);
            await saveToStorage('mailing_campaigns', state.campaigns);
            renderCampaigns();
            updateStats();
        }

        function cancelEditor() { showSection('campaigns'); }

        async function startMailing(campaignId) {
            const campaign = state.campaigns.find(c => c.id === campaignId);
            if (!campaign) return;

            const group = campaign.group;
            const recipients = group ? state.contacts.filter(c => c.group === group) : state.contacts;

            if (recipients.length === 0) return alert('Нет получателей');
            if (!confirm(`Запустить рассылку на ${recipients.length} адресов?`)) return;

            const progress = document.getElementById(`progress-${campaignId}`);
            const progressFill = progress.querySelector('.progress-fill');
            const status = document.getElementById(`status-${campaignId}`);
            const startBtn = document.getElementById(`btn-start-${campaignId}`);

            progress.classList.remove('hidden');
            startBtn.disabled = true;
            let sentCount = 0;

            const interval = parseInt(state.settings.mailing_interval || 2000);

            for (let contact of recipients) {
                status.textContent = `Отправка на ${contact.email} (${sentCount + 1}/${recipients.length})...`;

                try {
                    const resp = await fetch('../api/send_mailing.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: contact.email,
                            subject: campaign.subject,
                            message: campaign.message,
                            attachments: campaign.attachments
                        })
                    });
                    const res = await resp.json();
                } catch (e) { console.error(e); }

                sentCount++;
                progressFill.style.width = (sentCount / recipients.length * 100) + '%';

                if (sentCount < recipients.length) {
                    await new Promise(r => setTimeout(r, interval));
                }
            }

            status.textContent = `Завершено: отправлено ${sentCount} писем.`;
            startBtn.disabled = false;
        }

        init();
    </script>
</body>
</html>
