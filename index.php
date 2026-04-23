<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация в дисконтной программе</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .input-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            outline: none;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        .btn-premium {
            background: linear-gradient(90deg, #00dbde 0%, #fc00ff 100%);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dcfce7; color: #166534; }
    </style>
</head>
<body class="p-4">
    <!-- Registration Form -->
    <div id="registration-app" class="hidden glass rounded-3xl p-8 w-full max-w-md animate__animated animate__fadeIn">
        <div class="text-center mb-8">
            <h1 id="ui-title" class="text-3xl font-bold text-white mb-2">Загрузка...</h1>
            <p id="ui-description" class="text-purple-100 opacity-80"></p>
        </div>

        <form id="registration-form" class="space-y-6">
            <div id="form-fields-container" class="space-y-4">
                <!-- Динамические поля будут здесь -->
            </div>

            <button type="submit" class="w-full btn-premium text-white font-bold py-4 rounded-xl text-lg mt-4">
                Зарегистрироваться
            </button>
        </form>
    </div>

    <!-- Personal Account (Profile) -->
    <div id="profile-app" class="hidden glass rounded-3xl p-8 w-full max-w-md animate__animated animate__fadeIn">
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-white border-opacity-30">
                <i class="fas fa-user text-3xl text-white"></i>
            </div>
            <h2 id="profile-name" class="text-2xl font-bold text-white">Здравствуйте!</h2>
            <p class="text-purple-100 opacity-60">Личный кабинет участника</p>
        </div>

        <div class="space-y-4">
            <div class="bg-white bg-opacity-5 p-4 rounded-2xl border border-white border-opacity-10">
                <div class="flex justify-between items-center">
                    <span class="text-purple-100 opacity-70">Статус программы:</span>
                    <span id="profile-status" class="status-badge">Загрузка...</span>
                </div>
            </div>

            <div class="bg-white bg-opacity-5 p-4 rounded-2xl border border-white border-opacity-10">
                <div class="text-center">
                    <span class="block text-sm text-purple-100 opacity-70 mb-1">Ваша скидка:</span>
                    <span id="profile-discount" class="text-5xl font-black text-white">0%</span>
                </div>
            </div>

            <div class="bg-white bg-opacity-5 p-6 rounded-2xl border border-white border-opacity-10">
                <h3 class="text-lg font-bold text-white mb-3">Виды услуг:</h3>
                <ul class="space-y-2 text-purple-100 opacity-80 text-sm">
                    <li>• Скидки на все основные услуги</li>
                    <li>• Бонусные баллы за посещение</li>
                    <li>• Приоритетное обслуживание</li>
                </ul>
            </div>
        </div>

        <button onclick="localStorage.clear(); location.reload();" class="w-full mt-6 text-white text-sm opacity-50 hover:opacity-100 transition-opacity flex items-center justify-center">
            <i class="fas fa-sign-out-alt mr-2"></i> Выйти из профиля
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
