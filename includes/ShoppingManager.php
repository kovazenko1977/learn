<?php
require_once 'Storage.php';

class ShoppingManager {
    private $storage;
    private $filename = 'shopping';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function getList($user_id) {
        $lists = $this->storage->read($this->filename);
        if (!isset($lists['global'])) {
            $lists['global'] = [
                'items' => []
            ];
            $this->storage->write($this->filename, $lists);
        }
        return $lists['global']['items'];
    }

    public function addItem($text, $user_id) {
        $lists = $this->storage->read($this->filename);
        if (!isset($lists['global'])) $lists['global'] = ['items' => []];

        $lists['global']['items'][] = [
            'id' => $this->storage->generateId(),
            'text' => $text,
            'completed' => false,
            'user_id' => $user_id,
            'timestamp' => time()
        ];

        return $this->storage->write($this->filename, $lists);
    }

    public function toggleItem($id) {
        $lists = $this->storage->read($this->filename);
        if (!isset($lists['global'])) return false;

        foreach ($lists['global']['items'] as &$item) {
            if ($item['id'] === $id) {
                $item['completed'] = !$item['completed'];
                break;
            }
        }

        return $this->storage->write($this->filename, $lists);
    }

    public function clearCompleted() {
        $lists = $this->storage->read($this->filename);
        if (!isset($lists['global'])) return false;

        $lists['global']['items'] = array_filter($lists['global']['items'], function($item) {
            return !$item['completed'];
        });

        return $this->storage->write($this->filename, $lists);
    }
}
