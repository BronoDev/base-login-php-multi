<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// ---------- Security headers ----------
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // Em produção (HTTPS), descomente a linha abaixo:
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,   // PRODUÇÃO: altere para true (requer HTTPS)
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

// ---------- CSRF ----------

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------- Rate Limiting ----------

function _loginRateLimitBlocked(string $ip, string $email): bool
{
    try {
        $db = getDB();
        // Máx. 10 tentativas por IP em 15 min
        $s = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        $s->execute([$ip]);
        if ((int) $s->fetchColumn() >= 10) return true;
        // Máx. 5 tentativas por e-mail em 15 min
        $s = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        $s->execute([$email]);
        if ((int) $s->fetchColumn() >= 5) return true;
        return false;
    } catch (PDOException $e) {
        return false; // tabela ainda não criada — não bloqueia
    }
}

function _loginRecordAttempt(string $ip, string $email): void
{
    try {
        $db = getDB();
        $db->prepare('INSERT INTO login_attempts (ip, email) VALUES (?, ?)')->execute([$ip, $email]);
        // Limpa registros com mais de 24h para não crescer infinitamente
        $db->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)')->execute();
    } catch (PDOException $e) {}
}

// ---------- Autenticação ----------

define('SESSION_TIMEOUT', 300); // 5 minutos em segundos

function isLoggedIn(): bool  { return !empty($_SESSION['user_id']); }
function isAdmin(): bool     { return !empty($_SESSION['is_admin']); }

