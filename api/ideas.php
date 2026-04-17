<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$ideas = Storage::read('ideas.json');

switch ($method) {
    case 'GET':
        echo json_encode($ideas);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid data']);
            break;
        }

        if (isset($data['idea_id']) && isset($data['message'])) {
            // Add comment to existing idea
            foreach ($ideas as &$idea) {
                if ($idea['id'] === $data['idea_id']) {
                    $idea['comments'][] = [
                        'id' => uniqid(),
                        'text' => $data['message'],
                        'created_at' => date('c')
                    ];
                    Storage::write('ideas.json', $ideas);
                    echo json_encode($idea);
                    exit;
                }
            }
        } else {
            // Create new idea
            $newIdea = [
                'id' => uniqid(),
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'comments' => [],
                'created_at' => date('c')
            ];
            $ideas[] = $newIdea;
            Storage::write('ideas.json', $ideas);
            echo json_encode($newIdea);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        $newIdeas = array_filter($ideas, function($i) use ($id) {
            return $i['id'] !== $id;
        });
        Storage::write('ideas.json', array_values($newIdeas));
        echo json_encode(['success' => true]);
        break;

    default:
        header('HTTP/1.1 405 Method Not Allowed');
        break;
}
