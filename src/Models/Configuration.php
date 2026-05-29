<?php

namespace App\Models;

class Configuration {
    private array $data;

    public function __construct(array $initialData = []) {
        $this->data = array_merge($this->getDefaults(), $initialData);
    }

    private function getDefaults(): array {
        return [
            'metadata' => [
                'version' => '1.0',
                'device_type' => 'С2000М',
                'created_at' => date('c'),
            ],
            'devices' => [],
            'partitions' => [],
            'partition_groups' => [],
            'relays' => [],
            'users' => [],
            'zones' => [],
            'scenarios' => [],
            'event_translation' => [],
            'lcd_events' => []
        ];
    }

    public function getData(): array {
        return $this->data;
    }

    // Devices
    public function addDevice(array $device): void {
        $this->data['devices'][] = $device;
    }

    public function removeDevice(int $index): void {
        if (isset($this->data['devices'][$index])) {
            array_splice($this->data['devices'], $index, 1);
        }
    }

    // Partitions
    public function addPartition(array $partition): void {
        $this->data['partitions'][] = $partition;
    }

    public function updatePartition(int $index, array $partition): void {
        if (isset($this->data['partitions'][$index])) {
            $this->data['partitions'][$index] = $partition;
        }
    }

    public function removePartition(int $index): void {
        if (isset($this->data['partitions'][$index])) {
            array_splice($this->data['partitions'], $index, 1);
        }
    }

    // Users
    public function addUser(array $user): void {
        $this->data['users'][] = $user;
    }

    public function removeUser(int $index): void {
        if (isset($this->data['users'][$index])) {
            array_splice($this->data['users'], $index, 1);
        }
    }

    // Zones
    public function updateZone(int $index, array $zone): void {
        $this->data['zones'][$index] = $zone;
    }

    // Generic update
    public function updateField(string $field, $value): void {
        if (array_key_exists($field, $this->data)) {
            $this->data[$field] = $value;
        }
    }
}
