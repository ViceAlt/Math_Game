-- ============================================================
-- Math Game - Migração 004: Loja de Avatares & Moeda de Estrelas
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios_avatares (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    foto_nome   VARCHAR(50)  NOT NULL COMMENT 'Ex: foto1.webp, foto2.webp',
    preco_pago  INT UNSIGNED NOT NULL DEFAULT 10,
    comprado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ua_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT uq_user_foto UNIQUE (usuario_id, foto_nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garante que foto_url padrão seja foto1.webp caso esteja nula
-- UPDATE usuarios SET foto_url = 'fotos/foto1.webp' WHERE foto_url IS NULL OR foto_url = '';
