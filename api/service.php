<?php
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireAuth();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'delete_all') {
    $news_file = __DIR__ . '/../data/news.json';
    file_put_contents($news_file, json_encode([]), LOCK_EX);
    echo json_encode(['success' => true]);
} elseif ($action === 'cleanup') {
    $news_file = __DIR__ . '/../data/news.json';
    $news = [];
    if (file_exists($news_file)) {
        $news = json_decode(file_get_contents($news_file), true);
    }

    // Find all images used in news (including cover images and images within content)
    $used_images = [];
    foreach ($news as $item) {
        if (!empty($item['image'])) {
            $used_images[] = basename($item['image']);
        }

        // Match images in content (Rich text images)
        if (!empty($item['content'])) {
            preg_match_all('/uploads\/(img_[^"]+)/i', $item['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $used_images[] = $match;
                }
            }
        }
    }

    $upload_dir = __DIR__ . '/../uploads/';
    $files = glob($upload_dir . '*');
    $deleted_count = 0;

    foreach ($files as $file) {
        $filename = basename($file);
        if ($filename !== '.htaccess' && $filename !== 'no-image.png' && !in_array($filename, $used_images)) {
            if (unlink($file)) {
                $deleted_count++;
            }
        }
    }

    echo json_encode(['success' => true, 'deleted_count' => $deleted_count]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
