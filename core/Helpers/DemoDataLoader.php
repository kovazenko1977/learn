<?php
namespace Sanatorium\Core\Helpers;

use Sanatorium\Core\Database\JsonStore;

class DemoDataLoader {
    private $store;

    public function __construct() {
        $this->store = new JsonStore(__DIR__ . '/../data');
    }

    public function load() {
        // 1. Room Classes
        $classes = [
            ['id' => 1, 'name' => 'Эконом', 'description' => 'Бюджетный вариант для одного или двоих.'],
            ['id' => 2, 'name' => 'Стандарт', 'description' => 'Классический номер со всеми удобствами.'],
            ['id' => 3, 'name' => 'Люкс', 'description' => 'Просторный номер с улучшенной планировкой.'],
            ['id' => 4, 'name' => 'Апартаменты', 'description' => 'Роскошный номер с кухней и гостиной.']
        ];
        file_put_contents(__DIR__ . '/../data/room_classes.json', json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Rooms (30 rooms)
        $rooms = [];
        for ($i = 1; $i <= 30; $i++) {
            $classId = ($i <= 10) ? 1 : (($i <= 20) ? 2 : (($i <= 25) ? 3 : 4));
            $rooms[] = [
                'id' => $i,
                'room_number' => '1' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'room_class_id' => $classId,
                'price_per_day' => (int)(1500 + ($classId - 1) * 1200 + rand(0, 5) * 100),
                'capacity' => ($classId == 4) ? 4 : (($classId == 1) ? 1 : 2),
                'status' => 'free'
            ];
        }
        file_put_contents(__DIR__ . '/../data/rooms.json', json_encode($rooms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Procedures (25 procedures)
        $procNames = [
            'Массаж классический', 'Грязелечение', 'Подводный душ-массаж', 'Галотерапия', 'Ингаляции',
            'Электрофорез', 'Магнитотерапия', 'Лазерная терапия', 'Криотерапия', 'Озонотерапия',
            'Фитованны', 'Жемчужные ванны', 'Душ Шарко', 'Кедровая бочка', 'Прессотерапия',
            'Парафинотерапия', 'ЛФК (групповое)', 'Бассейн с минеральной водой', 'Ароматерапия', 'Гирудотерапия',
            'Карбокситерапия', 'Лимфодренаж', 'Соляная пещера', 'Кислородный коктейль', 'Скандинавская ходьба'
        ];
        $procedures = [];
        foreach ($procNames as $idx => $name) {
            $procedures[] = [
                'id' => $idx + 1,
                'name' => $name,
                'price' => (int)(400 + rand(0, 15) * 100),
                'duration' => '30 мин',
                'description' => 'Лечебно-профилактическая процедура.'
            ];
        }
        file_put_contents(__DIR__ . '/../data/procedures.json', json_encode($procedures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 4. Extra Services (10 services)
        $svcNames = [
            'Трансфер из аэропорта', 'Экскурсия в город', 'Аренда велосипеда', 'Услуги прачечной',
            'Дополнительная уборка', 'Завтрак в номер', 'Мини-бар (премиум)', 'Бильярд',
            'Теннисный корт', 'Сауна индивидуальная'
        ];
        $services = [];
        foreach ($svcNames as $idx => $name) {
            $services[] = [
                'id' => $idx + 1,
                'name' => $name,
                'price' => (int)(300 + rand(0, 20) * 100)
            ];
        }
        file_put_contents(__DIR__ . '/../data/extra_services.json', json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 5. Guests (30 guests)
        $firstNames = ['Александр', 'Михаил', 'Иван', 'Дмитрий', 'Сергей', 'Андрей', 'Алексей', 'Максим', 'Евгений', 'Николай'];
        $lastNames = ['Иванов', 'Смирнов', 'Кузнецов', 'Попов', 'Васильев', 'Петров', 'Соколов', 'Михайлов', 'Новиков', 'Федоров'];
        $guests = [];
        for ($i = 1; $i <= 30; $i++) {
            $guests[] = [
                'id' => $i,
                'name' => $firstNames[rand(0, 9)] . ' ' . $lastNames[rand(0, 9)],
                'phone' => '+375 (29) ' . rand(100, 999) . '-' . rand(10, 99) . '-' . rand(10, 99),
                'citizenship' => 'Беларусь',
                'address' => 'г. Минск, ул. Примерная, д. ' . $i,
                'passport' => 'MP' . rand(1000000, 9999999)
            ];
        }
        file_put_contents(__DIR__ . '/../data/guests.json', json_encode($guests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 6. Packages (5 packages)
        $packages = [
            ['id' => 1, 'name' => 'Оздоровительная', 'base_price' => 15000, 'duration_days' => 7, 'description' => 'Базовый набор процедур для отдыха.'],
            ['id' => 2, 'name' => 'Лечебная', 'base_price' => 25000, 'duration_days' => 10, 'description' => 'Углубленный курс лечения по профилю.'],
            ['id' => 3, 'name' => 'Детокс', 'base_price' => 18000, 'duration_days' => 5, 'description' => 'Программа очищения организма.'],
            ['id' => 4, 'name' => 'Мать и дитя', 'base_price' => 35000, 'duration_days' => 12, 'description' => 'Специальная программа для родителей с детьми.'],
            ['id' => 5, 'name' => 'Выходного дня', 'base_price' => 8000, 'duration_days' => 2, 'description' => 'Краткий курс релаксации.']
        ];
        file_put_contents(__DIR__ . '/../data/packages.json', json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 7. Bookings (15 bookings)
        $bookings = [];
        $calendar = [];
        $today = date('Y-m-d');
        for ($i = 1; $i <= 15; $i++) {
            $offset = $i - 5;
            $checkIn = date('Y-m-d', strtotime("$today $offset days"));
            $checkOut = date('Y-m-d', strtotime("$checkIn +5 days"));
            $roomId = $i; // Simplified: 1 booking per room for the first 15 rooms

            $status = ($i <= 5) ? 'confirmed' : (($i <= 10) ? 'booked' : 'reserved');

            $bookings[] = [
                'id' => $i,
                'guest_id' => $i,
                'client_name' => $guests[$i-1]['name'],
                'phone' => $guests[$i-1]['phone'],
                'room_id' => $roomId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'persons' => 2,
                'package_id' => rand(1, 5),
                'procedures' => [rand(1, 10), rand(11, 25)],
                'services' => [rand(1, 10)],
                'total_price' => 10000 + rand(0, 20) * 1000,
                'status' => $status,
                'admin_notes' => 'Демонстрационная бронь.'
            ];

            // Update Calendar
            $current = strtotime($checkIn);
            $end = strtotime($checkOut);
            while ($current < $end) {
                $calendar[] = [
                    'id' => count($calendar) + 1,
                    'room_id' => $roomId,
                    'date' => date('Y-m-d', $current),
                    'status' => ($status === 'reserved') ? 'reserved' : (($status === 'confirmed') ? 'occupied' : 'booked'),
                    'booking_id' => $i
                ];
                $current = strtotime("+1 day", $current);
            }
        }
        file_put_contents(__DIR__ . '/../data/bookings.json', json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents(__DIR__ . '/../data/room_calendar.json', json_encode($calendar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 8. Plans (10 tasks)
        $plans = [];
        $priorities = ['Low', 'Medium', 'High'];
        $tasks = ['Уборка номера', 'Замена белья', 'Проверка сантехники', 'Доставка завтрака', 'Пополнение мини-бара'];
        for ($i = 1; $i <= 10; $i++) {
            $plans[] = [
                'id' => $i,
                'title' => $tasks[rand(0, 4)] . ' ' . (100 + $i),
                'description' => 'Плановая задача для персонала.',
                'date' => $today,
                'priority' => $priorities[rand(0, 2)],
                'status' => (rand(0, 1) ? 'completed' : 'pending')
            ];
        }
        file_put_contents(__DIR__ . '/../data/plans.json', json_encode($plans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 9. Text Blocks
        $textBlocks = [
            ['id' => 1, 'slug' => 'booking_intro', 'content' => 'Выберите даты заезда и выезда, чтобы найти подходящий номер. В стоимость многих путевок уже включены лечебные процедуры.'],
            ['id' => 2, 'slug' => 'booking_success', 'content' => 'Спасибо! Ваша заявка успешно отправлена. Наш менеджер свяжется с вами в ближайшее время.'],
            ['id' => 3, 'slug' => 'footer_text', 'content' => '© 2026 Sanatorium Booking System']
        ];
        file_put_contents(__DIR__ . '/../data/text_blocks.json', json_encode($textBlocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return true;
    }
}
