<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Дисконтная программа</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        [v-cloak] { display: none; }
        .sidebar-item-active { background: rgba(255,255,255,0.1); border-left: 4px solid #fff; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div id="app" v-cloak>
        <!-- Login Overlay -->
        <div v-if="!isLoggedIn" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900">
            <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700">
                <h2 class="text-2xl font-bold mb-6 text-center">Вход в Админ-панель</h2>
                <div class="space-y-4">
                    <input v-model="loginPassword" type="password" placeholder="Пароль" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                    <button @click="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all">Войти</button>
                    <p v-if="loginError" class="text-red-400 text-sm text-center">{{ loginError }}</p>
                    <p class="text-xs text-gray-500 text-center mt-4">Пароль по умолчанию: <span class="font-mono text-gray-400">admin123</span></p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div v-else class="flex min-h-screen">
            <!-- Sidebar -->
            <div class="w-64 bg-gray-800 border-r border-gray-700">
                <div class="p-6">
                    <h1 class="text-xl font-bold text-blue-400">ULTRA ADMIN</h1>
                </div>
                <nav class="mt-4">
                    <button @click="currentTab = 'registrations'" :class="{'sidebar-item-active': currentTab === 'registrations'}" class="w-full flex items-center px-6 py-4 hover:bg-gray-700 transition-colors">
                        <i class="fas fa-users mr-3"></i> Регистрации
                    </button>
                    <button @click="currentTab = 'constructor'" :class="{'sidebar-item-active': currentTab === 'constructor'}" class="w-full flex items-center px-6 py-4 hover:bg-gray-700 transition-colors">
                        <i class="fas fa-tools mr-3"></i> Конструктор форм
                    </button>
                    <button @click="currentTab = 'settings'" :class="{'sidebar-item-active': currentTab === 'settings'}" class="w-full flex items-center px-6 py-4 hover:bg-gray-700 transition-colors">
                        <i class="fas fa-cog mr-3"></i> Настройки
                    </button>
                    <button @click="logout" class="w-full flex items-center px-6 py-4 text-red-400 hover:bg-gray-700 transition-colors mt-auto">
                        <i class="fas fa-sign-out-alt mr-3"></i> Выйти
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-auto p-8">
                <!-- Registrations Tab -->
                <div v-if="currentTab === 'registrations'">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-3xl font-bold">Регистрации</h2>
                        <div class="flex gap-4">
                            <button @click="exportCSV" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center transition-all">
                                <i class="fas fa-file-export mr-2"></i> Экспорт CSV
                            </button>
                        </div>
                    </div>

                    <!-- Stats Dashboard -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl">
                            <div class="text-gray-400 text-sm mb-1">Всего заявок</div>
                            <div class="text-3xl font-bold">{{ stats.total }}</div>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl border-l-4 border-yellow-500">
                            <div class="text-gray-400 text-sm mb-1">Ожидают</div>
                            <div class="text-3xl font-bold text-yellow-500">{{ stats.pending }}</div>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl border-l-4 border-green-500">
                            <div class="text-gray-400 text-sm mb-1">Одобрено</div>
                            <div class="text-3xl font-bold text-green-500">{{ stats.approved }}</div>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl border-l-4 border-blue-500">
                            <div class="text-gray-400 text-sm mb-1">Средняя скидка</div>
                            <div class="text-3xl font-bold text-blue-500">{{ stats.avgDiscount }}%</div>
                        </div>
                    </div>

                    <!-- Bulk Actions -->
                    <div v-if="selectedIds.length > 0" class="bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 p-4 rounded-xl mb-6 flex items-center justify-between animate__animated animate__fadeIn">
                        <div class="text-sm">Выбрано элементов: <span class="font-bold">{{ selectedIds.length }}</span></div>
                        <div class="flex gap-4 items-center">
                            <div class="flex items-center gap-2 bg-gray-800 rounded-lg px-3 py-2 border border-gray-700">
                                <span class="text-xs text-gray-400">Скидка:</span>
                                <input v-model="bulkDiscount" type="number" class="w-12 bg-transparent border-none text-sm text-center p-0">
                                <button @click="bulkAction('approve')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-bold transition-all">Одобрить выбрано</button>
                            </div>
                            <button @click="bulkAction('delete')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all">
                                <i class="fas fa-trash mr-2"></i> Удалить выбрано
                            </button>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="flex flex-col md:flex-row gap-4 mb-6">
                        <div class="flex-1 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                            <input v-model="searchQuery" type="text" placeholder="Поиск по имени, телефону или организации..."
                                   class="w-full pl-12 pr-4 py-3 bg-gray-800 border border-gray-700 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                        </div>
                        <div class="w-full md:w-48">
                            <select v-model="filterStatus" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-xl focus:outline-none focus:border-blue-500 transition-colors appearance-none">
                                <option value="all">Все статусы</option>
                                <option value="pending">Ожидают</option>
                                <option value="approved">Одобрено</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-2xl overflow-hidden border border-gray-700 shadow-xl">
                        <table class="w-full text-left">
                            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-4 w-10">
                                        <input type="checkbox" @change="toggleAll" :checked="isAllSelected">
                                    </th>
                                    <th @click="setSort('created_at')" class="px-6 py-4 cursor-pointer hover:text-white transition-colors">
                                        Дата <i class="fas ml-1 text-xs" :class="sortBy === 'created_at' ? (sortDesc ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-600'"></i>
                                    </th>
                                    <th v-for="field in settings.form_fields" :key="field.id" @click="setSort(field.id)" class="px-6 py-4 cursor-pointer hover:text-white transition-colors">
                                        {{ field.label }} <i class="fas ml-1 text-xs" :class="sortBy === field.id ? (sortDesc ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-600'"></i>
                                    </th>
                                    <th @click="setSort('discount')" class="px-6 py-4 cursor-pointer hover:text-white transition-colors">
                                        Статус / Скидка <i class="fas ml-1 text-xs" :class="sortBy === 'discount' ? (sortDesc ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-600'"></i>
                                    </th>
                                    <th class="px-6 py-4 text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="reg in filteredRegistrations" :key="reg.id" class="hover:bg-gray-750 transition-colors" :class="{'bg-blue-900 bg-opacity-10': selectedIds.includes(reg.id)}">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" :value="reg.id" v-model="selectedIds">
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-400">{{ reg.created_at }}</td>
                                    <td v-for="field in settings.form_fields" :key="field.id" class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            {{ reg[field.id] }}
                                            <a v-if="field.type === 'tel'" :href="'https://wa.me/' + reg[field.id].replace(/\D/g, '')" target="_blank" class="text-green-500 hover:text-green-400 text-xs" title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            <a v-if="field.type === 'tel'" :href="'https://t.me/' + reg[field.id].replace(/\D/g, '')" target="_blank" class="text-blue-400 hover:text-blue-300 text-xs" title="Telegram">
                                                <i class="fab fa-telegram"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="reg.status === 'pending'" class="flex items-center gap-2">
                                            <input v-model="reg.new_discount" type="number" placeholder="%" class="w-16 bg-gray-600 border-none rounded px-2 py-1 text-sm">
                                            <button @click="approveRegistration(reg)" class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded">Одобрить</button>
                                        </div>
                                        <div v-else class="flex items-center gap-2">
                                            <span class="text-green-400 font-bold text-sm">Активен</span>
                                            <div class="flex items-center bg-gray-700 rounded px-2 py-1">
                                                <input v-model="reg.discount" @change="updateDiscount(reg)" type="number" class="w-12 bg-transparent border-none text-sm font-bold text-center focus:ring-0 p-0">
                                                <span class="text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="deleteRegistration(reg.id)" class="text-red-400 hover:text-red-300 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="registrations.length === 0">
                                    <td :colspan="settings.form_fields.length + 2" class="px-6 py-12 text-center text-gray-500">Нет регистраций</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Constructor Tab -->
                <div v-if="currentTab === 'constructor'">
                    <h2 class="text-3xl font-bold mb-8">Конструктор форм</h2>
                    <!-- Constructor Implementation logic will go here in next step -->
                    <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl">
                        <div class="space-y-4">
                            <div v-for="(field, index) in settings.form_fields" :key="index" class="flex items-center gap-4 bg-gray-700 p-4 rounded-xl">
                                <i class="fas fa-grip-lines text-gray-500"></i>
                                <input v-model="field.label" placeholder="Название поля" class="bg-gray-600 border border-gray-500 rounded px-3 py-1 flex-1">
                                <select v-model="field.type" class="bg-gray-600 border border-gray-500 rounded px-3 py-1">
                                    <option value="text">Текст</option>
                                    <option value="tel">Телефон</option>
                                    <option value="email">Email</option>
                                    <option value="number">Число</option>
                                </select>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="field.required"> <span class="text-xs">Обязательно</span>
                                </label>
                                <button @click="removeField(index)" class="text-red-400 hover:text-red-300"><i class="fas fa-times"></i></button>
                            </div>
                            <button @click="addField" class="w-full py-3 border-2 border-dashed border-gray-600 rounded-xl hover:border-blue-500 hover:text-blue-500 transition-all">
                                <i class="fas fa-plus mr-2"></i> Добавить поле
                            </button>
                            <div class="pt-6 border-t border-gray-700">
                                <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all">Сохранить изменения</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div v-if="currentTab === 'settings'">
                    <h2 class="text-3xl font-bold mb-8">Настройки системы</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl">
                            <h3 class="text-xl font-bold mb-4 flex items-center"><i class="fas fa-paint-brush mr-2 text-blue-400"></i> Интерфейс</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Заголовок страницы</label>
                                    <input v-model="settings.ui.title" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Описание</label>
                                    <textarea v-model="settings.ui.description" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 h-24"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl">
                            <h3 class="text-xl font-bold mb-4 flex items-center"><i class="fas fa-lock mr-2 text-blue-400"></i> Смена пароля</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Текущий пароль</label>
                                    <input v-model="passChange.old" type="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Новый пароль</label>
                                    <input v-model="passChange.new" type="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                                </div>
                                <button @click="changePassword" class="w-full bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg transition-colors font-bold">Изменить пароль</button>
                            </div>
                        </div>

                        <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl">
                            <h3 class="text-xl font-bold mb-4 flex items-center"><i class="fab fa-telegram-plane mr-2 text-blue-400"></i> Telegram Уведомления</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Бот Токен</label>
                                    <input v-model="settings.social.telegram_token" type="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Chat ID</label>
                                    <input v-model="settings.social.telegram_chat_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2">
                                </div>
                                <p class="text-xs text-gray-500">Оставьте поля пустыми, чтобы отключить уведомления.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all">Сохранить всё</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
