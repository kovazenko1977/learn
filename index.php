<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e293b">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3067/3067451.png">
    <title>Старовойтов tools pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-900 overflow-x-hidden w-full">
    <div id="app" v-cloak class="min-h-screen flex flex-col w-full overflow-x-hidden">
        <!-- PWA Install Banner -->
        <div v-if="deferredPrompt" class="bg-blue-700 text-white p-4 flex justify-between items-center z-[60] sticky top-0 shadow-lg animate-bounce-slow">
            <div class="flex items-center">
                <i class="fas fa-mobile-alt mr-3 text-xl"></i>
                <div class="text-sm">
                    <div class="font-bold">Установить как приложение</div>
                    <div class="text-blue-100 text-[10px]">Работайте быстрее и без интернета</div>
                </div>
            </div>
            <div class="flex space-x-2">
                <button @click="installApp" class="bg-white text-blue-700 px-4 py-1 rounded-full text-xs font-bold">УСТАНОВИТЬ</button>
                <button @click="deferredPrompt = null" class="p-1"><i class="fas fa-times"></i></button>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-slate-800 text-white shadow-md p-4 flex justify-between items-center sticky top-0 z-50 w-full">
            <div class="flex items-center space-x-4">
                <button @click="showSidebar = !showSidebar" class="lg:hidden p-2 text-gray-300">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="bg-blue-600 p-2 rounded text-xl font-bold shadow-lg">S</div>
                <h1 class="text-sm lg:text-xl font-semibold uppercase tracking-wider truncate">Старовойтов tools pro</h1>
            </div>
            <div class="flex items-center space-x-2 lg:space-x-6">
                <button @click="toggleDemoMode" :class="isDemoMode ? 'bg-orange-600' : 'bg-slate-700'" class="px-2 lg:px-4 py-1 rounded-full text-[10px] lg:text-xs font-bold transition-all flex items-center shadow-inner">
                    <span :class="isDemoMode ? 'bg-white' : 'bg-orange-500'" class="w-2 h-2 rounded-full lg:mr-2 animate-pulse flex-shrink-0"></span>
                    <span class="hidden lg:inline">{{ isDemoMode ? 'ДЕМО-РЕЖИМ: ВКЛ' : 'ДЕМО-РЕЖИМ: ВЫКЛ' }}</span>
                    <span class="lg:hidden">{{ isDemoMode ? 'ДЕМО' : 'ДЕМО' }}</span>
                </button>
            <div id="connection-status" class="hidden md:flex items-center space-x-2 text-[10px] lg:text-sm text-green-400">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse flex-shrink-0"></span>
                <span class="hidden lg:inline">Пульт подключен (COM3)</span>
                <span class="lg:hidden">COM3</span>
                </div>
            <div class="flex space-x-1 lg:space-x-2">
                <button @click="readFromDevice" class="bg-blue-600 hover:bg-blue-700 px-2 lg:px-3 py-1.5 lg:py-1 rounded-lg text-[10px] lg:text-sm transition flex items-center">
                    <i class="fas fa-download lg:mr-1"></i> <span class="hidden lg:inline">Читать</span>
                    </button>
                <button @click="writeToDevice" class="bg-green-600 hover:bg-green-700 px-2 lg:px-3 py-1.5 lg:py-1 rounded-lg text-[10px] lg:text-sm transition flex items-center">
                    <i class="fas fa-upload lg:mr-1"></i> <span class="hidden lg:inline">Записать</span>
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
                    <li><button @click="currentTab = 'dashboard'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'dashboard'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-tachometer-alt w-6"></i>Главная</button></li>
                    <li><button @click="currentTab = 'devices'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'devices'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-microchip w-6"></i>Приборы</button></li>
                    <li><button @click="currentTab = 'partitions'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'partitions'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-th-large w-6"></i>Разделы</button></li>
                    <li><button @click="currentTab = 'relays'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'relays'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-toggle-on w-6"></i>Реле и тактики</button></li>
                    <li><button @click="currentTab = 'users'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'users'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-users w-6"></i>Пользователи</button></li>
                    <li><button @click="currentTab = 'zones'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'zones'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-sensor w-6"></i>Входные зоны</button></li>
                    <li><button @click="currentTab = 'scenarios'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'scenarios'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-project-diagram w-6"></i>Сценарии</button></li>
                    <li><button @click="currentTab = 'events'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'events'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-history w-6"></i>События ЖКИ</button></li>
                    <li><button @click="currentTab = 'about'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'about'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-info-circle w-6"></i>О программе</button></li>
                    <li><button @click="currentTab = 'help'; showSidebar = false" :class="{'bg-slate-600 text-white': currentTab === 'help'}" class="w-full text-left px-6 py-3 hover:bg-slate-600 transition flex items-center"><i class="fas fa-question-circle w-6"></i>Справка</button></li>
                    <li v-if="deferredPrompt" class="lg:hidden">
                        <button @click="installApp" class="w-full text-left px-6 py-4 bg-blue-600 text-white hover:bg-blue-500 transition flex items-center font-bold">
                            <i class="fas fa-download w-6"></i>Установить приложение
                        </button>
                    </li>
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
            <main class="flex-1 overflow-y-auto bg-slate-50 p-4 lg:p-8 relative">
                <!-- Simulation Overlay -->
                <div v-if="isDemoMode" class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-500 via-yellow-400 to-orange-500 animate-pulse z-10"></div>

                <!-- Dashboard / Console -->
                <section v-if="currentTab === 'dashboard'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center space-x-4">
                            <div class="bg-blue-100 p-3 rounded-xl text-blue-600"><i class="fas fa-microchip text-2xl"></i></div>
                            <div>
                                <div class="text-sm text-slate-500">Приборов</div>
                                <div class="text-2xl font-bold">{{ config.devices.length }}</div>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center space-x-4">
                            <div class="bg-purple-100 p-3 rounded-xl text-purple-600"><i class="fas fa-th-large text-2xl"></i></div>
                            <div>
                                <div class="text-sm text-slate-500">Разделов</div>
                                <div class="text-2xl font-bold">{{ config.partitions.length }}</div>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center space-x-4">
                            <div class="bg-green-100 p-3 rounded-xl text-green-600"><i class="fas fa-users text-2xl"></i></div>
                            <div>
                                <div class="text-sm text-slate-500">Пользователей</div>
                                <div class="text-2xl font-bold">{{ config.users.length }}</div>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center space-x-4">
                            <div class="bg-orange-100 p-3 rounded-xl text-orange-600"><i class="fas fa-project-diagram text-2xl"></i></div>
                            <div>
                                <div class="text-sm text-slate-500">Сценариев</div>
                                <div class="text-2xl font-bold">{{ config.scenarios.length }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-900 rounded-3xl p-6 lg:p-10 text-white shadow-2xl relative overflow-hidden">
                        <div class="absolute -right-20 -top-20 w-64 h-64 bg-blue-500 rounded-full opacity-10 blur-3xl"></div>
                        <div class="relative z-10 flex flex-col lg:flex-row justify-between items-start lg:items-center space-y-6 lg:space-y-0">
                            <div class="space-y-2">
                                <h2 class="text-3xl font-bold tracking-tight">Пульт С2000М</h2>
                                <p class="text-slate-400">Статус: <span class="text-green-400 font-medium">Работа в норме</span></p>
                                <div class="flex space-x-4 mt-6">
                                    <div class="text-center">
                                        <div class="text-xs uppercase text-slate-500 mb-1">Версия</div>
                                        <div class="bg-slate-800 px-3 py-1 rounded-lg text-sm font-mono">v4.12</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-xs uppercase text-slate-500 mb-1">События</div>
                                        <div class="bg-slate-800 px-3 py-1 rounded-lg text-sm font-mono">{{ events.length }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full lg:w-auto flex flex-col space-y-3">
                                <button @click="readFromDevice" class="w-full lg:w-64 bg-blue-600 hover:bg-blue-500 text-white py-4 px-6 rounded-2xl font-bold shadow-lg shadow-blue-900/20 transition-all flex justify-between items-center group">
                                    <span>Считать конфигурацию</span>
                                    <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-500"></i>
                                </button>
                                <button @click="writeToDevice" class="w-full lg:w-64 bg-slate-800 hover:bg-slate-700 text-white py-4 px-6 rounded-2xl font-bold border border-slate-700 transition-all flex justify-between items-center group">
                                    <span>Записать изменения</span>
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

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
                        <h2 class="text-2xl font-bold">Список приборов</h2>
                        <button @click="showAddDeviceModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-xl shadow-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i> <span class="hidden sm:inline">Добавить прибор</span>
                        </button>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-bold">
                                <tr>
                                    <th class="px-6 py-4">Адрес</th>
                                    <th class="px-6 py-4">Тип прибора</th>
                                    <th class="px-6 py-4">Версия</th>
                                    <th class="px-6 py-4 text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(device, index) in config.devices" :key="index" class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4"><span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-mono font-bold">{{ device.address }}</span></td>
                                    <td class="px-6 py-4 font-medium text-slate-700">{{ device.type }}</td>
                                    <td class="px-6 py-4 text-slate-500 font-mono">{{ device.version }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeDevice(index)" class="text-slate-400 hover:text-red-500 transition p-2">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="config.devices.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">Список приборов пуст</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="lg:hidden grid grid-cols-1 gap-4">
                        <div v-for="(device, index) in config.devices" :key="index" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <div class="bg-blue-600 text-white w-12 h-12 rounded-xl flex items-center justify-center font-bold text-xl font-mono shadow-md shadow-blue-100">{{ device.address }}</div>
                                <div>
                                    <div class="font-bold text-slate-800">{{ device.type }}</div>
                                    <div class="text-xs text-slate-500 font-mono">Версия: {{ device.version }}</div>
                                </div>
                            </div>
                            <button @click="removeDevice(index)" class="bg-red-50 text-red-600 w-10 h-10 rounded-xl flex items-center justify-center transition hover:bg-red-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div v-if="config.devices.length === 0" class="text-center py-12 text-slate-400 italic bg-white rounded-2xl border border-dashed border-slate-300">
                            Приборы не добавлены
                        </div>
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

                <!-- Help Tab -->
                <section v-if="currentTab === 'help'" class="max-w-4xl mx-auto space-y-8 pb-20">
                    <h2 class="text-3xl font-bold text-slate-800">Справка по работе с программой</h2>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <h3 class="text-xl font-bold text-blue-600 mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-3"></i> Суть программы
                        </h3>
                        <p class="text-slate-600 leading-relaxed">
                            <strong>Старовойтов tools pro</strong> — это кроссплатформенное веб-приложение, предназначенное для полноценной настройки и администрирования охранно-пожарного оборудования компании "Болид" (пультов С2000М и подключенных к ним устройств) через преобразователь интерфейса RS-485.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center">
                                <span class="bg-slate-100 text-slate-700 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">1</span>
                                Подключение
                            </h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Подключите преобразователь RS-485 к USB-порту вашего устройства. Убедитесь, что драйверы установлены и порт COM3 (по умолчанию) доступен. В мобильной версии используйте OTG-адаптер.
                            </p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center">
                                <span class="bg-slate-100 text-slate-700 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">2</span>
                                Чтение данных
                            </h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Нажмите кнопку <strong>"Считать конфигурацию"</strong> на главной странице. Программа опросит пульт и загрузит текущие настройки приборов, разделов и пользователей в память приложения.
                            </p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center">
                                <span class="bg-slate-100 text-slate-700 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">3</span>
                                Редактирование
                            </h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Используйте боковое меню для перехода в нужные разделы. Вы можете добавлять приборы, создавать логические группы шлейфов (разделы), менять пароли и настраивать сценарии автоматизации.
                            </p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center">
                                <span class="bg-slate-100 text-slate-700 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">4</span>
                                Запись и экспорт
                            </h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                После внесения изменений нажмите <strong>"Записать изменения"</strong>. Также вы можете сохранить конфигурацию в файл (текстовый или зашифрованный .bin) для последующей работы без подключения к пульту.
                            </p>
                        </div>
                    </div>

                    <div class="bg-orange-50 p-6 rounded-2xl border border-orange-100">
                        <h3 class="font-bold text-orange-800 mb-2 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-3"></i> Важное замечание
                        </h3>
                        <p class="text-sm text-orange-700 leading-relaxed">
                            Перед записью конфигурации в пульт С2000М убедитесь, что питание системы стабильно. Прерывание процесса записи может привести к сбросу настроек пульта до заводских значений.
                        </p>
                    </div>

                    <div class="bg-blue-600 p-8 rounded-3xl text-white">
                        <h3 class="text-xl font-bold mb-4">Демо-режим</h3>
                        <p class="text-blue-100 leading-relaxed mb-6">
                            Для ознакомления с интерфейсом без реального оборудования используйте кнопку <strong>"ДЕМО-РЕЖИМ"</strong> в верхней панели. Это активирует эмуляцию системных событий и позволяет протестировать все функции программы.
                        </p>
                        <button @click="currentTab = 'dashboard'" class="bg-white text-blue-600 px-6 py-2 rounded-xl font-bold hover:bg-blue-50 transition">Попробовать сейчас</button>
                    </div>
                </section>

                <!-- About Tab -->
                <section v-if="currentTab === 'about'" class="flex flex-col items-center justify-center h-full text-center py-10 lg:py-0">
                    <div class="bg-blue-50 p-8 lg:p-12 rounded-3xl border border-blue-100 shadow-inner max-w-2xl w-full">
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
                        <h2 class="text-2xl font-bold">Пользователи</h2>
                        <button @click="addUser" class="bg-slate-800 text-white px-4 py-2 rounded-xl shadow-lg hover:bg-slate-900 transition flex items-center">
                            <i class="fas fa-user-plus sm:mr-2"></i> <span class="hidden sm:inline">Добавить</span>
                        </button>
                    </div>

                    <!-- Desktop -->
                    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Имя / Описание</th>
                                    <th class="px-6 py-4">Пароль / PIN</th>
                                    <th class="px-6 py-4">Полномочия</th>
                                    <th class="px-6 py-4 text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(user, index) in config.users" :key="index" class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <input v-model="user.name" class="w-full bg-transparent border-b border-transparent focus:border-blue-500 focus:outline-none py-1" placeholder="Введите имя">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input v-model="user.password" type="password" class="w-24 font-mono bg-transparent border-b border-transparent focus:border-blue-500 focus:outline-none py-1" placeholder="******">
                                    </td>
                                    <td class="px-6 py-4">
                                        <select v-model="user.role" class="bg-slate-100 border-none text-sm rounded-lg px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                            <option value="user">Пользователь</option>
                                            <option value="operator">Оператор</option>
                                            <option value="admin">Администратор</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeUser(index)" class="text-slate-400 hover:text-red-500 p-2"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile -->
                    <div class="lg:hidden space-y-4">
                        <div v-for="(user, index) in config.users" :key="index" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-4">
                            <div class="flex justify-between items-center">
                                <div class="bg-blue-50 text-blue-700 w-10 h-10 rounded-xl flex items-center justify-center font-bold">
                                    <i class="fas fa-user"></i>
                                </div>
                                <button @click="removeUser(index)" class="text-red-500 p-2"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="space-y-3">
                                <label class="block">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 ml-1">Имя пользователя</span>
                                    <input v-model="user.name" class="w-full mt-1 bg-slate-50 rounded-xl px-4 py-2 border-transparent focus:border-blue-500 focus:bg-white focus:ring-0 text-sm" placeholder="Имя">
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label>
                                        <span class="text-[10px] uppercase font-bold text-slate-400 ml-1">Пароль</span>
                                        <input v-model="user.password" type="password" class="w-full mt-1 bg-slate-50 rounded-xl px-4 py-2 border-transparent focus:border-blue-500 focus:bg-white focus:ring-0 text-sm font-mono" placeholder="******">
                                    </label>
                                    <label>
                                        <span class="text-[10px] uppercase font-bold text-slate-400 ml-1">Роль</span>
                                        <select v-model="user.role" class="w-full mt-1 bg-slate-50 rounded-xl px-4 py-2 border-transparent focus:border-blue-500 focus:bg-white focus:ring-0 text-sm">
                                            <option value="user">Пользователь</option>
                                            <option value="operator">Оператор</option>
                                            <option value="admin">Админ</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Relays Tab -->
                <section v-if="currentTab === 'relays'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Реле и тактики</h2>
                        <button @click="addRelay" class="bg-slate-800 text-white px-4 py-2 rounded-xl shadow-lg hover:bg-slate-900 transition">
                            <i class="fas fa-plus sm:mr-2"></i> <span class="hidden sm:inline">Добавить</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="(relay, index) in config.relays" :key="index" class="bg-white p-5 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 shadow-sm hover:shadow-md transition">
                            <div class="bg-slate-100 text-slate-700 w-12 h-12 rounded-xl flex items-center justify-center font-bold shadow-inner">#{{ index + 1 }}</div>
                            <div class="flex-1 w-full grid grid-cols-1 md:grid-cols-3 gap-3">
                                <input v-model="relay.name" placeholder="Название реле" class="bg-slate-50 rounded-xl px-4 py-2 border-transparent focus:border-blue-500 focus:bg-white focus:ring-0 text-sm">
                                <select v-model="relay.tactic" class="bg-slate-50 rounded-xl px-4 py-2 border-transparent focus:border-blue-500 focus:bg-white focus:ring-0 text-sm">
                                    <option value="1">Включить при пожаре</option>
                                    <option value="2">Выключить при пожаре</option>
                                    <option value="3">ПЦН</option>
                                    <option value="4">Лампа</option>
                                </select>
                                <select v-model="relay.partition" class="bg-slate-50 rounded-xl px-4 py-2 border-transparent focus:border-blue-500 focus:bg-white focus:ring-0 text-sm">
                                    <option v-for="(p, pi) in config.partitions" :value="pi">Раздел: {{ p.name }}</option>
                                </select>
                            </div>
                            <button @click="config.relays.splice(index, 1)" class="text-red-500 p-2 self-end sm:self-center"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </section>

                <!-- Zones Tab -->
                <section v-if="currentTab === 'zones'">
                    <h2 class="text-2xl font-bold mb-6">Входные зоны</h2>

                    <!-- Desktop -->
                    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Зона</th>
                                    <th class="px-6 py-4">Тип ШС</th>
                                    <th class="px-6 py-4">Раздел</th>
                                    <th class="px-6 py-4">Задержка</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="i in 8" :key="i" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold text-slate-700">Шлейф {{ i }}</td>
                                    <td class="px-6 py-4">
                                        <select class="bg-slate-100 border-none text-sm rounded-lg px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                            <option>Охранный</option>
                                            <option>Пожарный</option>
                                            <option>Тревожный</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <select class="bg-slate-100 border-none text-sm rounded-lg px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                            <option v-for="(p, pi) in config.partitions" :value="pi">{{ p.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <input type="number" class="w-16 bg-slate-100 border-none text-sm rounded-lg px-3 py-1 focus:ring-2 focus:ring-blue-500" value="0">
                                            <span class="text-xs text-slate-400">сек.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile -->
                    <div class="lg:hidden grid grid-cols-2 gap-3">
                        <div v-for="i in 8" :key="i" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col space-y-3">
                            <div class="font-bold text-slate-800 border-b pb-2 flex justify-between items-center">
                                <span>ШС {{ i }}</span>
                                <i class="fas fa-shield-alt text-blue-500 text-xs"></i>
                            </div>
                            <div class="space-y-2">
                                <select class="w-full bg-slate-50 rounded-xl px-2 py-2 border-transparent text-[11px] font-bold uppercase">
                                    <option>Охранный</option>
                                    <option>Пожарный</option>
                                </select>
                                <select class="w-full bg-slate-50 rounded-xl px-2 py-2 border-transparent text-[11px]">
                                    <option v-for="(p, pi) in config.partitions" :value="pi">{{ p.name }}</option>
                                </select>
                                <div class="flex items-center space-x-1">
                                    <input type="number" class="w-12 bg-slate-50 rounded-xl px-2 py-1 border-transparent text-xs" value="0">
                                    <span class="text-[10px] text-slate-400 italic">сек. задержки</span>
                                </div>
                            </div>
                        </div>
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
        <div v-if="toast.show" :class="{'bg-green-600': toast.type === 'success', 'bg-red-600': toast.type === 'error', 'bg-blue-600': toast.type === 'info'}" class="fixed bottom-4 right-4 text-white px-6 py-3 rounded-2xl shadow-2xl z-[100] transition-all transform duration-300 flex items-center max-w-[90vw]">
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
