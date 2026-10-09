<?php
/**
 * api/auth/session.php
 * GET – Retorna os dados do usuário logado (sessão ativa) ou null.
 * Também retorna o token CSRF para uso nos formulários JS.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/sessao.php';
require_once __DIR__ . '/../../includes/csrf.php';

sessao_iniciar();

$csrfToken = csrf_gerar();

if (!empty($_SESSION['usuario'])) {
    echo json_encode([
        'ok'         => true,
        'usuario'    => $_SESSION['usuario'],
        'csrf_token' => $csrfToken,
    ]);
} else {
    echo json_encode([
        'ok'         => false,
        'usuario'    => null,
        'csrf_token' => $csrfToken,
    ]);
}
