<?php
/**
 * TL: Booking Engine Clone (Guest booking interface).
 */
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Config.php';

$hotel_name = Config::get('hotel_name');
$rooms = Storage::getRooms();

// Handle Form Submission via AJAX/POST
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $guest_name = trim(filter_input(INPUT_POST, 'guest_name', FILTER_DEFAULT));
    $guest_email = trim(filter_input(INPUT_POST, 'guest_email', FILTER_VALIDATE_EMAIL));
    $guest_phone = trim(filter_input(INPUT_POST, 'guest_phone', FILTER_DEFAULT));
    $check_in = trim(filter_input(INPUT_POST, 'check_in', FILTER_DEFAULT));
    $check_out = trim(filter_input(INPUT_POST, 'check_out', FILTER_DEFAULT));
    $guests = filter_input(INPUT_POST, 'guests', FILTER_VALIDATE_INT);

    if ($room_id && $guest_name && $guest_email && $guest_phone && $check_in && $check_out && $guests) {
        // Find room rate
        $selected_room = null;
        foreach ($rooms as $r) {
            if ($r['id'] == $room_id) {
                $selected_room = $r;
                break;
            }
        }

        if ($selected_room) {
            // Calculate nights
            $date1 = new DateTime($check_in);
            $date2 = new DateTime($check_out);
            $nights = $date1->diff($date2)->days;
            if ($nights <= 0) {
                $nights = 1;
            }

            $total_price = $selected_room['price'] * $nights;

            $booking = [
                'room_id' => $room_id,
                'guest_name' => $guest_name,
                'guest_email' => $guest_email,
                'guest_phone' => $guest_phone,
                'check_in' => $check_in,
                'check_out' => $check_out,
                'guests' => $guests,
                'total_price' => $total_price,
                'status' => 'confirmed'
            ];

            if (Storage::saveBooking($booking)) {
                $success_message = "Бронирование успешно оформлено! Номер брони будет отправлен на {$guest_email}. Ждем вас!";
            } else {
                $error_message = "Ошибка сохранения бронирования. Пожалуйста, попробуйте снова.";
            }
        } else {
            $error_message = "Указанный номер не найден.";
        }
    } else {
        $error_message = "Пожалуйста, корректно заполните все обязательные поля.";
    }
}

