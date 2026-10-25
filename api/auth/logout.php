<?php
/**
 * api/auth/logout.php
 * POST – Encerra a sessão do usuário.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/sessao.php';
require_once __DIR__ . '/../../conexao.php';

sessao_iniciar();

try {
    sessao_revogar_login_persistente(getDB());
} catch (\Throwable $e) {
    error_log('[Math Game] Falha ao revogar login persistente: ' . $e->getMessage());
    sessao_limpar_cookie_persistente();
}

sessao_destruir();

echo json_encode(['ok' => true]);
