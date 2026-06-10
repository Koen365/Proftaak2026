<?php
/**
 * API: Get all map definitions (public, no auth needed)
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db = (new \Config\Database())->getConnection();
if (!$db) { http_response_code(500); echo json_encode(['success'=>false]); exit; }

try {
    $rows = $db->query("SELECT id, name, description, grid_width, grid_height FROM maps ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Query failed']);
}
