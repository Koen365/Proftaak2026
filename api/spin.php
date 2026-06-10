<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$type  = ($input['type'] ?? 'standard') === 'mega' ? 'mega' : 'standard';

$db      = (new \Config\Database())->getConnection();
$manager = new \Classes\UnlockManager($db);
$result  = $manager->spin(getCurrentUserId(), $type);

echo json_encode($result);
