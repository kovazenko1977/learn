<?php
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireAuth();

header('Content-Type: application/json');

$news_file = __DIR__ . '/../data/news.json';

// Initialize news file if it doesn't exist
if (!file_exists($news_file)) {
    file_put_contents($news_file, json_encode([]));
}

function getNews() {
    global $news_file;
    return json_decode(file_get_contents($news_file), true);
}

function saveNews($news) {
    global $news_file;
    file_put_contents($news_file, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $news = getNews();

    // Sort news by date descending
    usort($news, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode($news);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $news = getNews();

    $new_item = [
        'id' => uniqid(),
        'title' => $data['title'] ?? '',
        'content' => $data['content'] ?? '',
        'image' => $data['image'] ?? '',
        'date' => $data['date'] ?? date('Y-m-d H:i:s'),
        'status' => $data['status'] ?? 'published'
    ];

    $news[] = $new_item;
    saveNews($news);
    echo json_encode($new_item);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $news = getNews();
    $updated = false;

    foreach ($news as &$item) {
        if ($item['id'] === $data['id']) {
            $item['title'] = $data['title'] ?? $item['title'];
            $item['content'] = $data['content'] ?? $item['content'];
            $item['image'] = $data['image'] ?? $item['image'];
            $item['date'] = $data['date'] ?? $item['date'];
            $item['status'] = $data['status'] ?? $item['status'];
            $updated = true;
            echo json_encode($item);
            break;
        }
    }

    if ($updated) {
        saveNews($news);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'News item not found']);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $news = getNews();
    $new_news = array_filter($news, function($item) use ($id) {
        return $item['id'] !== $id;
    });

    if (count($new_news) < count($news)) {
        saveNews(array_values($new_news));
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'News item not found']);
    }
} else {
    http_response_code(405);
}
