<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Requisição inválida. Tente novamente.';
    } else {
        $result = login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['ok']) {
            if (isset($_COOKIE['guest_theme'])) {
                updateTheme((int) $_SESSION['user_id'], $_COOKIE['guest_theme']);
            }
            header('Location: dashboard.php');
            exit;
        }
        $error = $result['error'];
    }
}

$csrf      = generateCsrfToken();
$guestDark = (($_COOKIE['guest_theme'] ?? '') === 'dark');
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $guestDark ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body class="has-header">

<?php require_once 'templates/_header_guest.php'; ?>

<?php if (!empty($_GET['timeout'])): ?>
<div class="toast-container">
    <div class="toast toast-error">
        <span>&#9201; Sessão encerrada por inatividade.</span>
        <button class="toast-close" aria-label="Fechar">✕</button>
    </div>
</div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div class="toast-container">
    <div class="toast toast-error">
        <span><?= htmlspecialchars($error) ?></span>
        <button class="toast-close" aria-label="Fechar">✕</button>
    </div>
</div>
<?php endif; ?>

<main class="main-content">
    <div class="card">
        <h1>Entrar</h1>

        <form method="POST" action="index.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            >

            <label for="password">Senha</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">Entrar</button>
        </form>

        <p class="link">Não tem conta? <a href="register.php">Cadastre-se</a></p>
    </div>
</main>
<script src="assets/js/toast.js"></script>
</body>
</html>
