<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$theme = ($input['theme'] ?? '') === 'dark' ? 'dark' : 'light';
updateTheme((int) $_SESSION['user_id'], $theme);

echo json_encode(['ok' => true, 'theme' => $theme]);
