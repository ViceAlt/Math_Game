-- ============================================================
-- Math Game - Schema SQL
-- Banco de dados para o sistema de jogos de Matemática Fundamental 2
-- ============================================================

CREATE DATABASE IF NOT EXISTS math_game
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE math_game;

-- ------------------------------------------------------------
-- Tabela: usuarios
-- Armazena os dados dos usuários cadastrados
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    foto_url    VARCHAR(500)     NULL DEFAULT NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: login_persistente
-- Tokens opacos para manter sessões entre visitas ao site
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Tabela: jogos
-- Catálogo de jogos disponíveis na plataforma
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jogos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    pasta_path  VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: jogo inicial
INSERT INTO jogos (nome, pasta_path) VALUES
    ('Frações com Pizza', 'jogo_1/');

-- ------------------------------------------------------------
-- Tabela: fases
-- Fases organizadas por ano letivo vinculadas a um jogo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fases (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ano_letivo  TINYINT UNSIGNED NOT NULL COMMENT '6, 7, 8 ou 9',
    numero_fase TINYINT UNSIGNED NOT NULL,
    jogo_id     INT UNSIGNED     NOT NULL,
    CONSTRAINT fk_fases_jogo FOREIGN KEY (jogo_id) REFERENCES jogos(id) ON DELETE CASCADE,
    CONSTRAINT uq_fase_ano UNIQUE (ano_letivo, numero_fase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: fases dos 4 anos letivos (3 fases cada)
INSERT INTO fases (ano_letivo, numero_fase, jogo_id) VALUES
    (6, 1, 1), (6, 2, 1), (6, 3, 1),
    (7, 1, 1), (7, 2, 1), (7, 3, 1),
    (8, 1, 1), (8, 2, 1), (8, 3, 1),
    (9, 1, 1), (9, 2, 1), (9, 3, 1);

-- ------------------------------------------------------------
-- Tabela: progresso_usuario
-- Armazena o progresso de cada usuário em cada fase
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progresso_usuario (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED     NOT NULL,
    fase_id         INT UNSIGNED     NOT NULL,
    pontuacao_max   INT UNSIGNED     NOT NULL DEFAULT 0,
    estrelas        TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 a 3 estrelas',
    desbloqueado    TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '1 = desbloqueado',
    ultimo_acesso   DATETIME             NULL DEFAULT NULL,
    CONSTRAINT fk_prog_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_prog_fase    FOREIGN KEY (fase_id)    REFERENCES fases(id)    ON DELETE CASCADE,
    CONSTRAINT uq_prog UNIQUE (usuario_id, fase_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: tentativas_acesso
-- Registra tentativas para proteção de rate limit do login
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tentativas_acesso (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo       VARCHAR(30)  NOT NULL COMMENT 'login',
    chave      VARCHAR(190) NOT NULL COMMENT 'identificador de login e IP',
    criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tentativas (tipo, chave, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

