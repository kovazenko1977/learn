<?php
namespace Hop\Core;

class AnalyticsManager {
    private JsonStore $requestStore;
    private JsonStore $serviceStore;

    public function __construct(JsonStore $requestStore, JsonStore $serviceStore) {
        $this->requestStore = $requestStore;
        $this->serviceStore = $serviceStore;
    }

    public function getStats(): array {
        $requests = $this->requestStore->read();
        $services = $this->serviceStore->read();

        $stats = [
            'total' => count($requests),
            'by_status' => [],
            'by_service' => [],
            'performance' => []
        ];

        foreach ($requests as $req) {
            $status = $req['status'];
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

            $serviceId = $req['service_id'];
            $stats['by_service'][$serviceId] = ($stats['by_service'][$serviceId] ?? 0) + 1;
        }

        return $stats;
    }
}
