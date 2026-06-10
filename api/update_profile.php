<?php
/**
 * API: Update profile (username, email, password)
 * Auth: session only (called from profile.php form via fetch)
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'POST only']);
    exit;
}

// Accept both JSON and form-data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$email    = trim($input['email']    ?? '');
$password = trim($input['new_password']     ?? $input['password'] ?? '');
$confirm  = trim($input['confirm_password'] ?? '');

if (!validateUsername($username)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid username']);
    exit;
}
if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid email']);
    exit;
}
if ($password && $password !== $confirm) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Passwords do not match']);
    exit;
}
if ($password && !validatePassword($password)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Password must be at least 8 characters']);
    exit;
}

$db = (new \Config\Database())->getConnection();
$uid = getCurrentUserId();

// Check duplicate username/email from OTHER users
$chk = $db->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id != ?");
$chk->execute([$username, $email, $uid]);
if ($chk->fetch()) {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Username or email already taken by another account']);
    exit;
}

$user = new \Classes\User($db);
$user->id       = $uid;
$user->username = $username;
$user->email    = $email;
$user->role     = getCurrentUserRole();

if (!$user->update()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Update failed']);
    exit;
}

if ($password) {
    $user->password = hashPassword($password);
    $user->updatePassword();
}

// Update session
$_SESSION['username'] = $username;

echo json_encode(['success'=>true,'message'=>'Profile updated successfully']);
