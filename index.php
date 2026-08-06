<?php
/**
 * TravelLine High-Fidelity Landing Page Clone.
 */
require_once __DIR__ . '/includes/Config.php';
$hotel_name = Config::get('hotel_name');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelLine — Платформа для гостиничного бизнеса</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .text-tl-blue {
            color: #007bff;
        }
        .bg-tl-blue {
            background-color: #007bff;
        }
        .hover-bg-tl-blue:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans" x-data="{ activeTab: 'booking' }">

    <!-- Top Info Bar -->
    <div class="bg-slate-900 text-slate-400 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span><i class="fa-solid fa-phone mr-1"></i> +7 (8362) 63-00-98</span>
                <span><i class="fa-solid fa-envelope mr-1"></i> welcome@travelline.ru</span>
                <span class="hidden md:inline"><i class="fa-solid fa-location-dot mr-1"></i> Йошкар-Ола, ул. Первомайская, 166</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="admin.php" class="text-white hover:text-blue-400 font-semibold transition"><i class="fa-solid fa-lock mr-1"></i> Личный кабинет (Extranet)</a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <!-- Brand Logo -->
            <a href="index.php" class="flex items-center gap-2">
                <div class="bg-blue-600 text-white font-extrabold text-2xl px-3 py-1 rounded shadow-md">TL</div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight text-slate-900 leading-none">TravelLine</span>
                    <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-widest leading-none">Platform</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-8 font-medium text-slate-600">
                <a href="#products" class="hover:text-blue-600 transition">Инструменты</a>
                <a href="#solutions" class="hover:text-blue-600 transition">Решения</a>
                <a href="#advantages" class="hover:text-blue-600 transition">Преимущества</a>
                <a href="#about" class="hover:text-blue-600 transition">О нас</a>
            </nav>

            <!-- CTA Actions -->
            <div class="flex items-center gap-4">
                <a href="booking.php" class="bg-orange-500 hover:bg-orange-600 text-white font-bold px-5 py-2.5 rounded-lg transition shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days"></i> Модуль бронирования
                </a>
                <a href="admin.php" class="border border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold px-4 py-2 rounded-lg transition">
                    Экстранет
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20 px-4 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(59,130,246,0.2),transparent)] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
            <!-- Hero Text -->
            <div class="space-y-6">
                <div class="inline-block bg-blue-500/20 text-blue-300 font-semibold px-4 py-1.5 rounded-full text-sm border border-blue-500/30">
                    <i class="fa-solid fa-bullhorn mr-1"></i> TravelLine: Единая платформа для вашего отеля
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-black leading-tight">
                    Помогаем гостиничному бизнесу расти и зарабатывать
                </h1>
                <p class="text-slate-300 text-lg md:text-xl font-normal leading-relaxed">
                    Наши технологии — ваш инструмент для роста загрузки и прибыли. Подходит для любого средства размещения — отеля, санатория, базы отдыха или сети апартаментов.
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <a href="booking.php" class="bg-blue-600 hover:bg-blue-700 text-white text-lg font-bold px-8 py-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-hotel mr-2"></i> Перейти к бронированию
                    </a>
                    <a href="admin.php" class="bg-slate-800 hover:bg-slate-700 text-white text-lg font-semibold px-8 py-4 rounded-xl border border-slate-700 transition transform hover:-translate-y-0.5">
                        Панель администратора
                    </a>
                </div>
                <!-- Mini Stats -->
                <div class="grid grid-cols-3 gap-6 pt-8 border-t border-slate-800">
                    <div>
                        <div class="text-3xl font-extrabold text-blue-400">12 000+</div>
                        <div class="text-xs text-slate-400 mt-1">отелей используют TL</div>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-blue-400">15 лет</div>
                        <div class="text-xs text-slate-400 mt-1">на рынке IT-технологий</div>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-blue-400">120+ млн</div>
                        <div class="text-xs text-slate-400 mt-1">броней обработано</div>
                    </div>
                </div>
            </div>

            <!-- Hero Image Mockup -->
            <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl blur opacity-30"></div>
                <div class="relative bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-2xl">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                            <span class="text-xs text-slate-400 ml-2 font-mono">WebPMS - Шахматка номеров</span>
                        </div>
                        <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded font-mono">ONLINE</span>
                    </div>
                    <!-- Virtual grid simulator -->
                    <div class="space-y-2">
                        <div class="grid grid-cols-6 gap-2 text-[10px] text-slate-500 font-bold uppercase text-center pb-1">
                            <div>Номер</div>
                            <div>Пн</div>
                            <div>Вт</div>
                            <div>Ср</div>
                            <div>Чт</div>
                            <div>Пт</div>
                        </div>
                        <div class="grid grid-cols-6 gap-2 items-center bg-slate-950/50 p-2 rounded border border-slate-800/80">
                            <div class="text-[11px] text-slate-300 font-semibold">101 Standard</div>
                            <div class="col-span-3 bg-blue-600/90 text-[10px] text-white p-1 rounded font-semibold text-center truncate">Иванов А. (3 ночи)</div>
                            <div class="col-span-2 text-slate-600 text-center">-</div>
                        </div>
                        <div class="grid grid-cols-6 gap-2 items-center bg-slate-950/50 p-2 rounded border border-slate-800/80">
                            <div class="text-[11px] text-slate-300 font-semibold">102 Superior</div>
                            <div class="col-span-1 text-slate-600 text-center">-</div>
                            <div class="col-span-4 bg-emerald-600/90 text-[10px] text-white p-1 rounded font-semibold text-center truncate">Смирнов К. (4 ночи)</div>
                        </div>
                        <div class="grid grid-cols-6 gap-2 items-center bg-slate-950/50 p-2 rounded border border-slate-800/80">
                            <div class="text-[11px] text-slate-300 font-semibold">103 Family</div>
                            <div class="col-span-2 bg-purple-600/90 text-[10px] text-white p-1 rounded font-semibold text-center truncate">Козлов (2 ночи)</div>
                            <div class="col-span-3 text-slate-600 text-center">-</div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-between items-center text-xs text-slate-400 border-t border-slate-800 pt-3">
                        <span>Загрузка отеля: <strong class="text-blue-400">78%</strong></span>
                        <span>Доход сегодня: <strong class="text-emerald-400">45 600 ₽</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Slider / Tabs -->
    <section id="products" class="py-20 px-4 max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-3xl md:text-4xl font-black text-slate-900">Инструменты платформы TravelLine</h2>
            <p class="text-slate-600 text-lg">
                Всё, что нужно для автоматизации продаж и управления отелем в одной экосистеме.
            </p>
        </div>

        <!-- Tab Controls -->
        <div class="flex flex-wrap justify-center gap-4 mb-12">
            <button
                @click="activeTab = 'booking'"
                :class="activeTab === 'booking' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                class="px-6 py-3 rounded-xl font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-calendar-check"></i> TL: Booking Engine
            </button>
            <button
                @click="activeTab = 'channel'"
                :class="activeTab === 'channel' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                class="px-6 py-3 rounded-xl font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-network-wired"></i> TL: Channel Manager
            </button>
            <button
                @click="activeTab = 'pms'"
                :class="activeTab === 'pms' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                class="px-6 py-3 rounded-xl font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-desktop"></i> TL: WebPMS
            </button>
        </div>

        <!-- Tab Content -->
        <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-xl">
            <!-- TL: Booking Engine -->
            <div x-show="activeTab === 'booking'" class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="text-xs bg-orange-100 text-orange-600 font-extrabold uppercase px-3 py-1 rounded-full inline-block">ЛИДЕР ПРОДАЖ</div>
                    <h3 class="text-3xl font-black text-slate-900">TL: Booking Engine</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Мощный модуль онлайн-бронирования для официального сайта отеля. Помогает конвертировать посетителей вашего сайта в прямых гостей без посредников и комиссий.
                    </p>
                    <ul class="space-y-3 text-slate-700">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Интеграция с любым сайтом за 5 минут</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Адаптивность под все мобильные устройства</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Умные программы лояльности и промокоды</span>
                        </li>
                    </ul>
                    <div class="pt-4">
                        <a href="booking.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl inline-flex items-center gap-2 transition">
                            Попробовать демо-модуль <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <img src="https://images.unsplash.com/photo-1540553016722-983e48a2cd10?w=800&auto=format&fit=crop&q=60" alt="Booking Engine" class="rounded-xl shadow-lg w-full h-64 object-cover">
                </div>
            </div>

            <!-- TL: Channel Manager -->
            <div x-show="activeTab === 'channel'" class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="text-xs bg-blue-100 text-blue-600 font-extrabold uppercase px-3 py-1 rounded-full inline-block">АВТОМАТИЗАЦИЯ</div>
                    <h3 class="text-3xl font-black text-slate-900">TL: Channel Manager</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Управляйте ценами и доступностью номеров в более чем 100 каналах онлайн-продаж (Ostrovok.ru, Bronevik.com, Яндекс.Путешествия, Avito и др.) из одного окна.
                    </p>
                    <ul class="space-y-3 text-slate-700">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Мгновенная синхронизация квот во избежание овербукинга</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Управление тарифами и ограничениями в пару кликов</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Автоматический импорт броней прямо в систему</span>
                        </li>
                    </ul>
                    <div class="pt-4">
                        <a href="admin.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl inline-flex items-center gap-2 transition">
                            Открыть менеджер каналов <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=60" alt="Channel Manager" class="rounded-xl shadow-lg w-full h-64 object-cover">
                </div>
            </div>

            <!-- TL: WebPMS -->
            <div x-show="activeTab === 'pms'" class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="text-xs bg-purple-100 text-purple-600 font-extrabold uppercase px-3 py-1 rounded-full inline-block">СИСТЕМА УПРАВЛЕНИЯ</div>
                    <h3 class="text-3xl font-black text-slate-900">TL: WebPMS (Шахматка)</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Полноценная облачная PMS-система для автоматизации работы ресепшн. Управляйте заездами, выездами, уборкой номеров, гостями и счетами на удобной визуальной интерактивной интерактивной шахматке.
                    </p>
                    <ul class="space-y-3 text-slate-700">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Интерактивная лента бронирования (Drag-and-Drop)</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Профили гостей с историей поездок</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            <span>Гибкая настройка типов номеров и тарифов</span>
                        </li>
                    </ul>
                    <div class="pt-4">
                        <a href="admin.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl inline-flex items-center gap-2 transition">
                            Войти в WebPMS <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=800&auto=format&fit=crop&q=60" alt="WebPMS" class="rounded-xl shadow-lg w-full h-64 object-cover">
                </div>
            </div>
        </div>
    </section>

    <!-- Why TravelLine -->
    <section id="advantages" class="bg-white py-20 border-t border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-3xl md:text-4xl font-black text-slate-900">Почему отельеры выбирают TravelLine?</h2>
                <p class="text-slate-600 text-lg">Каждый день мы делаем управление отелем проще, прозрачнее и прибыльнее.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Advantage 1 -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl transition duration-300">
                    <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-6 shadow-sm">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Рост прямых продаж до 45%</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Умный модуль бронирования подталкивает гостей завершить оформление, а программы лояльности мотивируют возвращаться к вам снова.
                    </p>
                </div>

                <!-- Advantage 2 -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl transition duration-300">
                    <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-6 shadow-sm">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Гибкое хранение данных</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Единственное решение, позволяющее моментально переключаться между легким JSON-форматом и полноценными реляционными базами SQL.
                    </p>
                </div>

                <!-- Advantage 3 -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl transition duration-300">
                    <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-6 shadow-sm">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Круглосуточная поддержка</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Наша служба заботы на связи 24/7. Поможем с настройками, обучим персонал и ответим на любые технические вопросы.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section id="about" class="gradient-bg text-white py-20 px-4 text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_70%,rgba(59,130,246,0.15),transparent)] pointer-events-none"></div>
        <div class="max-w-4xl mx-auto space-y-8 relative">
            <h2 class="text-3xl md:text-5xl font-black leading-tight">Готовы повысить загрузку вашего отеля?</h2>
            <p class="text-slate-300 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed">
                Попробуйте наш интерактивный демонстрационный стенд прямо сейчас. Оцените удобство бронирования номеров и возможности администрирования.
            </p>
            <div class="flex flex-wrap justify-center gap-4 pt-4">
                <a href="booking.php" class="bg-orange-500 hover:bg-orange-600 text-white text-lg font-bold px-8 py-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-calendar-days mr-2"></i> Открыть Модуль Бронирования
                </a>
                <a href="admin.php" class="bg-blue-600 hover:bg-blue-700 text-white text-lg font-bold px-8 py-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-gears mr-2"></i> Войти в Экстранет (PMS)
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-8">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="bg-blue-600 text-white font-extrabold text-xl px-2 py-0.5 rounded">TL</div>
                    <span class="text-lg font-bold text-white tracking-tight">TravelLine Clone</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    © <?php echo date('Y'); ?> TravelLine Clone. Все права защищены. Разработано в целях демонстрации и развертывания на PHP-хостингах.
                </p>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Продукты</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-blue-400">TL: Booking Engine</a></li>
                    <li><a href="#" class="hover:text-blue-400">TL: Channel Manager</a></li>
                    <li><a href="#" class="hover:text-blue-400">TL: WebPMS</a></li>
                    <li><a href="#" class="hover:text-blue-400">TL: Reputation</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Интеграция</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-blue-400">Ostrovok.ru</a></li>
                    <li><a href="#" class="hover:text-blue-400">Яндекс.Путешествия</a></li>
                    <li><a href="#" class="hover:text-blue-400">Bronevik.com</a></li>
                    <li><a href="#" class="hover:text-blue-400">100+ каналов продаж</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Контакты</h4>
                <p class="text-sm">Адрес отеля: <?php echo htmlspecialchars(Config::get('hotel_address')); ?></p>
                <p class="text-sm mt-2">Телефон: <?php echo htmlspecialchars(Config::get('hotel_phone')); ?></p>
                <p class="text-sm mt-1">Email: <?php echo htmlspecialchars(Config::get('hotel_email')); ?></p>
            </div>
        </div>
    </footer>

</body>
</html>
