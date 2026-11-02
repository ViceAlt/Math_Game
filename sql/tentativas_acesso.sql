-- ============================================================
-- Math Game - Tabela: tentativas_acesso
-- Controle de limite de tentativas (rate limiting) para login e recuperação de senha.
-- ============================================================

USE math_game;

CREATE TABLE IF NOT EXISTS tentativas_acesso (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo       VARCHAR(30)  NOT NULL COMMENT 'login',
    chave      VARCHAR(190) NOT NULL COMMENT 'identificador de login e IP',
    criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tentativas (tipo, chave, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
