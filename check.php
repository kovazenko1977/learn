<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка скидки | Дисконтная программа</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 24px; padding: 2rem; width: 100%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .input-dark { background: #0f172a; border: 1px solid #334155; color: white; width: 100%; padding: 1rem; border-radius: 12px; outline: none; transition: border-color 0.3s; }
        .input-dark:focus { border-color: #3b82f6; }
    </style>
</head>
<body class="p-4">
    <div class="card animate__animated animate__zoomIn">
        <h1 class="text-2xl font-bold text-white mb-6 text-center">Проверка скидки</h1>

        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1 ml-1">Номер телефона</label>
                <input id="phone-input" type="tel" value="+375" class="input-dark text-xl" placeholder="+375...">
            </div>

            <button id="check-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-all">
                Проверить
            </button>
        </div>

        <div id="result" class="mt-8 hidden animate__animated animate__fadeIn">
            <div id="result-content" class="p-6 rounded-2xl text-center">
                <!-- Result here -->
            </div>
        </div>
    </div>

    <script>
        document.getElementById('check-btn').onclick = async () => {
            const phone = document.getElementById('phone-input').value;
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('result-content');

            resultDiv.classList.add('hidden');

            try {
                const res = await fetch(`api/check_discount.php?phone=${encodeURIComponent(phone)}`);
                const data = await res.json();

                resultDiv.classList.remove('hidden');

                if (data.exists) {
                    if (data.status === 'approved') {
                        resultContent.className = 'p-6 rounded-2xl text-center bg-green-900 bg-opacity-30 border border-green-500 border-opacity-30';
                        resultContent.innerHTML = '';

                        const status = document.createElement('div');
                        status.className = 'text-green-400 text-sm mb-1 uppercase font-bold';
                        status.textContent = 'Скидка активна';

                        const disc = document.createElement('div');
                        disc.className = 'text-white text-4xl font-black';
                        disc.textContent = `${data.discount}%`;

                        const name = document.createElement('div');
                        name.className = 'text-gray-400 text-sm mt-2';
                        name.textContent = data.name;

                        resultContent.appendChild(status);
                        resultContent.appendChild(disc);
                        resultContent.appendChild(name);
                    } else {
                        resultContent.className = 'p-6 rounded-2xl text-center bg-yellow-900 bg-opacity-30 border border-yellow-500 border-opacity-30';
                        resultContent.innerHTML = `
                            <div class="text-yellow-400 text-sm mb-1 uppercase font-bold">На проверке</div>
                            <div class="text-white text-lg">Заявка еще не одобрена</div>
                        `;
                    }
                } else {
                    resultContent.className = 'p-6 rounded-2xl text-center bg-red-900 bg-opacity-30 border border-red-500 border-opacity-30';
                    resultContent.innerHTML = `
                        <div class="text-red-400 text-sm mb-1 uppercase font-bold">Не найдено</div>
                        <div class="text-white text-lg">Клиент не зарегистрирован</div>
                    `;
                }
            } catch (e) {
                alert('Ошибка сервера');
            }
        };

        // Auto-prefix logic
        document.getElementById('phone-input').addEventListener('input', (e) => {
            if (!e.target.value.startsWith('+375')) e.target.value = '+375';
            e.target.value = e.target.value.replace(/[^\d+]/g, '');
        });
    </script>
</body>
</html>
