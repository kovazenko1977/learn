<?php
namespace Hop\Core;

class UserManager {
    private JsonStore $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAll(): array {
        return $this->store->read();
    }

    public function getById(int $id): ?array {
        $users = $this->getAll();
        foreach ($users as $user) {
            if ($user['id'] === $id) return $user;
        }
        return null;
    }

    public function getByUsername(string $username): ?array {
        $users = $this->getAll();
        foreach ($users as $user) {
            if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
                return $user;
            }
        }
        return null;
    }

    public function getByCode(string $code): ?array {
        $users = $this->getAll();
        foreach ($users as $user) {
            if (isset($user['access_code']) && (string)$user['access_code'] === (string)$code) {
                return $user;
            }
        }
        return null;
    }

    public function authenticate(string $username, string $password): ?array {
        $user = $this->getByUsername($username);
        if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function authenticateByCode(string $code): ?array {
        return $this->getByCode($code);
    }

    public function create(array $data): int {
        $users = $this->getAll();
        $id = $this->store->getNextId();
        $data['id'] = $id;
        $users[] = $data;
        $this->store->save($users);
        return $id;
    }

    public function update(int $id, array $newData): bool {
        $users = $this->getAll();
        $found = false;
        foreach ($users as &$user) {
            if ($user['id'] === $id) {
                $user = array_merge($user, $newData);
                $found = true;
                break;
            }
        }
        if ($found) {
            return $this->store->save($users);
        }
        return false;
    }

    public function delete(int $id): bool {
        $users = $this->getAll();
        $initialCount = count($users);
        $users = array_filter($users, fn($u) => $u['id'] !== $id);
        if (count($users) < $initialCount) {
            return $this->store->save(array_values($users));
        }
        return false;
    }
}
