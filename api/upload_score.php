<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

$db = (new \Config\Database())->getConnection();
if (!$db) { echo json_encode(['success'=>false,'message'=>'DB error']); exit; }

// Auth: either session, or X-User-Id header (from minigame AJAX)
$userId = null;
if (isLoggedIn()) {
    $userId = getCurrentUserId();
} else {
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
    $xuid = $headers['X-User-Id'] ?? ($_SERVER['HTTP_X_USER_ID'] ?? null);
    if ($xuid) $userId = (int)$xuid;
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$map_id        = (int)($input['map_id']        ?? 1);
$score         = (int)($input['score']         ?? 0);
$waves         = (int)($input['waves_survived'] ?? 0);
$time_survived = (int)($input['time_survived']  ?? 0);

if ($score < 0 || $map_id <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO scores (user_id, map_id, score, waves_survived, time_survived) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $map_id, $score, $waves, $time_survived]);
    $scoreId = $db->lastInsertId();

    // Check for game-based unlocks and award coins
    $manager = new \Classes\UnlockManager($db);
    $unlockResult = $manager->checkGameUnlocks($userId, $score, $waves);

    echo json_encode([
        'success'      => true,
        'message'      => 'Score saved',
        'score_id'     => $scoreId,
        'coins_earned' => $unlockResult['coins_earned'],
        'new_unlocks'  => array_map(function($u) {
            return ['name' => $u['name'], 'type' => $u['type'], 'rarity' => $u['rarity']];
        }, $unlockResult['new_unlocks']),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Failed to save score']);
}
