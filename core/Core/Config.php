<?php

declare(strict_types=1);

namespace App\Core;

use App\Storage\StorageManager;

class Config
{
    private StorageManager $storage;
    private array $data = [];

    public function __construct(StorageManager $storage)
    {
        $this->storage = $storage;
        $this->load();
    }

    public function load(): void
    {
        $config = $this->storage->findOne('config', ['id' => 'system']);
        $this->data = $config ?: [
            'id' => 'system',
            'app_name' => 'Sanatorium 2.0',
            'theme' => [
                'primary_color' => '#2563eb',
                'accent_color' => '#8b5cf6',
                'font' => 'Inter'
            ],
            'modules' => []
        ];
    }

    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = $this->data;
        foreach ($parts as $part) {
            if (!isset($value[$part])) return $default;
            $value = $value[$part];
        }
        return $value;
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $temp = &$this->data;
        foreach ($parts as $part) {
            if (!isset($temp[$part])) $temp[$part] = [];
            $temp = &$temp[$part];
        }
        $temp = $value;
        $this->save();
    }

    public function save(): void
    {
        $this->storage->update('config', 'system', $this->data);
    }

    public function isModuleEnabled(string $moduleName): bool
    {
        $modules = $this->get('modules', []);
        return !isset($modules[$moduleName]) || $modules[$moduleName] === true;
    }
}
