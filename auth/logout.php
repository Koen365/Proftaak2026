<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit();
