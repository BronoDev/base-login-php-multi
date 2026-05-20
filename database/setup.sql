-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS login_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE login_system;

CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)   NOT NULL UNIQUE,
    email       VARCHAR(255)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    is_admin    TINYINT(1)    NOT NULL DEFAULT 0,
    avatar      VARCHAR(100)  NULL,
    theme         ENUM('light','dark') NOT NULL DEFAULT 'light',
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME      NULL,
    last_activity DATETIME      NULL,
    last_ip       VARCHAR(45)   NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Migrações para quem já tem a tabela criada:
-- -------------------------------------------------------
-- ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password;
-- ALTER TABLE users ADD COLUMN avatar VARCHAR(100) NULL AFTER is_admin;
-- ALTER TABLE users ADD COLUMN theme ENUM('light','dark') NOT NULL DEFAULT 'light' AFTER avatar;
-- ALTER TABLE users ADD COLUMN last_activity DATETIME NULL AFTER last_login;
-- ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL AFTER last_activity;

-- Para tornar um usuário admin manualmente (substitua o e-mail):
-- UPDATE users SET is_admin = 1 WHERE email = 'seu@email.com';

-- -------------------------------------------------------
-- Tabela de rate limiting para proteção contra brute force
-- EXECUTE ESTE BLOCO para ativar o bloqueio de tentativas de login
-- -------------------------------------------------------
-- -------------------------------------------------------
-- Tabela de histórico de IPs por usuário
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_ips (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    ip           VARCHAR(45)  NOT NULL,
    first_seen   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    access_count INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_user_ip (user_id, ip),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Tabela de rate limiting para proteção contra brute force
-- EXECUTE ESTE BLOCO para ativar o bloqueio de tentativas de login
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45)  NOT NULL,
    email        VARCHAR(255) NOT NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time    (ip, attempted_at),
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
