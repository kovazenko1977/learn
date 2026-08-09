<?php
/**
 * Advanced Interactive AI-Powered Chatbot Dashboard & Visual Builder
 * Author: WES.BY +375333533971 (Разработка сайтов и приложений)
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление чат-ботом | WES.BY</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vue 3 CDN -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        [v-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="text-slate-800">
    <div id="app" v-cloak class="min-h-screen flex flex-col">

        <!-- LOGIN OVERLAY -->
        <div v-if="!authenticated" class="flex-1 flex items-center justify-center px-4 py-12 bg-gradient-to-tr from-indigo-900 via-indigo-800 to-blue-700 relative overflow-hidden">
            <!-- Background particles -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(99,102,241,0.15),transparent_40%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_80%,rgba(59,130,246,0.15),transparent_40%)]"></div>

            <div class="max-w-md w-full space-y-8 bg-white/95 backdrop-blur-md p-8 rounded-2xl shadow-2xl relative z-10 border border-white/20 animate__animated animate__fadeInUp">
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-gradient-to-tr from-indigo-600 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 text-white text-3xl mb-4">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <h2 class="text-3xl font-extrabold text-indigo-950 tracking-tight">Вход в Панель</h2>
                    <p class="mt-2 text-sm text-slate-500 font-medium">Управление вашим интеллектуальным чат-ботом</p>
                </div>

                <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
                    <div class="rounded-md shadow-sm">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Пароль Администратора</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input id="password" v-model="loginPassword" type="password" required class="appearance-none rounded-xl relative block w-full pl-10 pr-3 py-3 border border-slate-300 placeholder-slate-400 text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Введите пароль">
                            </div>
                        </div>
                    </div>

                    <div v-if="loginError" class="text-red-500 text-sm font-semibold flex items-center gap-2 bg-red-50 p-3 rounded-lg border border-red-200 animate__animated animate__shakeX">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ loginError }}
                    </div>

                    <div>
                        <button type="submit" :disabled="loading" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 shadow-lg shadow-indigo-600/30 disabled:opacity-50">
                            <span v-if="loading"><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Вход...</span>
                            <span v-else>Войти <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i></span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400">
                    <p class="font-semibold">Разработано WES.BY</p>
                    <p class="mt-1">+375333533971 (Разработка сайтов и приложений)</p>
                </div>
            </div>
        </div>

        <!-- MAIN DASHBOARD -->
        <div v-else class="flex-1 flex flex-col md:flex-row min-h-screen">

            <!-- SIDEBAR NAVIGATION -->
            <aside class="w-full md:w-64 bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800">
                <!-- Branding Header -->
                <div class="p-6 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white text-xl font-black shadow-md shadow-indigo-500/20">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div>
                            <h2 class="text-white font-bold leading-none tracking-wide text-sm">WES.BOT</h2>
                            <span class="text-xs text-emerald-400 font-semibold flex items-center gap-1 mt-1">
                                <span class="h-1.5 w-1.5 bg-emerald-400 rounded-full animate-ping"></span> Активен
                            </span>
                        </div>
                    </div>
                    <!-- Mobile Menu Close icon here if needed, but we keep it clean -->
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
                    <a v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" href="#" :class="['flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200', activeTab === tab.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100']">
                        <i :class="tab.icon" class="w-5 text-center text-base"></i>
                        <span>{{ tab.name }}</span>
                    </a>
                </nav>

                <!-- Sidebar Footer -->
                <div class="p-4 bg-slate-950 border-t border-slate-800 space-y-3">
                    <div class="bg-slate-900 p-3 rounded-xl border border-slate-800 text-xs">
                        <p class="font-bold text-white mb-1"><i class="fa-solid fa-code mr-1"></i> Короткий код</p>
                        <p class="text-slate-400 mb-2">Скопируйте в конец тега &lt;body&gt;:</p>
                        <textarea readonly @click="$event.target.select()" class="w-full bg-slate-950 text-[10px] text-indigo-300 font-mono p-1.5 rounded border border-slate-800 resize-none h-16 focus:outline-none focus:ring-1 focus:ring-indigo-500" :value="embedCode"></textarea>
                    </div>

                    <button @click="handleLogout" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
                        <i class="fa-solid fa-power-off"></i> Выйти из панели
                    </button>
                </div>
            </aside>

            <!-- MAIN WORKING AREA -->
            <main class="flex-1 flex flex-col min-h-0 bg-slate-50">

                <!-- TOP UTILITIES HEADER -->
                <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ currentTabName }}</h1>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">Управляйте, кастомизируйте и настраивайте бота в реальном времени</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button @click="saveAllSettings" :disabled="saving" class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all duration-200 disabled:opacity-50">
                            <span v-if="saving"><i class="fa-solid fa-circle-notch fa-spin"></i> Сохранение...</span>
                            <span v-else><i class="fa-solid fa-floppy-disk"></i> Сохранить настройки</span>
                        </button>

                        <div v-if="saveSuccess" class="text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 animate__animated animate__fadeIn">
                            <i class="fa-solid fa-circle-check"></i> Сохранено!
                        </div>
                    </div>
                </header>

                <!-- WORKSPACE CONTENT AREA WITH LIVE PREVIEW AS SEPARATE TAB OR SIDE PANEL -->
                <div class="flex-1 p-8 overflow-y-auto custom-scrollbar">

                    <!-- TAB 1: GENERAL & APPEARANCE -->
                    <div v-if="activeTab === 'general'" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <div class="lg:col-span-7 space-y-6">
                            <!-- Widget Basic Info -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fa-solid fa-sliders text-indigo-600"></i> Основные настройки</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Заголовок виджета</label>
                                        <input v-model="settings.widget_title" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Подзаголовок виджета</label>
                                        <input v-model="settings.widget_subtitle" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Приветственное сообщение бота</label>
                                        <textarea v-model="settings.extra_greetings" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 h-24"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Пароль Администратора</label>
                                        <input v-model="settings.admin_password" type="text" placeholder="Оставьте пустым для сохранения прежнего" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Включить звук уведомлений</label>
                                        <select v-model="settings.sound_enabled" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option :value="true">Да, включить звук</option>
                                            <option :value="false">Нет, без звука</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-6 mt-6">
                                    <h4 class="text-sm font-bold text-indigo-950 mb-3"><i class="fa-solid fa-address-book text-indigo-600"></i> Контактная информация компании</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Телефон компании</label>
                                            <input v-model="settings.contact_phone" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">E-mail компании</label>
                                            <input v-model="settings.contact_email" type="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Адрес / Офис</label>
                                            <input v-model="settings.contact_address" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-6 mt-6">
                                    <h4 class="text-sm font-bold text-indigo-950 mb-3"><i class="fa-solid fa-circle-info text-indigo-600"></i> Настройки копирайта разработчика (в футере)</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Название разработчика</label>
                                            <input v-model="settings.dev_name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Телефон</label>
                                            <input v-model="settings.dev_phone" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Сайт разработчика</label>
                                            <input v-model="settings.dev_site" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Описание / Деятельность</label>
                                            <input v-model="settings.dev_desc" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Appearance Customization -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fa-solid fa-wand-magic-sparkles text-indigo-600"></i> Внешний вид и анимация</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Фирменный цвет чата</label>
                                        <div class="flex gap-2">
                                            <input v-model="settings.widget_color" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                            <input v-model="settings.widget_color" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Иконка виджета</label>
                                        <select v-model="settings.widget_icon" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="bubble">💬 Диалог</option>
                                            <option value="support">🎧 Поддержка</option>
                                            <option value="bot">🤖 Робот</option>
                                            <option value="wave">👋 Приветствие</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Ссылка на логотип (аватар) бота</label>
                                        <input v-model="settings.widget_avatar_url" type="text" placeholder="Пусто для дефолтной иконки" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Расположение значка</label>
                                        <select v-model="settings.widget_position" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="bottom-right">Справа внизу</option>
                                            <option value="bottom-left">Слева внизу</option>
                                            <option value="bottom-center">Снизу по центру</option>
                                            <option value="top-right">Справа вверху</option>
                                            <option value="top-left">Слева вверху</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Скорость набора текста бота (мс/символ)</label>
                                        <div class="flex items-center gap-2">
                                            <input v-model.number="settings.typing_speed" type="range" min="5" max="100" class="flex-1">
                                            <span class="text-sm font-mono font-bold text-slate-600 w-12 text-right">{{ settings.typing_speed }}мс</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Отступ по горизонтали (px)</label>
                                        <input v-model.number="settings.widget_offset_x" type="number" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Отступ по вертикали (px)</label>
                                        <input v-model.number="settings.widget_offset_y" type="number" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-6 mt-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-bold text-indigo-950"><i class="fa-solid fa-palette text-indigo-600"></i> Цветовая палитра диалогов и сообщений</h4>
                                        <!-- Interactive Preset Palette Picker -->
                                        <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-xl border border-slate-200/50">
                                            <span class="text-[9px] font-bold text-slate-500 uppercase px-1">Готовые темы:</span>
                                            <button @click="applyThemePreset('#2563eb', '#f8fafc', '#ffffff', '#1e293b', '#2563eb', '#ffffff')" title="Классический Синий" class="h-5 w-5 rounded-full bg-blue-600 border border-white hover:scale-110 transition-transform"></button>
                                            <button @click="applyThemePreset('#059669', '#f0fdf4', '#ffffff', '#064e3b', '#059669', '#ffffff')" title="Изумрудный Зеленый" class="h-5 w-5 rounded-full bg-emerald-600 border border-white hover:scale-110 transition-transform"></button>
                                            <button @click="applyThemePreset('#7c3aed', '#faf5ff', '#ffffff', '#2e1065', '#7c3aed', '#ffffff')" title="Аметистовый Фиолетовый" class="h-5 w-5 rounded-full bg-violet-600 border border-white hover:scale-110 transition-transform"></button>
                                            <button @click="applyThemePreset('#ea580c', '#fff7ed', '#ffffff', '#431407', '#ea580c', '#ffffff')" title="Апельсиновый Оранжевый" class="h-5 w-5 rounded-full bg-orange-600 border border-white hover:scale-110 transition-transform"></button>
                                            <button @click="applyThemePreset('#e11d48', '#fff1f2', '#ffffff', '#4c0519', '#e11d48', '#ffffff')" title="Малиновый Красный" class="h-5 w-5 rounded-full bg-rose-600 border border-white hover:scale-110 transition-transform"></button>
                                            <button @click="applyThemePreset('#1e293b', '#f1f5f9', '#ffffff', '#0f172a', '#334155', '#ffffff')" title="Глубокий Темный" class="h-5 w-5 rounded-full bg-slate-800 border border-white hover:scale-110 transition-transform"></button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Фон чата</label>
                                            <div class="flex gap-2">
                                                <input v-model="settings.chat_bg_color" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                                <input v-model="settings.chat_bg_color" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Баббл бота (фон)</label>
                                            <div class="flex gap-2">
                                                <input v-model="settings.bot_bubble_bg" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                                <input v-model="settings.bot_bubble_bg" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Текст бота (цвет)</label>
                                            <div class="flex gap-2">
                                                <input v-model="settings.bot_bubble_color" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                                <input v-model="settings.bot_bubble_color" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Баббл пользователя (фон)</label>
                                            <div class="flex gap-2">
                                                <input v-model="settings.user_bubble_bg" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                                <input v-model="settings.user_bubble_bg" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Текст пользователя (цвет)</label>
                                            <div class="flex gap-2">
                                                <input v-model="settings.user_bubble_color" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                                <input v-model="settings.user_bubble_color" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-6 mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Тип звукового уведомления</label>
                                        <select v-model="settings.sound_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none">
                                            <option value="synth">Синтезатор тональный (Synth)</option>
                                            <option value="alert">Звук оповещения (Alert)</option>
                                            <option value="chime">Элегантный колокольчик (Chime)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Заголовок оценки рейтинга (Stars)</label>
                                        <input v-model="settings.chat_rating_text" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none">
                                    </div>
                                </div>
                            </div>

                            <!-- Floating badge customization -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fa-solid fa-comment-dots text-indigo-600"></i> Облачко с текстом возле значка</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Всплывающий текст</label>
                                        <input v-model="settings.widget_badge_text" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Цвет фона облачка</label>
                                        <div class="flex gap-2">
                                            <input v-model="settings.widget_badge_bg" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                            <input v-model="settings.widget_badge_bg" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Цвет текста облачка</label>
                                        <div class="flex gap-2">
                                            <input v-model="settings.widget_badge_color" type="color" class="h-10 w-12 rounded-xl border border-slate-200 cursor-pointer">
                                            <input v-model="settings.widget_badge_color" type="text" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Эффект анимации облачка</label>
                                        <select v-model="settings.widget_badge_animation" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="none">Без анимации</option>
                                            <option value="pulse">Пульсация (Pulse)</option>
                                            <option value="bounce">Прыгающий эффект (Bounce)</option>
                                            <option value="slide-in">Плавный запуск (Slide-In)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Constructor: Quick Buttons Answers -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-tags text-indigo-600"></i> Быстрые кнопки</h3>
                                        <p class="text-[10px] text-slate-400 font-medium">Конструктор кнопок на старте диалога</p>
                                    </div>
                                    <button @click="addQuickButton" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-plus"></i></button>
                                </div>

                                <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar">
                                    <div v-for="(btn, bIdx) in settings.quick_buttons" :key="bIdx" class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <input v-model="btn.title" type="text" placeholder="Текст на кнопке" class="flex-1 px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs font-bold">
                                            <button @click="deleteQuickButton(bIdx)" class="text-red-500 hover:bg-red-50 p-1 rounded-lg"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <select v-model="btn.action" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[10px] font-semibold">
                                                <option value="message">Отправить фразу</option>
                                                <option value="form">Открыть форму</option>
                                            </select>
                                            <select v-if="btn.action === 'form'" v-model="btn.payload" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[10px] font-semibold">
                                                <option v-for="f in settings.forms" :key="f.id" :value="f.id">{{ f.title }}</option>
                                            </select>
                                            <input v-else v-model="btn.payload" type="text" placeholder="Текст фразы" class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[10px] font-medium">
                                        </div>
                                    </div>
                                    <div v-if="!settings.quick_buttons || settings.quick_buttons.length === 0" class="text-center py-4 text-xs text-slate-400 font-semibold">
                                        Кнопок пока нет. Добавьте первую!
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LIVE PREVIEW PANEL (Interactive Mock) -->
                        <div class="lg:col-span-5 space-y-6">
                            <div class="sticky top-6 bg-slate-900 rounded-2xl border border-slate-800 p-6 text-white shadow-xl min-h-[500px] flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Интерактивный Живой Просмотр</span>
                                        <span class="text-xs bg-indigo-600 text-white font-black px-2 py-0.5 rounded">WES.BY</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-2">Любые изменения цветов, иконок, заголовков и позиций виджета отображаются здесь мгновенно.</p>
                                </div>

                                <!-- Real-time Mock Widget rendering inside container -->
                                <div class="relative flex-1 flex items-center justify-center my-6 bg-slate-950/50 rounded-xl p-4 overflow-hidden min-h-[300px]">

                                    <!-- Embedded Widget Container Simulation -->
                                    <div class="w-full max-w-[320px] bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 text-slate-800 animate__animated animate__fadeIn">
                                        <!-- Header mock -->
                                        <div :style="{ backgroundColor: settings.widget_color }" class="p-4 text-white flex items-center gap-3">
                                            <div v-if="settings.widget_avatar_url" class="h-9 w-9 rounded-full overflow-hidden flex-shrink-0">
                                                <img :src="settings.widget_avatar_url" class="h-full w-full object-cover">
                                            </div>
                                            <div v-else class="h-9 w-9 bg-white/20 rounded-full flex items-center justify-center text-lg flex-shrink-0">
                                                <i v-if="settings.widget_icon === 'bubble'" class="fa-solid fa-comment"></i>
                                                <i v-else-if="settings.widget_icon === 'support'" class="fa-solid fa-headset"></i>
                                                <i v-else-if="settings.widget_icon === 'bot'" class="fa-solid fa-robot"></i>
                                                <i v-else-if="settings.widget_icon === 'wave'" class="fa-solid fa-hand-wave"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-bold text-sm truncate leading-none mb-1">{{ settings.widget_title }}</h4>
                                                <p class="text-[10px] text-white/85 truncate font-semibold">{{ settings.widget_subtitle }}</p>
                                            </div>
                                        </div>

                                        <!-- Messages flow mock -->
                                        <div class="p-4 space-y-3 h-48 overflow-y-auto custom-scrollbar text-[11px]">
                                            <div class="flex gap-2">
                                                <div v-if="settings.widget_avatar_url" class="h-6 w-6 rounded-full overflow-hidden flex-shrink-0">
                                                    <img :src="settings.widget_avatar_url" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else :style="{ backgroundColor: settings.widget_color }" class="h-6 w-6 rounded-full flex items-center justify-center text-white text-[10px] flex-shrink-0">
                                                    <i class="fa-solid fa-robot"></i>
                                                </div>
                                                <div class="bg-slate-100 p-2.5 rounded-2xl rounded-tl-none font-medium text-slate-700 max-w-[85%]">
                                                    {{ settings.extra_greetings }}
                                                </div>
                                            </div>

                                            <div class="flex gap-2 justify-end">
                                                <div class="bg-indigo-50 p-2.5 rounded-2xl rounded-tr-none font-medium text-slate-700 max-w-[85%]">
                                                    Как связаться с вами?
                                                </div>
                                            </div>

                                            <div class="flex gap-2">
                                                <div :style="{ backgroundColor: settings.widget_color }" class="h-6 w-6 rounded-full flex items-center justify-center text-white text-[10px] flex-shrink-0">
                                                    <i class="fa-solid fa-robot"></i>
                                                </div>
                                                <div class="bg-slate-100 p-2.5 rounded-2xl rounded-tl-none font-medium text-slate-700 max-w-[85%]">
                                                    Наши контакты: Телефон +375333533971. Будем рады звонку!
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Input box mock -->
                                        <div class="p-3 border-t border-slate-100 flex gap-2 items-center bg-slate-50">
                                            <input disabled type="text" placeholder="Введите сообщение..." class="flex-1 bg-white border border-slate-200 text-[11px] px-3 py-1.5 rounded-xl">
                                            <button disabled :style="{ backgroundColor: settings.widget_color }" class="h-7 w-7 text-white rounded-lg flex items-center justify-center text-xs">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <!-- Floating widget launcher preview -->
                                <div class="border-t border-slate-800 pt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <!-- Float Badge badge -->
                                        <div :style="{ backgroundColor: settings.widget_badge_bg, color: settings.widget_badge_color }" class="text-[10px] px-3 py-1.5 rounded-xl font-bold shadow-md relative" :class="{ 'animate-pulse': settings.widget_badge_animation === 'pulse', 'animate-bounce': settings.widget_badge_animation === 'bounce' }">
                                            {{ settings.widget_badge_text }}
                                        </div>
                                        <!-- Launcher icon -->
                                        <div :style="{ backgroundColor: settings.widget_color }" class="h-12 w-12 rounded-full flex items-center justify-center text-white text-xl shadow-lg cursor-pointer overflow-hidden">
                                            <img v-if="settings.widget_avatar_url" :src="settings.widget_avatar_url" class="h-full w-full object-cover">
                                            <template v-else>
                                                <i v-if="settings.widget_icon === 'bubble'" class="fa-solid fa-comment"></i>
                                                <i v-else-if="settings.widget_icon === 'support'" class="fa-solid fa-headset"></i>
                                                <i v-else-if="settings.widget_icon === 'bot'" class="fa-solid fa-robot"></i>
                                                <i v-else-if="settings.widget_icon === 'wave'" class="fa-solid fa-hand-wave"></i>
                                            </template>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-bold">Расположение: {{ settings.widget_position }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: FLEXIBLE WORKING SCHEDULE -->
                    <div v-if="activeTab === 'schedule'" class="max-w-4xl mx-auto space-y-6">
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-clock text-indigo-600"></i> График работы консультантов</h3>
                                <span class="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full">Умный режим</span>
                            </div>

                            <p class="text-sm text-slate-500 mb-6">Настройте дни недели и рабочие часы. В нерабочее время бот будет автоматически сообщать пользователю о выходном дне и сразу предложит заполнить форму обратной связи, чтобы не упустить потенциального клиента.</p>

                            <!-- Weekday Grid list -->
                            <div class="space-y-3.5">
                                <div v-for="(day, idx) in settings.schedule" :key="idx" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="day.enabled" class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </label>
                                        <span class="font-bold text-sm" :class="day.enabled ? 'text-slate-800' : 'text-slate-400 line-through'">{{ day.day }}</span>
                                    </div>

                                    <div class="flex items-center gap-2" v-if="day.enabled">
                                        <span class="text-xs font-semibold text-slate-500">с</span>
                                        <input type="time" v-model="day.from" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 bg-white focus:outline-none">
                                        <span class="text-xs font-semibold text-slate-500">до</span>
                                        <input type="time" v-model="day.to" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 bg-white focus:outline-none">
                                    </div>
                                    <div v-else class="text-xs text-red-500 font-bold flex items-center gap-1">
                                        <i class="fa-solid fa-circle-xmark"></i> Выходной день
                                    </div>
                                </div>
                            </div>

                            <!-- Offline behavior message -->
                            <div class="mt-6 border-t border-slate-100 pt-6">
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Сообщение бота в нерабочее время</label>
                                <textarea v-model="settings.schedule_offline_msg" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 h-20"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: CUSTOM FORM CONSTRUCTOR -->
                    <div v-if="activeTab === 'forms'" class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <!-- Forms Directory / Editor -->
                        <div class="lg:col-span-5 space-y-4">
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-base font-bold text-slate-900"><i class="fa-solid fa-list-check text-indigo-600 mr-1.5"></i> Доступные формы</h3>
                                    <button @click="addNewForm" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-plus"></i> Создать</button>
                                </div>

                                <div class="space-y-2">
                                    <div v-for="(form, idx) in settings.forms" :key="form.id" @click="selectedFormIndex = idx" class="p-3.5 rounded-xl border cursor-pointer transition-all flex items-center justify-between" :class="selectedFormIndex === idx ? 'border-indigo-600 bg-indigo-50/50 text-indigo-950 font-bold' : 'border-slate-100 hover:border-slate-200 text-slate-700'">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <i class="fa-solid fa-clipboard-question text-indigo-500"></i>
                                            <span class="truncate text-sm">{{ form.title }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <span class="text-[9px] px-1.5 py-0.5 bg-indigo-200/50 rounded font-black text-indigo-700">{{ form.fields.length }} полей</span>
                                            <button @click.stop="deleteForm(idx)" class="text-red-500 hover:bg-red-50 p-1.5 rounded-lg"><i class="fa-solid fa-trash-can"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Form Field Editor / Drag-and-drop simulated constructor -->
                        <div class="lg:col-span-7 space-y-6">
                            <div v-if="settings.forms[selectedFormIndex]" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-indigo-600"></i> Конструктор формы: {{ settings.forms[selectedFormIndex].title }}</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Название формы в чате</label>
                                        <input v-model="settings.forms[selectedFormIndex].title" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Куда скинуть данные?</label>
                                        <select v-model="settings.forms[selectedFormIndex].destination" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="both">Email + Telegram (Оба канала)</option>
                                            <option value="email">Только по Email</option>
                                            <option value="telegram">Только в Telegram</option>
                                            <option value="log">Сохранять только в панели</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Поля формы</span>
                                        <button @click="addFormField" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-plus-circle"></i> Добавить поле</button>
                                    </div>

                                    <!-- Fields List manager -->
                                    <div class="space-y-3">
                                        <div v-for="(field, fIdx) in settings.forms[selectedFormIndex].fields" :key="fIdx" class="grid grid-cols-12 gap-2 items-center p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                                            <div class="col-span-4">
                                                <input v-model="field.label" type="text" placeholder="Подпись (Label)" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:outline-none">
                                            </div>
                                            <div class="col-span-3">
                                                <select v-model="field.type" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold bg-white focus:outline-none">
                                                    <option value="text">Текст</option>
                                                    <option value="tel">Телефон</option>
                                                    <option value="email">Email</option>
                                                    <option value="number">Число</option>
                                                    <option value="date">Дата</option>
                                                    <option value="time">Время</option>
                                                    <option value="textarea">Область текста</option>
                                                </select>
                                            </div>
                                            <div class="col-span-3 flex items-center gap-2 justify-center">
                                                <input type="checkbox" v-model="field.required" class="rounded text-indigo-600">
                                                <span class="text-[11px] font-bold text-slate-500">Обяз.</span>
                                            </div>
                                            <div class="col-span-2 text-right">
                                                <button @click="deleteFormField(fIdx)" class="text-red-500 hover:bg-red-50 p-1.5 rounded-lg"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center p-12 bg-white rounded-2xl border border-dashed border-slate-300">
                                <p class="text-slate-400 font-semibold">Выберите или создайте форму для начала конструирования</p>
                            </div>

                            <!-- Smart Keyword-Action Rules Builder -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mt-6">
                                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-brain text-indigo-600"></i> Умные правила (Распознавание ключевых слов)</h3>
                                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Если клиент напишет любое из этих слов (например, "телефон" или "запись"), бот мгновенно откроет нужную форму обратной связи.</p>
                                    </div>
                                    <button @click="addSmartRule" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-plus-circle"></i> Добавить правило</button>
                                </div>

                                <div class="space-y-3">
                                    <div v-for="(rule, rIdx) in settings.smart_rules" :key="rIdx" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center p-4 rounded-xl border border-slate-100 bg-slate-50/50">
                                        <div class="md:col-span-3">
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Ключевое слово (содержит)</label>
                                            <input v-model="rule.keyword" type="text" placeholder="Например: телефон" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold bg-white focus:outline-none">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Фраза-ответ бота перед формой</label>
                                            <input v-model="rule.response" type="text" placeholder="Открываю форму..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:outline-none">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Действие</label>
                                            <select v-model="rule.action" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold bg-white focus:outline-none">
                                                <option value="trigger_form">Показать форму</option>
                                                <option value="open_url">Открыть страницу</option>
                                                <option value="alert">Всплывающее окно (Alert)</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Параметр действия (Payload)</label>
                                            <select v-if="rule.action === 'trigger_form'" v-model="rule.payload" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold bg-white focus:outline-none">
                                                <option v-for="f in settings.forms" :key="f.id" :value="f.id">{{ f.title }}</option>
                                            </select>
                                            <input v-else v-model="rule.payload" type="text" placeholder="URL или сообщение" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:outline-none">
                                        </div>
                                        <div class="md:col-span-1 text-right pt-4">
                                            <button @click="deleteSmartRule(rIdx)" class="text-red-500 hover:bg-red-50 p-1.5 rounded-lg"><i class="fa-solid fa-trash-can text-sm"></i></button>
                                        </div>
                                    </div>
                                    <div v-if="!settings.smart_rules || settings.smart_rules.length === 0" class="text-center py-6 text-slate-400 font-semibold text-xs">
                                        Список умных правил пуст. Добавьте первое правило для автоматизации!
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 4: KNOWLEDGE BASE -->
                    <div v-if="activeTab === 'knowledge'" class="max-w-6xl mx-auto space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                            <!-- Left: Plaintext Knowledge Loader/Synchronizer -->
                            <div class="lg:col-span-5 space-y-4">
                                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                    <h3 class="text-base font-bold text-slate-900 mb-2 flex items-center gap-2"><i class="fa-solid fa-file-import text-indigo-600"></i> Импорт базы знаний</h3>
                                    <p class="text-xs text-slate-500 mb-4">Быстрый текстовый формат. Используйте префиксы Q: (вопрос) и A: (ответ).</p>

                                    <textarea v-model="importText" placeholder="Q: Каковы ваши контакты?&#10;A: Наш телефон +375333533971.&#10;&#10;Q: Какой график работы?&#10;A: Мы работаем с 09:00 до 18:00 каждый день." class="w-full h-64 bg-slate-50 font-mono text-xs p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>

                                    <div class="grid grid-cols-2 gap-2 mt-4">
                                        <button @click="importKnowledge('replace')" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-md transition-all">
                                            Заменить базу
                                        </button>
                                        <button @click="importKnowledge('append')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition-all">
                                            Добавить к текущей
                                        </button>
                                    </div>
                                </div>

                                <!-- Crawler Page Scanner Block -->
                                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                    <h3 class="text-base font-bold text-slate-900 mb-2 flex items-center gap-2"><i class="fa-solid fa-spider text-indigo-600"></i> Сканер веб-страниц (Crawler)</h3>
                                    <p class="text-[11px] text-slate-400 font-medium mb-4">Укажите адрес любой страницы вашего сайта. Наша умная система проанализирует её контент, автоматически сгенерирует Q&A-пары и подберёт ключевые слова.</p>

                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Адрес страницы (URL)</label>
                                            <input v-model="scanUrl" type="url" placeholder="https://wes.by/about" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Режим импорта</label>
                                            <select v-model="scanMode" class="w-full px-2 py-2 rounded-xl border border-slate-200 text-[11px] font-semibold bg-white focus:outline-none">
                                                <option value="append">Добавить к текущей базе знаний</option>
                                                <option value="replace">Заменить всю базу знаний</option>
                                            </select>
                                        </div>
                                        <button @click="startUrlScanner" :disabled="scanningPage" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center justify-center gap-1.5 disabled:opacity-50">
                                            <i v-if="scanningPage" class="fa-solid fa-circle-notch fa-spin"></i>
                                            <span v-else><i class="fa-solid fa-radar"></i> Запустить сканирование</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Visual Knowledge Manager -->
                            <div class="lg:col-span-7 space-y-4">
                                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col min-h-[400px]">
                                    <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-1.5"><i class="fa-solid fa-graduation-cap text-indigo-600"></i> База знаний ({{ knowledgeBase.length }} пар)</h3>
                                        <button @click="addNewKbItem" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-plus"></i> Добавить пару</button>
                                    </div>

                                    <!-- Filter search input -->
                                    <div class="relative mb-4">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                        </span>
                                        <input v-model="kbSearch" type="text" placeholder="Поиск по вопросам или ключевым словам..." class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none">
                                    </div>

                                    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3 max-h-[350px]">
                                        <div v-for="(item, idx) in filteredKnowledge" :key="item.id" class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-colors space-y-2 relative">
                                            <button @click="deleteKbItem(item.id)" class="absolute top-3.5 right-3.5 text-red-500 hover:bg-red-50 p-1 rounded-lg"><i class="fa-solid fa-trash-can text-xs"></i></button>

                                            <div>
                                                <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded uppercase">Вопрос</span>
                                                <input v-model="item.question" type="text" class="w-full mt-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold bg-white focus:outline-none">
                                            </div>
                                            <div>
                                                <span class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded uppercase">Ответ</span>
                                                <textarea v-model="item.answer" class="w-full mt-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:outline-none h-16"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 border-t border-slate-100 pt-4 flex justify-between items-center">
                                        <p class="text-xs text-slate-400">После редактирования не забудьте сохранить все изменения.</p>
                                        <button @click="saveKnowledgeBase" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-md transition-all">Применить базу знаний</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 5: DIALOGUES HISTORY -->
                    <div v-if="activeTab === 'dialogues'" class="max-w-6xl mx-auto space-y-6">
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col min-h-[500px]">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-comments text-indigo-600"></i> Журнал всех диалогов</h3>
                                <button @click="clearAllDialogues" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-trash-can mr-1"></i> Очистить всю историю</button>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1">
                                <!-- Dialogues List -->
                                <div class="lg:col-span-5 border-r border-slate-100 pr-0 lg:pr-6 space-y-2 max-h-[450px] overflow-y-auto custom-scrollbar">
                                    <div v-for="(diag, idx) in dialogues" :key="diag.id" @click="selectedDialogueIndex = idx" class="p-3.5 rounded-xl border cursor-pointer transition-all flex justify-between items-start" :class="selectedDialogueIndex === idx ? 'border-indigo-600 bg-indigo-50/50 text-indigo-950' : 'border-slate-100 hover:border-slate-200 text-slate-700'">
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold truncate">Сессия: {{ diag.session_id }}</p>
                                            <p class="text-[10px] text-slate-400 font-semibold mt-1"><i class="fa-solid fa-calendar-days"></i> {{ diag.updated_at }}</p>
                                            <p class="text-xs text-slate-500 font-medium truncate mt-1">Последнее: {{ diag.messages[diag.messages.length - 1].text }}</p>
                                        </div>
                                        <button @click.stop="deleteDialogue(diag.id)" class="text-red-500 hover:bg-red-50 p-1 rounded"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                    </div>
                                    <div v-if="dialogues.length === 0" class="text-center p-6 text-xs text-slate-400 font-semibold">История пуста</div>
                                </div>

                                <!-- Active dialogue viewer -->
                                <div class="lg:col-span-7 flex flex-col justify-between max-h-[450px]">
                                    <div v-if="dialogues[selectedDialogueIndex]" class="flex-1 flex flex-col justify-between bg-slate-50 rounded-2xl p-4 border border-slate-200">
                                        <div class="border-b border-slate-200 pb-2 mb-3 flex justify-between items-center text-xs font-bold text-slate-600 gap-2">
                                            <div class="flex items-center gap-1 min-w-0">
                                                <span class="truncate">Сессия: {{ dialogues[selectedDialogueIndex].session_id }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <button @click="toggleOperatorActive(dialogues[selectedDialogueIndex])" :class="dialogues[selectedDialogueIndex].operator_active ? 'bg-emerald-500 text-white shadow-emerald-500/20 shadow-md' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'" class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1">
                                                    <i class="fa-solid" :class="dialogues[selectedDialogueIndex].operator_active ? 'fa-user-check' : 'fa-robot'"></i>
                                                    {{ dialogues[selectedDialogueIndex].operator_active ? 'Оператор на связи' : 'Бот отвечает' }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Messages flow -->
                                        <div id="admin-chat-flow" class="flex-1 overflow-y-auto custom-scrollbar space-y-3 pr-2 max-h-[220px] mb-3">
                                            <div v-for="(msg, mIdx) in dialogues[selectedDialogueIndex].messages" :key="mIdx" class="flex" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'">
                                                <div class="max-w-[85%] rounded-2xl p-3 text-xs font-medium relative" :class="msg.sender === 'user' ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none'">
                                                    <p>{{ msg.text }}</p>
                                                    <span class="text-[8px] absolute bottom-1 right-2" :class="msg.sender === 'user' ? 'text-white/70' : 'text-slate-400'">{{ msg.time }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Operator Input Form -->
                                        <div class="flex gap-2 border-t border-slate-200 pt-3">
                                            <input type="text" v-model="operatorReplyText" @keyup.enter="sendOperatorReply" placeholder="Введите ответ оператора..." class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                            <button @click="sendOperatorReply" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all"><i class="fa-solid fa-paper-plane"></i></button>
                                        </div>
                                    </div>
                                    <div v-else class="flex-1 flex items-center justify-center p-12 bg-slate-50 border border-slate-200 rounded-2xl text-slate-400 font-semibold text-xs">
                                        Выберите диалог из списка слева для просмотра переписки
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: FORM SUBMISSIONS (LEADS) -->
                    <div v-if="activeTab === 'submissions'" class="max-w-6xl mx-auto space-y-6">
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm min-h-[500px]">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-users-line text-indigo-600"></i> Собрано лидов / Заявок</h3>
                                <button @click="clearAllSubmissions" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-bold transition-all"><i class="fa-solid fa-trash-can"></i> Удалить все заявки</button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase">
                                            <th class="py-3 px-4">Форма</th>
                                            <th class="py-3 px-4">Дата / Время</th>
                                            <th class="py-3 px-4">Данные полей</th>
                                            <th class="py-3 px-4 text-right">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-xs">
                                        <tr v-for="sub in submissions" :key="sub.id" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                            <td class="py-4 px-4 font-bold text-indigo-950">{{ sub.form_title }}</td>
                                            <td class="py-4 px-4 text-slate-400 font-semibold"><i class="fa-solid fa-calendar-days text-[10px]"></i> {{ sub.created_at }}</td>
                                            <td class="py-4 px-4 max-w-sm">
                                                <div class="space-y-1 font-semibold text-slate-700">
                                                    <div v-for="(val, label) in sub.fields" :key="label" class="flex gap-2">
                                                        <span class="text-slate-400 font-bold">{{ label }}:</span>
                                                        <span>{{ val }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-right">
                                                <button @click="deleteSubmission(sub.id)" class="text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors"><i class="fa-solid fa-trash-can"></i></button>
                                            </td>
                                        </tr>
                                        <tr v-if="submissions.length === 0">
                                            <td colspan="4" class="text-center py-12 text-slate-400 font-semibold">Лидов пока не собрано. Разместите короткий код на вашем сайте!</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 7: 10 EXTRA POWERFUL FEATURES -->
                    <div v-if="activeTab === 'features'" class="max-w-6xl mx-auto space-y-6">
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2"><i class="fa-solid fa-cubes text-indigo-600"></i> Пакет Расширенных Функций (10+ Профессиональных приложений)</h3>
                            <p class="text-xs text-slate-500 mb-6">Активируйте мощный дополнительный функционал для интерактивного повышения конверсии посетителей на вашем веб-сайте.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                <!-- 1. Predefined Greetings trigger -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 1</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Быстрые кнопки ответов</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Показывает быстрые темы-кнопки (Контакты, Запись, Формы) внизу чата. Помогает клиентам находить информацию в один клик без ввода текста.
                                        </p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Интегрировано</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded"><i class="fa-solid fa-check"></i> Активно</span>
                                    </div>
                                </div>

                                <!-- 2. Custom CSS Injector -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 2</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Свои CSS Стили</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Позволяет переопределять шрифты, скругления, тени и размеры любых блоков виджета. Введите CSS код ниже:
                                        </p>
                                        <textarea v-model="settings.custom_css" placeholder=".launcher-btn { border-radius: 8px !important; }" class="w-full mt-2 bg-slate-50 border border-slate-200 p-2 text-[10px] rounded-lg font-mono h-20 focus:outline-none"></textarea>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Статус</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded"><i class="fa-solid fa-code"></i> CSS Готов</span>
                                    </div>
                                </div>

                                <!-- 3. Exit Intent Popup Tracker -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 3</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Захват лидов при выходе</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Удерживает посетителя! Если мышь уходит за верхнюю границу экрана браузера, виджет автоматически открывается и предлагает заполнить форму.
                                        </p>
                                        <div class="mt-2 space-y-1.5" v-if="settings.exit_intent_enabled">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Задержка показа (сек)</label>
                                                <input v-model.number="settings.exit_intent_delay" type="number" min="0" max="10" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-md text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Текст удержания</label>
                                                <textarea v-model="settings.exit_intent_text" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-md text-xs h-12 resize-none"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Активация</span>
                                        <select v-model="settings.exit_intent_enabled" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none">
                                            <option :value="true">Включено</option>
                                            <option :value="false">Выключено</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 4. Sound Alert Customization -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 4</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Звуковые сигналы</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Приятное звуковое сопровождение при получении ответа от бота. Выберите тип звука и статус:
                                        </p>
                                        <select v-model="settings.sound_type" class="w-full mt-2 px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[11px] font-semibold focus:outline-none">
                                            <option value="synth">Тональный синтезатор</option>
                                            <option value="alert">Звук триггера (Alert)</option>
                                            <option value="chime">Колокольчик (Chime)</option>
                                        </select>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Звук</span>
                                        <select v-model="settings.sound_enabled" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none">
                                            <option :value="true">Включен</option>
                                            <option :value="false">Без звука</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 5. Rating System & Evaluation -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 5</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Оценка качества (Stars Rating)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            При закрытии окна чата пользователю предлагается оценить работу консультанта звездами (от 1 до 5). Настройте текст:
                                        </p>
                                        <input v-model="settings.chat_rating_text" type="text" class="w-full mt-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[11px] focus:outline-none">
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Система оценки</span>
                                        <select v-model="settings.chat_rating_enabled" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none">
                                            <option :value="true">Активна</option>
                                            <option :value="false">Отключена</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 6. Global Widget Switcher Toggle -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 6</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Глобальный выключатель</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Позволяет полностью скрыть виджет чата с вашего сайта одним щелчком, не удаляя код интеграции со страниц. Удобно при проведении тех. работ.
                                        </p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Показ виджета</span>
                                        <select v-model="settings.widget_enabled" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none">
                                            <option :value="true">Отображать</option>
                                            <option :value="false">Скрыть</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 7. AI Brain Core Model Toggle -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 7</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Искусственный Интеллект ИИ</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Подключает бесплатный API сервер нейросетей от Hugging Face. Бот сможет рассуждать и общаться свободно как человек при сложных вопросах.
                                        </p>
                                        <div class="mt-2 space-y-1.5" v-if="settings.ai_enabled">
                                            <input v-model="settings.ai_api_key" type="password" placeholder="Ключ API Hugging Face" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                                            <input v-model="settings.ai_model" type="text" placeholder="Модель (например: Qwen/Qwen2.5-7B)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Режим ИИ</span>
                                        <select v-model="settings.ai_enabled" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none">
                                            <option :value="true">Включен</option>
                                            <option :value="false">Отключен</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 8. Auto-responder Mapper rules -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 8</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Мгновенные авто-ответы</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Таблица быстрых авто-ответов по ключевым словам. Бот мгновенно выдаст заданный текст, если найдет совпадение в запросе клиента.
                                        </p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Количество триггеров</span>
                                        <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ settings.auto_responders.length }} шт.</span>
                                    </div>
                                </div>

                                <!-- 9. Dead-end Deadlock Protection -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 9</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Защита от глупых ответов (Лидогенератор)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Если бот подряд не понимает вопросы клиента несколько раз, он не будет надоедать, а мягко предложит форму обратной связи для перезвона.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-[10px] text-slate-500">Порог ошибок:</span>
                                            <input v-model.number="settings.dead_end_threshold" type="number" min="1" max="5" class="w-12 px-2 py-1 text-xs border rounded-lg bg-slate-50 focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Защита</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded ml-1.5"><i class="fa-solid fa-shield-halved"></i> Активна</span>
                                    </div>
                                </div>

                                <!-- 10. Telegram / Email Lead Forwarder -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-indigo-300 transition-all flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 10</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Мгновенное оповещение администраторов</h4>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                            Как только клиент заполняет форму в чате, данные сразу отправляются на вашу электронную почту и в Telegram-группу! Заполните параметры:
                                        </p>
                                        <div class="mt-2 space-y-1.5">
                                            <input v-model="settings.email_destination" type="email" placeholder="E-mail получателя" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:outline-none">
                                            <input v-model="settings.telegram_bot_token" type="password" placeholder="Токен Telegram Бота" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:outline-none">
                                            <input v-model="settings.telegram_chat_id" type="text" placeholder="ID Чата Telegram" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Уведомления</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded"><i class="fa-solid fa-satellite-dish"></i> Настроены</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- TAB 8: DETAILED INTERACTIVE HELP GUIDE -->
                    <div v-if="activeTab === 'help'" class="max-w-5xl mx-auto space-y-8 animate__animated animate__fadeIn">

                        <!-- Header Banner -->
                        <div class="bg-gradient-to-r from-indigo-900 to-blue-700 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(255,255,255,0.1),transparent_40%)]"></div>
                            <div class="relative z-10 space-y-2">
                                <span class="text-xs bg-indigo-500 text-white font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">Документация и примеры</span>
                                <h2 class="text-3xl font-black tracking-tight">Полное руководство WES.BOT (10 страниц)</h2>
                                <p class="text-sm text-indigo-100/90 font-medium max-w-2xl">Подробное описание работы системы с реальными примерами для санаториев, гостиниц и бизнеса.</p>
                            </div>
                        </div>

                        <!-- Manual Pages -->
                        <div class="space-y-6">

                            <!-- Page 1: Введение и архитектура -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 1: Введение и архитектура решения</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    **WES.BOT** — это современная, быстрая диалоговая платформа, работающая без использования громоздких баз данных SQL (полностью на защищённых файлах JSON). Платформа состоит из трех компонентов:
                                </p>
                                <ul class="list-disc list-inside text-xs text-slate-500 space-y-1 pl-2">
                                    <li>**Админ-панель (index.php)** — визуальный конструктор и аналитика.</li>
                                    <li>**API Контроллер (api.php)** — обрабатывает запросы, логгирует диалоги, отправляет лиды на почту и в Telegram.</li>
                                    <li>**Встраиваемый виджет (widget.js)** — легкий скрипт, работающий внутри Shadow DOM (полная защита от конфликтов стилей со сторонними сайтами).</li>
                                </ul>
                            </div>

                            <!-- Page 2: Управление внешним видом -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 2: Управление дизайном и цветовой гаммой</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Вы можете полностью перекрасить чат под свой брендбук. На выбор доступны **Готовые пресетные темы** (Классический синий, изумрудно-зеленый санаторный, глубокий темный и т.д.) или ручной выбор цветов для фона чата, сообщений бота и сообщений пользователя. Также здесь задаются горизонтальные и вертикальные отступы значка запуска в пикселях.
                                </p>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-[11px] font-mono text-slate-600">
                                    Пример настройки: Цвет виджета: #059669 (Санаторный изумруд), Иконка: Support (🎧), Отступ по горизонтали: 30px, Отступ по вертикали: 30px.
                                </div>
                            </div>

                            <!-- Page 3: Быстрые кнопки на старте -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 3: Конструктор Быстрых Кнопок Ответов</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Быстрые кнопки отображаются в начале диалога, подталкивая клиента совершить целевое действие без необходимости писать текст. Кнопки могут имитировать отправку текстовой фразы боту (например, "Цены на путевки") или мгновенно запускать форму из Конструктора Форм (например, "Оставить заявку").
                                </p>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-[11px] font-mono text-slate-600">
                                    Пример 1: Кнопка "💰 Цены на путевки" -> Действие: Отправить фразу -> "Какие цены на путевки?"<br>
                                    Пример 2: Кнопка "📝 Забронировать" -> Действие: Открыть форму -> "booking"
                                </div>
                            </div>

                            <!-- Page 4: Календарь и график работы -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 4: График работы и ночной лидогенератор</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Для каждого дня недели задаются рабочие часы (например, с 09:00 до 18:00). Если посетитель пишет в нерабочее время, бот вежливо извиняется и выводит специальное сообщение (например: "Сейчас мы не в сети, но вы можете заполнить быструю форму ниже!"). В этот же момент под сообщением **автоматически открывается форма обратной связи**. Вы никогда не упустите ночного клиента!
                                </p>
                            </div>

                            <!-- Page 5: Конструктор интерактивных форм -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 5: Конструктор Форм и сбор заявок</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Позволяет создавать формы любой сложности. Поддерживаемые типы полей:
                                </p>
                                <ul class="list-disc list-inside text-xs text-slate-500 space-y-1 pl-2">
                                    <li>**Текст / Число** — для ввода имени или количества человек.</li>
                                    <li>**Телефон / Email** — для контактных данных (с автоматической валидацией).</li>
                                    <li>**Дата / Время** — для выбора желаемой даты заезда или времени звонка.</li>
                                    <li>**Область текста** — для подробных пожеланий клиента.</li>
                                </ul>
                            </div>

                            <!-- Page 6: База знаний и Crawler -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 6: Обучение бота и автоматический Краулер страниц</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Бот ищет совпадения по ключевым словам. Чтобы не писать базу знаний вручную, используйте **Сканер страниц (Crawler)**. Просто вставьте URL страницы вашего сайта (например, `https://wes.by/about`) и нажмите «Запустить». Сканер загрузит страницу, уберет лишние скрипты, разобьет статьи на логические пары "Вопрос-Ответ" и сгенерирует ключевые слова автоматически!
                                </p>
                            </div>

                            <!-- Page 7: Умные правила и Скрипты -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 7: Умные логические триггеры и выполнение скриптов</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Умные правила позволяют реагировать на слово или словосочетание в сообщении. Бот поддерживает запуск следующих скриптов на стороне клиента:
                                </p>
                                <ul class="list-disc list-inside text-xs text-slate-500 space-y-1 pl-2">
                                    <li>**Показать форму** — бот автоматически откроет выбранную форму обратной связи (например, "Заявка на путевку" при слове "купить").</li>
                                    <li>**Открыть страницу** — бот перенаправит клиента на новую страницу сайта (например, на страницу бронирования при слове "цены").</li>
                                    <li>**Всплывающее окно (Alert)** — покажет нативное браузерное сообщение.</li>
                                </ul>
                            </div>

                            <!-- Page 8: Настройка оповещений Telegram & Email -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 8: Мгновенные оповещения в Telegram и на E-mail</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Для мгновенного получения заявок настройте Приложение 10:
                                </p>
                                <ol class="list-decimal list-inside text-xs text-slate-500 space-y-1 pl-2">
                                    <li>**E-mail получателя** — введите адрес вашей почты (заявки отправляются через стандартный PHP mail).</li>
                                    <li>**Токен Telegram Бота** — создайте бота в Telegram через @BotFather и вставьте его токен.</li>
                                    <li>**ID Чата Telegram** — укажите ID вашего чата или группы (куда бот должен отправлять заполненные лиды).</li>
                                </ol>
                            </div>

                            <!-- Page 9: Журнал диалогов и лидов -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 9: Управление журналом диалогов и лидами</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Все заполненные лиды и подробная история переписки с каждым посетителем сохраняются во вкладках **«История чатов»** и **«Лиды и Заявки»**. Администратор может читать сообщения клиентов, смотреть какие правила сработали, анализировать проблемные вопросы и удалять ненужные диалоги одной кнопкой.
                                </p>
                            </div>

                            <!-- Page 10: Техническая поддержка WES.BY -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                                <h3 class="font-extrabold text-slate-900 text-sm border-b pb-2 text-indigo-600">Страница 10: Техническая поддержка и копирайты</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Данный программный продукт разработан веб-студией **WES.BY**. Мы занимаемся профессиональной разработкой сайтов, интернет-магазинов, CRM систем и чат-ботов любой сложности.
                                </p>
                                <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 text-xs font-semibold text-indigo-950">
                                    📞 Контакты разработчика:<br>
                                    Телефон: +375333533971<br>
                                    E-mail: info@wes.by<br>
                                    Сайт: https://wes.by<br>
                                    Разработано с заботой о вашем бизнесе! 😊
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- FOOTER WITH CREDITS -->
                <footer class="bg-white border-t border-slate-200 py-6 px-8 flex flex-col sm:flex-row items-center justify-between text-slate-400 text-xs font-semibold gap-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 bg-indigo-500 rounded-full"></span>
                        <span>Разработано <a :href="'tel:'+settings.dev_phone" class="text-indigo-900 font-bold hover:underline">{{ settings.dev_name }}</a> {{ settings.dev_phone }} ({{ settings.dev_desc }})</span>
                    </div>
                    <div>
                        <span>&copy; {{ new Date().getFullYear() }} Все права защищены</span>
                    </div>
                </footer>

            </main>
        </div>

    </div>

    <!-- Page initialization script -->
    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    authenticated: false,
                    loginPassword: '',
                    loginError: '',
                    loading: false,
                    saving: false,
                    saveSuccess: false,
                    activeTab: 'general',
                    selectedFormIndex: 0,
                    selectedDialogueIndex: 0,
                    kbSearch: '',
                    importText: '',
                    scanUrl: '',
                    scanMode: 'append',
                    scanningPage: false,
                    knowledgeBase: [],
                    dialogues: [],
                    operatorReplyText: '',
                    pollingInterval: null,
                    submissions: [],
                    settings: {
                        admin_password: '',
                        widget_enabled: true,
                        widget_title: '',
                        widget_subtitle: '',
                        widget_color: '',
                        widget_position: '',
                        widget_offset_x: 20,
                        widget_offset_y: 20,
                        widget_icon: 'bubble',
                        widget_avatar_url: '',
                        chat_bg_color: '#f8fafc',
                        bot_bubble_bg: '#ffffff',
                        bot_bubble_color: '#1e293b',
                        user_bubble_bg: '#2563eb',
                        user_bubble_color: '#ffffff',
                        sound_type: 'synth',
                        chat_rating_text: 'Оцените качество нашей консультации:',
                        contact_phone: '+375333533971',
                        contact_email: 'info@wes.by',
                        contact_address: 'г. Минск',
                        dev_name: 'WES.BY',
                        dev_phone: '+375333533971',
                        dev_site: 'https://wes.by',
                        dev_desc: 'Разработка сайтов и приложений',
                        widget_badge_text: '',
                        widget_badge_bg: '',
                        widget_badge_color: '',
                        widget_badge_animation: '',
                        typing_speed: 30,
                        ai_enabled: false,
                        ai_api_key: '',
                        ai_model: '',
                        dead_end_threshold: 2,
                        fallback_action: 'show_button',
                        fallback_form_id: 'feedback',
                        email_destination: '',
                        telegram_bot_token: '',
                        telegram_chat_id: '',
                        extra_greetings: '',
                        sound_enabled: true,
                        exit_intent_enabled: false,
                        exit_intent_delay: 2,
                        exit_intent_text: '',
                        custom_css: '',
                        chat_rating_enabled: true,
                        schedule: [],
                        schedule_offline_msg: '',
                        forms: [],
                        auto_responders: [],
                        smart_rules: [],
                        quick_buttons: []
                    },
                    tabs: [
                        { id: 'general', name: 'Внешний вид & Чат', icon: 'fa-solid fa-palette' },
                        { id: 'schedule', name: 'График работы', icon: 'fa-solid fa-clock' },
                        { id: 'forms', name: 'Конструктор форм', icon: 'fa-solid fa-list-check' },
                        { id: 'knowledge', name: 'База знаний', icon: 'fa-solid fa-graduation-cap' },
                        { id: 'dialogues', name: 'История чатов', icon: 'fa-solid fa-comments' },
                        { id: 'submissions', name: 'Лиды и Заявки', icon: 'fa-solid fa-users-line' },
                        { id: 'features', name: '10+ Приложений', icon: 'fa-solid fa-cubes' },
                        { id: 'help', name: 'Справка & Инструкция', icon: 'fa-solid fa-circle-question' }
                    ]
                };
            },
            computed: {
                currentTabName() {
                    const current = this.tabs.find(t => t.id === this.activeTab);
                    return current ? current.name : 'Управление';
                },
                embedCode() {
                    const baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
                    return `<!-- WES.BOT Embed Code -->\n<script src="${baseUrl}widget.js"><\/script>\n<!-- End of WES.BOT Embed -->`;
                },
                filteredKnowledge() {
                    if (!this.kbSearch) return this.knowledgeBase;
                    const q = this.kbSearch.toLowerCase();
                    return this.knowledgeBase.filter(k =>
                        k.question.toLowerCase().includes(q) ||
                        k.answer.toLowerCase().includes(q)
                    );
                }
            },
            mounted() {
                this.checkAuth();
                this.startDialoguePolling();
            },
            methods: {
                startDialoguePolling() {
                    if (this.pollingInterval) clearInterval(this.pollingInterval);
                    this.pollingInterval = setInterval(() => {
                        if (this.authenticated && this.activeTab === 'dialogues') {
                            this.loadDialogues(true);
                        }
                    }, 3000);
                },
                toggleOperatorActive(diag) {
                    const newStatus = !diag.operator_active;
                    fetch('api.php?action=toggle_operator_active', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ session_id: diag.session_id, active: newStatus })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            diag.operator_active = res.operator_active;
                        } else {
                            alert(res.error || 'Ошибка изменения статуса');
                        }
                    })
                    .catch(err => {
                        alert('Ошибка сети при переключении режима оператора');
                    });
                },
                sendOperatorReply() {
                    const diag = this.dialogues[this.selectedDialogueIndex];
                    if (!diag || !this.operatorReplyText.trim()) return;

                    const msgText = this.operatorReplyText.trim();
                    fetch('api.php?action=operator_reply', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ session_id: diag.session_id, message: msgText })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.operatorReplyText = '';
                            this.loadDialogues(true);
                            // Scroll to bottom of chat flow on next tick
                            this.$nextTick(() => {
                                const el = document.getElementById('admin-chat-flow');
                                if (el) el.scrollTop = el.scrollHeight;
                            });
                        } else {
                            alert(res.error || 'Ошибка отправки ответа');
                        }
                    })
                    .catch(err => {
                        alert('Ошибка отправки ответа');
                    });
                },
                loadDialogues(keepSelection = false) {
                    fetch('api.php?action=get_dialogues')
                        .then(res => res.json())
                        .then(data => {
                            this.dialogues = data;
                        });
                },
                checkAuth() {
                    fetch('api.php?action=check_auth')
                        .then(res => res.json())
                        .then(data => {
                            if (data.authenticated) {
                                this.authenticated = true;
                                this.fetchData();
                            }
                        });
                },
                handleLogin() {
                    this.loading = true;
                    this.loginError = '';
                    fetch('api.php?action=login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ password: this.loginPassword })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.authenticated = true;
                            this.fetchData();
                        } else {
                            this.loginError = data.error || 'Ошибка входа';
                        }
                    })
                    .catch(() => {
                        this.loginError = 'Ошибка сетевого подключения';
                    })
                    .finally(() => {
                        this.loading = false;
                    });
                },
                handleLogout() {
                    fetch('api.php?action=logout')
                        .then(() => {
                            this.authenticated = false;
                            this.loginPassword = '';
                        });
                },
                fetchData() {
                    // Settings
                    fetch('api.php?action=get_settings')
                        .then(res => res.json())
                        .then(data => {
                            this.settings = data;
                        });
                    // Knowledge Base
                    fetch('api.php?action=get_knowledge')
                        .then(res => res.json())
                        .then(data => {
                            this.knowledgeBase = data;
                        });
                    // Dialogues history
                    fetch('api.php?action=get_dialogues')
                        .then(res => res.json())
                        .then(data => {
                            this.dialogues = data;
                        });
                    // Submissions (Leads)
                    fetch('api.php?action=get_submissions')
                        .then(res => res.json())
                        .then(data => {
                            this.submissions = data;
                        });
                },
                saveAllSettings() {
                    this.saving = true;
                    fetch('api.php?action=save_settings', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.settings)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.settings = data.settings;
                            this.saveSuccess = true;
                            setTimeout(() => { this.saveSuccess = false; }, 3000);
                        }
                    })
                    .finally(() => {
                        this.saving = false;
                    });
                },
                // Knowledge Base Operations
                addNewKbItem() {
                    this.knowledgeBase.unshift({
                        id: 'kb_new_' + Date.now(),
                        question: 'Новый вопрос',
                        answer: 'Новый ответ',
                        keywords: []
                    });
                },
                deleteKbItem(id) {
                    this.knowledgeBase = this.knowledgeBase.filter(k => k.id !== id);
                },
                saveKnowledgeBase() {
                    fetch('api.php?action=save_knowledge', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.knowledgeBase)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('База знаний успешно обновлена!');
                        }
                    });
                },
                startUrlScanner() {
                    if (!this.scanUrl.trim()) {
                        alert('Пожалуйста, введите URL адрес страницы');
                        return;
                    }
                    this.scanningPage = true;
                    fetch('api.php?action=scan_page', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ url: this.scanUrl, mode: this.scanMode })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Успешно просканировано! Импортировано ${data.count} пар(ы) из содержимого страницы.`);
                            this.knowledgeBase = data.kb;
                            this.scanUrl = '';
                        } else {
                            alert(data.error || 'Ошибка при сканировании страницы');
                        }
                    })
                    .catch(() => {
                        alert('Ошибка подключения к серверу сканирования');
                    })
                    .finally(() => {
                        this.scanningPage = false;
                    });
                },
                importKnowledge(mode) {
                    if (!this.importText.trim()) {
                        alert('Введите текст для импорта');
                        return;
                    }
                    fetch('api.php?action=import_knowledge', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ text: this.importText, mode: mode })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Импортировано: ${data.count} пар(ы) вопросов/ответов`);
                            this.knowledgeBase = data.kb;
                            this.importText = '';
                        }
                    });
                },
                // Forms Creator
                addNewForm() {
                    const newId = 'form_' + Date.now();
                    this.settings.forms.push({
                        id: newId,
                        title: 'Новая форма обратной связи',
                        fields: [
                            { id: 'name', label: 'Ваше имя', type: 'text', required: true }
                        ],
                        destination: 'both'
                    });
                    this.selectedFormIndex = this.settings.forms.length - 1;
                },
                deleteForm(idx) {
                    this.settings.forms.splice(idx, 1);
                    if (this.selectedFormIndex >= this.settings.forms.length) {
                        this.selectedFormIndex = 0;
                    }
                },
                addSmartRule() {
                    if (!this.settings.smart_rules) {
                        this.settings.smart_rules = [];
                    }
                    this.settings.smart_rules.push({
                        keyword: 'новое слово',
                        action: 'trigger_form',
                        payload: 'feedback',
                        response: 'Пожалуйста, введите ваши контактные данные в форме ниже!'
                    });
                },
                deleteSmartRule(rIdx) {
                    this.settings.smart_rules.splice(rIdx, 1);
                },
                applyThemePreset(primary, bg, botBg, botColor, userBg, userColor) {
                    this.settings.widget_color = primary;
                    this.settings.chat_bg_color = bg;
                    this.settings.bot_bubble_bg = botBg;
                    this.settings.bot_bubble_color = botColor;
                    this.settings.user_bubble_bg = userBg;
                    this.settings.user_bubble_color = userColor;
                },
                addQuickButton() {
                    if (!this.settings.quick_buttons) {
                        this.settings.quick_buttons = [];
                    }
                    this.settings.quick_buttons.push({
                        title: 'Новая кнопка',
                        action: 'message',
                        payload: 'Текст вашего сообщения для отправки'
                    });
                },
                deleteQuickButton(bIdx) {
                    this.settings.quick_buttons.splice(bIdx, 1);
                },
                addFormField() {
                    if (this.settings.forms[this.selectedFormIndex]) {
                        this.settings.forms[this.selectedFormIndex].fields.push({
                            id: 'field_' + Date.now(),
                            label: 'Название поля',
                            type: 'text',
                            required: false
                        });
                    }
                },
                deleteFormField(fIdx) {
                    if (this.settings.forms[this.selectedFormIndex]) {
                        this.settings.forms[this.selectedFormIndex].fields.splice(fIdx, 1);
                    }
                },
                // Dialogue loggers
                deleteDialogue(id) {
                    fetch('api.php?action=delete_dialogue&id=' + id)
                        .then(res => res.json())
                        .then(() => {
                            this.dialogues = this.dialogues.filter(d => d.id !== id);
                            this.selectedDialogueIndex = 0;
                        });
                },
                clearAllDialogues() {
                    if (confirm('Вы уверены, что хотите удалить все диалоги?')) {
                        fetch('api.php?action=clear_dialogues')
                            .then(res => res.json())
                            .then(() => {
                                this.dialogues = [];
                                this.selectedDialogueIndex = 0;
                            });
                    }
                },
                // Lead submissions
                deleteSubmission(id) {
                    fetch('api.php?action=delete_submission&id=' + id)
                        .then(res => res.json())
                        .then(() => {
                            this.submissions = this.submissions.filter(s => s.id !== id);
                        });
                },
                clearAllSubmissions() {
                    if (confirm('Удалить все заявки?')) {
                        fetch('api.php?action=clear_submissions')
                            .then(res => res.json())
                            .then(() => {
                                this.submissions = [];
                            });
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
