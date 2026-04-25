<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service CRM - Создание заявки</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full card">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Новая заявка</h1>
            <a href="admin.php" class="text-sm text-blue-600 hover:underline">Личный кабинет</a>
        </div>

        <form id="requestForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">Заголовок</label>
                    <input type="text" name="title" required class="input-field" placeholder="Краткое описание проблемы">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">Категория</label>
                    <select name="category" id="categorySelect" class="input-field">
                        <!-- Categories will be loaded here -->
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">Приоритет</label>
                    <select name="priority" class="input-field">
                        <option value="low">Низкий</option>
                        <option value="medium" selected>Средний</option>
                        <option value="high">Высокий</option>
                        <option value="urgent">Срочный</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Описание</label>
                <textarea name="description" rows="4" class="input-field" placeholder="Подробное описание..."></textarea>
            </div>

            <div id="customFields" class="space-y-6">
                <!-- Custom fields from constructor will be rendered here -->
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Прикрепить фото/файлы</label>
                <input type="file" id="fileInput" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
            </div>

            <button type="submit" class="w-full btn-primary py-4 text-lg">Создать заявку</button>
        </form>
    </div>

    <script>
        // Form handling logic
        async function loadSettings() {
            const res = await fetch('api/settings.php');
            const settings = await res.json();

            const categorySelect = document.getElementById('categorySelect');
            settings.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat;
                opt.textContent = cat;
                categorySelect.appendChild(opt);
            });

            // Render custom fields
            const container = document.getElementById('customFields');
            settings.form_fields.forEach(field => {
                const div = document.createElement('div');
                div.className = 'space-y-2';
                div.innerHTML = `
                    <label class="text-sm font-semibold text-slate-700">${field.label}</label>
                    <input type="${field.type}" name="custom_${field.id}" class="input-field" ${field.required ? 'required' : ''}>
                `;
                container.appendChild(div);
            });
        }

        document.getElementById('requestForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Collect custom fields
            data.custom_fields = {};
            for (let key in data) {
                if (key.startsWith('custom_')) {
                    data.custom_fields[key.replace('custom_', '')] = data[key];
                    delete data[key];
                }
            }

            // Handle file upload first
            const fileInput = document.getElementById('fileInput');
            if (fileInput.files.length > 0) {
                const fileData = new FormData();
                fileData.append('file', fileInput.files[0]);
                const uploadRes = await fetch('api/uploads.php', { method: 'POST', body: fileData });
                const uploadResult = await uploadRes.json();
                data.attachment = uploadResult.url;
            }

            const res = await fetch('api/tasks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                alert('Заявка успешно создана!');
                e.target.reset();
            } else {
                const err = await res.json();
                alert('Ошибка: ' + (err.error || 'Неизвестная ошибка'));
            }
        });

        loadSettings();
    </script>
</body>
</html>
