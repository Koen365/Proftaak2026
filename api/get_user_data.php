<?php
/**
 * API: Get current user data
 * Auth: session (browser) or X-User-Id header (Unity)
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

$db = (new \Config\Database())->getConnection();
if (!$db) { http_response_code(500); echo json_encode(['success'=>false]); exit; }

// Auth
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

$user = new \Classes\User($db);
$user->id = $userId;
if (!$user->readById()) {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'User not found']);
    exit;
}

// Also pull coins + totals
$extras = $db->prepare("SELECT coins, total_score, total_waves FROM users WHERE id=?");
$extras->execute([$userId]);
$ext = $extras->fetch(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
    'success' => true,
    'data'    => [
        'id'          => $user->id,
        'username'    => $user->username,
        'email'       => $user->email,
        'role'        => $user->role,
        'created_at'  => $user->created_at,
        'coins'       => (int)($ext['coins']       ?? 0),
        'total_score' => (int)($ext['total_score'] ?? 0),
        'total_waves' => (int)($ext['total_waves'] ?? 0),
    ],
]);
