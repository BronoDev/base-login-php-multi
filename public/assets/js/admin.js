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
            + '<div class="ips-item-top">'
            +   '<button class="ips-ip-btn" data-ip="' + escHtml(item.ip) + '" title="Clique para copiar">'
            +     '<span class="ips-ip-text">' + escHtml(item.ip) + '</span>'
            +     '<span class="ips-copy-icon">&#128203;</span>'
            +   '</button>'
            +   '<div class="ips-item-badges">'
            +     (isCurrent ? '<span class="ips-tag-current">Atual</span>' : '')
            +     '<span class="ips-count">&#128273; ' + item.access_count + 'x</span>'
            +   '</div>'
            + '</div>'
            + '<div class="ips-item-dates">'
            +   '<span>1º acesso: ' + firstDate + '</span>'
            +   '<span>Último acesso: ' + lastDate + '</span>'
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

    // ------ Busca + filtro por perfil + lazy scroll ------
    var BATCH       = 25;
    var roleFilter  = 'all';
    var revealedUpTo = 0;

    var searchInput  = document.getElementById('search-input');
    var searchClear  = document.getElementById('search-clear');
    var searchEmpty  = document.getElementById('search-empty');
    var searchTerm   = document.getElementById('search-term');
    var userCount    = document.getElementById('user-count');
    var sentinel     = document.getElementById('scroll-sentinel');
    var loader       = document.getElementById('scroll-loader');
    var rows         = Array.from(document.querySelectorAll('.admin-table tbody tr'));
    var totalRows    = rows.length;

    // Indexar linhas e esconder além do primeiro lote
    rows.forEach(function (row, i) {
        row.dataset.lazyIdx = i;
        if (i >= BATCH) row.style.display = 'none';
    });
    revealedUpTo = Math.min(BATCH, totalRows);

    function anyFilterActive() {
        return searchInput.value.trim() !== '' || roleFilter !== 'all';
    }

    function updateSentinel() {
        sentinel.style.display = (!anyFilterActive() && revealedUpTo < totalRows) ? '' : 'none';
    }
    updateSentinel();

    // IntersectionObserver — revela o próximo lote ao rolar
    var observer = new IntersectionObserver(function (entries) {
        if (!entries[0].isIntersecting || anyFilterActive()) return;
        loader.style.display = 'flex';
        setTimeout(function () {
            var end = Math.min(revealedUpTo + BATCH, totalRows);
            for (var i = revealedUpTo; i < end; i++) {
                rows[i].style.display = '';
                rows[i].classList.add('row-fade-in');
            }
            revealedUpTo = end;
            loader.style.display = 'none';
            updateSentinel();
        }, 280);
    }, { rootMargin: '120px' });
    observer.observe(sentinel);

    // Botões de filtro por perfil
    document.querySelectorAll('.role-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.role-btn').forEach(function (b) {
                b.classList.remove('role-btn-active');
            });
            btn.classList.add('role-btn-active');
            roleFilter = btn.dataset.role;
            filterTable();
        });
    });

    // Função unificada de filtro
    function filterTable() {
        var query  = searchInput.value.trim().toLowerCase();
        var active = anyFilterActive();
        var visible = 0;

        rows.forEach(function (row) {
            var username = row.querySelector('.td-strong') ? row.querySelector('.td-strong').textContent.toLowerCase() : '';
            var email    = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
            var role     = row.dataset.role || 'user';

            var matchText = !query || username.includes(query) || email.includes(query);
            var matchRole = roleFilter === 'all' || role === roleFilter;
            var match     = matchText && matchRole;

            if (active) {
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            } else {
                var idx = parseInt(row.dataset.lazyIdx);
                row.style.display = idx < revealedUpTo ? '' : 'none';
            }
        });

        updateSentinel();
        searchClear.style.display = query ? 'flex' : 'none';
        userCount.textContent = active ? visible : totalRows;

        var noResults = active && visible === 0;
        searchEmpty.style.display = noResults ? 'block' : 'none';
        if (noResults) searchTerm.textContent = query || (roleFilter === 'admin' ? 'Admin' : 'Usuário');
    }

    searchInput.addEventListener('input', filterTable);

    searchClear.addEventListener('click', function () {
        searchInput.value = '';
        filterTable();
        searchInput.focus();
    });

    // ------ Atualização de status em tempo real ------
    var STATUS_INTERVAL = 20000; // consulta o servidor a cada 20s

    function refreshStatuses() {
        fetch('get-user-statuses.php', { method: 'GET' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) return;
                rows.forEach(function (row) {
                    var id = parseInt(row.dataset.userId);
                    if (!id) return;
                    if (!(id in data.statuses)) return;

                    var online  = data.statuses[id];
                    var dot     = row.querySelector('.status-dot');
                    var label   = row.querySelector('.status-label');
                    if (!dot || !label) return;

                    dot.className   = 'status-dot ' + (online ? 'status-online' : 'status-offline');
                    dot.title       = online ? 'Online' : 'Offline';
                    label.textContent = online ? 'Online' : 'Offline';
                });
            })
            .catch(function () {});
    }

    setInterval(refreshStatuses, STATUS_INTERVAL);

});
