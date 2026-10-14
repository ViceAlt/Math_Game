-- ============================================================
-- Math Game - Migração 002: Trilha com 20 Fases por Ano e Histórico
-- Atualiza catálogo de jogos, fases (20 por ano) e colunas de progresso
-- ============================================================

USE math_game;

-- 1. Garante que os 4 jogos estão cadastrados na tabela 'jogos'
INSERT INTO jogos (id, nome, pasta_path) VALUES
    (1, 'Frações com Pizza', 'jogo_1/'),
    (2, 'Gráfico de Barras', 'jogo_2/'),
    (3, 'Geometria e Formas', 'jogo_3/'),
    (4, 'Sucessores e Antecessores', 'jogo_4/')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    pasta_path = VALUES(pasta_path);

-- 2. Adiciona coluna 'vezes_jogadas' na tabela 'progresso_usuario' (se não existir)
SET @col_vezes_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'progresso_usuario'
      AND COLUMN_NAME = 'vezes_jogadas'
);

SET @sql_vezes = IF(
    @col_vezes_exists = 0,
    'ALTER TABLE progresso_usuario ADD COLUMN vezes_jogadas INT UNSIGNED NOT NULL DEFAULT 1 AFTER estrelas',
    'SELECT 1'
);

PREPARE stmt_vezes FROM @sql_vezes;
EXECUTE stmt_vezes;
DEALLOCATE PREPARE stmt_vezes;

-- 3. Tabela de histórico de acessos (para o card unificado da Home)
CREATE TABLE IF NOT EXISTS historico_acessos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED     NOT NULL,
    tipo          VARCHAR(30)      NOT NULL COMMENT 'trilha | jogo_livre',
    ano_letivo    TINYINT UNSIGNED     NULL COMMENT '6, 7, 8 ou 9',
    fase_id       INT UNSIGNED         NULL,
    jogo_id       INT UNSIGNED     NOT NULL,
    titulo_fase   VARCHAR(100)         NULL,
    estrelas      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    acessado_em   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_hist_usuario_data (usuario_id, acessado_em DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Povoa / atualiza as 20 fases para cada um dos 4 anos (80 fases no total)
-- Ano 6: Floresta dos Números (Jogo 4 e Jogo 1)
INSERT INTO fases (ano_letivo, numero_fase, jogo_id) VALUES
    (6, 1, 4), (6, 2, 4), (6, 3, 1), (6, 4, 1), (6, 5, 4),
    (6, 6, 1), (6, 7, 4), (6, 8, 1), (6, 9, 4), (6, 10, 1),
    (6, 11, 4), (6, 12, 1), (6, 13, 1), (6, 14, 4), (6, 15, 1),
    (6, 16, 4), (6, 17, 1), (6, 18, 1), (6, 19, 4), (6, 20, 1)
ON DUPLICATE KEY UPDATE jogo_id = VALUES(jogo_id);

-- Ano 7: Deserto das Frações & Estatística (Jogo 1 e Jogo 2)
INSERT INTO fases (ano_letivo, numero_fase, jogo_id) VALUES
    (7, 1, 1), (7, 2, 2), (7, 3, 1), (7, 4, 2), (7, 5, 1),
    (7, 6, 2), (7, 7, 1), (7, 8, 2), (7, 9, 1), (7, 10, 2),
    (7, 11, 1), (7, 12, 2), (7, 13, 1), (7, 14, 2), (7, 15, 1),
    (7, 16, 2), (7, 17, 1), (7, 18, 2), (7, 19, 1), (7, 20, 2)
ON DUPLICATE KEY UPDATE jogo_id = VALUES(jogo_id);

-- Ano 8: Galáxia da Geometria (Jogo 3 e Jogo 2)
INSERT INTO fases (ano_letivo, numero_fase, jogo_id) VALUES
    (8, 1, 3), (8, 2, 2), (8, 3, 3), (8, 4, 2), (8, 5, 3),
    (8, 6, 2), (8, 7, 3), (8, 8, 2), (8, 9, 3), (8, 10, 2),
    (8, 11, 3), (8, 12, 2), (8, 13, 3), (8, 14, 2), (8, 15, 3),
    (8, 16, 2), (8, 17, 3), (8, 18, 2), (8, 19, 3), (8, 20, 3)
ON DUPLICATE KEY UPDATE jogo_id = VALUES(jogo_id);

-- Ano 9: Cidadela dos Mestres (Mistura dos 4 jogos em dificuldade progressiva)
INSERT INTO fases (ano_letivo, numero_fase, jogo_id) VALUES
    (9, 1, 4), (9, 2, 1), (9, 3, 2), (9, 4, 3), (9, 5, 1),
    (9, 6, 2), (9, 7, 3), (9, 8, 4), (9, 9, 1), (9, 10, 2),
    (9, 11, 3), (9, 12, 4), (9, 13, 1), (9, 14, 2), (9, 15, 3),
    (9, 16, 4), (9, 17, 1), (9, 18, 2), (9, 19, 3), (9, 20, 1)
ON DUPLICATE KEY UPDATE jogo_id = VALUES(jogo_id);

-- 5. Inicializa automaticamente a Fase 1 de cada ano como desbloqueada para usuários existentes
INSERT IGNORE INTO progresso_usuario (usuario_id, fase_id, pontuacao_max, estrelas, desbloqueado, vezes_jogadas, ultimo_acesso)
SELECT u.id, f.id, 0, 0, 1, 0, NOW()
FROM usuarios u
JOIN fases f ON f.numero_fase = 1;
