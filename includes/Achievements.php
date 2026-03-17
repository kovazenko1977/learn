<?php
require_once __DIR__ . '/Storage.php';

class Achievements {
    private $storage;
    private $userAchievements;

    public function __construct() {
        $this->storage = new Storage('user_achievements.json');
        $this->userAchievements = $this->storage->read();
    }

    public function addProgress($userId, $type, $amount = 1) {
        if (!isset($this->userAchievements[$userId])) {
            $this->userAchievements[$userId] = ['stats' => [], 'earned' => []];
        }

        if (!isset($this->userAchievements[$userId]['stats'][$type])) {
            $this->userAchievements[$userId]['stats'][$type] = 0;
        }

        $this->userAchievements[$userId]['stats'][$type] += $amount;
        $this->checkUnlocks($userId);
        $this->storage->write($this->userAchievements);
    }

    private function checkUnlocks($userId) {
        $stats = $this->userAchievements[$userId]['stats'];
        $earned = &$this->userAchievements[$userId]['earned'];

        $rules = [
            ['id' => '1', 'type' => 'tasks_completed', 'threshold' => 10],
            ['id' => '2', 'type' => 'messages_sent', 'threshold' => 50],
            ['id' => '3', 'type' => 'shopping_items_added', 'threshold' => 20],
        ];

        foreach ($rules as $rule) {
            if (!in_array($rule['id'], $earned)) {
                $currentValue = $stats[$rule['type']] ?? 0;
                if ($currentValue >= $rule['threshold']) {
                    $earned[] = $rule['id'];
                }
            }
        }
    }

    public function getUserAchievements($userId) {
        $all = [
            ['id' => '1', 'title' => 'Помощник года', 'description' => 'Выполните 10 семейных дел', 'points' => 100, 'icon' => 'fa-star', 'type' => 'tasks_completed', 'threshold' => 10],
            ['id' => '2', 'title' => 'Собеседник', 'description' => 'Отправьте 50 сообщений в чат', 'points' => 50, 'icon' => 'fa-comments', 'type' => 'messages_sent', 'threshold' => 50],
            ['id' => '3', 'title' => 'Снабженец', 'description' => 'Добавьте 20 товаров в список покупок', 'points' => 75, 'icon' => 'fa-shopping-basket', 'type' => 'shopping_items_added', 'threshold' => 20],
        ];

        $userEarned = $this->userAchievements[$userId]['earned'] ?? [];
        $userStats = $this->userAchievements[$userId]['stats'] ?? [];

        foreach ($all as &$a) {
            $a['earned'] = in_array($a['id'], $userEarned);
            $a['progress'] = $userStats[$a['type']] ?? 0;
        }

        return $all;
    }
}
