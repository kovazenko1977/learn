<?php
namespace Managers;
use Core\JsonStore;

class AnnouncementManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('announcements');
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->store->create($data);
    }

    public function getActive() {
        return $this->store->findAll();
    }
}
