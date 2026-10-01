<?php
/**
 * api/auth/session.php
 * GET – Retorna os dados do usuário logado (sessão ativa) ou null.
 * Usado pelo frontend ao carregar qualquer página PHP.
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

if (!empty($_SESSION['usuario'])) {
    echo json_encode([
        'ok'      => true,
        'usuario' => $_SESSION['usuario'],
    ]);
} else {
    echo json_encode(['ok' => false, 'usuario' => null]);
}
