<?php
/**
 * Storage Manager supporting dual JSON/SQL engine for TravelLine Clone.
 */
require_once __DIR__ . '/Config.php';

class Storage {
    private static $pdo = null;
    private static $jsonDir = __DIR__ . '/../data/';

    private static function getPDO() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = Config::get();
        $driver = $config['sql_driver'];

        if ($driver === 'sqlite') {
            $dbPath = self::$jsonDir . 'database.sqlite';
            self::$pdo = new PDO("sqlite:" . $dbPath);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } else {
            // MySQL
            $host = $config['mysql_host'];
            $dbname = $config['mysql_dbname'];
            $user = $config['mysql_user'];
            $pass = $config['mysql_pass'];
            self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return self::$pdo;
    }

    public static function initStorage() {
        Config::init();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            self::migrateSQL();
        } else {
            self::initJSON();
        }
    }

    private static function migrateSQL() {
        $pdo = self::getPDO();
        $driver = Config::get('sql_driver');

        // Create Tables
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                price REAL NOT NULL,
                capacity INTEGER NOT NULL,
                image TEXT,
                description TEXT
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                room_id INTEGER NOT NULL,
                guest_name TEXT NOT NULL,
                guest_email TEXT NOT NULL,
                guest_phone TEXT NOT NULL,
                check_in TEXT NOT NULL,
                check_out TEXT NOT NULL,
                guests INTEGER NOT NULL,
                total_price REAL NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL
            )");
        } else {
            // MySQL syntax
            $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                capacity INT NOT NULL,
                image VARCHAR(255),
                description TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                room_id INT NOT NULL,
                guest_name VARCHAR(255) NOT NULL,
                guest_email VARCHAR(255) NOT NULL,
                guest_phone VARCHAR(50) NOT NULL,
                check_in DATE NOT NULL,
                check_out DATE NOT NULL,
                guests INT NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        // Seed with standard rooms if empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
        if ($stmt->fetchColumn() == 0) {
            $rooms = self::getDemoRooms();
            $insert = $pdo->prepare("INSERT INTO rooms (name, type, price, capacity, image, description) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($rooms as $r) {
                $insert->execute([$r['name'], $r['type'], $r['price'], $r['capacity'], $r['image'], $r['description']]);
            }
        }
    }

    private static function initJSON() {
        $roomsFile = self::$jsonDir . 'rooms.json';
        if (!file_exists($roomsFile)) {
            file_put_contents($roomsFile, json_encode(self::getDemoRooms(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $bookingsFile = self::$jsonDir . 'bookings.json';
        if (!file_exists($bookingsFile)) {
            file_put_contents($bookingsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    private static function getDemoRooms() {
        return [
            [
                'id' => 1,
                'name' => 'Стандартный Одноместный',
                'type' => 'Standard Single',
                'price' => 3500,
                'capacity' => 1,
                'image' => 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=800&auto=format&fit=crop&q=60',
                'description' => 'Уютный одноместный номер, идеально подходящий для деловых поездок. Бесплатный Wi-Fi, рабочая зона.'
            ],
            [
                'id' => 2,
                'name' => 'Улучшенный Двухместный (Double/Twin)',
                'type' => 'Superior Double',
                'price' => 5200,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&auto=format&fit=crop&q=60',
                'description' => 'Просторный номер с большой двуспальной кроватью или двумя раздельными кроватями. Отличный вид на город.'
            ],
            [
                'id' => 3,
                'name' => 'Семейный Полулюкс',
                'type' => 'Junior Suite',
                'price' => 7800,
                'capacity' => 4,
                'image' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&auto=format&fit=crop&q=60',
                'description' => 'Комфортабельный двухкомнатный номер для семейного отдыха. Дополнительный диван-кровать.'
            ],
            [
                'id' => 4,
                'name' => 'Представительский Люкс',
                'type' => 'Executive Suite',
                'price' => 12500,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&auto=format&fit=crop&q=60',
                'description' => 'Роскошный люкс премиум-класса с собственной гостиной, гидромассажной ванной и панорамными окнами.'
            ]
        ];
    }

    // --- Rooms CRUD ---
    public static function getRooms() {
        self::initStorage();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            try {
                $pdo = self::getPDO();
                return $pdo->query("SELECT * FROM rooms")->fetchAll();
            } catch (Exception $e) {
                return self::getDemoRooms();
            }
        } else {
            $roomsFile = self::$jsonDir . 'rooms.json';
            if (file_exists($roomsFile)) {
                return json_decode(file_get_contents($roomsFile), true) ?: [];
            }
            return [];
        }
    }

    public static function saveRoom($room) {
        self::initStorage();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            $pdo = self::getPDO();
            if (isset($room['id']) && $room['id'] > 0) {
                $stmt = $pdo->prepare("UPDATE rooms SET name = ?, type = ?, price = ?, capacity = ?, image = ?, description = ? WHERE id = ?");
                $stmt->execute([$room['name'], $room['type'], $room['price'], $room['capacity'], $room['image'], $room['description'], $room['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rooms (name, type, price, capacity, image, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$room['name'], $room['type'], $room['price'], $room['capacity'], $room['image'], $room['description']]);
            }
        } else {
            $rooms = self::getRooms();
            if (isset($room['id']) && $room['id'] > 0) {
                foreach ($rooms as &$r) {
                    if ($r['id'] == $room['id']) {
                        $r = array_merge($r, $room);
                        break;
                    }
                }
            } else {
                $maxId = 0;
                foreach ($rooms as $r) {
                    if ($r['id'] > $maxId) $maxId = $r['id'];
                }
                $room['id'] = $maxId + 1;
                $rooms[] = $room;
            }
            file_put_contents(self::$jsonDir . 'rooms.json', json_encode($rooms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    public static function deleteRoom($id) {
        self::initStorage();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            $pdo = self::getPDO();
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $rooms = self::getRooms();
            $rooms = array_values(array_filter($rooms, function($r) use ($id) {
                return $r['id'] != $id;
            }));
            file_put_contents(self::$jsonDir . 'rooms.json', json_encode($rooms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    // --- Bookings CRUD ---
    public static function getBookings() {
        self::initStorage();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            try {
                $pdo = self::getPDO();
                return $pdo->query("SELECT * FROM bookings ORDER BY check_in ASC")->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        } else {
            $bookingsFile = self::$jsonDir . 'bookings.json';
            if (file_exists($bookingsFile)) {
                return json_decode(file_get_contents($bookingsFile), true) ?: [];
            }
            return [];
        }
    }

    public static function saveBooking($booking) {
        self::initStorage();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            $pdo = self::getPDO();
            if (isset($booking['id']) && $booking['id'] > 0) {
                $stmt = $pdo->prepare("UPDATE bookings SET room_id = ?, guest_name = ?, guest_email = ?, guest_phone = ?, check_in = ?, check_out = ?, guests = ?, total_price = ?, status = ? WHERE id = ?");
                $stmt->execute([
                    $booking['room_id'],
                    $booking['guest_name'],
                    $booking['guest_email'],
                    $booking['guest_phone'],
                    $booking['check_in'],
                    $booking['check_out'],
                    $booking['guests'],
                    $booking['total_price'],
                    $booking['status'],
                    $booking['id']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO bookings (room_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $booking['room_id'],
                    $booking['guest_name'],
                    $booking['guest_email'],
                    $booking['guest_phone'],
                    $booking['check_in'],
                    $booking['check_out'],
                    $booking['guests'],
                    $booking['total_price'],
                    $booking['status'],
                    date('Y-m-d H:i:s')
                ]);
            }
        } else {
            $bookings = self::getBookings();
            if (isset($booking['id']) && $booking['id'] > 0) {
                foreach ($bookings as &$b) {
                    if ($b['id'] == $booking['id']) {
                        $b = array_merge($b, $booking);
                        break;
                    }
                }
            } else {
                $maxId = 0;
                foreach ($bookings as $b) {
                    if ($b['id'] > $maxId) $maxId = $b['id'];
                }
                $booking['id'] = $maxId + 1;
                $booking['created_at'] = date('Y-m-d H:i:s');
                $bookings[] = $booking;
            }
            file_put_contents(self::$jsonDir . 'bookings.json', json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    public static function deleteBooking($id) {
        self::initStorage();
        $config = Config::get();

        if ($config['storage_type'] === 'sql') {
            $pdo = self::getPDO();
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $bookings = self::getBookings();
            $bookings = array_values(array_filter($bookings, function($b) use ($id) {
                return $b['id'] != $id;
            }));
            file_put_contents(self::$jsonDir . 'bookings.json', json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return true;
    }
}
