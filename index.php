<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Демо Чат-бота</title>
    <link rel="stylesheet" href="assets/css/chatbot.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .welcome-screen {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .admin-link {
            display: inline-block;
            margin-top: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="welcome-screen">
        <h1>Добро пожаловать на наш сайт</h1>
        <p>Используйте виджет в углу для связи с помощником.</p>
        <a href="admin.php" class="admin-link">Панель управления</a>
    </div>

    <!-- Chat Widget -->
    <div id="chat-widget-container" v-cloak>
        <div v-if="isOpen" class="chat-window">
            <div class="chat-header">
                <span>{{ settings.bot_name }}</span>
                <button @click="toggleChat" style="background:none; border:none; color:white; cursor:pointer; font-size:20px;">×</button>
            </div>

            <div class="chat-messages" ref="messagesContainer">
                <div v-for="(msg, index) in messages" :key="index"
                     :class="['message', msg.isBot ? 'message-bot' : 'message-user']">
                    {{ msg.text }}

                    <a v-if="msg.fallback" :href="'tel:' + msg.fallback.phone" class="fallback-btn">
                        {{ msg.fallback.text }}
                    </a>
                </div>
                <div v-if="isLoading" class="message message-bot">...</div>
            </div>

            <form @submit.prevent="sendMessage" class="chat-input-area">
                <input type="text" v-model="userInput" placeholder="Введите ваш вопрос..." class="chat-input">
                <button type="submit" class="send-btn">Отправить</button>
            </form>
        </div>

        <button @click="toggleChat" class="chat-button">
            <svg v-if="!isOpen" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <span v-else>✕</span>
        </button>
    </div>

    <script src="assets/js/chatbot.js"></script>
</body>
</html>
