-- ============================================================
-- Math Game - Migração 001: Recuperação de Senha & Segurança
-- Não altera tabelas de jogos nem remove colunas/dados existentes
-- ============================================================

USE math_game;

-- ------------------------------------------------------------
-- Tabela: recuperacao_senha
-- Armazena os hashes de tokens para redefinição de senha
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recuperacao_senha (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    token_hash     CHAR(64)     NOT NULL COMMENT 'SHA-256 do token enviado por email',
    expira_em      DATETIME     NOT NULL,
    usado_em       DATETIME         NULL DEFAULT NULL,
    criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_solicitacao VARCHAR(45)      NULL DEFAULT NULL,
    CONSTRAINT fk_recsenha_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT uq_recsenha_token UNIQUE (token_hash),
    INDEX idx_recsenha_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: tentativas_acesso
-- Registra tentativas para proteção de rate limit (login e recuperação)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tentativas_acesso (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo       VARCHAR(30)  NOT NULL COMMENT 'login | recuperacao',
    chave      VARCHAR(190) NOT NULL COMMENT 'email ou IP (em minúsculas)',
    criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tentativas (tipo, chave, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Coluna: senha_alterada_em na tabela usuarios
-- Permite invalidar sessões anteriores à troca de senha
-- ------------------------------------------------------------
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'senha_alterada_em'
);

SET @sql_stmt = IF(
    @col_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN senha_alterada_em DATETIME NULL DEFAULT NULL AFTER senha_hash',
    'SELECT 1'
);

PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
