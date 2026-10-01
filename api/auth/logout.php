<?php
/**
 * api/auth/logout.php
 * POST – Encerra a sessão do usuário.
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
session_unset();
session_destroy();

// Invalida o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

echo json_encode(['ok' => true]);
