<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e293b">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3067/3067451.png">
    <title>Старовойтов tools pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-900">
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-slate-800 text-white shadow-md p-4 flex justify-between items-center sticky top-0 z-50">
            <div class="flex items-center space-x-4">
                <button @click="showSidebar = !showSidebar" class="lg:hidden p-2 text-gray-300">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="bg-blue-600 p-2 rounded text-xl font-bold shadow-lg">S</div>
                <h1 class="text-sm lg:text-xl font-semibold uppercase tracking-wider truncate">Старовойтов tools pro</h1>
            </div>
            <div class="flex items-center space-x-6">
                <button @click="toggleDemoMode" :class="isDemoMode ? 'bg-orange-600' : 'bg-slate-700'" class="px-2 lg:px-4 py-1 rounded-full text-[10px] lg:text-xs font-bold transition-all flex items-center shadow-inner">
                    <span :class="isDemoMode ? 'bg-white' : 'bg-orange-500'" class="w-2 h-2 rounded-full lg:mr-2 animate-pulse"></span>
                    <span class="hidden lg:inline">{{ isDemoMode ? 'ДЕМО-РЕЖИМ: ВКЛ' : 'ДЕМО-РЕЖИМ: ВЫКЛ' }}</span>
                    <span class="lg:hidden">{{ isDemoMode ? 'ДЕМО' : 'ДЕМО' }}</span>
                </button>
                <div id="connection-status" class="flex items-center space-x-2 text-[10px] lg:text-sm">
                    <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse flex-shrink-0"></span>
                    <span class="hidden sm:inline">Пульт подключен (COM3)</span>
                    <span class="sm:hidden">COM3</span>
                </div>
                <div class="flex space-x-2">
                    <button @click="readFromDevice" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-sm transition">
                        <i class="fas fa-download mr-1"></i> Читать
                    </button>
                    <button @click="writeToDevice" class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-sm transition">
                        <i class="fas fa-upload mr-1"></i> Записать
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden relative">
            <!-- Sidebar Navigation -->
            <nav :class="showSidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed lg:relative lg:translate-x-0 z-40 w-64 h-full bg-slate-700 text-gray-300 flex-shrink-0 overflow-y-auto transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
                <div class="p-4 uppercase text-xs font-bold text-gray-500 tracking-widest">Меню</div>
                <ul class="space-y-1">
                    <li><button @click="currentTab = 'devices'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'devices'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-microchip w-6"></i>Приборы</button></li>
                    <li><button @click="currentTab = 'partitions'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'partitions'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-th-large w-6"></i>Разделы</button></li>
                    <li><button @click="currentTab = 'relays'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'relays'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-toggle-on w-6"></i>Реле и тактики</button></li>
                    <li><button @click="currentTab = 'users'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'users'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-users w-6"></i>Пользователи</button></li>
                    <li><button @click="currentTab = 'zones'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'zones'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-sensor w-6"></i>Входные зоны</button></li>
                    <li><button @click="currentTab = 'scenarios'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'scenarios'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-project-diagram w-6"></i>Сценарии</button></li>
                    <li><button @click="currentTab = 'events'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'events'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-history w-6"></i>События ЖКИ</button></li>
                    <li><button @click="currentTab = 'about'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'about'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-info-circle w-6"></i>О программе</button></li>
                </ul>

                <div class="mt-8 p-4 uppercase text-xs font-bold text-gray-500 tracking-widest border-t border-slate-600">Файл</div>
                <div class="px-6 py-2 space-y-2">
                    <button @click="exportConfig('text')" class="w-full text-left text-sm hover:text-white transition"><i class="fas fa-file-alt mr-2"></i>Сохранить как текст</button>
                    <button @click="exportConfig('encrypted')" class="w-full text-left text-sm hover:text-white transition"><i class="fas fa-file-shield mr-2"></i>Сохранить зашифрованным</button>
                    <label class="block w-full text-left text-sm hover:text-white transition cursor-pointer">
                        <i class="fas fa-folder-open mr-2"></i>Загрузить файл
                        <input type="file" @change="importConfig" class="hidden" accept=".txt,.bin">
                    </label>
                </div>
            </nav>

            <!-- Overlay for mobile sidebar -->
            <div v-if="showSidebar" @click="showSidebar = false" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"></div>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto bg-white p-4 lg:p-8 relative">
                <!-- Simulation Overlay -->
                <div v-if="isDemoMode" class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-500 via-yellow-400 to-orange-500 animate-pulse z-10"></div>

                <!-- Event Log Drawer (Fixed Bottom) -->
                <div v-if="events.length > 0" class="fixed bottom-0 right-0 w-full lg:w-96 max-h-64 bg-slate-900 text-gray-300 shadow-2xl lg:rounded-tl-xl border-l border-t border-slate-700 overflow-hidden flex flex-col z-40">
                    <div class="p-3 bg-slate-800 flex justify-between items-center border-b border-slate-700">
                        <span class="text-xs font-bold uppercase tracking-widest text-slate-400"><i class="fas fa-terminal mr-2"></i>Лента событий</span>
                        <button @click="events = []" class="text-[10px] hover:text-white uppercase">Очистить</button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 font-mono text-[11px] space-y-1">
                        <div v-for="(event, idx) in events" :key="idx" class="flex space-x-2">
                            <span class="text-gray-500">[{{ event.time }}]</span>
                            <span :class="{
                                'text-red-400 font-bold': event.type === 'alarm',
                                'text-yellow-400': event.type === 'warning',
                                'text-green-400': event.type === 'status',
                                'text-blue-400': event.type === 'info'
                            }">{{ event.text }}</span>
                        </div>
                    </div>
                </div>
                <!-- Devices Tab -->
                <section v-if="currentTab === 'devices'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Список подключенных приборов</h2>
                        <button @click="showAddDeviceModal = true" class="bg-slate-800 text-white px-4 py-2 rounded shadow hover:bg-slate-900 transition">
                            <i class="fas fa-plus mr-2"></i> Добавить прибор
                        </button>
                    </div>

                    <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-x-auto">
                        <table class="w-full text-left min-w-[600px]">
                            <thead class="bg-gray-100 border-b border-gray-200 text-gray-600 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3">Адрес</th>
                                    <th class="px-6 py-3">Тип прибора</th>
                                    <th class="px-6 py-3">Версия</th>
                                    <th class="px-6 py-3 text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(device, index) in config.devices" :key="index" class="hover:bg-blue-50 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-blue-600">{{ device.address }}</td>
                                    <td class="px-6 py-4">{{ device.type }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ device.version }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeDevice(index)" class="text-red-500 hover:text-red-700 transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="config.devices.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">Приборы не добавлены</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Partitions Tab -->
                <section v-if="currentTab === 'partitions'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Разделы и группы разделов</h2>
                        <button @click="addPartition" class="bg-slate-800 text-white px-4 py-2 rounded shadow hover:bg-slate-900 transition">
                            <i class="fas fa-plus mr-2"></i> Создать раздел
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(partition, index) in config.partitions" :key="index" class="border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-4">
                                <div class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded">ID: {{ index + 1 }}</div>
                                <button @click="removePartition(index)" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
                            </div>
                            <input v-model="partition.name" class="w-full text-lg font-bold mb-2 border-b border-transparent focus:border-blue-500 focus:outline-none bg-transparent" placeholder="Название раздела">
                            <div class="text-sm text-gray-500 mb-4">Шлейфов: {{ partition.loops ? partition.loops.length : 0 }}</div>
                            <div class="flex space-x-2">
                                <button class="flex-1 text-xs bg-gray-100 hover:bg-gray-200 py-2 rounded transition">Редактировать шлейфы</button>
                                <button class="flex-1 text-xs bg-gray-100 hover:bg-gray-200 py-2 rounded transition">Привязка реле</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- About Tab -->
                <section v-if="currentTab === 'about'" class="flex flex-col items-center justify-center h-full text-center">
                    <div class="bg-blue-50 p-12 rounded-2xl border border-blue-100 shadow-inner max-w-2xl">
                        <div class="bg-blue-600 text-white w-20 h-20 rounded-2xl flex items-center justify-center text-4xl font-bold mx-auto mb-6 shadow-lg">S</div>
                        <h2 class="text-3xl font-extrabold text-slate-800 mb-4 tracking-tight">Старовойтов tools pro</h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-8">
                            Профессиональный комплекс для конфигурирования систем безопасности через интерфейс RS-485.
                        </p>
                        <div class="border-t border-blue-200 pt-8 mt-4 text-slate-500">
                            <p class="mb-2 font-medium">Разработчик:</p>
                            <p class="text-xl text-slate-700 font-bold mb-1">Коваженко С.Б.</p>
                            <a href="https://wes.by" target="_blank" class="text-blue-600 hover:underline">wes.by</a>
                            <div class="mt-8 text-sm italic">
                                Специально для Старовойтова Алексея.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Users Tab -->
                <section v-if="currentTab === 'users'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Пользователи и пароли</h2>
                        <button @click="addUser" class="bg-slate-800 text-white px-4 py-2 rounded shadow hover:bg-slate-900 transition">
                            <i class="fas fa-user-plus mr-2"></i> Добавить пользователя
                        </button>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Имя / Описание</th>
                                    <th class="px-6 py-4">Пароль / PIN</th>
                                    <th class="px-6 py-4">Полномочия</th>
                                    <th class="px-6 py-4 text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(user, index) in config.users" :key="index">
                                    <td class="px-6 py-4">
                                        <input v-model="user.name" class="w-full focus:outline-none focus:ring-1 focus:ring-blue-500 rounded px-1" placeholder="Введите имя">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input v-model="user.password" type="password" class="w-24 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500 rounded px-1" placeholder="******">
                                    </td>
                                    <td class="px-6 py-4">
                                        <select v-model="user.role" class="bg-gray-50 border-none text-sm focus:ring-0 rounded">
                                            <option value="user">Пользователь</option>
                                            <option value="operator">Оператор</option>
                                            <option value="admin">Администратор</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeUser(index)" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-user-minus"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Relays Tab -->
                <section v-if="currentTab === 'relays'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Управление реле и тактики</h2>
                        <button @click="addRelay" class="bg-slate-800 text-white px-4 py-2 rounded shadow hover:bg-slate-900 transition">
                            <i class="fas fa-plus mr-2"></i> Добавить реле
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="(relay, index) in config.relays" :key="index" class="p-4 border rounded-lg flex items-center space-x-4 bg-gray-50">
                            <div class="font-bold text-lg">#{{ index + 1 }}</div>
                            <div class="flex-1 grid grid-cols-3 gap-4">
                                <input v-model="relay.name" placeholder="Название реле" class="border p-2 rounded">
                                <select v-model="relay.tactic" class="border p-2 rounded">
                                    <option value="1">Включить при пожаре</option>
                                    <option value="2">Выключить при пожаре</option>
                                    <option value="3">ПЦН</option>
                                    <option value="4">Лампа</option>
                                </select>
                                <select v-model="relay.partition" class="border p-2 rounded">
                                    <option v-for="(p, pi) in config.partitions" :value="pi">Раздел: {{ p.name }}</option>
                                </select>
                            </div>
                            <button @click="config.relays.splice(index, 1)" class="text-red-500"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </section>

                <!-- Zones Tab -->
                <section v-if="currentTab === 'zones'">
                    <h2 class="text-2xl font-bold mb-6">Настройка входных зон</h2>
                    <div class="bg-white border rounded shadow-sm">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-xs">
                                <tr>
                                    <th class="p-3 text-left">Зона</th>
                                    <th class="p-3 text-left">Тип ШС</th>
                                    <th class="p-3 text-left">Раздел</th>
                                    <th class="p-3 text-left">Задержка</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="i in 8" :key="i" class="border-t">
                                    <td class="p-3">Шлейф {{ i }}</td>
                                    <td class="p-3">
                                        <select class="border text-sm p-1">
                                            <option>Охранный</option>
                                            <option>Пожарный</option>
                                            <option>Тревожный</option>
                                        </select>
                                    </td>
                                    <td class="p-3">
                                        <select class="border text-sm p-1">
                                            <option v-for="(p, pi) in config.partitions" :value="pi">{{ p.name }}</option>
                                        </select>
                                    </td>
                                    <td class="p-3"><input type="number" class="border w-16 p-1 text-sm" value="0"> сек.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Scenarios Tab -->
                <section v-if="currentTab === 'scenarios'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Сценарии управления (C2000M)</h2>
                        <button @click="config.scenarios.push({name: 'Новый сценарий', type: 'rele'})" class="bg-slate-800 text-white px-4 py-2 rounded shadow">Создать сценарий</button>
                    </div>
                    <div class="space-y-4">
                        <div v-for="(s, si) in config.scenarios" :key="si" class="p-4 border rounded-lg bg-blue-50">
                            <div class="flex justify-between mb-2">
                                <input v-model="s.name" class="font-bold bg-transparent border-b">
                                <button @click="config.scenarios.splice(si, 1)" class="text-red-500"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="block font-medium">Тип управления:</label>
                                    <select v-model="s.type" class="w-full border p-1 rounded">
                                        <option value="rele">Управление реле</option>
                                        <option value="fire">Пожаротушение</option>
                                        <option value="access">Доступ</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-medium">Условие запуска:</label>
                                    <select class="w-full border p-1 rounded">
                                        <option>Пожар 1</option>
                                        <option>Пожар 2</option>
                                        <option>Взлом</option>
                                        <option>Нарушение</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Events Tab -->
                <section v-if="currentTab === 'events'">
                    <h2 class="text-2xl font-bold mb-6">Переименование событий ЖКИ</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="(evt, ei) in defaultEvents" :key="ei" class="p-3 border rounded flex items-center justify-between">
                            <span class="text-gray-500 text-sm">{{ evt.original }}</span>
                            <i class="fas fa-arrow-right text-gray-300"></i>
                            <input v-model="evt.custom" class="border p-1 rounded text-sm w-1/2" :placeholder="evt.original">
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <!-- Modals -->
        <div v-if="showAddDeviceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-6">
                <h3 class="text-xl font-bold mb-4">Добавить новый прибор</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Сетевой адрес (1-127)</label>
                        <input v-model="newDevice.address" type="number" min="1" max="127" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Тип прибора</label>
                        <select v-model="newDevice.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option>С2000-4</option>
                            <option>С2000-КДЛ</option>
                            <option>С2000-КПБ</option>
                            <option>С2000-СП1</option>
                            <option>Сигнал-20</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="showAddDeviceModal = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Отмена</button>
                    <button @click="saveNewDevice" class="px-4 py-2 bg-slate-800 text-white rounded hover:bg-slate-900 transition">Добавить</button>
                </div>
            </div>
        </div>

        <!-- Notification Toast -->
        <div v-if="toast.show" :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'" class="fixed bottom-4 right-4 text-white px-6 py-3 rounded-lg shadow-xl z-50 transition-all transform duration-300">
            <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'" class="mr-2"></i>
            {{ toast.message }}
        </div>
    </div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>
