<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acesso negado.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token inválido.']);
    exit;
}

try {
    $r = updateAvatar((int) $_SESSION['user_id'], $_FILES['avatar'] ?? []);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro ao salvar no banco de dados. Verifique se a coluna avatar existe na tabela users.']);
    exit;
}

if ($r['ok']) {
    echo json_encode(['ok' => true, 'url' => 'uploads/avatars/' . $_SESSION['avatar']]);
} else {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $r['error']]);
}
