(function() {
    function initAllForms() {
        const containers = document.querySelectorAll('[data-zhanna-booking]');
        const oldContainer = document.getElementById('zhanna-booking-form');
        const allContainers = Array.from(containers);
        if (oldContainer && !allContainers.includes(oldContainer)) {
            allContainers.push(oldContainer);
        }

        // One-tag embedding: find the script that loaded us
        let selfScript = document.currentScript;
        if (!selfScript) {
            const scripts = document.getElementsByTagName('script');
            for (let i = scripts.length - 1; i >= 0; i--) {
                const s = scripts[i];
                if (s.src && (s.src.includes('embed.js') || s.src.includes('wes.by')) && s.hasAttribute('data-form-id')) {
                    selfScript = s;
                    break;
                }
            }
        }

        if (selfScript && selfScript.hasAttribute('data-form-id')) {
            const formId = selfScript.getAttribute('data-form-id');
            const wrapperId = 'zhanna-wrapper-' + formId;
            // Prevent duplicate insertion
            if (!document.getElementById(wrapperId)) {
                const wrapper = document.createElement('div');
                wrapper.id = wrapperId;
                wrapper.setAttribute('data-zhanna-booking', '');
                wrapper.setAttribute('data-form-id', formId);
                selfScript.parentNode.insertBefore(wrapper, selfScript);
                if (!allContainers.includes(wrapper)) {
                    allContainers.push(wrapper);
                }
            }
        }

        if (allContainers.length === 0) return;

        let apiBase = '';
        if (document.currentScript && document.currentScript.src) {
            apiBase = (new URL(document.currentScript.src)).origin;
        } else {
            const scripts = document.getElementsByTagName('script');
            for (let i = scripts.length - 1; i >= 0; i--) {
                const s = scripts[i];
                if (s.src && (s.src.includes('embed.js') || s.src.includes('wes.by'))) {
                    apiBase = (new URL(s.src)).origin;
                    break;
                }
            }
        }

        if (!apiBase) {
            // Last resort: assume current origin
            apiBase = window.location.origin;
        }

        allContainers.forEach(container => {
            const formId = container.getAttribute('data-form-id');
            if (formId) {
                loadAndRenderForm(container, formId, apiBase);
            }
        });
    }

    async function loadAndRenderForm(container, formId, apiBase) {
        try {
            const resp = await fetch(`${apiBase}/api/config.php?action=get&type=forms&id=${formId}`);
            if (!resp.ok) throw new Error('Form not found');
            const formConfig = await resp.json();
            renderForm(container, formConfig, apiBase);
        } catch (e) {
            console.error('Failed to load booking form:', e);
            container.innerHTML = '<p style="color:red;">Form configuration not found.</p>';
        }
    }

    function renderForm(container, config, apiBase) {
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
            <form class="zhanna-form zhanna-actual-form">
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
                <div class="zhanna-status zhanna-message"></div>
            </form>
        `;

        container.innerHTML = html;

        container.querySelector('.zhanna-actual-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('.zhanna-submit');
            const statusDiv = container.querySelector('.zhanna-status');

            // Honeypot check
            if (form.hp_name.value) return;

            submitBtn.disabled = true;
            statusDiv.classList.remove('success', 'error');
            statusDiv.classList.add('zhanna-message');
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
                    statusDiv.classList.add('success');
                    statusDiv.classList.remove('error');
                    statusDiv.textContent = result.message || 'Ваша заявка успешно отправлена!';
                    form.reset();
                } else {
                    throw new Error(result.error || 'Ошибка при отправке');
                }
            } catch (err) {
                statusDiv.classList.add('error');
                statusDiv.classList.remove('success');
                statusDiv.textContent = 'Ошибка: ' + err.message;
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllForms);
    } else {
        initAllForms();
    }
})();
