<?php
/**
 * ProfileController
 */

class ProfileController {
    public function handle($method) {
        $currentUser = require_auth();

        if ($method === 'GET') {
            $db = get_storage();
            $user = $db->find('users', $currentUser['id']);
            if (!$user) {
                json_out(['error' => 'Пользователь не найден'], 404);
            }
            unset($user['password']);
            json_out($user);
        } elseif ($method === 'PUT') {
            $data = json_in();
            $db = get_storage();
            $user = $db->find('users', $currentUser['id']);

            if (!$user) {
                json_out(['error' => 'Пользователь не найден'], 404);
            }

            $updateData = [
                'name' => clean($data['name'] ?? $user['name']),
                'phone' => clean($data['phone'] ?? $user['phone'])
            ];

            if (!empty($data['password']) && mb_strlen($data['password']) >= 4) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $db->update('users', $currentUser['id'], $updateData);
            json_out(['success' => true]);
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }
}
