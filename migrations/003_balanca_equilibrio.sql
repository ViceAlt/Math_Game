-- ============================================================
-- Math Game - Migração 003: Jogo 5 - A Balança de Equilíbrio
-- Insere o novo jogo e atualiza fases 7, 9, 13, 15, 17 e 19 do 7º ano
-- ============================================================

USE math_game;

-- 1. Garante o registro do Jogo 5
INSERT INTO jogos (id, nome, pasta_path) VALUES
    (5, 'A Balança de Equilíbrio', 'jogo_5/')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    pasta_path = VALUES(pasta_path);

-- 2. Atualiza as fases pedagógicas da trilha do 7º ano para o Jogo 5
-- Fases 7 e 9: Nível Fácil
-- Fases 13 e 15: Nível Médio
-- Fases 17 e 19: Nível Experiente
UPDATE fases
SET jogo_id = 5
WHERE ano_letivo = 7 AND numero_fase IN (7, 9, 13, 15, 17, 19);
