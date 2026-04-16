<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $leads = Storage::read('leads');
    echo json_encode($leads);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $data = Security::sanitize($data);
    $leads = Storage::read('leads');
    $action = $_GET['action'] ?? '';

    if ($action === 'convert') {
        $id = $data['id'] ?? '';
        $foundLead = null;
        $leads = array_filter($leads, function($l) use ($id, &$foundLead) {
            if ($l['id'] === $id) {
                $foundLead = $l;
                return false;
            }
            return true;
        });

        if ($foundLead) {
            $clients = Storage::read('clients');
            $newClient = [
                'id' => uniqid(),
                'name' => $foundLead['title'],
                'email' => $foundLead['email'] ?? '',
                'phone' => $foundLead['phone'] ?? '',
                'status' => 'active',
                'source' => $foundLead['source'] ?? 'lead_conversion',
                'tags' => $foundLead['tags'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $clients[] = $newClient;
            Storage::save('clients', $clients);
            Storage::log("Converted lead to client: " . $foundLead['title'], $currentUser['id']);
            Storage::addPoints($currentUser['id'], 50);
            Storage::save('leads', array_values($leads));
            echo json_encode(['success' => true, 'client_id' => $newClient['id']]);
            exit;
        }
    }

    if (isset($data['id'])) {
        foreach ($leads as &$lead) {
            if ($lead['id'] === $data['id']) {
                $oldStatus = $lead['status'];
                $lead = array_merge($lead, $data);
                if ($oldStatus !== $lead['status']) {
                    $lead['status_updated_at'] = date('Y-m-d H:i:s');
                    if ($lead['status'] === 'closed') {
                        Storage::addPoints($currentUser['id'], 20);
                        Storage::log("Closed deal: " . $lead['title'], $currentUser['id']);
                    }
                }
                break;
            }
        }
    } else {
        $data['id'] = uniqid('lead_');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status_updated_at'] = date('Y-m-d H:i:s');
        $leads[] = $data;
        Storage::log("Created lead: " . $data['title'], $currentUser['id']);
        Storage::addPoints($currentUser['id'], 10);
    }

    Storage::save('leads', $leads);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $leads = Storage::read('leads');
    $leads = array_filter($leads, function($l) use ($id) {
        return $l['id'] !== $id;
    });
    Storage::save('leads', array_values($leads));
    echo json_encode(['success' => true]);
}
