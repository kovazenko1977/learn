<?php
/**
 * File & Photo Upload Manager for МедСервис
 */

class UploadHandler {
    private string $uploadDir;
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    private array $allowedMimetypes = ['image/jpeg', 'image/png', 'image/webp'];
    private int $maxSizeBytes = 10485760; // 10 MB

    public function __construct(?string $uploadDir = null) {
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $htaccess = $this->uploadDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\.(php|php5|phtml|phar)\">\nRequire all denied\n</FilesMatch>\n");
        }
    }

    public function handleUpload(array $file): string {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Файл не был передан');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка при загрузке файла, код: ' . $file['error']);
        }

        if ($file['size'] > $this->maxSizeBytes) {
            throw new Exception('Превышен максимальный размер файла (10 МБ)');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions)) {
            throw new Exception('Неразрешенный тип файла. Допустимы: JPG, PNG, WEBP');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMimetypes)) {
            throw new Exception('Недопустимый MIME-тип файла: ' . $mime);
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $targetPath = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Не удалось сохранить загруженный файл');
        }

        // Return relative web path
        return 'uploads/' . $filename;
    }
}
