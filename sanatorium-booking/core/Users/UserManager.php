<?php

namespace Sanatorium\Core\Users;

use Sanatorium\Core\Database\JsonStore;

class UserManager {
    private JsonStore $store;
    private string $table = 'users';

    public function __construct(JsonStore $store) {
        $this->store = $store;
        $this->initDefaultAdmin();
    }

    private function initDefaultAdmin(): void {
        $users = $this->getUsers();
        if (empty($users)) {
            $this->addUser([
                'username' => 'admin',
                'password' => 'admin',
                'full_name' => 'Администратор',
                'role' => 'administrator',
                'permissions' => array_keys($this->getAllPermissions()),
                'api_token' => bin2hex(random_bytes(16))
            ]);
        }
    }

    public function getAllPermissions(): array {
        return [
            'view_dashboard' => 'Просмотр дашборда',
            'manage_bookings' => 'Управление бронированиями',
            'view_calendar' => 'Просмотр календаря',
            'manage_guests' => 'Управление гостями',
            'manage_rooms' => 'Управление номерами',
            'manage_catalog' => 'Управление справочниками',
            'view_analytics' => 'Просмотр аналитики',
            'manage_planning' => 'Управление планированием',
            'manage_users' => 'Управление пользователями',
            'manage_settings' => 'Управление настройками',
            'assign_procedures' => 'Назначение процедур',
            'manage_payments' => 'Прием оплаты',
            'record_attendance' => 'Отметка о посещении'
        ];
    }

    public function getUsers(): array {
        return $this->store->findAll($this->table);
    }

    public function getUser(int $id): ?array {
        return $this->store->findOne($this->table, $id);
    }

    public function getUserByUsername(string $username): ?array {
        $users = $this->getUsers();
        if (is_array($users)) {
            foreach ($users as $user) {
                if (is_array($user) && ($user['username'] ?? '') === $username) {
                    return $user;
                }
            }
        }
        return null;
    }

    public function getUserByCode(string $code): ?array {
        $users = $this->getUsers();
        if (is_array($users)) {
            foreach ($users as $user) {
                if (is_array($user) && ($user['access_code'] ?? '') === $code) {
                    return $user;
                }
            }
        }
        return null;
    }

    public function getUserByToken(string $token): ?array {
        $users = $this->getUsers();
        if (is_array($users)) {
            foreach ($users as $user) {
                if (is_array($user) && ($user['api_token'] ?? '') === $token) {
                    return $user;
                }
            }
        }
        return null;
    }

    public function authenticateByCode(string $code): ?array {
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return null;
        }
        return $this->getUserByCode($code);
    }

    public function addUser(array $data): int {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        if (!isset($data['api_token'])) {
            $data['api_token'] = bin2hex(random_bytes(16));
        }
        return $this->store->save($this->table, $data);
    }

    public function updateUser(int $id, array $data): bool {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        unset($data['password']);
        $data['id'] = $id;
        return (bool)$this->store->save($this->table, $data);
    }

    public function deleteUser(int $id): bool {
        return $this->store->delete($this->table, $id);
    }

    public function authenticate(string $username, string $password): ?array {
        $user = $this->getUserByUsername($username);
        if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public function checkPermission(array $user, string $permission): bool {
        if (($user['role'] ?? '') === 'administrator') {
            return true;
        }
        return in_array($permission, $user['permissions'] ?? []);
    }
}
