<?php $guestDark = (($_COOKIE['guest_theme'] ?? '') === 'dark'); ?>
<header class="site-header">
    <div class="header-inner">
        <div class="header-logo">
            <span class="header-logo-icon">⬡</span>
            Sistema
        </div>

        <nav class="header-nav">
            <button class="theme-toggle" id="theme-toggle" aria-label="Alternar tema">
                <span id="theme-icon"><?= $guestDark ? '☀️' : '🌙' ?></span>
            </button>
        </nav>
    </div>
</header>
<script src="assets/js/guest-theme.js"></script>
