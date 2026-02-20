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

    public function updateService(int $id, array $data): bool {
        $services = $this->getAllServices();
        $found = false;
        foreach ($services as &$s) {
            if ($s['id'] === $id) {
                $s = array_merge($s, $data);
                $found = true;
                break;
            }
        }
        return $found ? $this->serviceStore->save($services) : false;
    }

    public function deleteService(int $id): bool {
        $services = $this->getAllServices();
        $newServices = array_filter($services, fn($s) => $s['id'] !== $id);
        return $this->serviceStore->save(array_values($newServices));
    }

    public function getAllTemplates(): array {
        return $this->templateStore->read();
    }

    public function getTemplatesByService(int $serviceId): array {
        $templates = $this->getAllTemplates();
        return array_values(array_filter($templates, fn($t) => $t['service_id'] === $serviceId));
    }

    public function updateTemplate(int $id, array $data): bool {
        $templates = $this->getAllTemplates();
        $found = false;
        foreach ($templates as &$t) {
            if ($t['id'] === $id) {
                $t = array_merge($t, $data);
                $found = true;
                break;
            }
        }
        return $found ? $this->templateStore->save($templates) : false;
    }

    public function deleteTemplate(int $id): bool {
        $templates = $this->getAllTemplates();
        $newTemplates = array_filter($templates, fn($t) => $t['id'] !== $id);
        return $this->templateStore->save(array_values($newTemplates));
    }
}
