<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// PRG — pega flash da sessão
$toast = $_SESSION['flash'] ?? ['type' => '', 'msg' => ''];
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Requisição inválida.'];
        header('Location: profile.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) $_SESSION['user_id'];

    if ($action === 'password') {
        $r = updatePassword($id, $_POST['current'] ?? '', $_POST['new'] ?? '', $_POST['confirm'] ?? '');
        $_SESSION['flash'] = $r['ok']
            ? ['type' => 'success', 'msg' => 'Senha alterada com sucesso.']
            : ['type' => 'error',   'msg' => $r['error']];
        header('Location: profile.php#password');
        exit;
    }

    if ($action === 'email') {
        $r = updateEmail($id, $_POST['email'] ?? '', $_POST['password'] ?? '');
        $_SESSION['flash'] = $r['ok']
            ? ['type' => 'success', 'msg' => 'E-mail alterado com sucesso.']
            : ['type' => 'error',   'msg' => $r['error']];
        header('Location: profile.php#email');
        exit;
    }

    if ($action === 'avatar') {
        $r = updateAvatar($id, $_FILES['avatar'] ?? []);
        $_SESSION['flash'] = $r['ok']
            ? ['type' => 'success', 'msg' => 'Foto atualizada com sucesso.']
            : ['type' => 'error',   'msg' => $r['error']];
        header('Location: profile.php#photo');
        exit;
    }
}

$csrf       = generateCsrfToken();
$initial    = strtoupper(substr($_SESSION['username'], 0, 1));
$avatar     = $_SESSION['avatar'] ?? null;
$avatarUrl  = $avatar ? 'uploads/avatars/' . htmlspecialchars($avatar) : null;
$themeClass = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body class="has-header">

<?php if ($toast['msg'] !== ''): ?>
<div class="toast-container">
    <div class="toast toast-<?= $toast['type'] ?>">
        <span><?= htmlspecialchars($toast['msg']) ?></span>
        <button class="toast-close" aria-label="Fechar">✕</button>
    </div>
</div>
<?php endif; ?>

<?php require_once 'templates/_header.php'; ?>

<main class="main-content">
<div class="profile-wrapper">

    <!-- Card: identidade -->
    <div class="card profile-card">
        <div class="avatar avatar-xl" id="photo">
            <img src="<?= $avatarUrl ?? 'assets/img/user-svgrepo-com.png' ?>" alt=""
                 <?= $avatarUrl ? '' : 'class="avatar-default-icon"' ?>>
        </div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($_SESSION['username']) ?></h2>
            <span><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
            <?php if (isAdmin()): ?>
                <span class="header-badge" style="margin-top:.25rem">Admin</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card: Mudar foto -->
    <div class="card" id="photo-section">
        <h3 class="section-title">&#128247; Mudar foto de perfil</h3>
        <form id="avatar-form" method="POST" action="profile.php" enctype="multipart/form-data" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action"     value="avatar">

            <label class="avatar-drop" id="avatar-drop" for="avatar-input">
                <div class="avatar avatar-lg preview-avatar" id="preview-avatar">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= $avatarUrl ?>" id="preview-img" alt="">
                    <?php else: ?>
                        <img src="assets/img/user-svgrepo-com.png" id="preview-img" alt="" class="avatar-default-icon">
                    <?php endif; ?>
                </div>
                <div class="avatar-drop-text">
                    <strong>Clique ou arraste uma imagem</strong>
                    <span>JPEG, PNG, WebP ou GIF — máx. 2MB</span>
                </div>
                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none">
            </label>

            <button type="submit" class="btn-submit">Salvar foto</button>
        </form>
    </div>

    <!-- Card: Trocar senha -->
    <div class="card" id="password">
        <h3 class="section-title">&#128274; Trocar senha</h3>
        <form method="POST" action="profile.php" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action"     value="password">

            <label for="current-pass">Senha atual</label>
            <input type="password" id="current-pass" name="current" required autocomplete="current-password">

            <label for="new-pass">Nova senha</label>
            <input type="password" id="new-pass" name="new" required autocomplete="new-password">
            <small>Mín. 8 caracteres, uma letra maiúscula e um símbolo. Sem espaços.</small>

            <label for="confirm-pass">Confirmar nova senha</label>
            <input type="password" id="confirm-pass" name="confirm" required autocomplete="new-password">

            <button type="submit" class="btn-submit">Alterar senha</button>
        </form>
    </div>

    <!-- Card: Alterar e-mail -->
    <div class="card" id="email">
        <h3 class="section-title">&#9993; Alterar e-mail</h3>
        <form method="POST" action="profile.php" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action"     value="email">

            <label for="new-email">Novo e-mail</label>
            <input type="email" id="new-email" name="email" required autocomplete="email"
                   value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">

            <label for="email-password">Senha atual (para confirmar)</label>
            <input type="password" id="email-password" name="password" required autocomplete="current-password">

            <button type="submit" class="btn-submit">Salvar e-mail</button>
        </form>
    </div>

</div>
</main>

<script src="assets/js/toast.js"></script>
<script src="assets/js/profile.js"></script>
</body>
</html>
