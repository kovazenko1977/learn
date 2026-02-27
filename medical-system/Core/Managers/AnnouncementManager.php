<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class AnnouncementManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('announcements');
    }

    public function getAll() {
        $all = $this->store->getAll();
        // Sort by date created desc
        usort($all, function($a, $b) {
            return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
        });
        return $all;
    }

    public function getActive() {
        $all = $this->getAll();
        $now = date('Y-m-d');
        return array_filter($all, function($item) use ($now) {
            if (!empty($item['expires_at']) && $item['expires_at'] < $now) {
                return false;
            }
            return true;
        });
    }

    public function add($data) {
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['author'] = \Medical\Core\Auth::getUser()['name'] ?? 'Admin';
        $this->store->add($data);
        (new LogManager())->log('Создано объявление', ['title' => $data['title']]);
        return $data['id'];
    }

    public function delete($id) {
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удалено объявление', ['id' => $id]);
        }
        return $res;
    }
}
