<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Requisição inválida. Tente novamente.';
    } else {
        $result = register(
            $_POST['username'] ?? '',
            $_POST['email']    ?? '',
            $_POST['password'] ?? '',
            $_POST['confirm']  ?? ''
        );
        if ($result['ok']) {
            $success = 'Cadastro realizado com sucesso!';
        } else {
            $error = $result['error'];
        }
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
    <title>Cadastro</title>
    <link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body class="has-header">

<?php require_once 'templates/_header_guest.php'; ?>

<?php if ($error !== ''): ?>
<div class="toast-container">
    <div class="toast toast-error">
        <span><?= htmlspecialchars($error) ?></span>
        <button class="toast-close" aria-label="Fechar">✕</button>
    </div>
</div>
<?php endif; ?>

<?php if ($success !== ''): ?>
<div class="toast-container">
    <div class="toast toast-success">
        <span><?= htmlspecialchars($success) ?> — <a href="index.php">Faça login</a></span>
        <button class="toast-close" aria-label="Fechar">✕</button>
    </div>
</div>
<?php endif; ?>

<div id="loading-overlay" class="loading-overlay">
    <div class="loading-box">
        <div class="spinner"></div>
        <p>Criando sua conta...</p>
    </div>
</div>

<main class="main-content">
    <div class="card">
        <h1>Criar conta</h1>

        <?php if ($success !== ''): ?>
            <p class="link" style="text-align:center; margin-top:.5rem">Já tem conta? <a href="index.php">Entrar</a></p>
        <?php else: ?>

        <form id="register-form" method="POST" action="register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <label for="username">Nome de usuário</label>
            <input type="text" id="username" name="username" required
                   minlength="3" maxlength="50" autocomplete="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            <small>3–50 caracteres. Apenas letras, números e _. Sem espaços.</small>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required
                   autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required
                   autocomplete="new-password">

            <div class="password-rules">
                <div class="rule" id="rule-length">
                    <span class="rule-icon">✗</span><span>Mínimo 8 caracteres</span>
                </div>
                <div class="rule" id="rule-upper">
                    <span class="rule-icon">✗</span><span>Uma letra maiúscula</span>
                </div>
                <div class="rule" id="rule-symbol">
                    <span class="rule-icon">✗</span><span>Um símbolo (@, #, !, _…)</span>
                </div>
                <div class="rule" id="rule-nospace">
                    <span class="rule-icon">✗</span><span>Sem espaços</span>
                </div>
            </div>

            <label for="confirm">Confirmar senha</label>
            <input type="password" id="confirm" name="confirm" required
                   autocomplete="new-password">

            <div class="rule" id="rule-match" style="display:none">
                <span class="rule-icon">✗</span><span id="match-text">Senhas não coincidem</span>
            </div>

            <button type="submit" id="submit-btn">Cadastrar</button>
        </form>

        <p class="link">Já tem conta? <a href="index.php">Entrar</a></p>

        <?php endif; ?>
    </div>
</main>

<script src="assets/js/toast.js"></script>
<script src="assets/js/register.js"></script>
</body>
</html>
