<?php
require_once 'Storage.php';

class ChatManager {
    private $storage;
    private $filename = 'chats';
    private $groupsFilename = 'groups';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function sendMessage($from_id, $to_id, $message, $image = null, $reply_to = null, $file = null, $location = null, $voice = null) {
        $chats = $this->storage->read($this->filename);

        $chat_key = strpos($to_id, 'group_') === 0 ? $to_id : $this->getChatKey($from_id, $to_id);

        if (!isset($chats[$chat_key])) {
            $chats[$chat_key] = [];
        }

        $chats[$chat_key][] = [
            'id' => $this->storage->generateId(),
            'from' => $from_id,
            'to' => $to_id,
            'message' => $message,
            'image' => $image,
            'file' => $file,
            'location' => $location,
            'voice' => $voice,
            'reply_to' => $reply_to,
            'timestamp' => time(),
            'read' => false
        ];

        return $this->storage->write($this->filename, $chats);
    }

    public function createGroup($title, $creator_id, $participant_ids) {
        $groups = $this->storage->read($this->groupsFilename);
        $id = 'group_' . $this->storage->generateId();

        $groups[$id] = [
            'id' => $id,
            'title' => $title,
            'creator_id' => $creator_id,
            'participants' => array_unique(array_merge([$creator_id], $participant_ids)),
            'timestamp' => time()
        ];

        if ($this->storage->write($this->groupsFilename, $groups)) {
            return $groups[$id];
        }
        return false;
    }

    public function toggleStar($user_id, $message_id) {
        $chats = $this->storage->read($this->filename);
        foreach ($chats as &$messages) {
            foreach ($messages as &$msg) {
                if ($msg['id'] === $message_id) {
                    if (!isset($msg['starred_by'])) $msg['starred_by'] = [];
                    if (($key = array_search($user_id, $msg['starred_by'])) !== false) {
                        unset($msg['starred_by'][$key]);
                        $msg['starred_by'] = array_values($msg['starred_by']);
                    } else {
                        $msg['starred_by'][] = $user_id;
                    }
                    return $this->storage->write($this->filename, $chats);
                }
            }
        }
        return false;
    }

    public function listMyGroups($user_id) {
        $groups = $this->storage->read($this->groupsFilename);
        $myGroups = [];
        foreach ($groups as $group) {
            if (in_array($user_id, $group['participants'])) {
                $myGroups[] = $group;
            }
        }
        return $myGroups;
    }

    public function getHistory($user1_id, $target_id) {
        $chats = $this->storage->read($this->filename);
        $chat_key = strpos($target_id, 'group_') === 0 ? $target_id : $this->getChatKey($user1_id, $target_id);

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

    public function editMessage($user_id, $message_id, $new_text) {
        $chats = $this->storage->read($this->filename);
        foreach ($chats as &$messages) {
            foreach ($messages as &$msg) {
                if ($msg['id'] === $message_id && $msg['from'] === $user_id) {
                    $msg['message'] = $new_text;
                    $msg['edited'] = true;
                    return $this->storage->write($this->filename, $chats);
                }
            }
        }
        return false;
    }

    public function toggleReaction($user_id, $message_id, $reaction) {
        $chats = $this->storage->read($this->filename);
        foreach ($chats as &$messages) {
            foreach ($messages as &$msg) {
                if ($msg['id'] === $message_id) {
                    if (!isset($msg['reactions'])) $msg['reactions'] = [];
                    if (isset($msg['reactions'][$user_id]) && $msg['reactions'][$user_id] === $reaction) {
                        unset($msg['reactions'][$user_id]);
                    } else {
                        $msg['reactions'][$user_id] = $reaction;
                    }
                    return $this->storage->write($this->filename, $chats);
                }
            }
        }
        return false;
    }

    public function togglePin($message_id) {
        $chats = $this->storage->read($this->filename);
        foreach ($chats as &$messages) {
            foreach ($messages as &$msg) {
                if ($msg['id'] === $message_id) {
                    $msg['pinned'] = !isset($msg['pinned']) || !$msg['pinned'];
                    return $this->storage->write($this->filename, $chats);
                }
            }
        }
        return false;
    }

    public function deleteMessageForEveryone($user_id, $message_id) {
        $chats = $this->storage->read($this->filename);
        foreach ($chats as &$messages) {
            foreach ($messages as $index => $msg) {
                if ($msg['id'] === $message_id && $msg['from'] === $user_id) {
                    unset($messages[$index]);
                    $messages = array_values($messages);
                    return $this->storage->write($this->filename, $chats);
                }
            }
        }
        return false;
    }

    public function getUnreadCounts($user_id) {
        $chats = $this->storage->read($this->filename);
        $counts = [];
        foreach ($chats as $key => $messages) {
            $unread = 0;
            foreach ($messages as $msg) {
                // For private chats: match recipient ID
                // For groups: match participant but mark as read individually isn't fully implemented yet,
                // for now we'll check messages where the user is NOT the sender and read is false.
                if ($msg['from'] !== $user_id && !$msg['read']) {
                    // This is a simplification; in a real group chat 'read' should be per-user.
                    // Given the JSON architecture, we'll stick to a simple flag for now.
                    if ($msg['to'] === $user_id || strpos($msg['to'], 'group_') === 0) {
                         $unread++;
                    }
                }
            }
            if ($unread > 0) $counts[$key] = $unread;
        }
        return $counts;
    }

    private function getChatKey($id1, $id2) {
        $ids = [$id1, $id2];
        sort($ids);
        return implode('_', $ids);
    }
}