// Default values for dates
$default_in = date('Y-m-d', strtotime('+1 day'));
$default_out = date('Y-m-d', strtotime('+3 days'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TL: Booking Engine — Модуль онлайн-бронирования</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans" x-data="{
    selectedRoom: null,
    showModal: false,
    checkIn: '<?php echo $default_in; ?>',
    checkOut: '<?php echo $default_out; ?>',
    guestsCount: 1,
    getNights() {
        const d1 = new Date(this.checkIn);
        const d2 = new Date(this.checkOut);
        const diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : 1;
    }
}">

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2">
                <div class="bg-blue-600 text-white font-extrabold text-xl px-2 py-0.5 rounded">TL</div>
                <div class="flex flex-col">
                    <span class="text-md font-bold tracking-tight text-slate-950 leading-none">TravelLine</span>
                    <span class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider leading-none">Booking Engine</span>
                </div>
            </a>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium hidden sm:inline">Лицензионный стенд:</span>
                <span class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-100">
                    <i class="fa-solid fa-hotel mr-1"></i> <?php echo htmlspecialchars($hotel_name); ?>
                </span>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-4 py-8">

        <!-- Booking engine search bar widget -->
        <div class="bg-gradient-to-r from-blue-700 to-blue-900 rounded-2xl p-6 shadow-xl text-white mb-10">
            <h1 class="text-2xl font-black mb-4">Бронирование номеров онлайн</h1>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-blue-200 mb-1">Дата заезда</label>
                    <div class="relative">
                        <input type="date" x-model="checkIn" class="w-full bg-blue-950/40 border border-blue-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400 font-semibold">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-blue-200 mb-1">Дата выезда</label>
                    <div class="relative">
                        <input type="date" x-model="checkOut" class="w-full bg-blue-950/40 border border-blue-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400 font-semibold">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-blue-200 mb-1">Гости</label>
                    <select x-model="guestsCount" class="w-full bg-blue-950/40 border border-blue-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400 font-semibold">
                        <option class="text-slate-900" value="1">1 гость</option>
                        <option class="text-slate-900" value="2">2 гостя</option>
                        <option class="text-slate-900" value="3">3 гостя</option>
                        <option class="text-slate-900" value="4">4 гостя</option>
                    </select>
                </div>
                <div class="text-right">
                    <span class="text-xs text-blue-200 block mb-1">Продолжительность:</span>
                    <div class="text-lg font-black"><span x-text="getNights()"></span> ноч.</div>
                </div>
            </div>
        </div>

        <!-- Alert messages -->
        <?php if ($success_message): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-8 flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-emerald-500 text-xl mt-0.5"></i>
                <div>
                    <h3 class="font-bold">Успешно!</h3>
                    <p class="text-sm"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl mb-8 flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
                <div>
                    <h3 class="font-bold">Ошибка</h3>
                    <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Room Inventory Cards list -->
        <h2 class="text-xl font-black text-slate-900 mb-6">Доступные категории номеров</h2>
        <div class="space-y-6">
            <?php if (empty($rooms)): ?>
                <div class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                    <i class="fa-solid fa-ban text-slate-300 text-5xl mb-3"></i>
                    <p class="text-slate-500">Нет доступных номеров для бронирования.</p>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition duration-200 grid md:grid-cols-12">
                        <!-- Image Area -->
                        <div class="md:col-span-4 relative h-48 md:h-full min-h-[200px]">
                            <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" class="absolute inset-0 w-full h-full object-cover">
                            <span class="absolute top-3 left-3 bg-slate-900/80 text-white text-[10px] font-bold px-2 py-1 rounded font-mono">
                                ID: <?php echo $room['id']; ?>
                            </span>
                        </div>
                        <!-- Info Area -->
                        <div class="md:col-span-5 p-6 flex flex-col justify-between">
                            <div class="space-y-2">
                                <h3 class="text-lg font-black text-slate-900"><?php echo htmlspecialchars($room['name']); ?></h3>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider"><?php echo htmlspecialchars($room['type']); ?></p>
                                <p class="text-sm text-slate-600 leading-relaxed"><?php echo htmlspecialchars($room['description']); ?></p>
                            </div>
                            <!-- Room Features icons -->
                            <div class="flex items-center gap-4 text-slate-500 text-xs mt-4">
                                <span class="flex items-center gap-1"><i class="fa-solid fa-user-group"></i> до <?php echo $room['capacity']; ?> чел</span>
                                <span class="flex items-center gap-1"><i class="fa-solid fa-wifi"></i> Бесплатный Wi-Fi</span>
                                <span class="flex items-center gap-1"><i class="fa-solid fa-mug-hot"></i> Завтрак включен</span>
                            </div>
                        </div>
                        <!-- Action / Pricing Area -->
                        <div class="md:col-span-3 bg-slate-50 p-6 border-t md:border-t-0 md:border-l border-slate-100 flex flex-col justify-between items-stretch">
                            <div class="space-y-1">
                                <span class="text-xs text-slate-500 font-semibold">Лучшее предложение дня</span>
                                <div class="text-2xl font-black text-slate-900">
                                    <?php echo number_format($room['price'], 0, '.', ' '); ?> ₽ <span class="text-xs text-slate-400 font-normal">/ ночь</span>
                                </div>
                                <div class="text-xs text-emerald-600 font-bold">
                                    Итого за <span x-text="getNights()"></span> ноч.: <span x-text="numberWithSpaces(<?php echo $room['price']; ?> * getNights())"></span> ₽
                                </div>
                            </div>
                            <button
                                @click="selectedRoom = <?php echo htmlspecialchars(json_encode($room)); ?>; showModal = true"
                                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg transition mt-6 text-center">
                                Выбрать номер
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Booking Modal (Customer Form) -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100" @click.away="showModal = false">
            <!-- Modal Header -->
            <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-black">Подтверждение бронирования</h3>
                    <p class="text-xs text-slate-400">Заполните данные для регистрации заезда</p>
                </div>
                <button @click="showModal = false" class="text-slate-400 hover:text-white transition text-xl"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Modal Form -->
            <form action="booking.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="room_id" :value="selectedRoom ? selectedRoom.id : ''">
                <input type="hidden" name="check_in" :value="checkIn">
                <input type="hidden" name="check_out" :value="checkOut">
                <input type="hidden" name="guests" :value="guestsCount">

                <!-- Selected Room Summary -->
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-center gap-3">
                    <i class="fa-solid fa-hotel text-blue-600 text-xl"></i>
                    <div>
                        <h4 class="font-bold text-slate-900" x-text="selectedRoom ? selectedRoom.name : ''"></h4>
                        <p class="text-xs text-slate-500">
                            Период: <span x-text="checkIn"></span> — <span x-text="checkOut"></span> (<span x-text="getNights()"></span> ноч.)
                        </p>
                    </div>
                </div>

                <!-- Input Fields -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">ФИО Гостя <span class="text-red-500">*</span></label>
                    <input type="text" name="guest_name" required placeholder="Иванов Иван Иванович" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Эл. почта <span class="text-red-500">*</span></label>
                        <input type="email" name="guest_email" required placeholder="ivanov@example.com" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Телефон <span class="text-red-500">*</span></label>
                        <input type="tel" name="guest_phone" required placeholder="+7 (999) 123-4567" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50">
                    </div>
                </div>

                <!-- Price and submit button -->
                <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400">Итоговая стоимость:</span>
                        <div class="text-2xl font-black text-slate-900">
                            <span x-text="selectedRoom ? numberWithSpaces(selectedRoom.price * getNights()) : 0"></span> ₽
                        </div>
                    </div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3.5 px-6 rounded-xl transition shadow-md">
                        Забронировать <i class="fa-solid fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function numberWithSpaces(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }
    </script>
</body>
</html>
