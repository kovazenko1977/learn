<?php
namespace Medical\Core;

class UserManager {
    private $store;
    private $table = 'users';

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function login($username, $password) {
        $users = $this->store->findAll($this->table);
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function loginByCode($code) {
        $users = $this->store->findAll($this->table);
        foreach ($users as $user) {
            if (isset($user['access_code']) && $user['access_code'] === $code) {
                return $user;
            }
        }
        return false;
    }

    public function create($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->store->save($this->table, $data);
    }

    public function getAll() {
        return $this->store->findAll($this->table);
    }

    public function getById($id) {
        return $this->store->findOne($this->table, $id);
    }

    public function delete($id) {
        return $this->store->delete($this->table, $id);
    }

    public function initDefaults() {
        if (count($this->getAll()) > 0) return;

        $defaults = [
            [
                'username' => 'admin',
                'password' => 'admin',
                'role' => 'admin',
                'name' => 'Администратор',
                'access_code' => '123456'
            ],
            [
                'username' => 'doctor',
                'password' => 'doctor',
                'role' => 'doctor',
                'name' => 'Врач Терапевт',
                'access_code' => '101010'
            ],
            [
                'username' => 'cashier',
                'password' => 'cashier',
                'role' => 'cashier',
                'name' => 'Кассир',
                'access_code' => '202020'
            ],
            [
                'username' => 'nurse',
                'password' => 'nurse',
                'role' => 'nurse',
                'name' => 'Медсестра',
                'access_code' => '303030'
            ]
        ];

        foreach ($defaults as $user) {
            $this->create($user);
        }
    }
}
