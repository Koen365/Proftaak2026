<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$userId = (int)($input['user_id'] ?? 0);
$waves  = (int)($input['waves'] ?? 0);
$score  = (int)($input['score'] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user id'
    ]);
    exit;
}

$db = (new \Config\Database())->getConnection();

if (!$db) {
    echo json_encode([
        'success' => false,
        'message' => 'DB connection failed'
    ]);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO scores (user_id, waves_survived, score, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->execute([$userId, $waves, $score]);

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}