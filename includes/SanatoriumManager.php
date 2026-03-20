<?php
require_once __DIR__ . '/Storage.php';

/**
 * SanatoriumManager - Управление объектами размещения
 */
class SanatoriumManager {
    private static $file = 'sanatorium_data';

    public static function getInfo() {
        return Storage::read(self::$file);
    }

    public static function updateInfo(array $data) {
        return Storage::write(self::$file, $data);
    }

    /**
     * Возвращает список корпусов
     */
    public static function getBuildings() {
        $data = self::getInfo();
        return $data['buildings'] ?? [];
    }

    /**
     * Возвращает список всех номеров или номеров конкретного корпуса
     */
    public static function getRooms($buildingId = null) {
        $rooms = Storage::read('rooms_data');
        if ($buildingId) {
            return array_filter($rooms, fn($r) => $r['building_id'] == $buildingId);
        }
        return $rooms;
    }

    /**
     * Добавляет или обновляет информацию о номере
     */
    public static function saveRoom(array $roomData) {
        $rooms = self::getRooms();
        if (!isset($roomData['id'])) {
            $roomData['id'] = uniqid('RM_');
        }
        $rooms[$roomData['id']] = $roomData;
        return Storage::write('rooms_data', $rooms);
    }

    /**
     * Удаляет номер
     */
    public static function deleteRoom($id) {
        $rooms = self::getRooms();
        unset($rooms[$id]);
        return Storage::write('rooms_data', $rooms);
    }
}
