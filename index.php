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
    </style>
</head>
<body class="p-4">
    <div id="app" class="glass rounded-3xl p-8 w-full max-w-md animate__animated animate__fadeIn">
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

        <div id="success-message" class="hidden text-center animate__animated animate__zoomIn">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-bold text-white mb-2">Успешно!</h2>
            <p class="text-purple-100 opacity-80">Вы успешно зарегистрированы в дисконтной программе.</p>
            <button onclick="location.reload()" class="mt-6 text-white underline opacity-60 hover:opacity-100 transition-opacity">
                Вернуться назад
            </button>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
