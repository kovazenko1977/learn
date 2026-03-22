<?php
require_once 'Storage.php';

class AchievementManager {
    private $storage;
    private $filename = 'users';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function addAchievement($user_id, $achievement_id) {
        $users = $this->storage->read($this->filename);
        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                if (!isset($user['achievements'])) $user['achievements'] = [];
                if (!in_array($achievement_id, $user['achievements'])) {
                    $user['achievements'][] = $achievement_id;
                    $this->storage->write($this->filename, $users);
                    return true;
                }
            }
        }
        return false;
    }

    public function getAchievements($user_id) {
        $users = $this->storage->read($this->filename);
        foreach ($users as $user) {
            if ($user['id'] === $user_id) {
                return isset($user['achievements']) ? $user['achievements'] : [];
            }
        }
        return [];
    }

    public static function getMetadata() {
        return [
            'first_task' => ['name' => 'Первая задача', 'icon' => '🎯'],
            'five_tasks' => ['name' => 'Профи по задачам', 'icon' => '🏆'],
            'shopping_star' => ['name' => 'Звезда покупок', 'icon' => '💎']
        ];
    }
}
