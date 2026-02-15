<?php
namespace Hop\Core;

class ServiceManager {
    private JsonStore $serviceStore;
    private JsonStore $templateStore;

    public function __construct(JsonStore $serviceStore, JsonStore $templateStore) {
        $this->serviceStore = $serviceStore;
        $this->templateStore = $templateStore;
    }

    public function getAllServices(): array {
        return $this->serviceStore->read();
    }

    public function getServiceById(int $id): ?array {
        $services = $this->getAllServices();
        foreach ($services as $service) {
            if ($service['id'] === $id) return $service;
        }
        return null;
    }

    public function getAllTemplates(): array {
        return $this->templateStore->read();
    }

    public function getTemplatesByService(int $serviceId): array {
        $templates = $this->getAllTemplates();
        return array_values(array_filter($templates, fn($t) => $t['service_id'] === $serviceId));
    }
}
