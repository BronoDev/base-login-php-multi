(function () {
    var TIMEOUT    = 300000; // 5 minutos em ms
    var WARN_BEFORE = 30000; // avisa 30s antes
    var lastActive = Date.now();
    var warned     = false;
    var modal      = null;
    var countEl    = null;

    function createModal() {
        var el = document.createElement('div');
        el.id        = 'timeout-modal';
        el.className = 'modal-backdrop';
        el.innerHTML =
            '<div class="modal modal-sm">' +
            '  <div class="modal-header"><h2>&#9201; Sessão expirando</h2></div>' +
            '  <p class="confirm-msg" id="timeout-msg">Você será desconectado em ' +
            '  <strong id="timeout-count">30</strong> segundos por inatividade.</p>' +
            '  <div class="modal-footer">' +
            '    <button id="timeout-stay" class="btn-primary">Continuar conectado</button>' +
            '    <a href="logout.php" class="btn-secondary">Sair agora</a>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        document.getElementById('timeout-stay').addEventListener('click', function () {
            ping(function (ok) {
                if (ok) { resetTimer(); hideModal(); }
                else { expire(); }
            });
        });

        return el;
    }

    function showModal(secs) {
        if (!modal) {
            modal   = createModal();
            countEl = document.getElementById('timeout-count');
        }
        countEl.textContent = secs;
        modal.classList.add('active');
    }

    function hideModal() {
        if (modal) modal.classList.remove('active');
        warned = false;
    }

    function expire() {
        window.location.href = 'index.php?timeout=1';
    }

    function ping(cb) {
        fetch('ping.php', { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (d) { cb(d.ok === true); })
            .catch(function ()  { cb(false); });
    }

    function resetTimer() {
        lastActive = Date.now();
    }

    // Eventos de atividade do usuário
    ['mousemove', 'keydown', 'mousedown', 'scroll', 'touchstart'].forEach(function (ev) {
        document.addEventListener(ev, resetTimer, { passive: true });
    });

    // Loop principal a cada segundo
    setInterval(function () {
        var idle      = Date.now() - lastActive;
        var remaining = TIMEOUT - idle;

        if (remaining <= 0) {
            expire();
            return;
        }

        if (remaining <= WARN_BEFORE) {
            var secs = Math.ceil(remaining / 1000);
            if (!warned) { warned = true; showModal(secs); }
            else if (countEl) { countEl.textContent = secs; }
        } else if (warned) {
            hideModal();
        }
    }, 1000);
})();
