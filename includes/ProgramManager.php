<?php
require_once __DIR__ . '/Storage.php';

/**
 * ProgramManager - Управление лечебными программами
 */
class ProgramManager {
    private static $file = 'programs_data';

    /**
     * Получить список всех программ
     */
    public static function getAll() {
        return Storage::read(self::$file);
    }

    /**
     * Получить программу по ID
     */
    public static function getById($id) {
        $programs = self::getAll();
        return $programs[$id] ?? null;
    }

    /**
     * Создать или обновить программу
     */
    public static function save(array $data) {
        $programs = self::getAll();
        if (!isset($data['id'])) {
            $data['id'] = uniqid('PRG_');
        }
        $id = $data['id'];
        $programs[$id] = $data;
        if (Storage::write(self::$file, $programs)) {
            return $id;
        }
        return false;
    }

    /**
     * Удалить программу
     */
    public static function delete($id) {
        $programs = self::getAll();
        unset($programs[$id]);
        return Storage::write(self::$file, $programs);
    }

    /**
     * Получить список процедур (общее описание)
     */
    public static function getProcedures() {
        return [
            'massage' => 'Массаж',
            'physiotherapy' => 'ЛФК',
            'baths' => 'Ванны',
            'inhalations' => 'Ингаляции',
            'mud' => 'Грязелечение'
        ];
    }
}
