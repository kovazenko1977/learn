<?php
namespace Managers;

class MailingManager {
    public function send($data) {
        // Mock email sending logic
        return [
            'success' => true,
            'status' => 'queued',
            'to' => $data['to'] ?? 'all@clients.by',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
