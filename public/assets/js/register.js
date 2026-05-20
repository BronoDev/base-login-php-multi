document.addEventListener('DOMContentLoaded', function () {
    var passInput = document.getElementById('password');
    var confInput = document.getElementById('confirm');
    var form      = document.getElementById('register-form');

    if (!passInput || !form) return;

    function setRule(id, ok) {
        var el   = document.getElementById(id);
        var icon = el.querySelector('.rule-icon');
        var was  = el.classList.contains('rule-ok');

        icon.textContent = ok ? '✓' : '✗';
        el.className = 'rule ' + (ok ? 'rule-ok' : 'rule-fail');

        if (ok && !was) {
            el.classList.remove('rule-animate');
            el.offsetWidth;
            el.classList.add('rule-animate');
        }
    }

    function checkPassword() {
        var val = passInput.value;
        setRule('rule-length',  val.length >= 8);
        setRule('rule-upper',   /[A-Z]/.test(val));
        setRule('rule-symbol',  /[^a-zA-Z0-9\s]/.test(val));
        setRule('rule-nospace', val.length > 0 && val.indexOf(' ') === -1);
    }

    function checkMatch() {
        var el   = document.getElementById('rule-match');
        var text = document.getElementById('match-text');
        var conf = confInput.value;

        if (conf === '') { el.style.display = 'none'; return; }

        var ok = (passInput.value === conf);
        el.style.display = 'flex';
        text.textContent = ok ? 'Senhas coincidem' : 'Senhas não coincidem';
        setRule('rule-match', ok);
    }

    passInput.addEventListener('input', function () {
        checkPassword();
        checkMatch();
    });

    confInput.addEventListener('input', checkMatch);

    form.addEventListener('submit', function (e) {
        var username = document.getElementById('username').value;
        var email    = document.getElementById('email').value;
        var password = passInput.value;

        if (/\s/.test(username)) {
            e.preventDefault();
            alert('O nome de usuário não pode conter espaços.');
            return;
        }
        if (/\s/.test(email)) {
            e.preventDefault();
            alert('O e-mail não pode conter espaços.');
            return;
        }
        if (/\s/.test(password)) {
            e.preventDefault();
            alert('A senha não pode conter espaços.');
            return;
        }

        document.getElementById('loading-overlay').classList.add('active');
        document.getElementById('submit-btn').disabled = true;
    });
});
