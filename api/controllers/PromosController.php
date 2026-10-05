<?php
/**
 * PromosController
 */

class PromosController {
    public function handle($method) {
        if ($method === 'GET') {
            $this->getPromos();
        } elseif ($method === 'POST') {
            require_auth(true);
            $this->createPromo();
        } elseif ($method === 'PUT') {
            require_auth(true);
            $this->updatePromo();
        } elseif ($method === 'DELETE') {
            require_auth(true);
            $this->deletePromo();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function getPromos() {
        $db = get_storage();
        $promos = $db->get('promos');
        json_out(['promos' => $promos]);
    }

    private function createPromo() {
        $data = json_in();
        if (empty($data['title'])) {
            json_out(['error' => 'Укажите заголовок акции'], 400);
        }

        $db = get_storage();
        $newPromo = [
            'title' => clean($data['title']),
            'text' => clean($data['text'] ?? ''),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('promos', $newPromo);
        $newPromo['id'] = $id;

        json_out(['success' => true, 'promo' => $newPromo]);
    }

    private function updatePromo() {
        $data = json_in();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : ($data['id'] ?? null);

        if (!$id) {
            json_out(['error' => 'ID акции не указан'], 400);
        }

        $db = get_storage();
        $promo = $db->find('promos', $id);
        if (!$promo) {
            json_out(['error' => 'Акция не найдена'], 404);
        }

        $updateData = [
            'title' => clean($data['title'] ?? $promo['title']),
            'text' => clean($data['text'] ?? $promo['text'])
        ];

        $db->update('promos', $id, $updateData);
        json_out(['success' => true]);
    }

    private function deletePromo() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) {
            json_out(['error' => 'ID акции не указан'], 400);
        }

        $db = get_storage();
        $db->delete('promos', $id);
        json_out(['success' => true]);
    }
}
