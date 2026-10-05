<?php
/**
 * PromocodesController
 */

class PromocodesController {
    public function handle($method) {
        if ($method === 'GET') {
            require_auth(true);
            $this->getAll();
        } elseif ($method === 'POST') {
            $this->create();
        } elseif ($method === 'PUT') {
            require_auth(true);
            $this->update();
        } elseif ($method === 'DELETE') {
            require_auth(true);
            $this->delete();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    public function actionCheck() {
        $data = json_in();
        $code = strtoupper(trim(clean($data['code'] ?? '')));

        if (!$code) {
            json_out(['valid' => false, 'error' => 'Промокод не введён'], 400);
        }

        $db = get_storage();
        $promocodes = $db->get('promocodes');
        $found = null;

        foreach ($promocodes as $pc) {
            if (strtoupper($pc['code']) === $code) {
                $found = $pc;
                break;
            }
        }

        if (!$found) {
            json_out(['valid' => false, 'error' => 'Промокод не найден'], 404);
        }

        $used = $found['used'] ?? $found['uses_count'] ?? 0;
        $limit = $found['limit'] ?? $found['uses_limit'] ?? 100;
        if ($used >= $limit) {
            json_out(['valid' => false, 'error' => 'Превышен лимит использования промокода'], 400);
        }

        $discount = floatval($found['discount'] ?? $found['discount_percent'] ?? 0);

        json_out([
            'valid' => true,
            'success' => true,
            'discount' => $discount,
            'code' => $found['code']
        ]);
    }

    private function getAll() {
        $db = get_storage();
        $promocodes = $db->get('promocodes');
        foreach ($promocodes as &$pc) {
            $pc['discount_percent'] = $pc['discount'] ?? $pc['discount_percent'] ?? 0;
            $pc['uses_count'] = $pc['used'] ?? $pc['uses_count'] ?? 0;
            $pc['uses_limit'] = $pc['limit'] ?? $pc['uses_limit'] ?? 100;
        }
        json_out(['promocodes' => $promocodes]);
    }

    private function create() {
        require_auth(true);
        $data = json_in();
        $code = strtoupper(trim(clean($data['code'] ?? '')));
        $discount = floatval($data['discount'] ?? $data['discount_percent'] ?? 0);
        $limit = intval($data['limit'] ?? $data['uses_limit'] ?? 100);

        if (!$code || $discount <= 0) {
            json_out(['error' => 'Укажите код и процент скидки'], 400);
        }

        $db = get_storage();
        $newPc = [
            'code' => $code,
            'discount' => $discount,
            'discount_percent' => $discount,
            'used' => 0,
            'uses_count' => 0,
            'limit' => $limit,
            'uses_limit' => $limit,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('promocodes', $newPc);
        $newPc['id'] = $id;

        json_out(['success' => true, 'promocode' => $newPc]);
    }

    private function update() {
        $data = json_in();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : ($data['id'] ?? null);

        if (!$id) {
            json_out(['error' => 'ID не указан'], 400);
        }

        $db = get_storage();
        $pc = $db->find('promocodes', $id);
        if (!$pc) {
            json_out(['error' => 'Промокод не найден'], 404);
        }

        $discount = isset($data['discount']) ? floatval($data['discount']) : (isset($data['discount_percent']) ? floatval($data['discount_percent']) : $pc['discount']);
        $limit = isset($data['limit']) ? intval($data['limit']) : (isset($data['uses_limit']) ? intval($data['uses_limit']) : $pc['limit']);

        $updateData = [
            'code' => strtoupper(trim(clean($data['code'] ?? $pc['code']))),
            'discount' => $discount,
            'discount_percent' => $discount,
            'limit' => $limit,
            'uses_limit' => $limit,
            'used' => isset($data['used']) ? intval($data['used']) : ($pc['used'] ?? 0),
            'uses_count' => isset($data['used']) ? intval($data['used']) : ($pc['uses_count'] ?? 0)
        ];

        $db->update('promocodes', $id, $updateData);
        json_out(['success' => true]);
    }

    private function delete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) {
            json_out(['error' => 'ID не указан'], 400);
        }

        $db = get_storage();
        $db->delete('promocodes', $id);
        json_out(['success' => true]);
    }
}
