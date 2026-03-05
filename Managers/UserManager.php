<?php
namespace Managers;
use Core\JsonStore;

class UserManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('users');
    }

    public function getUsers() {
        $users = $this->store->findAll();
        // Remove passwords from list for security
        return array_map(function($u) {
            unset($u['password']);
            return $u;
        }, $users);
    }

    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->store->create($data);
    }

    public function deleteUser($id) {
        return $this->store->delete($id);
    }
}
