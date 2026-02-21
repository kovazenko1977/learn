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
        $status = $data['status'] ?? 'new';
        $data['status'] = $status;
        $data['created_at'] = date('Y-m-d H:i:s');

        $historyComment = 'Заявка создана';
        if (isset($data['performer_id']) && $status === 'assigned') {
            $historyComment = 'Заявка создана и автоматически назначена';
            if ($this->notifier) {
                $this->notifier->send($data['performer_id'], "🚀 Вам назначена новая заявка #$id");
            }
        }

        $data['history'] = [
            [
                'status' => $status,
                'user_id' => $data['initiator_id'],
                'timestamp' => $data['created_at'],
                'comment' => $historyComment
            ]
        ];
        $requests[] = $data;
        $this->store->save($requests);
        return $id;
    }

    public function updateStatus(int $id, string $newStatus, int $userId, string $comment = '', string $photo = '', int $rating = 0): bool {
        $requests = $this->getAll();
        $found = false;
        $targetRequest = null;
        $statusNames = [
            'new' => 'Новая', 'assigned' => 'Назначена', 'working' => 'В работе',
            'checking' => 'Проверка', 'returned' => 'Доработка', 'completed' => 'Выполнена', 'closed' => 'Закрыта'
        ];

        foreach ($requests as &$request) {
            if ($request['id'] === $id) {
                $request['status'] = $newStatus;
                if ($rating > 0) $request['rating'] = $rating;

                $entry = [
                    'status' => $newStatus,
                    'user_id' => $userId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'comment' => $comment
                ];
                if ($rating > 0) $entry['rating'] = $rating;
                if ($photo) {
                    $entry['photo'] = $photo;
                    if (!isset($request['photos'])) $request['photos'] = [];
                    $request['photos'][] = $photo;
                }
                $request['history'][] = $entry;
                $found = true;
                $targetRequest = $request;
                break;
            }
        }
        if ($found) {
            $this->store->save($requests);

            if ($this->notifier && $targetRequest) {
                $statusName = $statusNames[$newStatus] ?? $newStatus;
                $msg = "🔔 Заявка #$id\nСтатус: $statusName";
                if ($comment) {
                    $msg .= "\nКомментарий: " . mb_substr($comment, 0, 100) . (mb_strlen($comment) > 100 ? '...' : '');
                }

                // Notify initiator
                if ($targetRequest['initiator_id'] != $userId) {
                    $this->notifier->send($targetRequest['initiator_id'], $msg);
                }
                // Notify performer if status changed by controller/admin/initiator
                if (isset($targetRequest['performer_id']) && $targetRequest['performer_id'] != $userId) {
                    $this->notifier->send($targetRequest['performer_id'], $msg);
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
                    $this->notifier->send($performerId, "👷 Вам назначена новая заявка #$id");
                    if ($request['initiator_id'] != $assignerId) {
                        $this->notifier->send($request['initiator_id'], "✅ По вашей заявке #$id назначен исполнитель");
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function adminUpdate(int $id, array $data): bool {
        $requests = $this->getAll();
        $found = false;
        foreach ($requests as &$request) {
            if ($request['id'] === $id) {
                if (isset($data['description'])) $request['description'] = $data['description'];
                if (isset($data['priority'])) $request['priority'] = $data['priority'];
                if (isset($data['location'])) $request['location'] = array_merge($request['location'], $data['location']);
                if (isset($data['service_id'])) $request['service_id'] = (int)$data['service_id'];
                if (isset($data['performer_id'])) $request['performer_id'] = (int)$data['performer_id'];

                $request['history'][] = [
                    'status' => $request['status'],
                    'user_id' => 0, // System/Admin
                    'timestamp' => date('Y-m-d H:i:s'),
                    'comment' => 'Данные заявки изменены администратором'
                ];
                $found = true;
                break;
            }
        }
        return $found ? $this->store->save($requests) : false;
    }
}