function _recordUserIp(int $userId, string $ip): void
{
    $db = getDB();
    try {
        $db->exec('
            CREATE TABLE IF NOT EXISTS user_ips (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id      INT UNSIGNED NOT NULL,
                ip           VARCHAR(45)  NOT NULL,
                first_seen   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                access_count INT UNSIGNED NOT NULL DEFAULT 1,
                UNIQUE KEY uq_user_ip (user_id, ip),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        $db->prepare('UPDATE users SET last_ip = ? WHERE id = ?')->execute([$ip, $userId]);
        $db->prepare('
            INSERT INTO user_ips (user_id, ip, first_seen, last_seen, access_count)
            VALUES (?, ?, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE last_seen = NOW(), access_count = access_count + 1
        ')->execute([$userId, $ip]);
    } catch (PDOException $e) {}

    $_SESSION['current_ip'] = $ip;
}

function requireLogin(): void
{
    if (!isLoggedIn()) { header('Location: index.php'); exit; }

    $now  = time();
    $last = $_SESSION['last_activity'] ?? $now;

    if ($now - $last > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = $now;

    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        getDB()->prepare('UPDATE users SET last_activity = NOW() WHERE id = ?')
               ->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {}

    // Registra o IP se for novo ou ainda não gravado na sessão
    if (($_SESSION['current_ip'] ?? '') !== $currentIp) {
        _recordUserIp((int) $_SESSION['user_id'], $currentIp);
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) { header('Location: dashboard.php'); exit; }
}

function login(string $email, string $password): array
{
    $email = trim($email);
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Preencha e-mail e senha.'];
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (_loginRateLimitBlocked($ip, $email)) {
        return ['ok' => false, 'error' => 'Muitas tentativas incorretas. Aguarde 15 minutos antes de tentar novamente.'];
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        _loginRecordAttempt($ip, $email);
        return ['ok' => false, 'error' => 'E-mail ou senha incorretos.'];
    }

    $db->prepare('UPDATE users SET last_login = NOW(), last_activity = NOW() WHERE id = ?')
       ->execute([$user['id']]);

    _recordUserIp((int) $user['id'], $ip);

    $isAdmin = false;
    try {
        $r = $db->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
        $r->execute([$user['id']]);
        $isAdmin = (bool) $r->fetchColumn();
    } catch (PDOException $e) {}

    $avatar = null;
    try {
        $r = $db->prepare('SELECT avatar FROM users WHERE id = ? LIMIT 1');
        $r->execute([$user['id']]);
        $avatar = $r->fetchColumn() ?: null;
    } catch (PDOException $e) {}

    $theme = 'light';
    try {
        $r = $db->prepare('SELECT theme FROM users WHERE id = ? LIMIT 1');
        $r->execute([$user['id']]);
        $theme = $r->fetchColumn() ?: 'light';
    } catch (PDOException $e) {}

    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['is_admin']      = $isAdmin;
    $_SESSION['avatar']        = $avatar;
    $_SESSION['theme']         = $theme;
    $_SESSION['last_activity'] = time();

    return ['ok' => true];
}

function register(string $username, string $email, string $password, string $confirm): array
{
    $username = trim($username);
    $email    = trim($email);

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        return ['ok' => false, 'error' => 'Todos os campos são obrigatórios.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'E-mail inválido.'];
    }
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['ok' => false, 'error' => 'O nome de usuário deve ter entre 3 e 50 caracteres.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['ok' => false, 'error' => 'O nome de usuário só pode conter letras, números e _.'];
    }
    if (str_contains($username, ' ') || str_contains($email, ' ') || str_contains($password, ' ')) {
        return ['ok' => false, 'error' => 'Nenhum campo pode conter espaços.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'A senha deve ter pelo menos 8 caracteres.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['ok' => false, 'error' => 'A senha deve conter pelo menos uma letra maiúscula.'];
    }
    if (!preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        return ['ok' => false, 'error' => 'A senha deve conter pelo menos um símbolo (ex: @, #, !).'];
    }
    if ($password !== $confirm) {
        return ['ok' => false, 'error' => 'As senhas não coincidem.'];
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'E-mail ou nome de usuário já cadastrado.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)')->execute([$username, $email, $hash]);
    return ['ok' => true];
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// ---------- Tema ----------

function updateTheme(int $id, string $theme): void
{
    $theme = $theme === 'dark' ? 'dark' : 'light';
    try {
        getDB()->prepare('UPDATE users SET theme = ? WHERE id = ?')->execute([$theme, $id]);
    } catch (PDOException $e) {}
    $_SESSION['theme'] = $theme;
}

// ---------- Perfil ----------

function updatePassword(int $id, string $current, string $newPass, string $confirm): array
{
    $stmt = getDB()->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        return ['ok' => false, 'error' => 'Senha atual incorreta.'];
    }
    if (str_contains($newPass, ' ')) {
        return ['ok' => false, 'error' => 'A nova senha não pode conter espaços.'];
    }
    if (strlen($newPass) < 8) {
        return ['ok' => false, 'error' => 'A nova senha deve ter pelo menos 8 caracteres.'];
    }
    if (!preg_match('/[A-Z]/', $newPass)) {
        return ['ok' => false, 'error' => 'A nova senha deve ter pelo menos uma letra maiúscula.'];
    }
    if (!preg_match('/[^a-zA-Z0-9\s]/', $newPass)) {
        return ['ok' => false, 'error' => 'A nova senha deve ter pelo menos um símbolo.'];
    }
    if ($newPass !== $confirm) {
        return ['ok' => false, 'error' => 'As senhas não coincidem.'];
    }

    $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
    getDB()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $id]);
    return ['ok' => true];
}

function updateEmail(int $id, string $email, string $password): array
{
    $email = trim($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || str_contains($email, ' ')) {
        return ['ok' => false, 'error' => 'E-mail inválido.'];
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if (!password_verify($password, $stmt->fetchColumn())) {
        return ['ok' => false, 'error' => 'Senha incorreta.'];
    }

    $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $dup->execute([$email, $id]);
    if ($dup->fetch()) {
        return ['ok' => false, 'error' => 'Este e-mail já está em uso.'];
    }

    $db->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $id]);
    $_SESSION['email'] = $email;
    return ['ok' => true];
}

function updateAvatar(int $id, array $file): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Erro no envio do arquivo.'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'A imagem deve ter no máximo 2MB.'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => 'Formato não suportado. Use JPEG, PNG, WebP ou GIF.'];
    }

    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    };

    $uploadDir  = __DIR__ . '/../public/uploads/avatars/';
    $uploadsRoot = __DIR__ . '/../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    // Garante que PHP nunca seja executado no diretório de uploads
    $htaccess = $uploadsRoot . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess,
            "Options -ExecCGI -Indexes\nphp_flag engine off\n" .
            "<FilesMatch \"\\.(?i:php[0-9]?|phtml|phar|pl|py|cgi|sh)$\">\n    Require all denied\n</FilesMatch>\n"
        );
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['ok' => false, 'error' => 'Falha ao salvar a imagem.'];
    }

    $db   = getDB();
    $old  = $db->prepare('SELECT avatar FROM users WHERE id = ?');
    $old->execute([$id]);
    $oldAvatar = $old->fetchColumn();

    if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
        unlink($uploadDir . $oldAvatar);
    }

    $db->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$filename, $id]);
    $_SESSION['avatar'] = $filename;
    return ['ok' => true];
}

