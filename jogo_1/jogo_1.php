<?php
/**
 * jogo_1/jogo_1.php
 * Ponto de entrada do Jogo 1 (Frações com Pizza) com validação server-side de desbloqueio.
 */
require_once __DIR__ . '/../includes/validador_fase.php';

$ano  = isset($_GET['ano']) ? (int) $_GET['ano'] : null;
$fase = isset($_GET['fase']) ? (int) $_GET['fase'] : null;

if ($ano !== null && $fase !== null && !validarAcessoFaseServidor($ano, $fase)) {
    header('Location: ../conteudos/conteudos.php?aviso=fase_bloqueada');
    exit;
}

require __DIR__ . '/jogo_1.html';
