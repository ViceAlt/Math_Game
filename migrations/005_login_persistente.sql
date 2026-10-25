-- ============================================================
-- Math Game - Migracao 005: Tokens para login persistente
-- Execute com o banco da aplicacao selecionado.
-- Os valores de validacao ficam armazenados somente como hash.
-- ============================================================

CREATE TABLE IF NOT EXISTS login_persistente (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    seletor         CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    validador_hash  CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em       DATETIME NOT NULL,
    CONSTRAINT fk_login_persistente_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT uq_login_persistente_seletor UNIQUE (seletor),
    INDEX idx_login_persistente_usuario (usuario_id),
    INDEX idx_login_persistente_expiracao (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
