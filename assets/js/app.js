const App = {
    currentView: 'dashboard',
    recipients: [],
    settings: {},
    uploads: [],
    isAuthenticated: false,

    async init() {
        const authStatus = await this.fetchAPI('api/auth.php');
        if (authStatus && authStatus.authenticated) {
            this.isAuthenticated = true;
            this.showApp();
        } else {
            this.showLogin();
        }
        this.bindEvents();
    },

    bindEvents() {
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchView(e.target.dataset.view);
            });
        });

        document.getElementById('login-btn').addEventListener('click', () => this.login());
        document.getElementById('logout-btn').addEventListener('click', () => this.logout());
        document.getElementById('passcode-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.login();
        });
    },

    async login() {
        const passcode = document.getElementById('passcode-input').value;
        const res = await this.fetchAPI('api/auth.php', {
            method: 'POST',
            body: JSON.stringify({ passcode })
        });

        if (res && res.status === 'success') {
            this.isAuthenticated = true;
            this.showApp();
        } else {
            document.getElementById('login-error').style.display = 'block';
        }
    },

    async logout() {
        await this.fetchAPI('api/auth.php?logout=1');
        window.location.reload();
    },

    showLogin() {
        document.getElementById('login-screen').style.display = 'flex';
        document.getElementById('app').style.display = 'none';
    },

    async showApp() {
        document.getElementById('login-screen').style.display = 'none';
        document.getElementById('app').style.display = 'block';
        await this.loadSettings();
        await this.loadRecipients();
        await this.loadUploads();
        this.switchView('dashboard');
    },

    async fetchAPI(url, options = {}) {
        try {
            const response = await fetch(url, options);
            if (response.status === 401 && this.isAuthenticated) {
                window.location.reload();
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return null;
        }
    },

    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), parseInt(this.settings.toast_duration_ms || 3000));
    },

    async loadSettings() {
        const data = await this.fetchAPI('api/settings.php');
        if (data) this.settings = data;
    },

    async loadRecipients() {
        const data = await this.fetchAPI('api/recipients.php');
        if (data) this.recipients = data;
    },

    async loadUploads() {
        const data = await this.fetchAPI('api/uploads.php');
        if (data) this.uploads = data;
    },

    switchView(view) {
        this.currentView = view;
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        this.render();
    },

    render() {
        const main = document.getElementById('main-content');
        main.innerHTML = '';

        switch(this.currentView) {
            case 'dashboard': this.renderDashboard(main); break;
            case 'recipients': this.renderRecipients(main); break;
            case 'mailing': this.renderMailing(main); break;
            case 'settings': this.renderSettings(main); break;
        }
    },

    renderDashboard(container) {
        container.innerHTML = `
            <div class="grid">
                <div class="card">
                    <h2>Statistics</h2>
                    <p>Total Recipients: <strong>${this.recipients.length}</strong></p>
                    <p>Available Attachments: <strong>${this.uploads.length}</strong></p>
                    <p>Interval: <strong>${this.settings.interval_sec}s</strong></p>
                </div>
                <div class="card">
                    <h2>Quick Start</h2>
                    <button class="btn" onclick="App.switchView('mailing')">Create New Mailing</button>
                    <p style="font-size: 0.8rem; color: #666; margin-top: 1rem;">
                        Server: ${this.settings.smtp_host}<br>
                        Auth: ${this.isAuthenticated ? 'Verified' : 'Unverified'}
                    </p>
                </div>
            </div>
        `;
    },

    renderRecipients(container) {
        container.innerHTML = `
            <div class="card">
                <h2>Add Recipient</h2>
                <form id="add-recipient-form" class="grid" style="grid-template-columns: 1fr 1fr 1fr auto; align-items: end;">
                    <div>
                        <label>Email</label>
                        <input type="email" id="rec-email" required>
                    </div>
                    <div>
                        <label>Name</label>
                        <input type="text" id="rec-name">
                    </div>
                    <div>
                        <label>Group</label>
                        <input type="text" id="rec-group" placeholder="General">
                    </div>
                    <button type="submit" class="btn" style="margin-bottom: 1rem;">Add</button>
                </form>
            </div>
            <div class="card">
                <h2>Recipient List</h2>
                <table>
                    <thead>
                        <tr><th>Email</th><th>Name</th><th>Group</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="recipient-list">
                        ${this.recipients.map(r => `
                            <tr>
                                <td>${r.email}</td>
                                <td>${r.name || '-'}</td>
                                <td>${r.group || 'General'}</td>
                                <td><button class="btn btn-danger btn-sm" onclick="App.deleteRecipient('${r.id}')">Delete</button></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        document.getElementById('add-recipient-form').onsubmit = async (e) => {
            e.preventDefault();
            const email = document.getElementById('rec-email').value;
            const name = document.getElementById('rec-name').value;
            const group = document.getElementById('rec-group').value;

            const res = await this.fetchAPI('api/recipients.php', {
                method: 'POST',
                body: JSON.stringify({ email, name, group })
            });
            if (res && res.status === 'success') {
                this.recipients.push(res.item);
                this.renderRecipients(container);
                this.showToast('Recipient added');
            }
        };
    },

    async deleteRecipient(id) {
        const res = await this.fetchAPI(`api/recipients.php?id=${id}`, { method: 'DELETE' });
        if (res && res.status === 'success') {
            this.recipients = this.recipients.filter(r => r.id !== id);
            this.render();
            this.showToast('Recipient deleted');
        }
    },

    renderSettings(container) {
        const categories = [
            { id: 'smtp', label: 'SMTP Server', fields: ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure'] },
            { id: 'sending', label: 'Limits & Performance', fields: ['interval_sec', 'batch_size', 'max_per_hour', 'max_per_day', 'retry_attempts', 'retry_delay_sec'] },
            { id: 'identity', label: 'Identity', fields: ['from_name', 'from_email', 'reply_to', 'organization_name', 'sender_phone'] },
            { id: 'advanced', label: 'Advanced Headers', fields: ['email_priority', 'x_mailer_header', 'list_unsubscribe_url', 'precedence_header', 'auto_submitted_header', 'message_id_domain', 'content_type', 'charset'] },
            { id: 'tracking', label: 'Tracking & Logs', fields: ['enable_logs', 'log_retention_days', 'track_opens', 'track_clicks', 'tracking_domain', 'debug_mode'] },
            { id: 'security', label: 'Security & API', fields: ['app_passcode', 'session_timeout_min', 'allowed_upload_extensions', 'max_upload_size_mb', 'use_ssl_api', 'cors_policy'] },
            { id: 'ui', label: 'Interface', fields: ['app_theme', 'ui_accent_color', 'default_language', 'show_stats_on_dashboard', 'enable_toasts', 'toast_duration_ms', 'sidebar_collapsed', 'compact_mode'] },
            { id: 'notifications', label: 'Notifications', fields: ['notify_on_complete', 'notify_email', 'notify_on_failure', 'notify_webhook_url', 'desktop_notifications', 'sound_alerts'] }
        ];

        container.innerHTML = `
            <h2>Application Settings (${Object.keys(this.settings).length} parameters)</h2>
            <form id="settings-form">
                <div class="grid">
                    ${categories.map(cat => `
                        <div class="card">
                            <h3>${cat.label}</h3>
                            ${cat.fields.map(field => `
                                <label>${field.replace(/_/g, ' ').toUpperCase()}</label>
                                <input type="${field.includes('pass') ? 'password' : (typeof this.settings[field] === 'number' || !isNaN(this.settings[field]) && !isNaN(parseFloat(this.settings[field])) ? 'number' : 'text')}"
                                       name="${field}"
                                       value="${this.settings[field] || ''}">
                            `).join('')}
                        </div>
                    `).join('')}
                </div>
                <div style="text-align: right; margin-top: 1rem; position: sticky; bottom: 1rem;">
                    <button type="submit" class="btn" style="box-shadow: 0 4px 12px rgba(0,0,0,0.1);">Save All Settings</button>
                </div>
            </form>
        `;

        document.getElementById('settings-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const newSettings = Object.fromEntries(formData.entries());
            const res = await this.fetchAPI('api/settings.php', {
                method: 'POST',
                body: JSON.stringify(newSettings)
            });
            if (res && res.status === 'success') {
                this.settings = res.settings;
                this.showToast('Settings saved');
            }
        };
    },

    renderMailing(container) {
        container.innerHTML = `
            <div class="grid" style="grid-template-columns: 2fr 1fr;">
                <div class="card">
                    <h2>Compose Mailing</h2>
                    <label>Subject</label>
                    <input type="text" id="mail-subject" placeholder="Enter subject...">
                    <label>Message Body (HTML supported)</label>
                    <textarea id="mail-body" style="height: 300px;" placeholder="Hello {{name}}, ..."></textarea>

                    <h3>Select Attachments</h3>
                    <div id="attachment-list">
                        ${this.uploads.map(file => `
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: 400; border-bottom: 1px solid #eee; padding: 5px 0;">
                                <input type="checkbox" class="attachment-checkbox" value="${file}" style="width: auto; margin: 0;"> ${file}
                                <button onclick="App.deleteFile('${file}')" style="margin-left: auto; background: none; border: none; color: red; cursor: pointer;">&times;</button>
                            </label>
                        `).join('')}
                        ${this.uploads.length === 0 ? '<p>No files uploaded yet.</p>' : ''}
                    </div>

                    <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <input type="file" id="file-upload" style="display: none;">
                        <button class="btn btn-secondary" onclick="document.getElementById('file-upload').click()">Upload New Document</button>
                    </div>
                </div>

                <div class="card">
                    <h2>Campaign Configuration</h2>
                    <label>Target Groups (comma separated)</label>
                    <input type="text" id="mail-groups" placeholder="Leave empty for all">

                    <label>Interval (seconds)</label>
                    <input type="number" id="mail-interval" value="${this.settings.interval_sec || 5}">

                    <div style="margin-top: 2rem;">
                        <button class="btn" id="send-btn" style="width: 100%; font-size: 1.1rem; padding: 1rem;">Launch Mailing</button>
                        <button class="btn btn-secondary" id="simulate-btn" style="width: 100%; margin-top: 0.5rem;">Simulate Send</button>
                    </div>

                    <div id="mailing-progress" style="margin-top: 1rem; display: none;">
                        <div style="background: var(--secondary-color); border-radius: 10px; height: 10px; overflow: hidden;">
                            <div id="progress-bar" style="background: var(--accent-color); height: 100%; width: 0%;"></div>
                        </div>
                        <p id="progress-text" style="font-size: 0.8rem; text-align: center; margin-top: 5px;"></p>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('file-upload').onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch('api/uploads.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res && res.status === 'success') {
                await this.loadUploads();
                this.renderMailing(container);
                this.showToast('File uploaded');
            } else {
                this.showToast(res.message || 'Upload failed', 'error');
            }
        };

        const triggerMailing = async (simulate = false) => {
            const subject = document.getElementById('mail-subject').value;
            const body = document.getElementById('mail-body').value;
            const groupsStr = document.getElementById('mail-groups').value;
            const groups = groupsStr ? groupsStr.split(',').map(s => s.trim()) : [];
            const selectedAttachments = Array.from(document.querySelectorAll('.attachment-checkbox:checked')).map(cb => cb.value);

            let filteredRecipients = this.recipients;
            if (groups.length > 0) {
                filteredRecipients = this.recipients.filter(r => groups.includes(r.group));
            }

            if (filteredRecipients.length === 0) {
                alert('No recipients found for selected criteria.');
                return;
            }

            document.getElementById('mailing-progress').style.display = 'block';
            const btn = document.getElementById('send-btn');
            const progBar = document.getElementById('progress-bar');
            const progText = document.getElementById('progress-text');

            btn.disabled = true;
            btn.textContent = 'Mailing in progress...';

            const total = filteredRecipients.length;

            for (let i = 0; i < total; i++) {
                const recipient = filteredRecipients[i];
                progText.textContent = `Sending to ${recipient.email} (${i+1}/${total})`;
                progBar.style.width = `${((i+1)/total) * 100}%`;

                await this.fetchAPI('api/mail.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        subject,
                        body,
                        recipients: [recipient],
                        attachments: selectedAttachments,
                        simulate: simulate
                    })
                });

                if (i < total - 1) {
                    const wait = parseInt(document.getElementById('mail-interval').value) * 1000;
                    await new Promise(r => setTimeout(r, wait));
                }
            }

            this.showToast('Mailing complete!');
            btn.disabled = false;
            btn.textContent = 'Launch Mailing';
            setTimeout(() => {
                document.getElementById('mailing-progress').style.display = 'none';
                progBar.style.width = '0%';
            }, 2000);
        };

        document.getElementById('send-btn').onclick = () => triggerMailing(false);
        document.getElementById('simulate-btn').onclick = () => triggerMailing(true);
    },

    async deleteFile(name) {
        if (!confirm('Delete this file?')) return;
        const res = await this.fetchAPI(`api/uploads.php?name=${encodeURIComponent(name)}`, { method: 'DELETE' });
        if (res && res.status === 'success') {
            await this.loadUploads();
            this.render();
            this.showToast('File deleted');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
