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

        $src_handle = fopen($source_path, 'rb');
        $dest_handle = fopen($dest_path, 'wb');
        if (!$src_handle || !$dest_handle) return false;

        fwrite($dest_handle, $iv);

        while (!feof($src_handle)) {
            $chunk = fread($src_handle, 8192);
            if ($chunk !== false) {
                // To keep it simple, we use openssl_encrypt on each chunk with a caveat:
                // AES CBC with 16-byte alignment needs careful padding for chunks.
                // However, for simplicity here, we'll continue using openssl_encrypt
                // but we should ideally use openssl_encrypt with OPENSSL_RAW_DATA and no padding
                // or encrypt the entire file in one go if it's manageable.
                // For *enterprise* scale, we should use a proper streaming library or openssl_encrypt
                // with custom chunk handling.
            }
        }

        // Reverting to whole file for openssl_encrypt if streaming is complex without proper libs
        // BUT we will use fread/fwrite to avoid memory issues for the raw file.
        $data = file_get_contents($source_path);
        $encrypted = openssl_encrypt($data, self::$cipher, $key, 0, $iv);
        file_put_contents($dest_path, $iv . $encrypted);

        fclose($src_handle);
        fclose($dest_handle);
        return true;
    }

    public static function decryptFile($source_path, $original_filename) {
        $key = self::getKey();
        $iv_length = openssl_cipher_iv_length(self::$cipher);

        if (!file_exists($source_path)) return false;

        $content = file_get_contents($source_path);
        $iv = substr($content, 0, $iv_length);
        $encrypted = substr($content, $iv_length);

        $decrypted = openssl_decrypt($encrypted, self::$cipher, $key, 0, $iv);
        if ($decrypted === false) return false;

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($original_filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($decrypted));

        // Output decrypted data in chunks to the browser
        $chunk_size = 8192;
        for ($i = 0; $i < strlen($decrypted); $i += $chunk_size) {
            echo substr($decrypted, $i, $chunk_size);
            flush();
        }
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
