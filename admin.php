<?php
require_once __DIR__ . '/includes/Storage.php';

session_start();
$settings = Storage::read('settings.json');

// Login logic
if (isset($_POST['password'])) {
    if ($_POST['password'] === $settings['admin_password']) {
        $_SESSION['authenticated'] = true;
    } else {
        $error = "Неверный пароль";
    }
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Check auth
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Вход в панель управления</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <form method="POST" class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-4">Вход в панель</h1>
            <?php if (isset($error)): ?>
                <div class="text-red-500 mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            <input type="password" name="password" placeholder="Пароль" class="w-full border p-2 rounded mb-4" required>
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Войти</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Simple API for admin
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_data') {
        echo json_encode([
            'settings' => Storage::read('settings.json'),
            'knowledge' => Storage::read('knowledge.json')
        ]);
        exit;
    }

    if ($action === 'save_data') {
        $input = json_decode(file_get_contents('php://input'), true);
        Storage::write('settings.json', $input['settings']);
        Storage::write('knowledge.json', $input['knowledge']);
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления Чат-ботом</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>[v-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div id="admin-app" v-cloak class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Настройки Чат-бота</h1>
            <div class="flex items-center">
                <a href="index.php" class="text-blue-600 hover:underline mr-4">На сайт</a>
                <a href="admin.php?logout=1" class="text-red-600 hover:underline mr-4">Выход</a>
                <button @click="save" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Сохранить всё</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Basic Settings -->
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">Основные настройки</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Имя бота</label>
                        <input v-model="settings.bot_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Приветствие</label>
                        <textarea v-model="settings.welcome_message" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2"></textarea>
                    </div>

                    <div class="pt-4 border-t">
                        <h3 class="font-medium mb-2">Рабочее время</h3>
                        <div class="flex items-center mb-2">
                            <input type="checkbox" v-model="settings.working_hours.enabled" class="mr-2">
                            <span>Включить проверку</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="time" v-model="settings.working_hours.start" class="border p-1 rounded">
                            <input type="time" v-model="settings.working_hours.end" class="border p-1 rounded">
                        </div>
                        <input v-model="settings.working_hours.timezone" placeholder="Timezone (Europe/Moscow)" class="w-full border p-1 rounded mb-2 text-sm">
                        <textarea v-model="settings.working_hours.out_of_hours_message" placeholder="Сообщение в нерабочее время" class="w-full border p-1 rounded text-sm"></textarea>
                    </div>

                    <div class="pt-4 border-t">
                        <h3 class="font-medium mb-2">Направления работы</h3>
                        <div v-for="(dir, i) in settings.directions" :key="i" class="flex mb-1">
                            <input v-model="settings.directions[i]" class="flex-1 border p-1 rounded text-sm mr-2">
                            <button @click="settings.directions.splice(i, 1)" class="text-red-500">×</button>
                        </div>
                        <button @click="settings.directions.push('')" class="text-blue-500 text-xs mt-1">+ Добавить направление</button>
                    </div>

                    <div class="pt-4 border-t">
                        <h3 class="font-medium mb-2">Контакты</h3>
                        <input v-model="settings.contacts.phone" placeholder="Телефон" class="w-full border p-2 mb-2 rounded">
                        <input v-model="settings.contacts.email" placeholder="Email" class="w-full border p-2 mb-2 rounded">
                        <input v-model="settings.contacts.address" placeholder="Адрес" class="w-full border p-2 rounded">
                    </div>

                    <div class="pt-4 border-t">
                        <h3 class="font-medium mb-2">Порог уверенности и пароль</h3>
                        <div class="flex items-center mb-2">
                            <span class="text-xs mr-2">Порог: {{ settings.fallback.threshold }}%</span>
                            <input type="range" v-model="settings.fallback.threshold" min="0" max="100" class="flex-1">
                        </div>
                        <input type="password" v-model="settings.admin_password" placeholder="Новый пароль админа" class="w-full border p-2 rounded text-sm">
                    </div>
                </div>
            </div>

            <!-- Knowledge Base -->
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-xl font-semibold">База знаний</h2>
                    <button @click="addQnA" class="text-blue-600 text-sm font-bold">+ Добавить</button>
                </div>

                <div class="space-y-6 max-h-[600px] overflow-y-auto pr-2">
                    <div v-for="(item, index) in knowledge" :key="index" class="p-4 bg-gray-50 rounded-lg relative">
                        <button @click="removeQnA(index)" class="absolute top-2 right-2 text-red-500 text-sm">Удалить</button>

                        <label class="block text-xs font-bold text-gray-500 uppercase">Ключевые слова (через запятую)</label>
                        <input :value="item.keywords.join(', ')"
                               @input="updateKeywords(index, $event.target.value)"
                               class="w-full border p-2 mb-2 rounded text-sm">

                        <label class="block text-xs font-bold text-gray-500 uppercase">Ответ</label>
                        <textarea v-model="item.answer" class="w-full border p-2 rounded text-sm"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
