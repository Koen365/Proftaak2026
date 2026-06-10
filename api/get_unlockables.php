<?php
/**
 * API: Get user's unlockables (owned items)
 * Auth: session or X-User-Id header
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

$db = (new \Config\Database())->getConnection();
if (!$db) { http_response_code(500); echo json_encode(['success'=>false]); exit; }

$userId = null;
if (isLoggedIn()) {
    $userId = getCurrentUserId();
} else {
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
    $xid = $headers['X-User-Id'] ?? ($_SERVER['HTTP_X_USER_ID'] ?? null);
    if ($xid) $userId = (int)$xid;
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.type, u.rarity, u.description, uu.unlocked_at
        FROM unlockables u
        JOIN user_unlockables uu ON u.id = uu.unlockable_id
        WHERE uu.user_id = ?
        ORDER BY u.type, u.rarity
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also include default-unlocked items the user may not have a row for
    $defaults = $db->query("SELECT id, name, type, rarity, description FROM unlockables WHERE unlocked_by_default=1")->fetchAll(PDO::FETCH_ASSOC);
    $ownedIds = array_column($items, 'id');
    foreach ($defaults as $d) {
        if (!in_array($d['id'], $ownedIds)) {
            $d['unlocked_at'] = null;
            $items[] = $d;
        }
    }

    echo json_encode(['success'=>true, 'data'=>$items]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Query failed']);
}
