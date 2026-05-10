<?php
namespace Sanatorium\Core\Helpers;

use Sanatorium\Core\Database\JsonStore;

class DemoDataLoader {
    private $store;

    public function __construct() {
        $this->store = new JsonStore(__DIR__ . '/../../data');
    }

    public function load() {
        $dataDir = __DIR__ . '/../../data';

        // 1. Room Classes
        $classes = [
            ['id' => 1, 'name' => 'Эконом', 'description' => 'Бюджетный вариант.', 'booking_type' => 'daily', 'show_slots' => false, 'min_duration' => 1, 'buffer_time' => 0],
            ['id' => 2, 'name' => 'Стандарт', 'description' => 'Классический номер.', 'booking_type' => 'daily', 'show_slots' => false, 'min_duration' => 1, 'buffer_time' => 0],
            ['id' => 3, 'name' => 'Люкс', 'description' => 'Улучшенная планировка.', 'booking_type' => 'daily', 'show_slots' => false, 'min_duration' => 1, 'buffer_time' => 0],
            ['id' => 4, 'name' => 'Апартаменты', 'description' => 'Кухня и гостиная.', 'booking_type' => 'daily', 'show_slots' => false, 'min_duration' => 1, 'buffer_time' => 0],
            ['id' => 5, 'name' => 'Сауна', 'description' => 'Почасовое бронирование.', 'booking_type' => 'hourly', 'show_slots' => true, 'min_duration' => 1, 'buffer_time' => 0]
        ];
        file_put_contents("$dataDir/room_classes.json", json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Rooms (40 rooms)
        $rooms = [];
        for ($i = 1; $i <= 40; $i++) {
            if ($i <= 35) {
                $classId = ($i <= 10) ? 1 : (($i <= 20) ? 2 : (($i <= 30) ? 3 : 4));
                $rooms[] = [
                    'id' => $i,
                    'room_number' => '1' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'room_class_id' => $classId,
                    'price_per_day' => (int)(1500 + ($classId - 1) * 1200 + rand(0, 5) * 100),
                    'price_per_hour' => 0,
                    'capacity' => ($classId == 4) ? 4 : 2,
                    'status' => 'free'
                ];
            } else {
                $rooms[] = [
                    'id' => $i,
                    'room_number' => 'Сауна-' . ($i - 35),
                    'room_class_id' => 5,
                    'price_per_day' => 10000,
                    'price_per_hour' => 1500,
                    'capacity' => 10,
                    'status' => 'free'
                ];
            }
        }
        file_put_contents("$dataDir/rooms.json", json_encode($rooms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Procedures
        $procNames = ['Массаж', 'Грязелечение', 'Ингаляции', 'Фитованны', 'Бассейн', 'ЛФК', 'Йога', 'Соляная пещера'];
        $procedures = [];
        foreach ($procNames as $idx => $name) {
            $procedures[] = ['id' => $idx + 1, 'name' => $name, 'price' => 500 + rand(1, 10) * 100];
        }
        file_put_contents("$dataDir/procedures.json", json_encode($procedures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 4. Extra Services
        $svcNames = ['Трансфер', 'Экскурсия', 'Велосипед', 'Прачечная', 'Мини-бар', 'Бильярд'];
        $services = [];
        foreach ($svcNames as $idx => $name) {
            $services[] = ['id' => $idx + 1, 'name' => $name, 'price' => 300 + rand(1, 20) * 100];
        }
        file_put_contents("$dataDir/extra_services.json", json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 5. Guests (100)
        $firstNames = ['Александр', 'Михаил', 'Иван', 'Сергей', 'Анна', 'Мария', 'Елена'];
        $lastNames = ['Иванов', 'Петров', 'Смирнов', 'Кузнецов', 'Попова', 'Соколова'];
        $guests = [];
        for ($i = 1; $i <= 100; $i++) {
            $guests[] = [
                'id' => $i,
                'name' => $firstNames[rand(0, 6)] . ' ' . $lastNames[rand(0, 5)],
                'phone' => '+7 (900) ' . rand(100, 999) . '-' . rand(10, 99) . '-' . rand(10, 99),
                'citizenship' => 'РФ',
                'address' => 'г. Москва, ул. Мира ' . $i
            ];
        }
        file_put_contents("$dataDir/guests.json", json_encode($guests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 6. Packages
        $packages = [
            ['id' => 1, 'name' => 'Стандарт', 'base_price' => 10000, 'duration_days' => 7],
            ['id' => 2, 'name' => 'Интенсив', 'base_price' => 20000, 'duration_days' => 10],
            ['id' => 3, 'name' => 'Выходной', 'base_price' => 5000, 'duration_days' => 2]
        ];
        file_put_contents("$dataDir/packages.json", json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 7. Bookings (350 bookings)
        $bookings = [];
        $today = date('Y-m-d');
        for ($i = 1; $i <= 350; $i++) {
            $guestIdx = rand(0, 99);
            $roomIdx = rand(0, 39);
            $room = $rooms[$roomIdx];
            $isSauna = ($room['room_class_id'] == 5);

            $offset = rand(-30, 30);
            $checkInTime = ($isSauna) ? sprintf('%02d:00', rand(9, 21)) : '14:00';
            $checkOutTime = ($isSauna) ? sprintf('%02d:00', rand(11, 23)) : '12:00';

            $checkInDate = date('Y-m-d', strtotime("$today $offset days"));
            $checkIn = "$checkInDate $checkInTime:00";
            $duration = ($isSauna) ? 2 : rand(2, 14);
            $checkOut = date('Y-m-d H:i:s', strtotime("$checkIn +$duration " . ($isSauna ? "hours" : "days")));

            $bookings[] = [
                'id' => $i,
                'guest_id' => $guests[$guestIdx]['id'],
                'client_name' => $guests[$guestIdx]['name'],
                'phone' => $guests[$guestIdx]['phone'],
                'room_id' => $room['id'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'persons' => rand(1, 3),
                'package_id' => $isSauna ? null : rand(1, 3),
                'status' => ($offset < 0) ? 'confirmed' : 'booked',
                'is_hourly' => $isSauna,
                'total_price' => 5000 + rand(1, 50) * 500,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        file_put_contents("$dataDir/bookings.json", json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents("$dataDir/room_calendar.json", json_encode([]));

        return true;
    }
}
