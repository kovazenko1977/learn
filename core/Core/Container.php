<?php

declare(strict_types=1);

namespace App\Core;

class Container
{
    private array $services = [];
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->services[$id])) {
            throw new \Exception("Service not found: " . $id);
        }

        $this->instances[$id] = $this->services[$id]($this);
        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->instances[$id]);
    }
}