// ---------- Admin ----------

function adminListUsers(): array
{
    return getDB()
        ->query('SELECT id, username, email, is_admin, created_at, last_activity, last_ip,
                        (last_activity IS NOT NULL AND last_activity >= DATE_SUB(NOW(), INTERVAL 300 SECOND)) AS is_online
                 FROM users ORDER BY id')
        ->fetchAll();
}

function adminGetUserIps(int $id): array
{
    $db = getDB();

    // Tenta buscar histórico completo da tabela user_ips
    try {
        $stmt = $db->prepare('
            SELECT ip, first_seen, last_seen, access_count
            FROM user_ips
            WHERE user_id = ?
            ORDER BY last_seen DESC
        ');
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll();
        if (!empty($rows)) return $rows;
    } catch (PDOException $e) {}

    // Fallback: tabela user_ips ausente ou vazia — usa last_ip de users
    try {
        $stmt = $db->prepare('SELECT last_ip, last_login FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && $row['last_ip']) {
            $ts = $row['last_login'] ?? date('Y-m-d H:i:s');
            return [[
                'ip'           => $row['last_ip'],
                'first_seen'   => $ts,
                'last_seen'    => $ts,
                'access_count' => 1,
            ]];
        }
    } catch (PDOException $e) {}

    return [];
}

function adminCreateUser(string $username, string $email, string $password, bool $makeAdmin): array
{
    $username = trim($username);
    $email    = trim($email);

    if ($username === '' || $email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Preencha todos os campos.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'E-mail inválido.'];
    }
    if (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['ok' => false, 'error' => 'Usuário inválido (3–50 chars, letras/números/_).'];
    }
    if (str_contains($password, ' ') || strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Senha inválida (mín. 8 chars, sem espaços).'];
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        return ['ok' => false, 'error' => 'Senha deve ter maiúscula e símbolo.'];
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) return ['ok' => false, 'error' => 'E-mail ou usuário já cadastrado.'];

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare('INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)')
       ->execute([$username, $email, $hash, $makeAdmin ? 1 : 0]);
    return ['ok' => true];
}

function adminUpdateUser(int $id, string $username, string $password, bool $makeAdmin): array
{
    $username = trim($username);
    $db       = getDB();

    if ($username !== '') {
        if (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['ok' => false, 'error' => 'Usuário deve ter 3–50 caracteres (letras, números e _).'];
        }
        $dup = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $dup->execute([$username, $id]);
        if ($dup->fetch()) return ['ok' => false, 'error' => 'Nome de usuário já está em uso.'];
        $db->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$username, $id]);
    }

    if ($password !== '') {
        if (str_contains($password, ' ') || strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) || !preg_match('/[^a-zA-Z0-9\s]/', $password)) {
            return ['ok' => false, 'error' => 'Senha deve ter mín. 8 chars, maiúscula, símbolo e sem espaços.'];
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $id]);
    }

    // Nunca altera o próprio status de admin
    if ($id !== (int) $_SESSION['user_id']) {
        if (!$makeAdmin) {
            $count = (int) $db->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
            if ($count <= 1) {
                $cur = $db->prepare('SELECT is_admin FROM users WHERE id = ?');
                $cur->execute([$id]);
                if ((bool) $cur->fetchColumn()) {
                    return ['ok' => false, 'error' => 'Não é possível remover o único administrador.'];
                }
            }
        }
        $db->prepare('UPDATE users SET is_admin = ? WHERE id = ?')->execute([$makeAdmin ? 1 : 0, $id]);
    }

    return ['ok' => true];
}


function adminDeleteUser(int $id): array
{
    if ($id === (int) $_SESSION['user_id']) {
        return ['ok' => false, 'error' => 'Você não pode excluir sua própria conta.'];
    }
    $db   = getDB();
    $stmt = $db->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) return ['ok' => false, 'error' => 'Usuário não encontrado.'];

    if ($user['is_admin']) {
        $count = (int) $db->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
        if ($count <= 1) return ['ok' => false, 'error' => 'Não é possível excluir o único administrador.'];
    }

    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    return ['ok' => true];
}
