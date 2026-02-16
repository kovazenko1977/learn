<?php
namespace Hop\Core;

class RequestManager {
    private JsonStore $store;
    private ?NotificationManager $notifier;

    public function __construct(JsonStore $store, ?NotificationManager $notifier = null) {
        $this->store = $store;
        $this->notifier = $notifier;
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
        $data['status'] = 'new';
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

        if ($this->notifier) {
            // Notify Service Lead (ideally find lead of service_id)
            $this->notifier->send(0, "Новая заявка #$id: {$data['description']}", 'info');
        }

        return $id;
    }

    public function updateStatus(int $id, string $newStatus, int $userId, string $comment = '', string $photo = ''): bool {
        $requests = $this->getAll();
        $found = false;
        $reqData = null;
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
                $reqData = $request;
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->store->save($requests);
            if ($this->notifier && $reqData) {
                if ($newStatus === 'checking') {
                    // Notify Controllers
                    $this->notifier->send(0, "Заявка #$id ожидает проверки", 'info');
                } elseif ($newStatus === 'returned') {
                    $this->notifier->send($reqData['performer_id'], "Заявка #$id возвращена на доработку", 'warning');
                } elseif ($newStatus === 'completed') {
                    $this->notifier->send($reqData['initiator_id'], "Заявка #$id выполнена", 'success');
                }
            }
            return true;
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
                $this->store->save($requests);
                if ($this->notifier) {
                    $this->notifier->send($performerId, "Вам назначена новая заявка #$id", 'info');
                }
                return true;
            }
        }
        return false;
    }
}
