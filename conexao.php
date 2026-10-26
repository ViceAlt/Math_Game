<?php
/**
 * conexao.php
 * Centraliza a conexão PDO com o banco de dados.
 * Não exibe mensagens – lança exceção em caso de falha.
 */

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (DB_HOST === '' || DB_NAME === '' || DB_USER === '' || (APP_ENV === 'production' && DB_PASS === '')) {
        throw new RuntimeException('Configure DB_HOST, DB_NAME, DB_USER e, em produção, DB_PASS no ambiente.');
    }

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