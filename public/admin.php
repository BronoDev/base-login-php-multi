<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$toast = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $toast = ['type' => 'error', 'msg' => 'Requisição inválida.'];
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $r = adminCreateUser(
                $_POST['username'] ?? '',
                $_POST['email']    ?? '',
                $_POST['password'] ?? '',
                isset($_POST['is_admin'])
            );
            $toast = $r['ok']
                ? ['type' => 'success', 'msg' => 'Usuário criado com sucesso.']
                : ['type' => 'error',   'msg' => $r['error']];

        } elseif ($action === 'update') {
            $r = adminUpdateUser(
                (int) ($_POST['id'] ?? 0),
                $_POST['username'] ?? '',
                $_POST['password'] ?? '',
                ($_POST['is_admin'] ?? '0') === '1'
            );
            $toast = $r['ok']
                ? ['type' => 'success', 'msg' => 'Usuário atualizado com sucesso.']
                : ['type' => 'error',   'msg' => $r['error']];

        } elseif ($action === 'delete') {
            $r = adminDeleteUser((int) ($_POST['id'] ?? 0));
            $toast = $r['ok']
                ? ['type' => 'success', 'msg' => 'Usuário excluído com sucesso.']
                : ['type' => 'error',   'msg' => $r['error']];
        }
    }
}

$users = adminListUsers();
$csrf  = generateCsrfToken();
?>
<?php $themeClass = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark' : ''; ?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body class="has-header" data-user-id="<?= (int) $_SESSION['user_id'] ?>">

<?php require_once 'templates/_header.php'; ?>

<?php if ($toast['msg'] !== ''): ?>
<div class="toast-container">
    <div class="toast toast-<?= $toast['type'] ?>">
        <span><?= htmlspecialchars($toast['msg']) ?></span>
        <button class="toast-close" aria-label="Fechar">✕</button>
    </div>
</div>
<?php endif; ?>

<!-- Modal: IPs do usuário -->
<div class="modal-backdrop" id="modal-ips">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2>IPs de <span id="ips-username"></span></h2>
            <button class="modal-close" id="modal-ips-close" aria-label="Fechar">✕</button>
        </div>
        <div id="ips-body">
            <p class="ips-loading">Carregando…</p>
        </div>
    </div>
</div>

<!-- Modal: Confirmação -->
<div class="modal-backdrop" id="modal-confirm">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2 id="confirm-title">Confirmar ação</h2>
        </div>
        <p id="confirm-message" class="confirm-msg"></p>
        <div class="modal-footer">
            <button id="confirm-cancel" class="btn-secondary">Cancelar</button>
            <button id="confirm-ok"     class="btn-danger">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal: Editar usuário -->
<div class="modal-backdrop" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <h2>Editar usuário</h2>
            <button class="modal-close" id="modal-edit-close" aria-label="Fechar">✕</button>
        </div>
        <form method="POST" action="admin.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">

            <label for="edit-username">Novo nome de usuário</label>
            <input type="text" id="edit-username" name="username"
                   minlength="3" maxlength="50" autocomplete="off">
            <small>Deixe em branco para não alterar.</small>

            <label for="edit-password">Nova senha</label>
            <input type="password" id="edit-password" name="password" autocomplete="new-password">
            <small>Deixe em branco para não alterar. Mín. 8 caracteres, maiúscula e símbolo.</small>

            <div class="admin-toggle-row" id="edit-admin-toggle-row">
                <span class="admin-toggle-label">Permissão de administrador</span>
                <label class="checkbox-label" id="edit-admin-label">
                    <input type="hidden" name="is_admin" id="edit-is-admin-hidden" value="0">
                    <input type="checkbox" name="is_admin" id="edit-is-admin" value="1">
                    <span id="edit-admin-text">Administrador</span>
                </label>
            </div>

            <button type="submit">Salvar alterações</button>
        </form>
    </div>
</div>

