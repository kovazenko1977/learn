<?php
require_once 'Storage.php';

class EventManager {
    private $storage;
    private $filename = 'events';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function getEvents() {
        $events = $this->storage->read($this->filename);
        usort($events, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        return $events;
    }

    public function addEvent($title, $date, $description, $user_id) {
        $events = $this->storage->read($this->filename);
        $events[] = [
            'id' => $this->storage->generateId(),
            'title' => $title,
            'date' => $date,
            'description' => $description,
            'user_id' => $user_id,
            'created_at' => time()
        ];
        return $this->storage->write($this->filename, $events);
    }

    public function deleteEvent($id) {
        $events = $this->storage->read($this->filename);
        $events = array_filter($events, function($e) use ($id) {
            return $e['id'] !== $id;
        });
        return $this->storage->write($this->filename, $events);
    }
}
