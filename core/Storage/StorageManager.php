<?php

declare(strict_types=1);

namespace App\Storage;

class StorageManager
{
    private StorageInterface $driver;

    public function __construct(StorageInterface $driver)
    {
        $this->driver = $driver;
    }

    public function find(string $collection, array $criteria = []): array
    {
        return $this->driver->find($collection, $criteria);
    }

    public function findOne(string $collection, array $criteria = []): ?array
    {
        return $this->driver->findOne($collection, $criteria);
    }

    public function insert(string $collection, array $data): string
    {
        return $this->driver->insert($collection, $data);
    }

    public function update(string $collection, string $id, array $data): bool
    {
        return $this->driver->update($collection, $id, $data);
    }

    public function delete(string $collection, string $id): bool
    {
        return $this->driver->delete($collection, $id);
    }

    public function seed(string $collection, array $data): void
    {
        $existing = $this->find($collection);
        if (empty($existing)) {
            foreach ($data as $item) {
                $this->insert($collection, $item);
            }
        }
    }
}
