(function () {
    var btn  = document.getElementById('theme-toggle');
    var icon = document.getElementById('theme-icon');
    if (!btn || !icon) return;

    function setThemeCookie(theme) {
        document.cookie = 'guest_theme=' + theme + '; max-age=31536000; path=/; SameSite=Lax';
    }

    btn.addEventListener('click', function () {
        var isDark = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', isDark);
        var theme = isDark ? 'dark' : 'light';
        setThemeCookie(theme);
        icon.textContent = isDark ? '☀️' : '🌙';
    });
})();
