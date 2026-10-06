<?php
/**
 * UploadController
 */

class UploadController {
    public function handle($method) {
        if ($method === 'POST') {
            require_auth(true);
            $this->uploadFile();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function uploadFile() {
        $file = $_FILES['file'] ?? $_FILES['image'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            json_out(['error' => 'Ошибка при загрузке файла'], 400);
        }

        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $maxSize) {
            json_out(['error' => 'Превышен максимальный размер файла (5MB)'], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }
        }

        if (!$mime && function_exists('getimagesize')) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo && isset($imageInfo['mime'])) {
                $mime = $imageInfo['mime'];
            }
        }

        if (!$mime) {
            $mime = strtolower($file['type'] ?? '');
        }

        if (!in_array($mime, $allowedMimes)) {
            json_out(['error' => 'Недопустимый формат файла. Разрешены JPG, PNG, WEBP, GIF'], 400);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extension, $allowedExtensions)) {
            $mimeMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif'
            ];
            $extension = $mimeMap[$mime] ?? 'jpg';
        }

        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'img_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            json_out([
                'success' => true,
                'url' => 'uploads/' . $filename
            ]);
        } else {
            json_out(['error' => 'Не удалось сохранить файл на сервере'], 500);
        }
    }
}
