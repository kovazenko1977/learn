<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Storage.php';

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sanitizeInput(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generatePageCode(int $length = 8): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return $code;
}

function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script);
    if ($dir === '/' || $dir === '\\') {
        $dir = '';
    }
    return rtrim($protocol . $host . $dir, '/');
}

function normalizePhone(string $phone): string {
    // Remove non-digit characters except leading plus
    $clean = preg_replace('/[^\d+]/', '', $phone);
    return $clean;
}

function uploadImage(array $file, string $uploadDir = '../uploads/'): ?string {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $targetDir = __DIR__ . '/' . $uploadDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed)) {
        return null;
    }

    $filename = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return 'uploads/' . $filename;
    }

    return null;
}

function uploadMultipleImages(array $filesArray, int $maxCount = 10, string $uploadDir = '../uploads/'): array {
    $uploadedPaths = [];
    if (empty($filesArray['name']) || !is_array($filesArray['name'])) {
        return $uploadedPaths;
    }

    $count = min(count($filesArray['name']), $maxCount);
    for ($i = 0; $i < $count; $i++) {
        if ($filesArray['error'][$i] === UPLOAD_ERR_OK && !empty($filesArray['tmp_name'][$i])) {
            $file = [
                'name' => $filesArray['name'][$i],
                'tmp_name' => $filesArray['tmp_name'][$i],
                'error' => $filesArray['error'][$i]
            ];
            $path = uploadImage($file, $uploadDir);
            if ($path) {
                $uploadedPaths[] = $path;
            }
        }
    }

    return $uploadedPaths;
}

function extractYear(?string $dateStr): ?int {
    if (!$dateStr) return null;
    if (preg_match('/(\d{4})/', $dateStr, $matches)) {
        return (int)$matches[1];
    }
    return null;
}
