document.addEventListener('DOMContentLoaded', async () => {
    const regApp = document.getElementById('registration-app');
    const profileApp = document.getElementById('profile-app');
    const form = document.getElementById('registration-form');
    const fieldsContainer = document.getElementById('form-fields-container');
    const uiTitle = document.getElementById('ui-title');
    const uiDescription = document.getElementById('ui-description');

    const regId = localStorage.getItem('reg_id');

    if (regId) {
        // Show Profile
        profileApp.classList.remove('hidden');
        await loadProfile(regId);
    } else {
        // Show Registration
        regApp.classList.remove('hidden');
        await loadRegistrationForm();
    }

    async function loadProfile(id) {
        try {
            const response = await fetch(`api/get_profile.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                document.getElementById('profile-name').textContent = data.name || 'Участник';
                document.getElementById('profile-discount').textContent = `${data.discount}%`;

                const statusEl = document.getElementById('profile-status');
                if (data.status === 'pending') {
                    statusEl.textContent = 'Ожидает подтверждения';
                    statusEl.className = 'status-badge status-pending';
                } else if (data.status === 'approved') {
                    statusEl.textContent = 'Активна';
                    statusEl.className = 'status-badge status-approved';
                }
            } else {
                localStorage.clear();
                location.reload();
            }
        } catch (error) {
            console.error('Failed to load profile', error);
        }
    }

    async function loadRegistrationForm() {
        try {
            const response = await fetch('api/settings.php');
            const settings = await response.json();

            uiTitle.textContent = settings.ui.title;
            uiDescription.textContent = settings.ui.description;

            settings.form_fields.forEach(field => {
                const wrapper = document.createElement('div');
                wrapper.className = 'space-y-1';

                const label = document.createElement('label');
                label.className = 'block text-sm font-medium text-purple-100 ml-1';
                label.textContent = field.label;

                const inputContainer = document.createElement('div');
                inputContainer.className = 'relative';

                const input = document.createElement('input');
                input.type = field.type;
                input.name = field.id;
                input.required = field.required;
                input.placeholder = `Введите ${field.label.toLowerCase()}...`;
                input.className = 'w-full px-4 py-3 rounded-xl input-glass placeholder-purple-300 placeholder-opacity-50';

                if (field.type === 'tel') {
                    input.value = '+375';
                    input.addEventListener('input', (e) => {
                        if (!e.target.value.startsWith('+375')) {
                            e.target.value = '+375';
                        }
                        e.target.value = e.target.value.replace(/[^\d+]/g, '');
                    });
                }

                inputContainer.appendChild(input);
                wrapper.appendChild(label);
                wrapper.appendChild(inputContainer);
                fieldsContainer.appendChild(wrapper);
            });
        } catch (error) {
            console.error('Failed to load settings:', error);
            uiTitle.textContent = 'Ошибка загрузки';
        }
    }

    // Handle form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Обработка...';

        try {
            const response = await fetch('api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                localStorage.setItem('reg_id', result.id);
                localStorage.setItem('reg_phone', data.phone || '');
                location.reload();
            } else {
                alert('Ошибка: ' + result.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Зарегистрироваться';
            }
        } catch (error) {
            alert('Произошла ошибка при отправке формы.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Зарегистрироваться';
        }
    });
});
