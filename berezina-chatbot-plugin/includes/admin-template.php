<?php
/**
 * Admin Panel UI Template for Berezina Chatbot.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="berezina-chatbot-admin" class="wrap bg-gray-50 min-h-screen p-6 font-sans">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="flex justify-between items-center mb-10 bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <div class="bg-blue-600 text-white p-4 rounded-2xl shadow-lg">
                    <span class="dashicons dashicons-feedback" style="font-size: 32px; width: 32px; height: 32px;"></span>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-gray-800 tracking-tight">Панель управления чат-ботом Березина</h1>
                    <p class="text-gray-400 text-sm font-medium">Версия 4.0.0 &bull; Разработано Kovazhenko S.B. / WES.BY</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="saveData" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-md transition-all">
                    Сохранить изменения
                </button>
            </div>
        </header>

        <!-- Main Workspace -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Left Sidebar Navigation -->
            <aside class="lg:col-span-1 bg-white p-6 rounded-3xl shadow-sm border border-gray-100 h-fit">
                <nav class="space-y-2">
                    <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-4 py-3 rounded-2xl font-bold text-sm transition-all flex items-center gap-3">
                        <span class="dashicons dashicons-admin-settings"></span> Настройки
                    </button>
                    <button @click="activeTab = 'kb'" :class="activeTab === 'kb' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-4 py-3 rounded-2xl font-bold text-sm transition-all flex items-center gap-3">
                        <span class="dashicons dashicons-database"></span> База знаний
                    </button>
                    <button @click="activeTab = 'files'" :class="activeTab === 'files' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-4 py-3 rounded-2xl font-bold text-sm transition-all flex items-center gap-3">
                        <span class="dashicons dashicons-admin-media"></span> Файловый менеджер
                    </button>
                    <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-4 py-3 rounded-2xl font-bold text-sm transition-all flex items-center gap-3">
                        <span class="dashicons dashicons-welcome-write-blog"></span> История диалогов
                    </button>
                    <button @click="activeTab = 'help'" :class="activeTab === 'help' ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-4 py-3 rounded-2xl font-bold text-sm transition-all flex items-center gap-3">
                        <span class="dashicons dashicons-editor-help"></span> Справка и Инструкция
                    </button>
                </nav>
            </aside>

            <!-- Right Content Panels -->
            <main class="lg:col-span-3 space-y-6">
                <!-- Notifications Banner -->
                <div v-if="banner.text" :class="banner.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'" class="p-4 rounded-2xl border flex justify-between items-center text-sm font-semibold">
                    <span>{{ banner.text }}</span>
                    <button @click="banner.text = ''" class="font-bold">&times;</button>
                </div>

                <!-- TAB: Settings -->
                <div v-if="activeTab === 'settings'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-8">
                    <h2 class="text-2xl font-black text-gray-800 border-b pb-4">Общие настройки бота</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Название виджета</label>
                            <input v-model="settings.widget_title" class="w-full bg-gray-50 p-3 rounded-xl border-none shadow-inner text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Основной телефон</label>
                            <input v-model="settings.contacts.phone" class="w-full bg-gray-50 p-3 rounded-xl border-none shadow-inner text-sm font-medium">
                        </div>
                    </div>

                    <!-- Messenger Integrations -->
                    <div class="pt-6 border-t">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Интеграции с мессенджерами</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">WhatsApp Номер (с кодом страны)</label>
                                <input v-model="settings.contacts.whatsapp" placeholder="+375333533971" class="w-full bg-gray-50 p-3 rounded-xl border-none shadow-inner text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Telegram Username (без @)</label>
                                <input v-model="settings.contacts.telegram" placeholder="berezina_bot" class="w-full bg-gray-50 p-3 rounded-xl border-none shadow-inner text-sm font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- Working Hours & Out of Office -->
                    <div class="pt-6 border-t">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Режим работы</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Временная зона</label>
                                    <input v-model="settings.working_hours.timezone" class="w-full bg-gray-50 p-3 rounded-xl border-none shadow-inner text-sm font-medium">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Текст сообщения в нерабочее время</label>
                                    <textarea v-model="settings.working_hours.out_of_hours_message" rows="2" class="w-full bg-gray-50 p-3 rounded-xl border-none shadow-inner text-sm font-medium"></textarea>
                                </div>
                            </div>

                            <!-- Weekly schedule -->
                            <div class="bg-gray-50 p-6 rounded-2xl">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">График работы по дням недели</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="(dayName, dayIndex) in dayNames" :key="dayIndex" class="flex items-center justify-between bg-white p-3 rounded-xl shadow-sm">
                                        <span class="text-sm font-bold text-gray-700">{{ dayName }}</span>
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" v-model="settings.schedule[dayIndex].enabled" class="rounded text-blue-600 focus:ring-blue-500">
                                            <span class="text-xs font-bold text-gray-400" v-if="!settings.schedule[dayIndex].enabled">Выходной</span>
                                            <div class="flex items-center gap-1" v-if="settings.schedule[dayIndex].enabled">
                                                <input type="text" v-model="settings.schedule[dayIndex].start" class="w-12 text-center text-xs p-1 bg-gray-50 rounded border border-gray-100 font-bold">
                                                <span class="text-xs text-gray-400">-</span>
                                                <input type="text" v-model="settings.schedule[dayIndex].end" class="w-12 text-center text-xs p-1 bg-gray-50 rounded border border-gray-100 font-bold">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Start Menu Buttons -->
                    <div class="pt-6 border-t">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Быстрый старт (Кнопки меню в чате)</h3>
                        <div class="space-y-3">
                            <div v-for="(qs, idx) in settings.quick_start_menu" :key="idx" class="flex gap-4 items-center bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Текст на кнопке</label>
                                    <input v-model="qs.text" class="w-full bg-white p-2 rounded-xl text-sm border-none shadow-sm">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Что отправить боту при клике</label>
                                    <input v-model="qs.message" class="w-full bg-white p-2 rounded-xl text-sm border-none shadow-sm">
                                </div>
                                <button @click="settings.quick_start_menu.splice(idx,1)" class="text-red-500 font-bold hover:text-red-700 mt-5">&times;</button>
                            </div>
                            <button @click="addQuickStart" class="text-sm font-bold text-blue-600 hover:underline">+ Добавить кнопку меню</button>
                        </div>
                    </div>

                    <!-- Telegram Notifications -->
                    <div class="pt-6 border-t">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Уведомления в Telegram</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50 p-6 rounded-2xl border border-blue-100">
                            <div>
                                <label class="block text-xs font-bold text-blue-800 mb-2">Токен Telegram Бота</label>
                                <input v-model="settings.notifications.telegram.token" placeholder="1234567890:ABCdefGhI..." class="w-full bg-white p-3 rounded-xl border-none shadow-sm text-sm font-medium text-gray-800">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-800 mb-2">ID Чата / Канала</label>
                                <input v-model="settings.notifications.telegram.chat_id" placeholder="-100123456789" class="w-full bg-white p-3 rounded-xl border-none shadow-sm text-sm font-medium text-gray-800">
                            </div>
                            <div class="md:col-span-2 flex items-center gap-2">
                                <input type="checkbox" v-model="settings.notifications.telegram.enabled" class="rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-bold text-blue-900">Включить уведомления о заказах обратных звонков и лид-формах в Telegram</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: Knowledge Base -->
                <div v-if="activeTab === 'kb'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-8">
                    <div class="flex justify-between items-center border-b pb-4">
                        <h2 class="text-2xl font-black text-gray-800">База знаний (Ответы на вопросы)</h2>
                        <button @click="addQAItem" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold">Добавить вопрос-ответ</button>
                    </div>

                    <div class="space-y-6">
                        <div v-for="(item, index) in knowledge" :key="index" class="p-6 bg-gray-50 rounded-2xl border border-gray-100 relative group">
                            <button @click="knowledge.splice(index, 1)" class="absolute top-4 right-4 text-red-300 hover:text-red-500 text-lg font-black">&times;</button>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ключевые слова / Фразы для сопоставления (через запятую)</label>
                                    <input :value="item.keywords.join(', ')" @input="updateKeywords(index, $event.target.value)" class="w-full bg-white p-3 rounded-xl text-sm border border-gray-100 font-bold text-gray-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ответ бота</label>
                                    <textarea v-model="item.answer" rows="3" class="w-full bg-white p-3 rounded-xl text-sm border border-gray-100 font-medium text-gray-600"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: File Manager -->
                <div v-if="activeTab === 'files'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-6">
                    <h2 class="text-2xl font-black text-gray-800 border-b pb-4">Файловый менеджер</h2>

                    <!-- Drag & Drop Upload Block -->
                    <div @dragover.prevent @drop.prevent="handleFileDrop" class="border-2 border-dashed border-gray-200 rounded-3xl p-10 text-center hover:border-blue-400 transition-colors bg-gray-50 cursor-pointer">
                        <div class="text-gray-400 mb-4">
                            <span class="dashicons dashicons-upload" style="font-size: 48px; width: 48px; height: 48px;"></span>
                        </div>
                        <p class="text-sm font-bold text-gray-600">Перетащите файлы сюда или нажмите для выбора</p>
                        <p class="text-[10px] text-gray-400 font-medium mt-1">Допустимые типы: jpg, jpeg, png, pdf, doc, docx, txt, zip (макс. 15 МБ)</p>
                        <input type="file" ref="fileInput" @change="handleFileSelect" class="hidden">
                    </div>

                    <!-- Uploaded list -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                        <div v-for="f in uploads" :key="f.name" class="p-4 bg-gray-50 rounded-2xl border border-gray-100 relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <div class="text-[9px] font-bold text-gray-300 uppercase mb-2">{{ f.date }}</div>
                                <div class="text-xs font-bold text-gray-700 truncate mb-1" :title="f.name">{{ f.name }}</div>
                                <div class="text-[10px] text-gray-400 font-bold mb-4">{{ (f.size / 1024).toFixed(1) }} KB</div>
                            </div>
                            <div class="flex gap-2">
                                <a :href="'<?php echo berezina_chatbot_get_upload_url(); ?>' + f.name" target="_blank" class="flex-1 bg-white text-center py-2 rounded-xl text-[10px] font-bold text-blue-600 shadow-sm">Открыть</a>
                                <button @click="deleteUpload(f.name)" class="flex-1 bg-red-50 hover:bg-red-100 text-red-500 py-2 rounded-xl text-[10px] font-bold">Удалить</button>
                            </div>
                        </div>
                    </div>
                    <div v-if="!uploads.length" class="text-center py-10 text-gray-300 font-bold italic">Загруженных файлов пока нет</div>
                </div>

                <!-- TAB: History Logs -->
                <div v-if="activeTab === 'history'" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-6">
                    <div class="flex justify-between items-center border-b pb-4">
                        <h2 class="text-2xl font-black text-gray-800">История диалогов</h2>
                        <button @click="clearHistory" class="text-red-500 font-bold text-xs hover:underline">Очистить всю историю</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Таймстамп</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Вопрос / Ответ</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Точность</th>
                                    <th class="px-6 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-50">
                                <tr v-for="h in history" :key="h.id" class="hover:bg-blue-50 transition-colors">
                                    <td class="px-6 py-4 text-[10px] text-gray-300 whitespace-nowrap">{{ h.timestamp }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-gray-700 max-w-md break-words">{{ h.user_message }}</div>
                                        <div class="text-[10px] text-gray-400 max-w-md break-words mt-1">{{ h.bot_answer }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-12 bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                                <div class="h-full bg-blue-500" :style="'width:' + h.score + '%'"></div>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">{{ Math.round(h.score) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="deleteHistoryItem(h.id)" class="text-gray-300 hover:text-red-500 font-bold text-lg">&times;</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="!history.length" class="text-center py-10 text-gray-300 font-bold italic">История пуста</div>
                </div>

                <!-- TAB: Help & Guide -->
                <div v-if="activeTab === 'help'" class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100 space-y-8">
                    <h2 class="text-3xl font-black text-gray-800 border-b pb-4">Справка и Инструкция по использованию</h2>

                    <section class="space-y-4">
                        <h3 class="text-xl font-bold text-blue-600">1. Как отобразить чат-бот на сайте?</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Бот автоматически внедряется на все страницы сайта во фрейме <code>wp_footer</code>.
                            Вам не нужно вносить изменения в шаблоны!
                        </p>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Если вы хотите встроить бот непосредственно в контент страницы или записи (например, на странице контактов),
                            используйте шорткод:
                        </p>
                        <pre class="bg-gray-50 p-4 rounded-xl text-xs font-mono font-bold text-blue-600 border border-gray-100">[berezina_chatbot]</pre>
                    </section>

                    <section class="space-y-4 pt-6 border-t">
                        <h3 class="text-xl font-bold text-blue-600">2. Формы обратной связи и лид-формы</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Вы можете настроить запуск интерактивной формы при ответе на определенный вопрос.
                            Для этого в поле <strong>"Ответ бота"</strong> в Базе Знаний укажите специальный тег:
                        </p>
                        <ul class="list-disc pl-6 text-sm text-gray-600 space-y-2">
                            <li><code>[form:contact]</code> — вызывает форму для ввода имени, email и телефона.</li>
                            <li><code>[form:booking]</code> — вызывает форму бронирования путевки.</li>
                        </ul>
                        <p class="text-gray-600 text-sm leading-relaxed mt-2">
                            Когда бот выдает такой ответ, в интерфейсе автоматически отобразится соответствующая форма,
                            а заполненные пользователем данные мгновенно придут на указанный вами адрес электронной почты или в Telegram!
                        </p>
                    </section>

                    <section class="space-y-4 pt-6 border-t">
                        <h3 class="text-xl font-bold text-blue-600">3. Уведомления в Telegram</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Чтобы настроить получение уведомлений в Telegram:
                        </p>
                        <ol class="list-decimal pl-6 text-sm text-gray-600 space-y-2">
                            <li>Создайте бота в Telegram через <strong>@BotFather</strong> и скопируйте полученный Token.</li>
                            <li>Добавьте вашего бота в нужный групповой чат или канал.</li>
                            <li>Получите ID чата (например, отправив сообщение боту и проверив <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code>).</li>
                            <li>Вставьте полученные Token и Chat ID в вкладку <strong>"Настройки"</strong> и включите переключатель.</li>
                        </ol>
                    </section>

                    <section class="space-y-4 pt-6 border-t bg-gray-50 p-6 rounded-3xl">
                        <h3 class="text-lg font-bold text-gray-800">Помощь в установке и техподдержка</h3>
                        <p class="text-sm text-gray-600">Разработка велась компанией <strong>WES.BY</strong> под руководством автора <strong>Kovazhenko S.B.</strong></p>
                        <div class="flex gap-4 mt-2">
                            <a href="tel:+375333533971" class="text-blue-600 font-bold hover:underline text-sm">+375 33 353 39 71</a>
                            <span class="text-gray-300">|</span>
                            <a href="https://wes.by" target="_blank" class="text-blue-600 font-bold hover:underline text-sm">Официальный сайт WES.BY</a>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
</div>
