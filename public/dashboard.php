<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$themeClass = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body class="has-header">
<?php require_once 'templates/_header.php'; ?>
<main class="main-content">
    <div class="card">
        <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
        <p>Você está autenticado com sucesso.</p>
    </div>
</main>
</body>
</html>
