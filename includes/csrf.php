<?php
/**
 * includes/csrf.php
 * Centraliza geração e validação de tokens CSRF.
 * Requer que a sessão já esteja iniciada antes do uso.
 */

/**
 * Gera um token CSRF e armazena na sessão.
 * Idempotente: só gera novo token se a sessão não tiver um.
 *
 * @return string Token CSRF em hexadecimal.
 */
function csrf_gerar(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna o token CSRF atual (sem gerar novo).
 * Útil para incluir em respostas JSON.
 *
 * @return string Token CSRF ou string vazia se não existir.
 */
function csrf_obter(): string {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Valida o token CSRF enviado comparando com o da sessão (timing-safe).
 * Retorna false e encerra a execução com erro 403 se inválido.
 *
 * @param string $tokenEnviado Token recebido do formulário/request.
 * @return bool True se válido.
 */
function csrf_validar(string $tokenEnviado): bool {
    $tokenSessao = $_SESSION['csrf_token'] ?? '';
    if (empty($tokenSessao) || !hash_equals($tokenSessao, $tokenEnviado)) {
        return false;
    }
    return true;
}

/**
 * Regenera o token CSRF (use após operações sensíveis para invalidar reuso).
 *
 * @return string Novo token gerado.
 */
function csrf_regenerar(): string {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
