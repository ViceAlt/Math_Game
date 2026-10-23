<?php
/**
 * includes/senha.php
 * Centraliza funções de hash, validação e verificação de senhas.
 * Utiliza o algoritmo nativo BCRYPT com custo 12.
 */

// Hash falso fixo (bcrypt cost 12 válido) para mitigar timing attacks quando o usuário não é encontrado
define('DUMMY_BCRYPT_HASH', '$2y$12$e8xL1i9n4Y5hO5z0Q2G8Tu2r2e3q4u5a6l7i8t9y0z1a2b3c4d5e.');

/**
 * Gera o hash seguro da senha com bcrypt e custo 12.
 *
 * @param string $senha Senha em texto puro.
 * @return string Hash gerado.
 */
function gerar_hash_senha(string $senha): string {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica se a senha fornecida corresponde ao hash armazenado.
 * Suporta migração transparente para hashes legados caso necessário.
 *
 * @param string $senha Senha informada pelo usuário.
 * @param string $hash Hash armazenado no banco de dados.
 * @return bool True se a senha for válida, false caso contrário.
 */
function verificar_senha(string $senha, string $hash): bool {
    if (empty($hash)) {
        return false;
    }

    // Se já é um hash bcrypt
    if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$')) {
        return password_verify($senha, $hash);
    }

    // Fallback para migração transparente de senhas legadas (ex: MD5, SHA-1 ou texto puro)
    if (md5($senha) === $hash || sha1($senha) === $hash || $senha === $hash) {
        return true;
    }

    return false;
}

/**
 * Executa password_verify contra um hash falso fixo para igualar o tempo de resposta
 * e prevenir enumeração de contas por análise de tempo (timing attacks).
 *
 * @param string $senha Senha informada.
 * @return void
 */
function verificar_dummy_hash(string $senha): void {
    password_verify($senha, DUMMY_BCRYPT_HASH);
}

/**
 * Valida o comprimento e integridade da senha.
 * - Mínimo de 8 caracteres.
 * - Máximo de 72 bytes (strlen) para não exceder o limite do algoritmo bcrypt.
 * - Não aplica trim() para não alterar intencionais espaços digitados pelo usuário.
 *
 * @param string $senha Senha informada.
 * @return string|null Retorna mensagem de erro em caso de falha, ou null se válida.
 */
function validar_forca_senha(string $senha): ?string {
    $bytes = strlen($senha);

    if ($bytes < 8) {
        return 'A senha deve conter no mínimo 8 caracteres.';
    }

    if ($bytes > 72) {
        return 'A senha não pode exceder 72 bytes (limite de segurança do algoritmo de criptografia).';
    }

    return null;
}

/**
 * Verifica se o hash atual precisa ser recalculado (ex: caso os parâmetros do bcrypt mudem).
 *
 * @param string $hash Hash atual.
 * @return bool True se precisar de novo hash.
 */
function precisa_rehash(string $hash): bool {
    // Se não for bcrypt, precisa de rehash
    if (!str_starts_with($hash, '$2y$')) {
        return true;
    }
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}
