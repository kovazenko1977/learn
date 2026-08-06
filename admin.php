<?php
/**
 * TL: WebPMS & Extranet Admin Console.
 */
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/Config.php';

// Initialize storage schema if not initialized
Storage::initStorage();

// Actions handling
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Storage / config update action
if ($action === 'update_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'storage_type' => $_POST['storage_type'],
        'sql_driver' => $_POST['sql_driver'],
        'mysql_host' => $_POST['mysql_host'],
        'mysql_dbname' => $_POST['mysql_dbname'],
        'mysql_user' => $_POST['mysql_user'],
        'mysql_pass' => $_POST['mysql_pass'],
        'hotel_name' => $_POST['hotel_name'],
        'hotel_address' => $_POST['hotel_address'],
        'hotel_phone' => $_POST['hotel_phone'],
        'hotel_email' => $_POST['hotel_email']
    ];
    Config::save($settings);
    // Re-initialize storage for immediate effect
    Storage::initStorage();
    header("Location: admin.php?msg=settings_updated");
    exit;
}

// 2. Room CRUD handling
if ($action === 'save_room' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = [
        'id' => isset($_POST['id']) ? intval($_POST['id']) : 0,
        'name' => $_POST['name'],
        'type' => $_POST['type'],
        'price' => floatval($_POST['price']),
        'capacity' => intval($_POST['capacity']),
        'image' => $_POST['image'],
        'description' => $_POST['description']
    ];
    Storage::saveRoom($room);
    header("Location: admin.php?msg=room_saved#rooms");
    exit;
}

if ($action === 'delete_room' && isset($_GET['id'])) {
    Storage::deleteRoom(intval($_GET['id']));
    header("Location: admin.php?msg=room_deleted#rooms");
    exit;
}

// 3. Booking CRUD handling
if ($action === 'save_booking' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'id' => isset($_POST['id']) ? intval($_POST['id']) : 0,
        'room_id' => intval($_POST['room_id']),
        'guest_name' => $_POST['guest_name'],
        'guest_email' => $_POST['guest_email'],
        'guest_phone' => $_POST['guest_phone'],
        'check_in' => $_POST['check_in'],
        'check_out' => $_POST['check_out'],
        'guests' => intval($_POST['guests']),
        'total_price' => floatval($_POST['total_price']),
        'status' => $_POST['status']
    ];
    Storage::saveBooking($booking);
    header("Location: admin.php?msg=booking_saved#bookings");
    exit;
}

if ($action === 'delete_booking' && isset($_GET['id'])) {
    Storage::deleteBooking(intval($_GET['id']));
    header("Location: admin.php?msg=booking_deleted#bookings");
    exit;
}

// Fetch all database records
$rooms = Storage::getRooms();
$bookings = Storage::getBookings();
$config = Config::get();

// Calculate operational KPI metrics
$total_revenue = 0;
$active_bookings_count = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'confirmed') {
        $total_revenue += $b['total_price'];
        $active_bookings_count++;
    }
}
$average_rate = count($rooms) > 0 ? array_sum(array_column($rooms, 'price')) / count($rooms) : 0;
$total_rooms = count($rooms);
$occupancy_rate = $total_rooms > 0 ? round(($active_bookings_count / ($total_rooms * 30)) * 100, 1) : 0; // Simple approximation

