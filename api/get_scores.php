<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

$db = (new \Config\Database())->getConnection();
if (!$db) {
    echo json_encode(['success'=>false,'data'=>[],'message'=>'DB connection failed']);
    exit;
}

$type   = in_array($_GET['type']??'', ['score','waves','time']) ? $_GET['type'] : 'score';
$limit  = min((int)($_GET['limit'] ?? 50), 200);
$map_id = isset($_GET['map_id']) && $_GET['map_id'] !== '' ? (int)$_GET['map_id'] : null;

$where = $map_id ? "WHERE s.map_id = $map_id" : '';
$order = match($type) {
    'waves' => "s.waves_survived DESC, s.score DESC",
    'time'  => "s.time_survived ASC, s.score DESC",
    default => "s.score DESC, s.waves_survived DESC",
};

try {
    $sql = "SELECT s.id, s.user_id, s.score, s.waves_survived, s.time_survived, s.created_at,
                   u.username, m.name as map_name
            FROM scores s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN maps  m ON s.map_id  = m.id
            $where
            ORDER BY $order
            LIMIT $limit";
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'data'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'data'=>[],'message'=>$e->getMessage()]);
}
