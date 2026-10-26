<?php
/**
 * conexao.php
 * Centraliza a conexão PDO com o banco de dados.
 * Não exibe mensagens – lança exceção em caso de falha.
 */

require_once __DIR__ . '/config.php';

if (!defined('DB_HOST')) define('DB_HOST', 'sql113.byethost4.com');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_NAME')) define('DB_NAME', 'b4_42774075_math_game');
if (!defined('DB_USER')) define('DB_USER', 'b4_42774075');
if (!defined('DB_PASS')) define('DB_PASS', 'senai@571');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}