<!-- Modal: Criar usuário -->
<div class="modal-backdrop" id="modal-create">
    <div class="modal">
        <div class="modal-header">
            <h2>Criar usuário</h2>
            <button class="modal-close" id="modal-create-close" aria-label="Fechar">✕</button>
        </div>
        <form method="POST" action="admin.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="create">

            <label for="new-username">Nome de usuário</label>
            <input type="text" id="new-username" name="username" required
                   minlength="3" maxlength="50" autocomplete="off">

            <label for="new-email">E-mail</label>
            <input type="email" id="new-email" name="email" required autocomplete="off">

            <label for="new-password">Senha</label>
            <input type="password" id="new-password" name="password" required autocomplete="new-password">
            <small>Mín. 8 caracteres, uma maiúscula e um símbolo.</small>

            <label class="checkbox-label">
                <input type="checkbox" name="is_admin" value="1">
                Tornar administrador
            </label>

            <button type="submit">Criar usuário</button>
        </form>
    </div>
</div>

<main class="main-content main-content-wide">
<div class="admin-wrapper">
    <div class="admin-header">
        <div>
            <h1>Gerenciar Usuários</h1>
            <p class="admin-sub">Total: <span id="user-count"><?= count($users) ?></span> usuário<?= count($users) !== 1 ? 's' : '' ?></p>
        </div>
        <div class="admin-header-actions">
            <button class="btn-primary" id="btn-open-create">+ Criar usuário</button>
        </div>
    </div>

    <div class="search-bar">
        <span class="search-icon">&#128269;</span>
        <input type="text" id="search-input" placeholder="Buscar por usuário ou e-mail…" autocomplete="off">
        <button id="search-clear" class="search-clear" aria-label="Limpar" style="display:none">✕</button>
    </div>

    <div class="role-filter">
        <button class="role-btn role-btn-active" data-role="all">Todos</button>
        <button class="role-btn" data-role="admin">Admin</button>
        <button class="role-btn" data-role="user">Usuário</button>
    </div>

    <p id="search-empty" class="search-empty" style="display:none">Nenhum usuário encontrado para "<span id="search-term"></span>".</p>

    <div class="table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>IP Atual</th>
                    <th>Cadastrado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $isOnline = (bool) $u['is_online'];
            ?>
                <tr data-role="<?= $u['is_admin'] ? 'admin' : 'user' ?>"
                    <?= $u['id'] == $_SESSION['user_id'] ? 'class="row-self"' : '' ?>>
                    <td class="td-id"><?= $u['id'] ?></td>
                    <td class="td-status">
                        <span class="status-dot <?= $isOnline ? 'status-online' : 'status-offline' ?>"
                              title="<?= $isOnline ? 'Online' : 'Offline' ?>"></span>
                        <span class="status-label"><?= $isOnline ? 'Online' : 'Offline' ?></span>
                    </td>
                    <td class="td-strong"><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if ($u['is_admin']): ?>
                            <span class="badge badge-admin">Admin</span>
                        <?php else: ?>
                            <span class="badge badge-user">Usuário</span>
                        <?php endif; ?>
                    </td>
                    <td class="td-ip">
                        <?php if ($u['last_ip']): ?>
                            <button class="ip-badge btn-ips"
                                    data-id="<?= $u['id'] ?>"
                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                    title="Ver histórico de IPs">
                                <?= htmlspecialchars($u['last_ip']) ?>
                            </button>
                        <?php else: ?>
                            <span class="td-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="td-date"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td class="td-actions">
                        <!-- Editar -->
                        <button class="btn-action btn-edit"
                                data-id="<?= $u['id'] ?>"
                                data-username="<?= htmlspecialchars($u['username']) ?>"
                                data-is-admin="<?= $u['is_admin'] ? '1' : '0' ?>">
                            Editar
                        </button>

                        <!-- Excluir -->
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="admin.php" class="form-inline form-delete">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-action btn-delete">Excluir</button>
                        </form>
                        <?php else: ?>
                            <span class="td-self-label">Você</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="scroll-sentinel" class="scroll-sentinel">
        <span id="scroll-loader" class="scroll-loader" style="display:none">
            <span class="scroll-loader-dot"></span>
            <span class="scroll-loader-dot"></span>
            <span class="scroll-loader-dot"></span>
        </span>
    </div>

</div>
</main>

<script src="assets/js/toast.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
