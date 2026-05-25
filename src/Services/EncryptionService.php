<?php

namespace App\Services;

class EncryptionService {
    private string $key = 'bolid-secret-key-123';

    public function encrypt(string $data): string {
        // Simple XOR encryption simulation for a configuration tool
        $res = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $res .= $data[$i] ^ $this->key[$i % strlen($this->key)];
        }
        return base64_encode($res);
    }

    public function decrypt(string $encodedData): string {
        $data = base64_decode($encodedData);
        $res = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $res .= $data[$i] ^ $this->key[$i % strlen($this->key)];
        }
        return $res;
    }
}
