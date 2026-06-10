<?php
require_once dirname(__DIR__) . '/autoload.php';

function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('td_session');
        session_start();
    }
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function setFlash($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = ['message' => $_SESSION['flash_message'], 'type' => $_SESSION['flash_type'] ?? 'info'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $flash;
    }
    return null;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function sanitizeOutput($input) {
    return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateUsername($username) {
    return (bool)preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function formatTimestamp($ts) {
    if (!$ts) return '';
    try {
        $dt = new DateTime($ts);
        return $dt->format('d M Y, H:i');
    } catch (Exception $e) {
        return $ts;
    }
}

function formatNumber($number) {
    return number_format((int)$number);
}

function getRarityClass($rarity) {
    $map = ['common'=>'rarity-common','uncommon'=>'rarity-uncommon','rare'=>'rarity-rare','epic'=>'rarity-epic','legendary'=>'rarity-legendary'];
    return $map[$rarity] ?? '';
}

function getRarityDisplay($rarity) {
    return ucfirst((string)$rarity);
}

function logActivity($user_id, $action, $details = '') {
    error_log("[TD] user=$user_id action=$action details=$details");
}
