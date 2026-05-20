window.showToast = function (type, msg) {
    var container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var toast    = document.createElement('div');
    toast.className = 'toast toast-' + type;

    var span = document.createElement('span');
    span.textContent = msg;

    var btn = document.createElement('button');
    btn.className = 'toast-close';
    btn.setAttribute('aria-label', 'Fechar');
    btn.textContent = '✕';
    btn.addEventListener('click', function () {
        toast.classList.add('toast-closing');
        setTimeout(function () { toast.remove(); }, 350);
    });

    toast.appendChild(span);
    toast.appendChild(btn);
    container.appendChild(toast);
};

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toast-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var toast = btn.closest('.toast');
            toast.classList.add('toast-closing');
            setTimeout(function () { toast.remove(); }, 350);
        });
    });
});
