document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('avatar-input');
    var drop  = document.getElementById('avatar-drop');
    var form  = document.getElementById('avatar-form');

    if (!input || !drop || !form) return;

    var btn = form.querySelector('button[type="submit"]');

    // ------ Preview local ------
    function showPreview(src) {
        var img = document.getElementById('preview-img');
        if (!img) return;
        img.src = src;
        img.classList.remove('avatar-default-icon');
    }

    function previewFile(file) {
        if (!file || !file.type.startsWith('image/')) return;
        var reader = new FileReader();
        reader.onload = function (e) { showPreview(e.target.result); };
        reader.readAsDataURL(file);
    }

    input.addEventListener('change', function () {
        previewFile(input.files[0]);
    });

    // ------ Drag & drop ------
    drop.addEventListener('dragover', function (e) {
        e.preventDefault();
        drop.classList.add('drag-over');
    });
    drop.addEventListener('dragleave', function () {
        drop.classList.remove('drag-over');
    });
    drop.addEventListener('drop', function (e) {
        e.preventDefault();
        drop.classList.remove('drag-over');
        var file = e.dataTransfer.files[0];
        if (file) {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            previewFile(file);
        }
    });

    // ------ Upload AJAX ------
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!input.files[0]) {
            showToast('error', 'Selecione uma imagem antes de salvar.');
            return;
        }

        var csrf = form.querySelector('[name="csrf_token"]').value;
        var fd   = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('avatar', input.files[0]);

        btn.disabled    = true;
        btn.textContent = 'Salvando…';

        fetch('upload-avatar.php', { method: 'POST', body: fd })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.ok) {
                    var stamp = '?t=' + Date.now();
                    // Atualiza todos os avatares da página (card perfil + header)
                    document.querySelectorAll('.avatar img').forEach(function (img) {
                        img.src = data.url + stamp;
                        img.classList.remove('avatar-default-icon');
                    });
                    input.value = '';
                    showToast('success', 'Foto atualizada com sucesso.');
                } else {
                    showToast('error', data.error || 'Erro ao salvar a foto.');
                }
            })
            .catch(function () {
                showToast('error', 'Erro de conexão. Tente novamente.');
            })
            .finally(function () {
                btn.disabled    = false;
                btn.textContent = 'Salvar foto';
            });
    });
});
