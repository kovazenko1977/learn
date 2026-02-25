<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;
use Medical\Core\Auth;

class AnnouncementManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('announcements');
    }

    public function getAll() {
        $announcements = $this->store->getAll();
        // Sort by date descending
        usort($announcements, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        return $announcements;
    }

    public function getActive() {
        $all = $this->getAll();
        $now = date('Y-m-d');
        return array_filter($all, function($a) use ($now) {
            return (empty($a['expires_at']) || $a['expires_at'] >= $now);
        });
    }

    public function add($data) {
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['author'] = Auth::getUser()['name'];
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
