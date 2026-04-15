<?php
class Security {
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            if (is_string($data)) {
                $data = trim($data);
                $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            }
        }
        return $data;
    }

    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }
}
