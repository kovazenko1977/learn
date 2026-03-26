(function() {
    const container = document.getElementById('zhanna-booking-form');
    if (!container) return;

    const formId = container.getAttribute('data-form-id');
    const apiBase = (new URL(document.currentScript.src)).origin;

    async function init() {
        try {
            const resp = await fetch(`${apiBase}/api/config.php?action=get&type=forms&id=${formId}`);
            if (!resp.ok) throw new Error('Form not found');
            const formConfig = await resp.json();
            renderForm(formConfig);
        } catch (e) {
            console.error('Failed to load booking form:', e);
            container.innerHTML = '<p style="color:red;">Form configuration not found.</p>';
        }
    }

    function renderForm(config) {
        const btnColor = config.btnColor || '#7360f2';
        const borderRadius = (config.borderRadius || 8) + 'px';

        let html = `
            <style>
                .zhanna-form { font-family: -apple-system, system-ui, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: ${borderRadius}; background: #fff; max-width: 400px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                .zhanna-group { margin-bottom: 15px; }
                .zhanna-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; color: #444; }
                .zhanna-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: ${borderRadius}; box-sizing: border-box; font-size: 1rem; }
                .zhanna-input:focus { border-color: ${btnColor}; outline: none; }
                .zhanna-textarea { height: 80px; resize: vertical; }
                .zhanna-submit { width: 100%; padding: 12px; background: ${btnColor}; color: white; border: none; border-radius: ${borderRadius}; font-size: 1rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
                .zhanna-submit:hover { opacity: 0.9; }
                .zhanna-submit:disabled { opacity: 0.6; cursor: not-allowed; }
                .zhanna-message { margin-top: 15px; padding: 10px; border-radius: ${borderRadius}; display: none; text-align: center; }
                .zhanna-message.success { background: #d4edda; color: #155724; display: block; }
                .zhanna-message.error { background: #f8d7da; color: #721c24; display: block; }
                .zhanna-hp { display: none; }
            </style>
            <form class="zhanna-form" id="zhanna-actual-form">
                <input type="hidden" name="form_id" value="${config.id}">
                <div class="zhanna-hp"><input type="text" name="hp_name"></div>
        `;

        config.fields.forEach(f => {
            const fieldName = f.name || 'field_' + Math.random().toString(36).substr(2, 5);
            html += `
                <div class="zhanna-group">
                    <label class="zhanna-label">${f.label} ${f.required ? '*' : ''}</label>
                    ${f.type === 'textarea'
                        ? `<textarea name="${fieldName}" class="zhanna-input zhanna-textarea" ${f.required ? 'required' : ''}></textarea>`
                        : `<input type="${f.type}" name="${fieldName}" class="zhanna-input" ${f.required ? 'required' : ''}>`
                    }
                </div>
            `;
        });

        html += `
                <button type="submit" class="zhanna-submit">Отправить заявку</button>
                <div id="zhanna-status" class="zhanna-message"></div>
            </form>
        `;

        container.innerHTML = html;

        document.getElementById('zhanna-actual-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('.zhanna-submit');
            const statusDiv = document.getElementById('zhanna-status');

            // Honeypot check
            if (form.hp_name.value) return;

            submitBtn.disabled = true;
            statusDiv.className = 'zhanna-message';
            statusDiv.textContent = 'Отправка...';
            statusDiv.style.display = 'block';

            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            try {
                const response = await fetch(`${apiBase}/api/submit.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    statusDiv.className = 'zhanna-message success';
                    statusDiv.textContent = 'Ваша заявка успешно отправлена!';
                    form.reset();
                } else {
                    throw new Error(result.error || 'Ошибка при отправке');
                }
            } catch (err) {
                statusDiv.className = 'zhanna-message error';
                statusDiv.textContent = 'Ошибка: ' + err.message;
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    init();
})();
