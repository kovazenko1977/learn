<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать заявку - Service CRM PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen flex items-center justify-center p-4">
    <div id="app" class="w-full max-w-lg glass border border-slate-800 rounded-2xl shadow-2xl p-8">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-white mb-2" id="system-name">Service CRM PRO</h1>
            <p class="text-slate-400">Заполните форму ниже, чтобы оставить заявку на обслуживание</p>
        </div>

        <form id="request-form" class="space-y-4">
            <div id="dynamic-fields" class="space-y-4">
                <!-- Fields will be rendered here -->
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-300">Приоритет</label>
                <select name="priority" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    <option value="Low">Низкий</option>
                    <option value="Medium" selected>Средний</option>
                    <option value="High">Высокий</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-lg transition-colors shadow-lg shadow-blue-900/20">
                Отправить заявку
            </button>
        </form>

        <div id="success-msg" class="hidden text-center py-10">
            <div class="text-green-500 text-6xl mb-4">✓</div>
            <h2 class="text-2xl font-bold text-white mb-2">Заявка принята!</h2>
            <p class="text-slate-400">Ваш номер заявки: <span id="ticket-id" class="font-mono text-blue-400"></span></p>
            <button onclick="location.reload()" class="mt-6 text-blue-400 hover:underline">Создать еще одну</button>
        </div>
    </div>

    <footer class="fixed bottom-4 text-center w-full text-slate-500 text-xs">
        Разработано WES.BY +375333533971 (Разработка сайтов и приложений)
    </footer>

    <script>
        async function init() {
            const resp = await fetch('api/settings.php');
            const settings = await resp.json();
            document.getElementById('system-name').textContent = settings.system_name;

            const fieldsContainer = document.getElementById('dynamic-fields');
            settings.form_fields.forEach(field => {
                const div = document.createElement('div');
                div.className = 'space-y-2';

                const label = document.createElement('label');
                label.className = 'block text-sm font-medium text-slate-300';
                label.textContent = field.label;

                let input;
                if (field.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.rows = 3;
                } else {
                    input = document.createElement('input');
                    input.type = field.type;
                }

                input.name = field.id;
                input.required = field.required;
                input.className = 'w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition-all';

                div.appendChild(label);
                div.appendChild(input);
                fieldsContainer.appendChild(div);
            });
        }

        document.getElementById('request-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            const resp = await fetch('api/tasks.php?action=create_public', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const res = await resp.json();
            if (res.success) {
                document.getElementById('request-form').classList.add('hidden');
                document.getElementById('success-msg').classList.remove('hidden');
                document.getElementById('ticket-id').textContent = res.id;
            }
        };

        init();
    </script>
</body>
</html>
