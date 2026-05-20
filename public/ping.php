<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'expired' => true]);
    exit;
}

$now = time();
$last = $_SESSION['last_activity'] ?? $now;

if ($now - $last > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['ok' => false, 'expired' => true]);
    exit;
}

$_SESSION['last_activity'] = $now;

try {
    getDB()->prepare('UPDATE users SET last_activity = NOW() WHERE id = ?')
           ->execute([$_SESSION['user_id']]);
} catch (PDOException $e) {}

echo json_encode(['ok' => true]);
