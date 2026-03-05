<?php
namespace Managers;
use Core\JsonStore;

class AnnouncementManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('announcements');
    }

    public function create($data) {
        $data['target'] = $data['target'] ?? 'all';
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->store->create($data);
    }

    public function getActive($role = 'all') {
        $all = $this->store->findAll();
        if ($role === 'admin') return $all;
        return array_filter($all, function($a) use ($role) {
            return $a['target'] === 'all' || $a['target'] === $role;
        });
    }
}
