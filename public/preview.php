<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Демонстрация Попапов</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/popup.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        .demo-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 3rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-demo {
            margin: 10px;
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-demo:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <h1 class="mb-4">Веб-приложение для всплывающих сообщений</h1>
        <p class="mb-5">Нажмите на кнопки ниже, чтобы протестировать созданные вами попапы.</p>

        <div class="d-flex flex-wrap justify-content-center">
            <button class="btn btn-light btn-demo" data-popup-code="promo_1">Акция 1 (Fade)</button>
            <button class="btn btn-warning btn-demo" data-popup-code="discount_50">Скидка 50% (Slide)</button>
            <button class="btn btn-info btn-demo" data-popup-code="welcome_gift">Подарок! (Zoom)</button>
        </div>

        <div class="mt-5">
            <a href="admin/index.php" class="btn btn-outline-light">Перейти в админку</a>
        </div>
    </div>

    <script src="js/popup.js"></script>
</body>
</html>
