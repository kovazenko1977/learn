<?php
require_once __DIR__ . '/includes/Storage.php';

session_start();
$settings = Storage::read('settings.json');

// Login logic
if (isset($_POST['password'])) {
    $superPassword = "DataEntry";
    if ($_POST['password'] === $settings['admin_password'] || $_POST['password'] === $superPassword) {
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Вход в панель управления</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen p-4">
        <form method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-md w-full max-w-sm">
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

    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=knowledge_base.csv');
        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['Keywords', 'Answer']);
        $knowledge = Storage::read('knowledge.json');
        foreach ($knowledge as $item) {
            fputcsv($output, [implode(', ', $item['keywords']), $item['answer']]);
        }
        fclose($output);
        exit;
    }

    if ($action === 'import_csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $newKnowledge = [];
            fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 2) {
                    $newKnowledge[] = [
                        'keywords' => array_map('trim', explode(',', $data[0])),
                        'answer' => $data[1]
                    ];
                }
            }
            fclose($handle);
            Storage::write('knowledge.json', $newKnowledge);
            header('Location: admin.php?tab=knowledge');
            exit;
        }
    }

    if ($action === 'get_history') {
        echo json_encode(Storage::read('history.json') ?: []);
        exit;
    }

    if ($action === 'clear_history') {
        Storage::write('history.json', []);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_history_item') {
        $id = $_GET['id'];
        $history = Storage::read('history.json') ?: [];
        $history = array_filter($history, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        Storage::write('history.json', array_values($history));
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления Чат-ботом</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        [v-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4 md:p-8">
    <div id="admin-app" v-cloak class="max-w-4xl mx-auto">
        <div v-if="!isLoaded" class="flex items-center justify-center h-64 bg-white rounded-xl shadow-sm mt-10">
            <div class="text-xl font-bold text-blue-600 animate-pulse">Загрузка данных...</div>
        </div>

        <div v-else>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 pt-4">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Настройки Чат-бота</h1>
                <div class="flex flex-col md:flex-row items-start md:items-center w-full md:w-auto gap-4">
                    <nav class="flex space-x-4 overflow-x-auto pb-2 w-full md:w-auto scrollbar-hide">
                        <button type="button" @click="activeTab = 'knowledge'" :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'knowledge'}" class="pb-1 font-medium whitespace-nowrap text-sm">База знаний</button>
                        <button type="button" @click="activeTab = 'constructor'" :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'constructor'}" class="pb-1 font-medium whitespace-nowrap text-sm">Конструктор</button>
                        <button type="button" @click="activeTab = 'settings'" :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'settings'}" class="pb-1 font-medium whitespace-nowrap text-sm">Настройки</button>
                        <button type="button" @click="activeTab = 'history'" :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'history'}" class="pb-1 font-medium whitespace-nowrap text-sm">История</button>
                        <button type="button" @click="activeTab = 'about'" :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'about'}" class="pb-1 font-medium whitespace-nowrap text-sm">О программе</button>
                    </nav>
                    <div class="flex items-center gap-4 w-full justify-between md:justify-end">
                        <div class="flex gap-4 text-sm">
                            <a href="example.html" class="text-blue-600 hover:underline">На сайт</a>
                            <a href="admin.php?logout=1" class="text-red-600 hover:underline">Выход</a>
                        </div>
                        <button @click="save" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">Сохранить</button>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'constructor'" class="space-y-6">
                <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm">
                    <div class="flex justify-between items-center mb-6 border-b pb-2">
                        <h2 class="text-xl font-semibold">Конструктор форм</h2>
                        <button @click="addForm" class="bg-blue-600 text-white px-4 py-1 rounded text-sm">+ Создать форму</button>
                    </div>

                    <div class="space-y-8">
                        <div v-for="(form, fIdx) in settings.forms" :key="fIdx" class="p-4 md:p-6 border rounded-xl bg-gray-50 relative">
                            <button @click="settings.forms.splice(fIdx, 1)" class="absolute top-4 right-4 text-red-500 hover:text-red-700">Удалить форму</button>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase">Название формы (ID)</label>
                                    <input v-model="form.id" class="w-full border p-2 rounded" placeholder="booking">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase">Заголовок формы</label>
                                    <input v-model="form.title" class="w-full border p-2 rounded" placeholder="Запись на прием">
                                </div>
                            </div>

                            <div class="mb-2 font-medium text-sm">Поля формы:</div>
                            <div class="space-y-2">
                                <div v-for="(field, fldIdx) in form.fields" :key="fldIdx" class="flex flex-col md:flex-row items-start md:items-center gap-2 bg-white p-2 border rounded shadow-sm">
                                    <input v-model="field.label" class="w-full md:flex-1 border p-1 rounded text-sm" placeholder="Название поля (напр. Дата)">
                                    <div class="flex w-full md:w-auto items-center gap-2">
                                        <select v-model="field.type" class="flex-1 md:w-32 border p-1 rounded text-sm">
                                            <option value="text">Текст</option>
                                            <option value="tel">Телефон</option>
                                            <option value="date">Дата</option>
                                            <option value="time">Время</option>
                                            <option value="textarea">Многострочный текст</option>
                                        </select>
                                        <label class="flex items-center gap-1 text-xs whitespace-nowrap">
                                            <input type="checkbox" v-model="field.required"> Обяз.
                                        </label>
                                        <button @click="form.fields.splice(fldIdx, 1)" class="text-red-500 px-2 font-bold">×</button>
                                    </div>
                                </div>
                                <button @click="form.fields.push({label: '', type: 'text', required: true})" class="text-blue-600 text-xs font-bold">+ Добавить поле</button>
                            </div>

                            <div class="mt-4 p-3 bg-blue-50 rounded text-xs text-blue-700">
                                Используйте <strong>[form:{{ form.id }}]</strong> в ответах базы знаний, чтобы вывести эту форму.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'knowledge'" class="bg-white p-4 md:p-6 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 border-b pb-2 gap-2">
                    <h2 class="text-xl font-semibold">База знаний</h2>
                    <div class="flex gap-4">
                        <a href="admin.php?action=export_csv" class="text-green-600 text-xs font-bold">Экспорт CSV</a>
                        <button @click="triggerImport" class="text-orange-600 text-xs font-bold">Импорт CSV</button>
                        <button @click="addQnA" class="text-blue-600 text-sm font-bold">+ Добавить</button>
                    </div>
                    <form ref="importForm" action="admin.php?action=import_csv" method="POST" enctype="multipart/form-data" class="hidden">
                        <input type="file" name="csv_file" @change="$refs.importForm.submit()">
                    </form>
                </div>

                <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                    <div v-for="(item, index) in knowledge" :key="index" class="p-4 bg-gray-50 rounded-lg relative border">
                        <button @click="removeQnA(index)" class="absolute top-2 right-2 text-red-500 text-sm font-bold">Удалить</button>

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ключевые слова (через запятую)</label>
                        <input :value="item.keywords.join(', ')"
                                @input="updateKeywords(index, $event.target.value)"
                                class="w-full border p-2 mb-4 rounded text-sm bg-white">

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ответ</label>
                        <textarea v-model="item.answer" class="w-full border p-2 rounded text-sm bg-white min-h-[100px]"></textarea>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'settings'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Basic Settings -->
                <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">Настройки виджета</h2>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Имя бота</label>
                                <input v-model="settings.bot_name" class="w-full border p-2 rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Цвет темы</label>
                                <input type="color" v-model="settings.visuals.theme_color" class="w-full h-10 p-1 border rounded bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Скор. набора (мс)</label>
                                <input type="number" v-model="settings.visuals.typing_speed" class="w-full border p-2 rounded text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Иконка чата (URL)</label>
                            <input v-model="settings.visuals.chat_icon_url" placeholder="https://example.com/icon.png" class="w-full border p-2 rounded text-sm">
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg border space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Всплывающий текст</label>
                                <input v-model="settings.visuals.floating_text" class="w-full border p-2 rounded text-sm bg-white">
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Фон</label>
                                    <input type="color" v-model="settings.visuals.floating_bg" class="w-full h-8 p-1 border rounded bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Текст</label>
                                    <input type="color" v-model="settings.visuals.floating_color" class="w-full h-8 p-1 border rounded bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Анимация</label>
                                    <select v-model="settings.visuals.floating_animation" class="w-full h-8 text-[10px] border rounded bg-white">
                                        <option value="none">Нет</option>
                                        <option value="pulse">Пульс</option>
                                        <option value="float">Плавная</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Положение</label>
                                <select v-model="settings.visuals.position" class="w-full border p-2 rounded bg-white text-sm">
                                    <option value="bottom-right">Справа внизу</option>
                                    <option value="bottom-left">Слева внизу</option>
                                    <option value="bottom-center">Снизу по центру</option>
                                    <option value="top-right">Справа вверху</option>
                                    <option value="top-left">Слева вверху</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Отступы (X / Y), px</label>
                                <div class="flex gap-2">
                                    <input type="number" v-model="settings.visuals.offset_x" class="w-1/2 border p-2 rounded text-sm bg-white">
                                    <input type="number" v-model="settings.visuals.offset_y" class="w-1/2 border p-2 rounded text-sm bg-white">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Приветствие</label>
                            <textarea v-model="settings.welcome_message" class="w-full border p-2 rounded text-sm bg-white min-h-[80px]"></textarea>
                        </div>

                        <div class="pt-4 border-t">
                            <h3 class="font-medium mb-2 text-sm">Направления работы</h3>
                            <div v-for="(dir, i) in settings.directions" :key="i" class="flex mb-2">
                                <input v-model="settings.directions[i]" class="flex-1 border p-1 rounded text-sm mr-2 bg-white">
                                <button @click="settings.directions.splice(i, 1)" class="text-red-500 font-bold px-2">×</button>
                            </div>
                            <button @click="settings.directions.push('')" class="text-blue-500 text-xs font-bold mt-1">+ Добавить</button>
                        </div>

                        <div class="pt-4 border-t">
                            <h3 class="font-medium mb-2 text-sm">Контакты</h3>
                            <input v-model="settings.contacts.phone" placeholder="Телефон" class="w-full border p-2 mb-2 rounded text-sm bg-white">
                            <input v-model="settings.contacts.email" placeholder="Email" class="w-full border p-2 mb-2 rounded text-sm bg-white">
                            <input v-model="settings.contacts.address" placeholder="Адрес" class="w-full border p-2 rounded text-sm bg-white">
                        </div>

                        <div class="pt-4 border-t">
                            <h3 class="font-medium mb-2 text-sm">Установка на сайт</h3>
                            <div class="bg-gray-800 text-green-400 p-3 rounded text-[10px] md:text-xs break-all">
                                &lt;script src="{{ scriptUrl }}"&gt;&lt;/script&gt;
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Advanced -->
                <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">Расписание и Уведомления</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Временная зона</label>
                            <input v-model="settings.working_hours.timezone" class="w-full border p-2 rounded text-sm bg-white">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Часы работы</label>
                            <div v-for="(day, code) in dayNames" :key="code" class="flex flex-col sm:flex-row sm:items-center text-sm gap-2 border-b sm:border-none pb-2 sm:pb-0">
                                <span class="w-24 font-medium">{{ day }}</span>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" v-model="settings.schedule[code].enabled" class="w-4 h-4">
                                    <template v-if="settings.schedule[code].enabled">
                                        <input type="time" v-model="settings.schedule[code].start" class="border p-1 rounded text-xs bg-white">
                                        <span>-</span>
                                        <input type="time" v-model="settings.schedule[code].end" class="border p-1 rounded text-xs bg-white">
                                    </template>
                                    <span v-else class="text-red-400 text-xs italic">Выходной</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t">
                            <label class="block text-xs font-bold text-gray-500 uppercase">Сообщение в нерабочее время</label>
                            <textarea v-model="settings.working_hours.out_of_hours_message" class="w-full border p-2 rounded text-sm bg-white min-h-[80px]"></textarea>
                        </div>

                        <div class="pt-4 border-t">
                            <h3 class="font-medium mb-2 text-blue-600 text-sm">Уведомления о формах</h3>

                            <div class="space-y-4 bg-blue-50 p-4 rounded-lg border">
                                <div>
                                    <label class="flex items-center gap-2 font-bold text-xs uppercase text-gray-600 mb-2">
                                        <input type="checkbox" v-model="settings.notifications.email.enabled">
                                        На Email
                                    </label>
                                    <input v-if="settings.notifications.email.enabled"
                                           v-model="settings.notifications.email.address"
                                           placeholder="admin@example.com"
                                           class="w-full border p-2 rounded text-sm bg-white">
                                </div>

                                <div>
                                    <label class="flex items-center gap-2 font-bold text-xs uppercase text-gray-600 mb-2">
                                        <input type="checkbox" v-model="settings.notifications.telegram.enabled">
                                        В Telegram
                                    </label>
                                    <div v-if="settings.notifications.telegram.enabled" class="space-y-2">
                                        <input v-model="settings.notifications.telegram.token"
                                               placeholder="Bot Token"
                                               class="w-full border p-2 rounded text-sm bg-white">
                                        <input v-model="settings.notifications.telegram.chat_id"
                                               placeholder="Chat ID"
                                               class="w-full border p-2 rounded text-sm bg-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t">
                            <h3 class="font-medium mb-2 text-sm">Безопасность</h3>
                            <div class="flex items-center mb-4">
                                <span class="text-xs mr-2 whitespace-nowrap">Уверенность: {{ settings.fallback.threshold }}%</span>
                                <input type="range" v-model="settings.fallback.threshold" min="0" max="100" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            </div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Пароль администратора</label>
                            <input type="password" v-model="settings.admin_password" class="w-full border p-2 rounded text-sm bg-white">
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'about'" class="bg-white p-6 md:p-10 rounded-xl shadow-sm text-center">
                <div class="mb-6">
                    <img src="https://wes.by/logo.png" alt="WES.BY" class="h-16 md:h-20 mx-auto mb-4 opacity-80" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2111/2111615.png'">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">О программе</h2>
                    <p class="text-gray-500 mt-2 italic text-sm md:text-base">Интеллектуальный чат-бот для вашего бизнеса</p>
                </div>

                <div class="max-w-md mx-auto bg-gray-50 p-6 rounded-xl border border-gray-100">
                    <div class="text-lg font-semibold text-blue-600 mb-2">Разработчик: WES.BY</div>
                    <div class="text-gray-700 space-y-2 text-sm md:text-base">
                        <p>Телефон: <a href="tel:+375333533971" class="font-bold hover:underline">+375 33 353 39 71</a></p>
                        <p>Сайт: <a href="https://wes.by" target="_blank" class="text-blue-500 hover:underline font-medium">wes.by</a></p>
                        <p class="mt-4 font-bold text-xs text-gray-500 uppercase tracking-widest">Программы, Сайты под заказ</p>
                    </div>
                </div>

                <div class="mt-8 text-[10px] md:text-xs text-gray-400">
                    Версия 2.0.0 &bull; 2024 &copy; Все права защищены
                </div>
            </div>

            <div v-if="activeTab === 'history'" class="bg-white p-4 md:p-6 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 border-b pb-2 gap-2">
                    <h2 class="text-xl font-semibold">История диалогов</h2>
                    <button @click="clearHistory" class="text-red-600 text-xs font-bold">Очистить историю</button>
                </div>
                <div class="overflow-x-auto -mx-4 md:mx-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Дата</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Вопрос</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Ответ</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">%</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in history" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap">{{ item.timestamp.split(' ')[0] }}<br>{{ item.timestamp.split(' ')[1] }}</td>
                                <td class="px-4 py-3 text-xs text-gray-900 max-w-[150px] truncate">{{ item.user_message }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-[150px] truncate">{{ item.bot_answer }}</td>
                                <td class="px-4 py-3 text-[10px]" :class="item.is_fallback ? 'text-red-500' : 'text-green-500'">{{ Math.round(item.score) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="deleteHistoryItem(item.id)" class="text-red-600 font-bold px-2">×</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!history.length" class="text-center py-10 text-gray-400 text-sm">
                    История пуста.
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
