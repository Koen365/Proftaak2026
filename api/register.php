<?php
/**
 * API: Register (Unity / external clients)
 * POST {"username":"…","email":"…","password":"…"}
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
$username = trim($input['username'] ?? '');
$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'All fields required']);
    exit;
}
if (!validateUsername($username)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid username (3-20 chars, letters/numbers/underscore)']);
    exit;
}
if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid email']);
    exit;
}
if (!validatePassword($password)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Password must be at least 8 characters']);
    exit;
}

$db = (new \Config\Database())->getConnection();
if (!$db) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB error']); exit; }

// Duplicate check
$chk = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
$chk->execute([$username, $email]);
if ($chk->fetch()) {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Username or email already taken']);
    exit;
}

$user = new \Classes\User($db);
$user->username = $username;
$user->email    = $email;
$user->password = hashPassword($password);
$user->role     = 'user';

if ($user->create()) {
    $newId = $db->lastInsertId();
    echo json_encode([
        'success'  => true,
        'message'  => 'Registration successful',
        'token'    => $newId,
        'user'     => ['id'=>$newId,'username'=>$username,'email'=>$email,'role'=>'user'],
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Registration failed']);
}
