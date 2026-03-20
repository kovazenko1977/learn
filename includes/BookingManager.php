<?php
require_once __DIR__ . '/Storage.php';

/**
 * BookingManager - Жизненный цикл бронирований
 */
class BookingManager {
    private static $file = 'bookings_data';

    /**
     * Получить список всех бронирований
     */
    public static function getAll() {
        return Storage::read(self::$file);
    }

    /**
     * Получить бронирование по ID
     */
    public static function getById($id) {
        $bookings = self::getAll();
        return $bookings[$id] ?? null;
    }

    /**
     * Создать новое бронирование
     */
    public static function create(array $data) {
        $bookings = self::getAll();
        $id = uniqid('BRK_');
        $data['id'] = $id;
        $data['status'] = 'new'; // new, pending_payment, confirmed, cancelled, no_show
        $data['created_at'] = date('Y-m-d H:i:s');

        $bookings[$id] = $data;
        if (Storage::write(self::$file, $bookings)) {
            return $id;
        }
        return false;
    }

    /**
     * Обновить статус бронирования
     */
    public static function updateStatus($id, $status) {
        $bookings = self::getAll();
        if (isset($bookings[$id])) {
            $bookings[$id]['status'] = $status;
            $bookings[$id]['updated_at'] = date('Y-m-d H:i:s');
            return Storage::write(self::$file, $bookings);
        }
        return false;
    }

    /**
     * Обновить данные бронирования
     */
    public static function update($id, array $data) {
        $bookings = self::getAll();
        if (isset($bookings[$id])) {
            $bookings[$id] = array_merge($bookings[$id], $data);
            $bookings[$id]['updated_at'] = date('Y-m-d H:i:s');
            return Storage::write(self::$file, $bookings);
        }
        return false;
    }

    /**
     * Удалить бронирование
     */
    public static function delete($id) {
        $bookings = self::getAll();
        unset($bookings[$id]);
        return Storage::write(self::$file, $bookings);
    }

    /**
     * Поиск бронирований по критериям
     */
    public static function find(array $criteria) {
        $bookings = self::getAll();
        return array_filter($bookings, function($b) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if (!isset($b[$key]) || $b[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }
}
