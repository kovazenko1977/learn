<?php
require_once '../includes/AuthManager.php';
AuthManager::check();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления бронированием</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #7360f2; --bg: #f8f9fa; --card-bg: #ffffff; --text: #333; --border: #ddd; }
        body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg); margin: 0; display: flex; height: 100vh; color: var(--text); }

        /* Sidebar */
        .sidebar { width: 260px; background: white; border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 20px; }
        .sidebar h2 { font-size: 1.2rem; margin-bottom: 2rem; color: var(--primary); }
        .nav-item { padding: 12px; margin-bottom: 8px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; transition: 0.2s; }
        .nav-item:hover { background: #f0f0f0; }
        .nav-item.active { background: var(--primary); color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }

        /* Main Content */
        .main { flex: 1; overflow-y: auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #eee; color: #333; }
        .btn-danger { background: #ff4d4d; color: white; }

        /* Cards and Form List */
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .form-list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .form-list-item:last-child { border-bottom: none; }

        /* Form Editor */
        .editor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .field-item { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #eee; position: relative; }
        .field-controls { position: absolute; top: 10px; right: 10px; display: flex; gap: 10px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; }
        input[type="text"], input[type="email"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }

        /* Preview */
        .preview-container { border: 2px dashed #ccc; border-radius: 12px; padding: 20px; background: #fff; min-height: 200px; }

        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%; max-height: 90vh; overflow-y: auto; }

        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Санаторий Контроль</h2>
        <div class="nav-item active" onclick="showSection('forms')"><i class="fas fa-list"></i> Формы</div>
        <div class="nav-item" onclick="showSection('settings')"><i class="fas fa-cog"></i> Настройки почты</div>
        <div class="nav-item" style="margin-top: auto;" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Выйти</div>
    </div>

    <div class="main">
        <!-- Forms List Section -->
        <section id="section-forms">
            <div class="header">
                <h1>Список форм бронирования</h1>
                <button class="btn btn-primary" onclick="createNewForm()"><i class="fas fa-plus"></i> Новая форма</button>
            </div>
            <div class="card" id="forms-container">
                <!-- Forms will be loaded here -->
            </div>
        </section>

        <!-- Settings Section -->
        <section id="section-settings" class="hidden">
            <div class="header">
                <h1>Настройки почты (SMTP)</h1>
            </div>
            <div class="card">
                <div class="input-group">
                    <label>SMTP Сервер</label>
                    <input type="text" id="smtp_host" placeholder="smtp.gmail.com">
                </div>
                <div class="input-group">
                    <label>SMTP Порт</label>
                    <input type="text" id="smtp_port" placeholder="587">
                </div>
                <div class="input-group">
                    <label>Пользователь (Email)</label>
                    <input type="text" id="smtp_user" placeholder="your@email.com">
                </div>
                <div class="input-group">
                    <label>Пароль (SMTP)</label>
                    <input type="password" id="smtp_pass" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="input-group">
                    <label>Код доступа администратора</label>
                    <input type="text" id="admin_passcode" maxlength="6" value="123456">
                </div>
                <button class="btn btn-primary" onclick="saveSettings()">Сохранить настройки</button>
            </div>
        </section>

        <!-- Form Editor Section -->
        <section id="section-editor" class="hidden">
            <div class="header">
                <h1 id="editor-title">Редактирование формы</h1>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-secondary" onclick="showSection('forms')">Отмена</button>
                    <button class="btn btn-primary" onclick="saveCurrentForm()">Сохранить форму</button>
                </div>
            </div>

            <div class="editor-grid">
                <div class="config-panel">
                    <div class="card">
                        <h3>Основные настройки</h3>
                        <div class="input-group">
                            <label>Название формы (внутреннее)</label>
                            <input type="text" id="form-name-internal">
                        </div>
                        <div class="input-group">
                            <label>Email получателя заявок</label>
                            <input type="email" id="form-recipient">
                        </div>
                        <div class="input-group">
                            <label>Тема письма</label>
                            <input type="text" id="form-subject">
                        </div>
                    </div>

                    <div class="card">
                        <h3>Поля формы</h3>
                        <div id="fields-container"></div>
                        <button class="btn btn-secondary" onclick="addField()"><i class="fas fa-plus"></i> Добавить поле</button>
                    </div>

                    <div class="card">
                        <h3>Оформление</h3>
                        <div class="input-group">
                            <label>Цвет кнопки</label>
                            <input type="color" id="form-btn-color" value="#7360f2">
                        </div>
                        <div class="input-group">
                            <label>Радиус скругления (px)</label>
                            <input type="number" id="form-border-radius" value="8">
                        </div>
                    </div>
                </div>

                <div class="preview-panel">
                    <h3>Предпросмотр</h3>
                    <div class="preview-container" id="form-preview"></div>
                    <div class="card" style="margin-top: 20px;">
                        <h3>Код для вставки</h3>
                        <p style="font-size:0.8rem; color:#666; background: #fff3cd; padding: 10px; border-radius: 4px; border: 1px solid #ffeeba;"><strong>Внимание:</strong> Сначала сохраните форму, прежде чем копировать код!</p>
                        <p style="font-size:0.8rem; color:#666;">Скопируйте этот код на ваш сайт:</p>
                        <textarea id="embed-code" readonly style="height: 100px; font-family: monospace; font-size: 0.8rem;"></textarea>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        let forms = [];
        let globalSettings = {};
        let currentEditingId = null;

        async function init() {
            await loadForms();
            await loadSettings();
            showSection('forms');
        }

        async function loadForms() {
            const resp = await fetch('../api/config.php?action=get&type=forms');
            forms = await resp.json();
            renderFormsList();
        }

        async function loadSettings() {
            const resp = await fetch('../api/config.php?action=get&type=settings');
            globalSettings = await resp.json();
            if (globalSettings.smtp_host) {
                document.getElementById('smtp_host').value = globalSettings.smtp_host || '';
                document.getElementById('smtp_port').value = globalSettings.smtp_port || '';
                document.getElementById('smtp_user').value = globalSettings.smtp_user || '';
                document.getElementById('smtp_pass').value = globalSettings.smtp_pass || '';
                document.getElementById('admin_passcode').value = globalSettings.admin_passcode || '123456';
            }
        }

        function renderFormsList() {
            const container = document.getElementById('forms-container');
            if (forms.length === 0) {
                container.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Нет созданных форм. Создайте свою первую форму!</div>';
                return;
            }
            container.innerHTML = forms.map(f => `
                <div class="form-list-item">
                    <div>
                        <strong>${f.name}</strong><br>
                        <small style="color: #666;">ID: ${f.id} | Полей: ${f.fields.length}</small>
                    </div>
                    <div>
                        <button class="btn btn-secondary" onclick="editForm('${f.id}')"><i class="fas fa-edit"></i> Редактировать</button>
                        <button class="btn btn-danger" onclick="deleteForm('${f.id}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }

        function showSection(section) {
            document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
            document.getElementById('section-' + section).classList.remove('hidden');
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            if (section === 'forms') document.querySelector('.nav-item:nth-child(2)').classList.add('active');
            if (section === 'settings') document.querySelector('.nav-item:nth-child(3)').classList.add('active');
        }

        function createNewForm() {
            currentEditingId = 'form_' + Math.random().toString(36).substr(2, 9);
            document.getElementById('form-name-internal').value = 'Новая форма';
            document.getElementById('form-recipient').value = '';
            document.getElementById('form-subject').value = 'Новая заявка на бронирование';
            document.getElementById('fields-container').innerHTML = '';

            // Add some default fields
            addField('Имя', 'text', true);
            addField('Телефон', 'text', true);
            addField('Дата заезда', 'date', true);

            updatePreview();
            showSection('editor');
        }

        function editForm(id) {
            const form = forms.find(f => f.id === id);
            if (!form) return;
            currentEditingId = id;
            document.getElementById('form-name-internal').value = form.name;
            document.getElementById('form-recipient').value = form.recipient;
            document.getElementById('form-subject').value = form.subject;
            document.getElementById('form-btn-color').value = form.btnColor || '#7360f2';
            document.getElementById('form-border-radius').value = form.borderRadius || '8';

            document.getElementById('fields-container').innerHTML = '';
            form.fields.forEach(field => addField(field.label, field.type, field.required));

            updatePreview();
            showSection('editor');
        }

        function addField(label = '', type = 'text', required = false) {
            const div = document.createElement('div');
            div.className = 'field-item';
            div.innerHTML = `
                <div class="field-controls">
                    <button class="btn btn-danger" style="padding: 5px 10px;" onclick="this.parentElement.parentElement.remove(); updatePreview();"><i class="fas fa-times"></i></button>
                </div>
                <div class="input-group">
                    <label>Название поля</label>
                    <input type="text" class="field-label" value="${label}" oninput="updatePreview()">
                </div>
                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label>Тип</label>
                        <select class="field-type" onchange="updatePreview()">
                            <option value="text" ${type==='text'?'selected':''}>Текст</option>
                            <option value="number" ${type==='number'?'selected':''}>Число</option>
                            <option value="date" ${type==='date'?'selected':''}>Дата</option>
                            <option value="email" ${type==='email'?'selected':''}>Email</option>
                            <option value="textarea" ${type==='textarea'?'selected':''}>Многострочный текст</option>
                        </select>
                    </div>
                    <div style="display:flex; align-items:center; gap:5px; margin-top:20px;">
                        <input type="checkbox" class="field-required" ${required?'checked':''} onchange="updatePreview()"> Обязательно
                    </div>
                </div>
            `;
            document.getElementById('fields-container').appendChild(div);
            updatePreview();
        }

        function updatePreview() {
            const container = document.getElementById('form-preview');
            const fields = Array.from(document.querySelectorAll('.field-item')).map(item => ({
                label: item.querySelector('.field-label').value,
                type: item.querySelector('.field-type').value,
                required: item.querySelector('.field-required').checked
            }));

            const btnColor = document.getElementById('form-btn-color').value;
            const borderRadius = document.getElementById('form-border-radius').value;

            let html = `<div style="padding: 20px; border: 1px solid #eee; border-radius: ${borderRadius}px;">`;
            fields.forEach(f => {
                html += `
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.9rem;">${f.label} ${f.required ? '*' : ''}</label>
                        ${f.type === 'textarea'
                            ? `<textarea disabled style="width:100%; border:1px solid #ddd; border-radius:${borderRadius}px; height:60px;"></textarea>`
                            : `<input type="${f.type}" disabled style="width:100%; padding:8px; border:1px solid #ddd; border-radius:${borderRadius}px; box-sizing:border-box;">`
                        }
                    </div>
                `;
            });
            html += `<button disabled style="width:100%; padding:10px; background:${btnColor}; color:white; border:none; border-radius:${borderRadius}px; cursor:not-allowed;">Отправить заявку</button>`;
            html += `</div>`;
            container.innerHTML = html;

            // Update embed code
            const scriptUrl = window.location.origin + '/assets/js/embed.js';
            document.getElementById('embed-code').value = `<div data-zhanna-booking data-form-id="${currentEditingId}"></div>\n<script src="${scriptUrl}"><\/script>`;
        }

        async function saveCurrentForm() {
            const fields = Array.from(document.querySelectorAll('.field-item')).map(item => ({
                label: item.querySelector('.field-label').value,
                type: item.querySelector('.field-type').value,
                required: item.querySelector('.field-required').checked,
                name: 'field_' + Math.random().toString(36).substr(2, 5)
            }));

            const formConfig = {
                id: currentEditingId,
                name: document.getElementById('form-name-internal').value,
                recipient: document.getElementById('form-recipient').value,
                subject: document.getElementById('form-subject').value,
                btnColor: document.getElementById('form-btn-color').value,
                borderRadius: document.getElementById('form-border-radius').value,
                fields: fields
            };

            const existingIndex = forms.findIndex(f => f.id === currentEditingId);
            if (existingIndex > -1) {
                forms[existingIndex] = formConfig;
            } else {
                forms.push(formConfig);
            }

            await saveToStorage('forms', forms);
            alert('Форма сохранена!');
            showSection('forms');
            renderFormsList();
        }

        async function deleteForm(id) {
            if (!confirm('Удалить эту форму?')) return;
            forms = forms.filter(f => f.id !== id);
            await saveToStorage('forms', forms);
            renderFormsList();
        }

        async function saveSettings() {
            const settings = {
                smtp_host: document.getElementById('smtp_host').value,
                smtp_port: document.getElementById('smtp_port').value,
                smtp_user: document.getElementById('smtp_user').value,
                smtp_pass: document.getElementById('smtp_pass').value,
                admin_passcode: document.getElementById('admin_passcode').value,
            };
            await saveToStorage('settings', settings);
            alert('Настройки сохранены!');
        }

        async function saveToStorage(type, data) {
            await fetch(`../api/config.php?action=save&type=${type}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        }

        init();
    </script>
</body>
</html>
