document.addEventListener('DOMContentLoaded', function () {

    // ------ Modal helpers ------
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Fechar ao clicar no backdrop
    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) closeModal(backdrop.id);
        });
    });

    document.getElementById('modal-edit-close').addEventListener('click', function () {
        closeModal('modal-edit');
    });

    document.getElementById('modal-create-close').addEventListener('click', function () {
        closeModal('modal-create');
    });

    document.getElementById('modal-ips-close').addEventListener('click', function () {
        closeModal('modal-ips');
    });

    // ------ Modal de confirmação ------
    var pendingForm = null;

    var confirmTitle   = document.getElementById('confirm-title');
    var confirmMessage = document.getElementById('confirm-message');
    var confirmOk      = document.getElementById('confirm-ok');
    var confirmCancel  = document.getElementById('confirm-cancel');

    function askConfirm(title, message, okLabel, okClass, form) {
        confirmTitle.textContent   = title;
        confirmMessage.textContent = message;
        confirmOk.textContent      = okLabel;
        confirmOk.className        = 'btn-danger ' + (okClass || '');
        pendingForm                = form;
        openModal('modal-confirm');
    }

    confirmOk.addEventListener('click', function () {
        closeModal('modal-confirm');
        if (pendingForm) {
            pendingForm.submit();
            pendingForm = null;
        }
    });

    confirmCancel.addEventListener('click', function () {
        closeModal('modal-confirm');
        pendingForm = null;
    });

    // ------ Abrir modal de edição ------
    var selfId = document.body.dataset.userId;

    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var isSelf   = btn.dataset.id === selfId;
            var isAdmin  = btn.dataset.isAdmin === '1';

            document.getElementById('edit-id').value             = btn.dataset.id;
            document.getElementById('edit-username').value       = '';
            document.getElementById('edit-username').placeholder = btn.dataset.username;
            document.getElementById('edit-password').value       = '';

            var adminCheck  = document.getElementById('edit-is-admin');
            var adminHidden = document.getElementById('edit-is-admin-hidden');
            var adminText   = document.getElementById('edit-admin-text');
            var adminLabel  = document.getElementById('edit-admin-label');
            var adminRow    = document.getElementById('edit-admin-toggle-row');

            adminCheck.checked  = isAdmin;
            adminCheck.disabled = isSelf;
            adminHidden.value   = isAdmin ? '1' : '0';
            adminText.textContent = isAdmin ? 'Administrador (ativo)' : 'Tornar administrador';

            adminLabel.title       = isSelf ? 'Você não pode alterar sua própria permissão.' : '';
            adminLabel.style.opacity = isSelf ? '.5' : '';
            adminRow.style.opacity   = isSelf ? '.5' : '';

            adminCheck.onchange = function () {
                adminHidden.value     = adminCheck.checked ? '1' : '0';
                adminText.textContent = adminCheck.checked ? 'Administrador (ativo)' : 'Tornar administrador';
            };

            openModal('modal-edit');
        });
    });

    // ------ Abrir modal de criação ------
    document.getElementById('btn-open-create').addEventListener('click', function () {
        openModal('modal-create');
    });

    // ------ Modal de IPs ------
    var ipsUsername = document.getElementById('ips-username');
    var ipsBody     = document.getElementById('ips-body');

    document.querySelectorAll('.btn-ips').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var userId   = btn.dataset.id;
            var username = btn.dataset.username;

            ipsUsername.textContent = username;
            ipsBody.innerHTML = '<p class="ips-loading">Carregando…</p>';
            openModal('modal-ips');

            var fd = new FormData();
            fd.append('user_id', userId);

            fetch('get-user-ips.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok || !data.ips.length) {
                        ipsBody.innerHTML = '<p class="ips-empty">Nenhum IP registrado.</p>';
                        return;
                    }

                    var html = '';

                    // Separar atual dos antigos
                    var current = data.ips[0];
                    var older   = data.ips.slice(1);

                    html += '<p class="ips-section-label">IP atual</p>';
                    html += '<ul class="ips-list">' + buildIpItem(current, true) + '</ul>';

                    if (older.length) {
                        html += '<p class="ips-section-label ips-section-label-old">IPs anteriores</p>';
                        html += '<ul class="ips-list">';
                        older.forEach(function (item) { html += buildIpItem(item, false); });
                        html += '</ul>';
                    }

                    ipsBody.innerHTML = html;

                    // Click para copiar
                    ipsBody.querySelectorAll('.ips-ip-btn').forEach(function (el) {
                        el.addEventListener('click', function () {
                            var ip = el.dataset.ip;
                            navigator.clipboard.writeText(ip).then(function () {
                                var orig = el.innerHTML;
                                el.innerHTML = '&#10003; Copiado!';
                                el.classList.add('ips-copied');
                                setTimeout(function () {
                                    el.innerHTML = orig;
                                    el.classList.remove('ips-copied');
                                }, 1500);
                            });
                        });
                    });
                })
                .catch(function () {
                    ipsBody.innerHTML = '<p class="ips-empty">Erro ao carregar IPs.</p>';
                });
        });
    });

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function buildIpItem(item, isCurrent) {
        var firstDate = item.first_seen.substring(0, 16).replace('T', ' ');
        var lastDate  = item.last_seen.substring(0, 16).replace('T', ' ');
        return '<li class="' + (isCurrent ? 'ips-current' : '') + '">'
            + '<button class="ips-ip-btn" data-ip="' + escHtml(item.ip) + '" title="Clique para copiar">'
            + '<span class="ips-ip-text">' + escHtml(item.ip) + '</span>'
            + '<span class="ips-copy-icon">&#128203;</span>'
            + (isCurrent ? '<span class="ips-tag-current">Atual</span>' : '')
            + '</button>'
            + '<div style="display:flex;align-items:center;gap:6px">'
            + '<span class="ips-count">&#128273; ' + item.access_count + 'x</span>'
            + '<span class="ips-meta">'
            + '1º acesso: ' + firstDate + '<br>'
            + 'Último: ' + lastDate
            + '</span>'
            + '</div>'
            + '</li>';
    }

    // ------ Confirmar exclusão ------
    document.querySelectorAll('.form-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            askConfirm(
                'Excluir usuário',
                'Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.',
                'Excluir',
                '',
                form
            );
        });
    });

    // ------ Busca em tempo real ------
    var searchInput = document.getElementById('search-input');
    var searchClear = document.getElementById('search-clear');
    var searchEmpty = document.getElementById('search-empty');
    var searchTerm  = document.getElementById('search-term');
    var userCount   = document.getElementById('user-count');
    var rows        = document.querySelectorAll('.admin-table tbody tr');

    function filterTable() {
        var query   = searchInput.value.trim().toLowerCase();
        var visible = 0;

        rows.forEach(function (row) {
            var username = row.querySelector('.td-strong') ? row.querySelector('.td-strong').textContent.toLowerCase() : '';
            var email    = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
            var match    = username.includes(query) || email.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        searchClear.style.display = query ? 'flex' : 'none';
        userCount.textContent     = visible;

        if (query && visible === 0) {
            searchEmpty.style.display = 'block';
            searchTerm.textContent    = searchInput.value.trim();
        } else {
            searchEmpty.style.display = 'none';
        }
    }

    searchInput.addEventListener('input', filterTable);

    searchClear.addEventListener('click', function () {
        searchInput.value = '';
        filterTable();
        searchInput.focus();
    });

});
