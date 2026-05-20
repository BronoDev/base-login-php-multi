<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $rows = getDB()
        ->query('SELECT id,
                        (last_activity IS NOT NULL
                         AND last_activity >= DATE_SUB(NOW(), INTERVAL 300 SECOND)) AS is_online
                 FROM users')
        ->fetchAll();

    $statuses = [];
    foreach ($rows as $r) {
        $statuses[(int) $r['id']] = (bool) $r['is_online'];
    }

    echo json_encode(['ok' => true, 'statuses' => $statuses]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false]);
}
