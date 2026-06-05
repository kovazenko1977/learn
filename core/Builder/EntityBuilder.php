<?php

declare(strict_types=1);

namespace App\Builder;

use App\Storage\StorageManager;

class EntityBuilder
{
    private StorageManager $storage;

    public function __construct(StorageManager $storage)
    {
        $this->storage = $storage;
    }

    public function createEntity(string $name, array $fields): void
    {
        $meta = $this->storage->findOne('meta_entities', ['name' => $name]);
        if ($meta) {
            throw new \Exception("Entity already exists: $name");
        }

        $this->storage->insert('meta_entities', [
            'name' => $name,
            'fields' => $fields,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getEntities(): array
    {
        return $this->storage->find('meta_entities');
    }

    public function getEntity(string $name): ?array
    {
        return $this->storage->findOne('meta_entities', ['name' => $name]);
    }

    public function addField(string $entityName, array $field): void
    {
        $entity = $this->getEntity($entityName);
        if (!$entity) {
            throw new \Exception("Entity not found: $entityName");
        }

        $entity['fields'][] = $field;
        $this->storage->update('meta_entities', $entity['id'], ['fields' => $entity['fields']]);
    }
}
