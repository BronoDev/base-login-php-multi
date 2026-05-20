<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$initial     = strtoupper(substr($_SESSION['username'], 0, 1));
$avatarFile  = $_SESSION['avatar'] ?? null;
$avatarUrl   = $avatarFile ? 'uploads/avatars/' . htmlspecialchars($avatarFile) : null;
$isDark      = ($_SESSION['theme'] ?? 'light') === 'dark';
?>
<header class="site-header">
    <div class="header-inner">
        <a href="dashboard.php" class="header-logo">
            <span class="header-logo-icon">⬡</span>
            Sistema
        </a>

        <nav class="header-nav">
            <?php if (isAdmin()): ?>
            <a href="admin.php" class="header-link <?= $currentPage === 'admin.php' ? 'header-link-active' : '' ?>">
                Gerenciar Usuários
            </a>
            <?php endif; ?>

            <div class="header-divider"></div>

            <!-- Botão de tema -->
            <button class="theme-toggle"
                    id="theme-toggle"
                    aria-label="Alternar tema"
                    data-theme="<?= $isDark ? 'dark' : 'light' ?>"
                    data-csrf="<?= htmlspecialchars(generateCsrfToken()) ?>">
                <span id="theme-icon"><?= $isDark ? '☀️' : '🌙' ?></span>
            </button>

            <div class="header-divider"></div>

            <!-- Menu do usuário -->
            <div class="user-menu" id="user-menu">
                <button class="user-menu-trigger" id="user-menu-trigger" aria-expanded="false" aria-haspopup="true">
                    <div class="avatar avatar-sm">
                        <img src="<?= $avatarUrl ?? 'assets/img/user-svgrepo-com.png' ?>" alt=""
                             <?= $avatarUrl ? '' : 'class="avatar-default-icon"' ?>>
                    </div>
                    <span class="header-username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php if (isAdmin()): ?>
                        <span class="header-badge">Admin</span>
                    <?php endif; ?>
                    <span class="dropdown-arrow" id="dropdown-arrow">&#9660;</span>
                </button>

                <div class="user-dropdown" id="user-dropdown" aria-hidden="true">
                    <div class="dropdown-profile">
                        <div class="avatar avatar-md">
                            <img src="<?= $avatarUrl ?? 'assets/img/user-svgrepo-com.png' ?>" alt=""
                                 <?= $avatarUrl ? '' : 'class="avatar-default-icon"' ?>>
                        </div>
                        <div class="dropdown-profile-info">
                            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                            <span><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <a href="profile.php#photo"    class="dropdown-item">&#128247; Mudar foto</a>
                    <a href="profile.php#password" class="dropdown-item">&#128274; Trocar senha</a>
                    <a href="profile.php#email"    class="dropdown-item">&#9993; Alterar e-mail</a>

                    <div class="dropdown-divider"></div>

                    <a href="logout.php" class="dropdown-item dropdown-item-danger">&#8594; Sair</a>
                </div>
            </div>
        </nav>
    </div>
</header>
<script src="assets/js/header.js"  defer></script>
<script src="assets/js/timeout.js" defer></script>
