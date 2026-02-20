<?php
namespace Hop\Core;

class ChatManager {
    private JsonStore $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getMessages(int $limit = 50): array {
        $messages = $this->store->read();
        return array_slice(array_reverse($messages), 0, $limit);
    }

    public function postMessage(int $userId, string $text): bool {
        $messages = $this->store->read();
        $messages[] = [
            'id' => $this->store->getNextId(),
            'user_id' => $userId,
            'text' => $text,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        return $this->store->save($messages);
    }

    public function deleteMessage(int $id): bool {
        $messages = $this->store->read();
        $newMessages = array_filter($messages, fn($m) => $m['id'] !== $id);
        return $this->store->save(array_values($newMessages));
    }
}
