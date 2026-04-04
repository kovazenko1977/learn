<?php

class Security {
    private static $cipher = "aes-256-cbc";
    private static $key_file = __DIR__ . '/../data/encryption_key.bin';

    private static function getKey() {
        if (!file_exists(self::$key_file)) {
            $key = openssl_random_pseudo_bytes(32);
            file_put_contents(self::$key_file, $key);
            return $key;
        }
        return file_get_contents(self::$key_file);
    }

    public static function encryptFile($source_path, $dest_path) {
        $key = self::getKey();
        $iv_length = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $data = file_get_contents($source_path);
        if ($data === false) return false;

        $encrypted = openssl_encrypt($data, self::$cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) return false;

        return file_put_contents($dest_path, $iv . $encrypted) !== false;
    }

    public static function decryptFile($source_path, $original_filename) {
        $key = self::getKey();
        $iv_length = openssl_cipher_iv_length(self::$cipher);

        if (!file_exists($source_path)) return false;

        $content = file_get_contents($source_path);
        if ($content === false) return false;

        $iv = substr($content, 0, $iv_length);
        $encrypted = substr($content, $iv_length);

        $decrypted = openssl_decrypt($encrypted, self::$cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) return false;

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($original_filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($decrypted));

        echo $decrypted;
        return true;
    }

    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else if (is_string($data)) {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    public static function log($type, $user_id, $object, $data = []) {
        require_once __DIR__ . '/Storage.php';
        $log_entry = [
            'id' => uniqid(),
            'type' => $type,
            'user_id' => $user_id,
            'object' => $object,
            'date' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'data' => $data
        ];
        Storage::insert('logs', $log_entry);
    }
}
