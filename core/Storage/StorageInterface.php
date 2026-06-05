<?php

declare(strict_types=1);

namespace App\Storage;

interface StorageInterface
{
    public function find(string $collection, array $criteria = []): array;
    public function findOne(string $collection, array $criteria = []): ?array;
    public function insert(string $collection, array $data): string;
    public function update(string $collection, string $id, array $data): bool;
    public function delete(string $collection, string $id): bool;
}
