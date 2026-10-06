<?php
// api/controllers/UploadController.php

require_once __DIR__ . '/../helpers.php';

class UploadController {
    public function index() {
        require_admin();

        if (empty($_FILES['image'])) {
            json_out(['error' => 'Файл изображения не передан'], 400);
        }

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            json_out(['error' => 'Ошибка при загрузке файла'], 400);
        }

        // Limit size to 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            json_out(['error' => 'Превышен максимальный размер файла (5МБ)'], 400);
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mimeType, $allowedMimes)) {
            json_out(['error' => 'Недопустимый формат файла. Разрешены: JPG, PNG, WEBP, GIF'], 400);
        }

        // Validate image dimensions / integrity using getimagesize
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            json_out(['error' => 'Загруженный файл не является валидным изображением'], 400);
        }

        $extMap = [
            'image/jpeg' => '.jpg',
            'image/jpg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            'image/gif' => '.gif'
        ];
        $extension = $extMap[$mimeType] ?? '.jpg';

        $safeFileName = 'img_' . uniqid() . '_' . time() . $extension;
        $targetPath = UPLOADS_DIR . '/' . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            json_out(['error' => 'Не удалось сохранить файл на сервере'], 500);
        }

        $urlPath = '/uploads/' . $safeFileName;

        json_out([
            'success' => true,
            'url' => $urlPath,
            'filename' => $safeFileName
        ], 201);
    }
}
