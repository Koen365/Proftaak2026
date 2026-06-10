<?php
/**
 * API: Login (Unity / external clients)
 * POST {"email":"…","password":"…"}
 * Returns {"success":true,"token":"<user_id>","user":{…}}
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'POST only']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Email and password required']);
    exit;
}

$db   = (new \Config\Database())->getConnection();
if (!$db) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB error']); exit; }

$user = new \Classes\User($db);
$user->email = $email;

if ($user->readByEmail() && verifyPassword($password, $user->password)) {
    echo json_encode([
        'success' => true,
        'token'   => $user->id,           // simple user-id token (upgrade to JWT later)
        'user'    => [
            'id'         => $user->id,
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $user->role,
            'created_at' => $user->created_at,
        ],
    ]);
} else {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
}
