<?php
require_once 'Storage.php';

class ChatManager {
    private $storage;
    private $filename = 'chats';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function sendMessage($from_id, $to_id, $message, $image = null) {
        $chats = $this->storage->read($this->filename);

        $chat_key = $this->getChatKey($from_id, $to_id);

        if (!isset($chats[$chat_key])) {
            $chats[$chat_key] = [];
        }

        $chats[$chat_key][] = [
            'from' => $from_id,
            'to' => $to_id,
            'message' => $message,
            'image' => $image,
            'timestamp' => time(),
            'read' => false
        ];

        return $this->storage->write($this->filename, $chats);
    }

    public function getHistory($user1_id, $user2_id) {
        $chats = $this->storage->read($this->filename);
        $chat_key = $this->getChatKey($user1_id, $user2_id);

        $history = isset($chats[$chat_key]) ? $chats[$chat_key] : [];

        // Mark as read
        $updated = false;
        foreach ($history as &$msg) {
            if ($msg['to'] === $user1_id && !$msg['read']) {
                $msg['read'] = true;
                $updated = true;
            }
        }

        if ($updated) {
            $chats[$chat_key] = $history;
            $this->storage->write($this->filename, $chats);
        }

        return $history;
    }

    private function getChatKey($id1, $id2) {
        $ids = [$id1, $id2];
        sort($ids);
        return implode('_', $ids);
    }
}
