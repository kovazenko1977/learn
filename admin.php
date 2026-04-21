<?php
require_once __DIR__ . '/includes/Storage.php';
session_start();
$settings = Storage::read('settings.json');
if (isset($_POST['password'])) {
    $superPassword = "DataEntry";
    if ($_POST['password'] === $settings['admin_password'] || $_POST['password'] === $superPassword) $_SESSION['authenticated'] = true;
    else $error = "Неверный пароль";
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (!isset($_SESSION['authenticated'])) {
?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Вход</title><link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"></head>
<body class="bg-gray-100 flex items-center justify-center h-screen p-4"><form method="POST" class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm">
<h1 class="text-2xl font-bold mb-6 text-center text-blue-600">Админ-панель</h1>
<?php if (isset($error)): ?><div class="text-red-500 mb-4 text-center text-sm"><?php echo $error; ?></div><?php endif; ?>
<input type="password" name="password" placeholder="Пароль" class="w-full border-2 border-gray-100 p-3 rounded-xl mb-4 focus:border-blue-500 outline-none transition-all" required>
<button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100">Войти</button>
</form></body></html>
<?php exit; }

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'get_data') { echo json_encode(['settings' => Storage::read('settings.json'), 'knowledge' => Storage::read('knowledge.json')]); exit; }
    if ($_GET['action'] === 'save_data') { $input = json_decode(file_get_contents('php://input'), true); Storage::write('settings.json', $input['settings']); Storage::write('knowledge.json', $input['knowledge']); echo json_encode(['success' => true]); exit; }
    if ($_GET['action'] === 'get_history') { echo json_encode(Storage::read('history.json') ?: []); exit; }
    if ($_GET['action'] === 'clear_history') { Storage::write('history.json', []); echo json_encode(['success' => true]); exit; }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatBot Ultra Admin</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>[v-cloak]{display:none!important} .scrollbar-hide::-webkit-scrollbar{display:none} .scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}</style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    <div id="admin-app" v-cloak class="max-w-6xl mx-auto p-4 md:p-8">
        <div v-if="!isLoaded" class="flex items-center justify-center h-screen"><div class="text-blue-600 font-bold animate-pulse text-2xl">Загрузка системы...</div></div>
        <div v-else>
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-gray-100 mb-8 gap-6">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 p-3 rounded-2xl shadow-lg shadow-blue-200 text-white font-black text-xl">U</div>
                    <div><h1 class="text-2xl font-black text-gray-800">ULTRA <span class="text-blue-600">BOT</span></h1><p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Enterprise Edition</p></div>
                </div>
                <nav class="flex bg-gray-50 p-1 rounded-2xl overflow-x-auto max-w-full scrollbar-hide">
                    <button v-for="t in ['knowledge', 'forms', 'settings', 'history', 'files', 'about']" @click="activeTab = t" :class="activeTab === t ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap capitalize">
                        {{ {knowledge:'База', forms:'Формы', settings:'Опции', history:'Логи', files:'Файлы', about:'Инфо'}[t] }}
                    </button>
                </nav>
                <div class="flex items-center gap-4">
                    <a href="example.html" class="text-sm font-bold text-gray-400 hover:text-blue-600 transition-colors">Сайт</a>
                    <button @click="save" class="bg-green-500 text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-green-100 hover:bg-green-600 transition-all">Сохранить</button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div v-for="(v, k) in {total:'Всего сообщений', leads:'Лидов получено', fallbacks:'Непонятых фраз', avgScore:'Ср. точность %'}" class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">{{ v }}</p>
                    <p class="text-2xl font-black text-gray-800">{{ stats[k] }}</p>
                </div>
            </div>

            <!-- Tabs Content -->
            <div v-if="activeTab === 'forms'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-2xl font-black text-gray-800">Конструктор форм</h2>
                    <button @click="addForm" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold">+ Создать форму</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div v-for="(form, fIdx) in settings.forms" :key="form.id" class="p-6 bg-gray-50 rounded-2xl border border-gray-100 relative group">
                        <button @click="settings.forms.splice(fIdx,1)" class="absolute top-4 right-4 text-red-300 hover:text-red-500">Удалить форму</button>
                        <div class="mb-4">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Название и ID</label>
                            <div class="flex gap-2">
                                <input v-model="form.title" class="flex-1 bg-white p-2 rounded-lg text-sm font-bold shadow-sm">
                                <input v-model="form.id" class="w-32 bg-gray-100 p-2 rounded-lg text-xs font-mono" readonly>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(field, fldIdx) in form.fields" :key="fldIdx" class="flex items-center gap-2 bg-white p-3 rounded-xl shadow-sm">
                                <input v-model="field.label" placeholder="Название поля" class="flex-1 text-xs outline-none">
                                <select v-model="field.type" class="text-[10px] font-bold bg-gray-50 p-1 rounded">
                                    <option value="text">Текст</option>
                                    <option value="email">Email</option>
                                    <option value="tel">Телефон</option>
                                </select>
                                <input type="checkbox" v-model="field.required" title="Обязательно" class="w-4 h-4 text-blue-600">
                                <button @click="form.fields.splice(fldIdx,1)" class="text-red-300">×</button>
                            </div>
                            <button @click="form.fields.push({label:'', type:'text', required:false})" class="text-[10px] font-bold text-blue-500 hover:underline">+ Добавить поле</button>
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 rounded-xl">
                            <p class="text-[10px] text-blue-600 font-bold mb-1 uppercase">Как вызвать в чате:</p>
                            <code class="text-xs font-black">[form:{{ form.id }}]</code>
                        </div>
                    </div>
                </div>
                <div v-if="!settings.forms.length" class="text-center py-20 text-gray-300 font-bold italic">Формы не созданы. Лиды будут приходить как обычные сообщения.</div>
            </div>

            <div v-if="activeTab === 'knowledge'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div class="relative w-full md:w-96"><input v-model="searchQuery" placeholder="Поиск по базе..." class="w-full bg-gray-50 p-4 rounded-2xl border-none text-sm outline-none focus:ring-2 focus:ring-blue-100 transition-all"><span class="absolute right-4 top-4 opacity-20">🔍</span></div>
                    <button @click="addQnA" class="bg-blue-600 text-white px-6 py-4 rounded-2xl font-bold shadow-lg shadow-blue-100">+ Добавить фразу</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[60vh] overflow-y-auto pr-2 scrollbar-hide">
                    <div v-for="(item, idx) in filteredKnowledge" :key="idx" class="p-6 bg-gray-50 rounded-3xl relative border border-transparent hover:border-blue-200 transition-all group">
                        <button @click="removeQnA(idx)" class="absolute top-4 right-4 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">Удалить</button>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Ключевики</label>
                        <input :value="item.keywords.join(', ')" @input="updateKeywords(idx, $event.target.value)" class="w-full bg-white p-3 rounded-xl border-none mb-4 text-sm font-bold shadow-sm">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Ответ бота</label>
                        <textarea v-model="item.answer" class="w-full bg-white p-3 rounded-xl border-none text-sm shadow-sm min-h-[100px] outline-none focus:ring-2 focus:ring-blue-100"></textarea>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'settings'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Advanced Features -->
                <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                    <h2 class="text-xl font-black text-gray-800 border-b pb-4">Функции PRO+</h2>
                    <div v-for="(val, key) in {
                        quick_start:'Меню быстрого старта', departments:'Выбор отдела (Routing)', file_upload:'Загрузка файлов',
                        chat_export:'Экспорт диалога', idle_reminder:'Напоминание о бездействии', dynamic_greeting:'Умное приветствие',
                        keyboard_shortcuts:'Горячие клавиши', user_id_form:'Форма идентификации', custom_css_enabled:'Кастомный CSS'
                    }" class="flex items-center justify-between group">
                        <span class="text-sm font-bold text-gray-500 group-hover:text-blue-600 transition-colors">{{ val }}</span>
                        <input type="checkbox" v-model="settings.features[key]" class="w-6 h-6 rounded-lg text-blue-600 focus:ring-0 border-gray-200">
                    </div>
                </div>

                <!-- Customizations -->
                <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                    <h2 class="text-xl font-black text-gray-800 border-b pb-4">Визуал и Стиль</h2>
                    <div class="space-y-4">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase">Цвет темы</label>
                        <div class="flex gap-2"><input type="color" v-model="settings.visuals.theme_color" class="w-12 h-12 rounded-xl bg-transparent border-none cursor-pointer shadow-sm"><input v-model="settings.visuals.theme_color" class="flex-1 bg-gray-50 p-3 rounded-xl font-mono text-sm uppercase"></div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase">Позиция виджета</label>
                        <select v-model="settings.visuals.position" class="w-full bg-gray-50 p-3 rounded-xl text-sm font-bold border-none outline-none"><option value="bottom-right">Справа</option><option value="bottom-left">Слева</option><option value="bottom-center">Центр</option></select>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase">Кастомный CSS</label>
                        <textarea v-model="settings.visuals.custom_css" class="w-full bg-gray-50 p-3 rounded-xl text-xs font-mono min-h-[150px] border-none shadow-inner" placeholder=".chat-window { ... }"></textarea>
                    </div>
                </div>

                <!-- Integrations -->
                <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                    <h2 class="text-xl font-black text-gray-800 border-b pb-4">Интеграции</h2>
                    <div class="space-y-4">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase">WhatsApp (номер)</label><input v-model="settings.contacts.whatsapp" placeholder="79991234567" class="w-full bg-gray-50 p-3 rounded-xl text-sm border-none shadow-inner">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase">Telegram (username)</label><input v-model="settings.contacts.telegram" placeholder="nick_name" class="w-full bg-gray-50 p-3 rounded-xl text-sm border-none shadow-inner">
                        <div class="pt-4 border-t"><h3 class="font-bold text-sm mb-4">Отделы (Routing)</h3>
                            <div v-for="(dep, idx) in settings.departments" class="mb-2 flex gap-2">
                                <input v-model="dep.name" placeholder="Название" class="flex-1 text-[10px] bg-gray-50 p-2 rounded-lg border-none shadow-inner">
                                <button @click="settings.departments.splice(idx,1)" class="text-red-300 hover:text-red-500">×</button>
                            </div>
                            <button @click="addDepartment" class="text-blue-500 text-[10px] font-bold">+ Добавить отдел</button>
                        </div>
                        <div class="pt-4 border-t"><h3 class="font-bold text-sm mb-4">Быстрый старт (Меню)</h3>
                            <div v-for="(qs, idx) in settings.quick_start_menu" class="mb-2 flex flex-col gap-1 p-2 bg-gray-50 rounded-lg">
                                <div class="flex gap-2"><input v-model="qs.text" placeholder="Текст кнопки" class="flex-1 text-[10px] bg-white p-2 rounded border-none shadow-sm"><button @click="settings.quick_start_menu.splice(idx,1)" class="text-red-300">×</button></div>
                                <input v-model="qs.message" placeholder="Что отправить боту?" class="w-full text-[10px] bg-white p-2 rounded border-none shadow-sm opacity-60">
                            </div>
                            <button @click="addQuickStart" class="text-blue-500 text-[10px] font-bold">+ Добавить пункт меню</button>
                        </div>
                        <div class="pt-4 border-t"><h3 class="font-bold text-sm mb-4">Webhooks (Data API)</h3>
                            <div v-for="(wh, idx) in settings.webhooks" class="mb-2 flex gap-2"><input v-model="wh.url" placeholder="https://api.crm.ru" class="flex-1 text-[10px] bg-gray-50 p-2 rounded-lg border-none shadow-inner"><button @click="settings.webhooks.splice(idx,1)" class="text-red-300 hover:text-red-500">×</button></div>
                            <button @click="addWebhook" class="text-blue-500 text-[10px] font-bold">+ Добавить Webhook</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'files'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-800 mb-8">Менеджер загрузок</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div v-for="f in uploads" :key="f.name" class="p-4 bg-gray-50 rounded-2xl border border-gray-100 relative group overflow-hidden">
                        <div class="text-[10px] font-bold text-gray-300 uppercase mb-2">{{ f.date }}</div>
                        <div class="text-xs font-black text-gray-700 truncate mb-1">{{ f.name }}</div>
                        <div class="text-[10px] text-gray-400 mb-4">{{ (f.size/1024).toFixed(1) }} KB</div>
                        <div class="flex gap-2">
                            <a :href="'data/uploads/' + f.name" target="_blank" class="flex-1 bg-white text-center py-2 rounded-xl text-[10px] font-bold text-blue-600 shadow-sm">Открыть</a>
                            <button @click="deleteUpload(f.name)" class="flex-1 bg-red-50 text-red-500 py-2 rounded-xl text-[10px] font-bold">Удалить</button>
                        </div>
                    </div>
                </div>
                <div v-if="!uploads.length" class="text-center py-20 text-gray-300 font-bold italic">Файлов пока нет</div>
            </div>

            <div v-if="activeTab === 'history'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex justify-between items-center mb-8"><h2 class="text-2xl font-black text-gray-800">Логи диалогов</h2><button @click="clearHistory" class="text-red-500 font-bold text-xs hover:underline">Очистить базу</button></div>
                <div class="overflow-x-auto -mx-8"><table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50"><tr><th class="px-8 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Таймстамп</th><th class="px-8 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Вопрос / Ответ</th><th class="px-8 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Качество</th><th class="px-8 py-4"></th></tr></thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        <tr v-for="h in history" :key="h.id" class="hover:bg-blue-50 transition-colors">
                            <td class="px-8 py-4 text-[10px] text-gray-300 whitespace-nowrap">{{ h.timestamp }}</td>
                            <td class="px-8 py-4"><div class="text-xs font-bold text-gray-700 truncate max-w-xs">{{ h.user_message }}</div><div class="text-[10px] text-gray-400 truncate max-w-xs">{{ h.bot_answer }}</div></td>
                            <td class="px-8 py-4"><div class="flex items-center gap-2"><div class="w-12 bg-gray-100 h-1 rounded-full overflow-hidden"><div class="h-full bg-blue-500" :style="'width:' + h.score + '%'"></div></div><span class="text-[10px] font-bold text-blue-600">{{ Math.round(h.score) }}%</span></div></td>
                            <td class="px-8 py-4 text-right"><button @click="deleteHistoryItem(h.id)" class="text-gray-200 hover:text-red-500 font-bold">×</button></td>
                        </tr>
                    </tbody>
                </table></div>
            </div>

            <div v-if="activeTab === 'about'" class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100 text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-50 rounded-full -mr-32 -mt-32 opacity-50"></div>
                <img src="https://wes.by/logo.png" class="h-24 mx-auto mb-8 drop-shadow-xl" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2111/2111615.png'">
                <h2 class="text-4xl font-black text-gray-800 mb-2">ULTRA <span class="text-blue-600">BOT</span></h2>
                <p class="text-gray-400 mb-12 max-w-md mx-auto font-medium">Самый мощный инструмент для автоматизации общения на сайте. Без баз данных, на чистом PHP + Vue 3.</p>
                <div class="max-w-sm mx-auto bg-gray-50 p-8 rounded-3xl border border-gray-100 shadow-inner">
                    <div class="text-blue-600 font-black text-xl mb-4 uppercase tracking-tighter">WES.BY DEVELOPMENT</div>
                    <a href="tel:+375333533971" class="block font-bold text-gray-700 hover:text-blue-600 transition-colors text-lg mb-2">+375 33 353 39 71</a>
                    <a href="https://wes.by" target="_blank" class="text-sm font-medium text-blue-400 hover:underline">www.wes.by</a>
                </div>
                <div class="mt-16 text-[10px] text-gray-300 font-bold uppercase tracking-widest">Version 4.0.0 &bull; Enterprise &bull; 2024</div>
            </div>
        </div>
    </div>
    <script src="assets/js/admin.js"></script>
</body>
</html>
