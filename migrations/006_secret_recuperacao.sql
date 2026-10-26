-- Adiciona identificador secreto individual sem excluir nem recriar contas.
ALTER TABLE usuarios
    ADD COLUMN recovery_secret_encrypted TEXT NULL,
    ADD COLUMN recovery_secret_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    ADD UNIQUE KEY uq_usuarios_recovery_secret_hash (recovery_secret_hash);