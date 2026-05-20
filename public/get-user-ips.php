<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

$id = (int) ($_POST['user_id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

echo json_encode(['ok' => true, 'ips' => adminGetUserIps($id)]);
