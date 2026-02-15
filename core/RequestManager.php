<?php
namespace Hop\Core;

class RequestManager {
    private JsonStore $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAll(): array {
        return $this->store->read();
    }

    public function getById(int $id): ?array {
        $requests = $this->getAll();
        foreach ($requests as $request) {
            if ($request['id'] === $id) return $request;
        }
        return null;
    }

    public function create(array $data): int {
        $requests = $this->getAll();
        $id = $this->store->getNextId();
        $data['id'] = $id;
        $data['status'] = 'new'; // Новая
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['history'] = [
            [
                'status' => 'new',
                'user_id' => $data['initiator_id'],
                'timestamp' => $data['created_at'],
                'comment' => 'Заявка создана'
            ]
        ];
        $requests[] = $data;
        $this->store->save($requests);
        return $id;
    }

    public function updateStatus(int $id, string $newStatus, int $userId, string $comment = '', string $photo = ''): bool {
        $requests = $this->getAll();
        $found = false;
        foreach ($requests as &$request) {
            if ($request['id'] === $id) {
                $request['status'] = $newStatus;
                $entry = [
                    'status' => $newStatus,
                    'user_id' => $userId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'comment' => $comment
                ];
                if ($photo) {
                    $entry['photo'] = $photo;
                    if (!isset($request['photos'])) $request['photos'] = [];
                    $request['photos'][] = $photo;
                }
                $request['history'][] = $entry;

                if ($newStatus === 'assigned' && isset($userId)) {
                    $request['performer_id'] = $userId; // Although Service Lead assigns it to a Performer
                }

                $found = true;
                break;
            }
        }
        if ($found) {
            return $this->store->save($requests);
        }
        return false;
    }

    public function assign(int $id, int $performerId, int $assignerId): bool {
        $requests = $this->getAll();
        foreach ($requests as &$request) {
            if ($request['id'] === $id) {
                $request['status'] = 'assigned';
                $request['performer_id'] = $performerId;
                $request['history'][] = [
                    'status' => 'assigned',
                    'user_id' => $assignerId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'comment' => 'Назначен исполнитель'
                ];
                return $this->store->save($requests);
            }
        }
        return false;
    }
}
