<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;

header('Content-Type: application/json');

$query = mb_strtolower($_GET['q'] ?? '');
if (mb_strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$requestStore = new JsonStore('data/requests.json');
$requests = $requestStore->read();

$serviceStore = new JsonStore('data/services.json');
$services = [];
foreach ($serviceStore->read() as $s) $services[$s['id']] = $s['name'];

$results = [];
foreach ($requests as $req) {
    // Check role access (simple check for search)
    $allowed = false;
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];

    if ($userRole === 'admin' || $userRole === 'manager') $allowed = true;
    elseif ($userRole === 'initiator' && $req['initiator_id'] === $userId) $allowed = true;
    elseif ($userRole === 'performer' && (($req['performer_id'] ?? 0) === $userId || ($req['status'] === 'new' && $req['service_id'] == ($_SESSION['user_service_id'] ?? 0)))) $allowed = true;
    elseif ($userRole === 'service_lead' && $req['service_id'] == ($_SESSION['user_service_id'] ?? 0)) $allowed = true;
    elseif ($userRole === 'controller' && in_array($req['status'], ['checking', 'completed'])) $allowed = true;

    if (!$allowed) continue;

    $match = false;
    if ((string)$req['id'] === $query) $match = true;
    if (mb_strpos(mb_strtolower($req['description']), $query) !== false) $match = true;

    if ($match) {
        $results[] = [
            'id' => $req['id'],
            'description' => $req['description'],
            'service' => $services[$req['service_id']] ?? 'Служба',
            'status' => $req['status']
        ];
    }

    if (count($results) >= 10) break;
}

echo json_encode($results);
