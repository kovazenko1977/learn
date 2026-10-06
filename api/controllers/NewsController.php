<?php
// api/controllers/NewsController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class NewsController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        $news = $this->storage->all('news');
        usort($news, function($a, $b) {
            return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
        });
        json_out($news);
    }

    public function create() {
        require_admin();
        $data = json_in();

        if (empty($data['title']) || empty($data['content'])) {
            json_out(['error' => 'Заголовок и содержание новости обязательны'], 400);
        }

        $newItem = [
            'id' => 'news_' . uniqid(),
            'title' => trim($data['title']),
            'content' => trim($data['content']),
            'image' => trim($data['image'] ?? '📢'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $created = $this->storage->insert('news', $newItem);
        json_out($created, 201);
    }

    public function delete($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID новости не указан'], 400);
        }

        $deleted = $this->storage->delete('news', $id);
        if (!$deleted) {
            json_out(['error' => 'Новость не найдена'], 404);
        }

        json_out(['success' => true, 'message' => 'Новость успешно удалена']);
    }
}
