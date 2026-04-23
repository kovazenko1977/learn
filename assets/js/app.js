document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('registration-form');
    const fieldsContainer = document.getElementById('form-fields-container');
    const successMessage = document.getElementById('success-message');
    const uiTitle = document.getElementById('ui-title');
    const uiDescription = document.getElementById('ui-description');

    // Load settings and fields
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

            const input = document.createElement('input');
            input.type = field.type;
            input.name = field.id;
            input.required = field.required;
            input.placeholder = `Введите ${field.label.toLowerCase()}...`;
            input.className = 'w-full px-4 py-3 rounded-xl input-glass placeholder-purple-300 placeholder-opacity-50';

            wrapper.appendChild(label);
            wrapper.appendChild(input);
            fieldsContainer.appendChild(wrapper);
        });
    } catch (error) {
        console.error('Failed to load settings:', error);
        uiTitle.textContent = 'Ошибка загрузки';
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
                form.classList.add('hidden');
                document.querySelector('.text-center.mb-8').classList.add('hidden');
                successMessage.classList.remove('hidden');
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
