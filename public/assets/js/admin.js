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

    // ------ Confirmar exclusão ------
    document.querySelectorAll('.form-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            askConfirm(
                'Excluir usuário',
                'Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.',
                'Excluir',
                'btn-danger-red',
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
