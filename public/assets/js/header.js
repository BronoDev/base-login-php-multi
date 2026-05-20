document.addEventListener('DOMContentLoaded', function () {

    // ------ Dropdown do usuário ------
    var trigger  = document.getElementById('user-menu-trigger');
    var dropdown = document.getElementById('user-dropdown');
    var arrow    = document.getElementById('dropdown-arrow');
    var menu     = document.getElementById('user-menu');

    if (trigger && dropdown) {
        function openMenu()  {
            dropdown.classList.add('open');
            arrow.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            dropdown.setAttribute('aria-hidden', 'false');
        }
        function closeMenu() {
            dropdown.classList.remove('open');
            arrow.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
            dropdown.setAttribute('aria-hidden', 'true');
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.contains('open') ? closeMenu() : openMenu();
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target)) closeMenu();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    }

    // ------ Toggle de tema ------
    var themeBtn  = document.getElementById('theme-toggle');
    var themeIcon = document.getElementById('theme-icon');

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var html     = document.documentElement;
            var current  = themeBtn.dataset.theme;
            var next     = current === 'dark' ? 'light' : 'dark';

            html.classList.toggle('dark', next === 'dark');
            themeBtn.dataset.theme   = next;
            themeIcon.textContent    = next === 'dark' ? '☀️' : '🌙';
            localStorage.setItem('theme', next);
            document.cookie = 'guest_theme=' + next + '; max-age=31536000; path=/; SameSite=Lax';

            fetch('set-theme.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    csrf_token: themeBtn.dataset.csrf,
                    theme:      next
                })
            });
        });
    }
});
