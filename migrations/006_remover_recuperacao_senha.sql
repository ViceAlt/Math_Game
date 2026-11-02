-- Remove estruturas exclusivas do fluxo de recuperação de senha.

USE math_game;

DROP TABLE IF EXISTS recuperacao_senha;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'senha_alterada_em'
);

SET @sql_stmt = IF(
    @col_exists > 0,
    'ALTER TABLE usuarios DROP COLUMN senha_alterada_em',
    'SELECT 1'
);

PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;