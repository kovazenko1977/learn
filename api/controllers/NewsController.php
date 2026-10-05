<?php
/**
 * NewsController
 */

class NewsController {
    public function handle($method) {
        if ($method === 'GET') {
            $this->getNews();
        } elseif ($method === 'POST') {
            require_auth(true);
            $this->createNews();
        } elseif ($method === 'PUT') {
            require_auth(true);
            $this->updateNews();
        } elseif ($method === 'DELETE') {
            require_auth(true);
            $this->deleteNews();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function getNews() {
        $db = get_storage();
        $news = $db->get('news');
        json_out(['news' => $news]);
    }

    private function createNews() {
        $data = json_in();
        if (empty($data['title'])) {
            json_out(['error' => 'Укажите заголовок новости'], 400);
        }

        $db = get_storage();
        $newItem = [
            'title' => clean($data['title']),
            'text' => clean($data['text'] ?? ''),
            'date' => clean($data['date'] ?? date('Y-m-d')),
            'image' => clean($data['image'] ?? '📰'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('news', $newItem);
        $newItem['id'] = $id;

        json_out(['success' => true, 'news' => $newItem]);
    }

    private function updateNews() {
        $data = json_in();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : ($data['id'] ?? null);

        if (!$id) {
            json_out(['error' => 'ID новости не указан'], 400);
        }

        $db = get_storage();
        $item = $db->find('news', $id);
        if (!$item) {
            json_out(['error' => 'Новость не найдена'], 404);
        }

        $updateData = [
            'title' => clean($data['title'] ?? $item['title']),
            'text' => clean($data['text'] ?? $item['text']),
            'date' => clean($data['date'] ?? $item['date']),
            'image' => clean($data['image'] ?? $item['image'])
        ];

        $db->update('news', $id, $updateData);
        json_out(['success' => true]);
    }

    private function deleteNews() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) {
            json_out(['error' => 'ID новости не указан'], 400);
        }

        $db = get_storage();
        $db->delete('news', $id);
        json_out(['success' => true]);
    }
}
