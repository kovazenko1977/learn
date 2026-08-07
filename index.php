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
                                        <div class="border-b border-slate-200 pb-2 mb-3 flex justify-between items-center text-xs font-bold text-slate-600">
                                            <span>Сессия: {{ dialogues[selectedDialogueIndex].session_id }}</span>
                                            <span>{{ dialogues[selectedDialogueIndex].created_at }}</span>
                                        </div>

                                        <!-- Messages flow -->
                                        <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3 pr-2 max-h-[300px]">
                                            <div v-for="(msg, mIdx) in dialogues[selectedDialogueIndex].messages" :key="mIdx" class="flex" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'">
                                                <div class="max-w-[85%] rounded-2xl p-3 text-xs font-medium relative" :class="msg.sender === 'user' ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none'">
                                                    <p>{{ msg.text }}</p>
                                                    <span class="text-[8px] absolute bottom-1 right-2" :class="msg.sender === 'user' ? 'text-white/70' : 'text-slate-400'">{{ msg.time }}</span>
                                                </div>
                                            </div>
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
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 1</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Предзаданные кнопки (Быстрые ответы)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Отображает быстрые темы в начале диалога (например, контакты, адрес, запись).</p>
                                    </div>
                                    <div class="mt-4">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded ml-1.5"><i class="fa-solid fa-check"></i> Интегрировано</span>
                                    </div>
                                </div>

                                <!-- 2. Custom CSS Injector -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 2</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Кастомные стили (Custom CSS)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Встройте собственный CSS код для стилизации виджета под цветовую палитру сайта.</p>
                                        <textarea v-model="settings.custom_css" placeholder=".wes-chat-launcher { border-radius: 4px; }" class="w-full mt-2 bg-white border border-slate-200 p-1.5 text-[10px] rounded font-mono h-16"></textarea>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded ml-1.5"><i class="fa-solid fa-check"></i> Активно</span>
                                    </div>
                                </div>

                                <!-- 3. Exit Intent Popup Tracker -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 3</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Удержание при выходе (Exit Intent)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Автоматически всплывает при попытке ухода мыши за пределы экрана браузера.</p>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <select v-model="settings.exit_intent_enabled" class="px-2 py-1 border border-slate-200 rounded text-xs">
                                            <option :value="true">Включено</option>
                                            <option :value="false">Выключено</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 4. Sound Alert Customization -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 4</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Звуковые эффекты диалога</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Издает приятный легкий звук при отправке и получении ответа от чат-бота.</p>
                                    </div>
                                    <div class="mt-4">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded ml-1.5"><i class="fa-solid fa-circle-check"></i> Включено</span>
                                    </div>
                                </div>

                                <!-- 5. Rating System & Evaluation -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 5</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Оценка качества (Stars Rating)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Предлагает клиенту оценить диалог звездами (от 1 до 5) перед закрытием чата.</p>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <select v-model="settings.chat_rating_enabled" class="px-2 py-1 border border-slate-200 rounded text-xs">
                                            <option :value="true">Включено</option>
                                            <option :value="false">Выключено</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 6. Global Widget Switcher Toggle -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 6</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Глобальное отключение чата</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Один клик для полного сокрытия виджета на сайте во время тех. работ.</p>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <select v-model="settings.widget_enabled" class="px-2 py-1 border border-slate-200 rounded text-xs">
                                            <option :value="true">Отображать на сайте</option>
                                            <option :value="false">Скрыть с сайта</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 7. AI Brain Core Model Toggle -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 7</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Интеграция ИИ (Hugging Face API)</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Позволяет подключить современные нейросети бесплатно без БД.</p>

                                        <div class="mt-2 space-y-1.5" v-if="settings.ai_enabled">
                                            <input v-model="settings.ai_api_key" type="password" placeholder="Huggingface API token" class="w-full px-2 py-1.5 rounded border border-slate-200 text-xs">
                                            <input v-model="settings.ai_model" type="text" placeholder="Model path" class="w-full px-2 py-1.5 rounded border border-slate-200 text-xs">
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Статус ИИ:</span>
                                        <select v-model="settings.ai_enabled" class="px-2 py-1 border border-slate-200 rounded text-xs">
                                            <option :value="true">Включен</option>
                                            <option :value="false">Отключен</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 8. Auto-responder Mapper rules -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 8</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Быстрые триггеры / Ключевые слова</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Мгновенный подбор ответа при нахождении совпадения триггера в тексте.</p>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500">Настроек:</span>
                                        <span class="text-xs font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded">{{ settings.auto_responders.length }} триггеров</span>
                                    </div>
                                </div>

                                <!-- 9. Dead-end Deadlock Protection -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 9</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Анти-тупиковая система</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Если бот подряд не понимает вопросы, вызывается форма обратной связи.</p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-[10px] text-slate-500">Порог тупика:</span>
                                            <input v-model.number="settings.dead_end_threshold" type="number" min="1" max="5" class="w-12 px-1.5 py-1 text-xs border rounded bg-white">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded ml-1.5"><i class="fa-solid fa-shield-halved"></i> Защищено</span>
                                    </div>
                                </div>

                                <!-- 10. Telegram / Email Lead Forwarder -->
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-indigo-200 transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="text-[9px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded uppercase">Приложение 10</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">Мгновенные уведомления о заявках</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Оповещения в Телеграм канал/группу бота и на ваш почтовый ящик.</p>

                                        <div class="mt-2 space-y-1">
                                            <input v-model="settings.email_destination" type="email" placeholder="E-mail получателя" class="w-full px-2 py-1 text-xs border rounded">
                                            <input v-model="settings.telegram_bot_token" type="password" placeholder="Telegram Bot Token" class="w-full px-2 py-1 text-xs border rounded">
                                            <input v-model="settings.telegram_chat_id" type="text" placeholder="Telegram Chat ID" class="w-full px-2 py-1 text-xs border rounded">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-xs font-bold text-slate-500">Статус:</span>
                                        <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded ml-1.5"><i class="fa-solid fa-satellite-dish"></i> Настроено</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <!-- FOOTER WITH CREDITS -->
                <footer class="bg-white border-t border-slate-200 py-6 px-8 flex flex-col sm:flex-row items-center justify-between text-slate-400 text-xs font-semibold gap-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 bg-indigo-500 rounded-full"></span>
                        <span>Разработано <span class="text-indigo-900 font-bold">WES.BY</span> +375333533971 (Разработка сайтов и приложений)</span>
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
                    knowledgeBase: [],
                    dialogues: [],
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
                        custom_css: '',
                        chat_rating_enabled: true,
                        schedule: [],
                        schedule_offline_msg: '',
                        forms: [],
                        auto_responders: []
                    },
                    tabs: [
                        { id: 'general', name: 'Внешний вид & Чат', icon: 'fa-solid fa-palette' },
                        { id: 'schedule', name: 'График работы', icon: 'fa-solid fa-clock' },
                        { id: 'forms', name: 'Конструктор форм', icon: 'fa-solid fa-list-check' },
                        { id: 'knowledge', name: 'База знаний', icon: 'fa-solid fa-graduation-cap' },
                        { id: 'dialogues', name: 'История чатов', icon: 'fa-solid fa-comments' },
                        { id: 'submissions', name: 'Лиды и Заявки', icon: 'fa-solid fa-users-line' },
                        { id: 'features', name: '10+ Приложений', icon: 'fa-solid fa-cubes' }
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
            },
            methods: {
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