// Setup tape chart (Шахматка) dates
$dates = [];
for ($i = -2; $i <= 10; $i++) {
    $dates[] = date('Y-m-d', strtotime("$i days"));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelLine WebPMS — Панель администрирования</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans" x-data="{
    currentTab: 'pms',
    roomModalOpen: false,
    editRoom: { id: 0, name: '', type: '', price: 3500, capacity: 2, image: '', description: '' },
    bookingModalOpen: false,
    editBooking: { id: 0, room_id: '', guest_name: '', guest_email: '', guest_phone: '', check_in: '', check_out: '', guests: 1, total_price: 0, status: 'confirmed' }
}">

    <!-- Left Navigation Bar -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex flex-col justify-between flex-shrink-0">
            <div>
                <!-- Brand Header -->
                <div class="p-6 border-b border-slate-800 flex items-center gap-3">
                    <div class="bg-blue-600 text-white font-extrabold text-xl px-2 py-0.5 rounded shadow">TL</div>
                    <div class="flex flex-col">
                        <span class="text-md font-bold tracking-tight text-white leading-none">TravelLine</span>
                        <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider leading-none">WebPMS Console</span>
                    </div>
                </div>

                <!-- Nav links -->
                <nav class="p-4 space-y-1">
                    <button @click="currentTab = 'pms'" :class="currentTab === 'pms' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition text-left">
                        <i class="fa-solid fa-calendar-days text-lg"></i>
                        <span>Шахматка (Tape Chart)</span>
                    </button>
                    <button @click="currentTab = 'bookings'" :class="currentTab === 'bookings' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition text-left">
                        <i class="fa-solid fa-receipt text-lg"></i>
                        <span>Бронирования (<?php echo count($bookings); ?>)</span>
                    </button>
                    <button @click="currentTab = 'rooms'" :class="currentTab === 'rooms' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition text-left">
                        <i class="fa-solid fa-door-open text-lg"></i>
                        <span>Номера (<?php echo count($rooms); ?>)</span>
                    </button>
                    <button @click="currentTab = 'settings'" :class="currentTab === 'settings' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition text-left">
                        <i class="fa-solid fa-gears text-lg"></i>
                        <span>Настройки БД и Отеля</span>
                    </button>
                </nav>
            </div>

            <!-- Footer Meta -->
            <div class="p-6 border-t border-slate-800 text-xs text-slate-500 space-y-2">
                <div>Режим хранения:</div>
                <div class="font-bold uppercase tracking-wider <?php echo $config['storage_type'] === 'sql' ? 'text-emerald-400' : 'text-orange-400'; ?>">
                    <i class="fa-solid <?php echo $config['storage_type'] === 'sql' ? 'fa-database' : 'fa-file-code'; ?> mr-1"></i>
                    <?php echo $config['storage_type'] === 'sql' ? 'SQL: ' . $config['sql_driver'] : 'JSON Files'; ?>
                </div>
                <a href="index.php" class="text-blue-400 hover:underline block pt-2"><i class="fa-solid fa-arrow-left mr-1"></i> На главную</a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 bg-slate-50 overflow-y-auto flex flex-col justify-between">

            <!-- Top Header -->
            <header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center flex-shrink-0">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-black text-slate-900"><?php echo htmlspecialchars($config['hotel_name']); ?></h2>
                    <span class="text-xs bg-slate-100 text-slate-600 font-medium px-2.5 py-1 rounded-full border border-slate-200"><?php echo htmlspecialchars($config['hotel_address']); ?></span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="booking.php" target="_blank" class="bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs px-4 py-2 rounded-lg shadow transition">
                        <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Модуль онлайн-бронирования
                    </a>
                </div>
            </header>

            <!-- Main Work Panel -->
            <div class="p-8 flex-1">

                <!-- Alert Messages -->
                <?php if (isset($_GET['msg'])): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl mb-6 flex items-center gap-3" x-data="{ show: true }" x-show="show">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                        <span class="text-sm font-semibold">Операция успешно выполнена!</span>
                        <button @click="show = false" class="ml-auto text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                <?php endif; ?>

                <!-- KPI stats row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Всего доходов</span>
                            <div class="text-2xl font-black text-slate-950 mt-1"><?php echo number_format($total_revenue, 0, '.', ' '); ?> ₽</div>
                        </div>
                        <div class="bg-emerald-50 text-emerald-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-wallet"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Активные брони</span>
                            <div class="text-2xl font-black text-slate-950 mt-1"><?php echo $active_bookings_count; ?></div>
                        </div>
                        <div class="bg-blue-50 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-calendar-check"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Загрузка номеров</span>
                            <div class="text-2xl font-black text-slate-950 mt-1"><?php echo $occupancy_rate; ?>%</div>
                        </div>
                        <div class="bg-purple-50 text-purple-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-chart-pie"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Средний тариф ADR</span>
                            <div class="text-2xl font-black text-slate-950 mt-1"><?php echo number_format($average_rate, 0, '.', ' '); ?> ₽</div>
                        </div>
                        <div class="bg-orange-50 text-orange-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-tag"></i></div>
                    </div>
                </div>

                <!-- Tab 1: WebPMS Tape Chart (Шахматка) -->
                <div x-show="currentTab === 'pms'">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center flex-wrap gap-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">Интерактивная лента бронирования (Шахматка)</h3>
                                <p class="text-xs text-slate-500">Визуальное управление квотами, заездами и занятостью номеров</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded bg-blue-500 inline-block"></span> <span class="text-xs text-slate-500 mr-4">Подтверждено</span>
                                <span class="w-3 h-3 rounded bg-amber-500 inline-block"></span> <span class="text-xs text-slate-500 mr-4">Ожидает оплаты</span>
                                <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> <span class="text-xs text-slate-500">Заезд</span>
                            </div>
                        </div>

                        <!-- Tape Chart Layout Grid -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse min-w-[1000px]">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] font-bold text-center border-b border-slate-200">
                                        <th class="p-4 text-left font-semibold border-r border-slate-200 w-56 bg-slate-50 sticky left-0 z-10">Категория / Номер</th>
                                        <?php foreach ($dates as $date): ?>
                                            <th class="p-3 border-r border-slate-200 min-w-[80px]">
                                                <div><?php echo date('d.m', strtotime($date)); ?></div>
                                                <div class="text-[9px] text-slate-400 mt-0.5"><?php echo date('D', strtotime($date)); ?></div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rooms)): ?>
                                        <tr>
                                            <td colspan="<?php echo count($dates) + 1; ?>" class="text-center py-12 text-slate-400">
                                                Добавьте категории номеров во вкладке "Номера", чтобы активировать шахматку.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                                                <td class="p-4 font-bold text-slate-900 border-r border-slate-200 bg-white sticky left-0 z-10">
                                                    <div class="text-xs"><?php echo htmlspecialchars($room['name']); ?></div>
                                                    <div class="text-[9px] text-slate-400 font-semibold uppercase mt-0.5"><?php echo htmlspecialchars($room['type']); ?></div>
                                                </td>
                                                <?php foreach ($dates as $date): ?>
                                                    <?php
                                                    // Find active booking overlapping this date and room
                                                    $active_b = null;
                                                    foreach ($bookings as $b) {
                                                        if ($b['room_id'] == $room['id'] && $date >= $b['check_in'] && $date <= $b['check_out']) {
                                                            $active_b = $b;
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    <td class="p-1 border-r border-slate-100 relative h-16 min-w-[80px]">
                                                        <?php if ($active_b): ?>
                                                            <?php
                                                            // Customize status colors
                                                            $status_class = 'bg-blue-600 text-white';
                                                            if ($active_b['status'] === 'pending') $status_class = 'bg-amber-500 text-white';
                                                            if ($active_b['status'] === 'checked_in') $status_class = 'bg-emerald-600 text-white';
                                                            ?>
                                                            <div @click="
                                                                editBooking = <?php echo htmlspecialchars(json_encode($active_b)); ?>;
                                                                bookingModalOpen = true;
                                                            " class="<?php echo $status_class; ?> absolute inset-1 rounded-lg p-1.5 shadow-sm text-[10px] font-bold flex flex-col justify-between cursor-pointer hover:brightness-110 transition overflow-hidden">
                                                                <span class="truncate leading-none"><?php echo htmlspecialchars($active_b['guest_name']); ?></span>
                                                                <span class="text-[8px] opacity-90 leading-none mt-1 font-mono"><?php echo $active_b['total_price']; ?> ₽</span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="w-full h-full text-slate-300 flex items-center justify-center text-xs opacity-0 hover:opacity-100 transition">
                                                                <button @click="
                                                                    editBooking = { id: 0, room_id: '<?php echo $room['id']; ?>', guest_name: '', guest_email: '', guest_phone: '', check_in: '<?php echo $date; ?>', check_out: '<?php echo date('Y-m-d', strtotime($date . ' + 1 day')); ?>', guests: 1, total_price: <?php echo $room['price']; ?>, status: 'confirmed' };
                                                                    bookingModalOpen = true;
                                                                " class="text-blue-600 font-bold hover:underline">
                                                                    + Бронь
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Reservations Management -->
                <div x-show="currentTab === 'bookings'">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">Список бронирований гостей</h3>
                                <p class="text-xs text-slate-500">Просмотр, редактирование и аннулирование броней</p>
                            </div>
                            <button @click="
                                editBooking = { id: 0, room_id: '', guest_name: '', guest_email: '', guest_phone: '', check_in: '<?php echo date('Y-m-d'); ?>', check_out: '<?php echo date('Y-m-d', strtotime('+2 days')); ?>', guests: 1, total_price: 0, status: 'confirmed' };
                                bookingModalOpen = true;
                            " class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-4 py-2.5 rounded-xl transition shadow flex items-center gap-1.5">
                                <i class="fa-solid fa-plus"></i> Новое бронирование
                            </button>
                        </div>

                        <!-- Bookings Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-left uppercase tracking-wider text-[10px] font-bold border-b border-slate-200">
                                        <th class="p-4">ID</th>
                                        <th class="p-4">Гость</th>
                                        <th class="p-4">Период</th>
                                        <th class="p-4">Категория Номера</th>
                                        <th class="p-4">Гости</th>
                                        <th class="p-4">Стоимость</th>
                                        <th class="p-4">Статус</th>
                                        <th class="p-4 text-center">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-12 text-slate-400">Нет сохраненных броней. Создайте первую бронь прямо сейчас!</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $b): ?>
                                            <?php
                                            $room_name = "Неизвестный номер";
                                            foreach ($rooms as $r) {
                                                if ($r['id'] == $b['room_id']) {
                                                    $room_name = $r['name'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <tr class="hover:bg-slate-50/60">
                                                <td class="p-4 text-xs font-mono text-slate-400">#<?php echo $b['id']; ?></td>
                                                <td class="p-4">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                                    <div class="text-xs text-slate-400 font-semibold"><?php echo htmlspecialchars($b['guest_phone']); ?> | <?php echo htmlspecialchars($b['guest_email']); ?></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="font-bold text-xs text-slate-700"><?php echo date('d.m.Y', strtotime($b['check_in'])); ?> — <?php echo date('d.m.Y', strtotime($b['check_out'])); ?></div>
                                                    <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-wider"><?php echo ceil((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400); ?> ноч.</div>
                                                </td>
                                                <td class="p-4 text-xs font-semibold text-slate-600"><?php echo htmlspecialchars($room_name); ?></td>
                                                <td class="p-4 text-xs font-bold text-slate-600"><?php echo $b['guests']; ?> чел</td>
                                                <td class="p-4 font-black text-slate-900"><?php echo number_format($b['total_price'], 0, '.', ' '); ?> ₽</td>
                                                <td class="p-4">
                                                    <?php if ($b['status'] === 'confirmed'): ?>
                                                        <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-blue-200 uppercase tracking-wider">Подтверждено</span>
                                                    <?php elseif ($b['status'] === 'pending'): ?>
                                                        <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-amber-200 uppercase tracking-wider">Ожидание</span>
                                                    <?php else: ?>
                                                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-emerald-200 uppercase tracking-wider">Заезд</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-4 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button @click="
                                                            editBooking = <?php echo htmlspecialchars(json_encode($b)); ?>;
                                                            bookingModalOpen = true;
                                                        " class="text-blue-600 hover:text-blue-800 p-1.5 hover:bg-blue-50 rounded-lg transition" title="Редактировать"><i class="fa-solid fa-pen-to-square"></i></button>
                                                        <a href="admin.php?action=delete_booking&id=<?php echo $b['id']; ?>" onclick="return confirm('Вы уверены, что хотите аннулировать эту бронь?');" class="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-50 rounded-lg transition" title="Аннулировать"><i class="fa-solid fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Rooms Inventory CRUD -->
                <div x-show="currentTab === 'rooms'">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">Управление категориями номеров</h3>
                                <p class="text-xs text-slate-500">Добавление, редактирование тарифов и квоты номеров в отеле</p>
                            </div>
                            <button @click="
                                editRoom = { id: 0, name: '', type: '', price: 3500, capacity: 2, image: '', description: '' };
                                roomModalOpen = true;
                            " class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-4 py-2.5 rounded-xl transition shadow flex items-center gap-1.5">
                                <i class="fa-solid fa-plus"></i> Новая категория
                            </button>
                        </div>

                        <!-- Rooms Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-left uppercase tracking-wider text-[10px] font-bold border-b border-slate-200">
                                        <th class="p-4 w-20">Фото</th>
                                        <th class="p-4">Категория</th>
                                        <th class="p-4">Тип / Код</th>
                                        <th class="p-4">Базовая цена</th>
                                        <th class="p-4">Вместимость</th>
                                        <th class="p-4">Описание</th>
                                        <th class="p-4 text-center">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($rooms)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-12 text-slate-400">Нет доступных категорий номеров.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <tr class="hover:bg-slate-50/60">
                                                <td class="p-4">
                                                    <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="Room photo" class="w-12 h-12 rounded-xl object-cover shadow-sm border border-slate-200">
                                                </td>
                                                <td class="p-4 font-black text-slate-900"><?php echo htmlspecialchars($room['name']); ?></td>
                                                <td class="p-4 text-xs font-mono text-slate-500 uppercase"><?php echo htmlspecialchars($room['type']); ?></td>
                                                <td class="p-4 font-bold text-blue-600"><?php echo number_format($room['price'], 0, '.', ' '); ?> ₽ / ночь</td>
                                                <td class="p-4 text-xs font-bold text-slate-600"><i class="fa-solid fa-user-group mr-1"></i> до <?php echo $room['capacity']; ?> чел</td>
                                                <td class="p-4 text-xs text-slate-500 max-w-sm truncate"><?php echo htmlspecialchars($room['description']); ?></td>
                                                <td class="p-4 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button @click="
                                                            editRoom = <?php echo htmlspecialchars(json_encode($room)); ?>;
                                                            roomModalOpen = true;
                                                        " class="text-blue-600 hover:text-blue-800 p-1.5 hover:bg-blue-50 rounded-lg transition" title="Редактировать"><i class="fa-solid fa-pen-to-square"></i></button>
                                                        <a href="admin.php?action=delete_room&id=<?php echo $room['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить эту категорию? Все бронирования данного номера будут нарушены.');" class="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-50 rounded-lg transition" title="Удалить"><i class="fa-solid fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Database & Hotel Config Settings -->
                <div x-show="currentTab === 'settings'">
                    <div class="grid lg:grid-cols-2 gap-8">

                        <!-- Storage Engine Configuration form -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                            <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-database text-blue-600"></i> Конфигуратор Хранения Данных
                            </h3>
                            <p class="text-xs text-slate-500 mb-6">Инновационная поддержка переключения движка квоты и броней на лету</p>

                            <form action="admin.php?action=update_settings" method="POST" class="space-y-4">
                                <!-- Pass unmodified hotel attributes -->
                                <input type="hidden" name="hotel_name" value="<?php echo htmlspecialchars($config['hotel_name']); ?>">
                                <input type="hidden" name="hotel_address" value="<?php echo htmlspecialchars($config['hotel_address']); ?>">
                                <input type="hidden" name="hotel_phone" value="<?php echo htmlspecialchars($config['hotel_phone']); ?>">
                                <input type="hidden" name="hotel_email" value="<?php echo htmlspecialchars($config['hotel_email']); ?>">

                                <div x-data="{ mode: '<?php echo $config['storage_type']; ?>' }">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Режим сохранения (Engine)</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="border rounded-2xl p-4 flex flex-col justify-between cursor-pointer transition" :class="mode === 'json' ? 'border-blue-600 bg-blue-50/50 text-blue-900' : 'border-slate-200 hover:bg-slate-50'">
                                            <input type="radio" name="storage_type" value="json" x-model="mode" class="sr-only">
                                            <div class="flex items-center gap-2 font-bold text-sm">
                                                <i class="fa-solid fa-file-code text-orange-500 text-lg"></i> JSON Движок
                                            </div>
                                            <span class="text-[10px] text-slate-400 mt-2">Локальные файлы JSON в папке data/</span>
                                        </label>
                                        <label class="border rounded-2xl p-4 flex flex-col justify-between cursor-pointer transition" :class="mode === 'sql' ? 'border-blue-600 bg-blue-50/50 text-blue-900' : 'border-slate-200 hover:bg-slate-50'">
                                            <input type="radio" name="storage_type" value="sql" x-model="mode" class="sr-only">
                                            <div class="flex items-center gap-2 font-bold text-sm">
                                                <i class="fa-solid fa-database text-emerald-500 text-lg"></i> SQL Движок (PDO)
                                            </div>
                                            <span class="text-[10px] text-slate-400 mt-2">База SQLite или сервер MySQL</span>
                                        </label>
                                    </div>

                                    <!-- SQL Specific Settings -->
                                    <div x-show="mode === 'sql'" class="mt-6 space-y-4 border-t border-slate-100 pt-6" x-cloak x-data="{ driver: '<?php echo $config['sql_driver']; ?>' }">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">SQL Драйвер</label>
                                            <select name="sql_driver" x-model="driver" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                                                <option value="sqlite">SQLite (рекомендуется для хостинга без СУБД)</option>
                                                <option value="mysql">MySQL Server</option>
                                            </select>
                                        </div>

                                        <div x-show="driver === 'mysql'" class="space-y-4 border-l-2 border-emerald-500 pl-4" x-cloak>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">MySQL Хост</label>
                                                <input type="text" name="mysql_host" value="<?php echo htmlspecialchars($config['mysql_host']); ?>" class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">MySQL Имя Базы Данных</label>
                                                <input type="text" name="mysql_dbname" value="<?php echo htmlspecialchars($config['mysql_dbname']); ?>" class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-xs">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Пользователь</label>
                                                    <input type="text" name="mysql_user" value="<?php echo htmlspecialchars($config['mysql_user']); ?>" class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Пароль</label>
                                                    <input type="password" name="mysql_pass" value="<?php echo htmlspecialchars($config['mysql_pass']); ?>" class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-xs">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow transition mt-6">
                                    Сохранить конфигурацию хранения <i class="fa-solid fa-save ml-1"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Hotel Profile Configuration form -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                            <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-hotel text-blue-600"></i> Профиль Отеля / Санатория
                            </h3>
                            <p class="text-xs text-slate-500 mb-6">Сведения об объекте размещения, транслируемые на гостевые интерфейсы</p>

                            <form action="admin.php?action=update_settings" method="POST" class="space-y-4">
                                <input type="hidden" name="storage_type" value="<?php echo htmlspecialchars($config['storage_type']); ?>">
                                <input type="hidden" name="sql_driver" value="<?php echo htmlspecialchars($config['sql_driver']); ?>">
                                <input type="hidden" name="mysql_host" value="<?php echo htmlspecialchars($config['mysql_host']); ?>">
                                <input type="hidden" name="mysql_dbname" value="<?php echo htmlspecialchars($config['mysql_dbname']); ?>">
                                <input type="hidden" name="mysql_user" value="<?php echo htmlspecialchars($config['mysql_user']); ?>">
                                <input type="hidden" name="mysql_pass" value="<?php echo htmlspecialchars($config['mysql_pass']); ?>">

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Название объекта</label>
                                    <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($config['hotel_name']); ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Физический адрес</label>
                                    <input type="text" name="hotel_address" value="<?php echo htmlspecialchars($config['hotel_address']); ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Контактный телефон</label>
                                        <input type="text" name="hotel_phone" value="<?php echo htmlspecialchars($config['hotel_phone']); ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email адрес</label>
                                        <input type="email" name="hotel_email" value="<?php echo htmlspecialchars($config['hotel_email']); ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow transition mt-6">
                                    Обновить реквизиты отеля <i class="fa-solid fa-save ml-1"></i>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Dashboard Footer -->
            <footer class="bg-white border-t border-slate-200 py-4 px-8 text-center text-xs text-slate-400 flex-shrink-0">
                <span>© <?php echo date('Y'); ?> TravelLine WebPMS. Лицензионное демонстрационное ПО.</span>
            </footer>
        </main>
    </div>

    <!-- Room CRUD Modal -->
    <div x-show="roomModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100" @click.away="roomModalOpen = false">
            <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
                <h3 class="text-md font-black" x-text="editRoom.id > 0 ? 'Редактировать категорию' : 'Добавить новую категорию'"></h3>
                <button @click="roomModalOpen = false" class="text-slate-400 hover:text-white text-xl"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="admin.php?action=save_room" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" :value="editRoom.id">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Название категории</label>
                    <input type="text" name="name" x-model="editRoom.name" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Код / Тип</label>
                        <input type="text" name="type" x-model="editRoom.type" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Вместимость (чел)</label>
                        <input type="number" name="capacity" x-model="editRoom.capacity" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Базовый тариф (за ночь, ₽)</label>
                    <input type="number" name="price" x-model="editRoom.price" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">URL изображения номера</label>
                    <input type="url" name="image" x-model="editRoom.image" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Описание категории</label>
                    <textarea name="description" x-model="editRoom.description" rows="3" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-xs font-medium"></textarea>
                </div>
                <div class="border-t border-slate-100 pt-4 flex justify-end gap-3">
                    <button type="button" @click="roomModalOpen = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2.5 rounded-xl text-sm transition">Отмена</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Booking CRUD Modal -->
    <div x-show="bookingModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100" @click.away="bookingModalOpen = false">
            <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
                <h3 class="text-md font-black" x-text="editBooking.id > 0 ? 'Редактировать бронирование #' + editBooking.id : 'Создать ручное бронирование'"></h3>
                <button @click="bookingModalOpen = false" class="text-slate-400 hover:text-white text-xl"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="admin.php?action=save_booking" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" :value="editBooking.id">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Категория Номера</label>
                        <select name="room_id" x-model="editBooking.room_id" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                            <option value="">-- Выберите номер --</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?> (<?php echo $room['price']; ?> ₽)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Количество гостей</label>
                        <input type="number" name="guests" x-model="editBooking.guests" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">ФИО Гостя</label>
                    <input type="text" name="guest_name" x-model="editBooking.guest_name" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Эл. почта</label>
                        <input type="email" name="guest_email" x-model="editBooking.guest_email" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Телефон</label>
                        <input type="text" name="guest_phone" x-model="editBooking.guest_phone" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Дата заезда (Check-in)</label>
                        <input type="date" name="check_in" x-model="editBooking.check_in" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Дата выезда (Check-out)</label>
                        <input type="date" name="check_out" x-model="editBooking.check_out" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Статус бронирования</label>
                        <select name="status" x-model="editBooking.status" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                            <option value="confirmed">Подтверждено</option>
                            <option value="pending">Ожидает оплаты</option>
                            <option value="checked_in">Гость заехал</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Общая стоимость (₽)</label>
                        <input type="number" name="total_price" x-model="editBooking.total_price" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-sm font-semibold">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 flex justify-end gap-3">
                    <button type="button" @click="bookingModalOpen = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2.5 rounded-xl text-sm transition">Отмена</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
