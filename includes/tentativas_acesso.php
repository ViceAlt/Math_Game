<?php
/**
 * Funções compartilhadas de rate limit para autenticação.
 */

require_once __DIR__ . '/../conexao.php';

function tentativas_obter_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return strtolower(substr(trim($ip), 0, 45));
}

function tentativas_registrar(string $tipo, string $chave): void {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO tentativas_acesso (tipo, chave, criado_em) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$tipo, strtolower($chave)]);
    } catch (\PDOException $e) {
        error_log('[Math Game] Erro ao registrar tentativa de acesso: ' . $e->getMessage());
    }
}

function tentativas_contar(string $tipo, string $chave, int $minutos = 60): int {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_acesso
             WHERE tipo = ? AND chave = ?
               AND criado_em >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$tipo, strtolower($chave), $minutos]);
        return (int) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log('[Math Game] Erro ao contar tentativas de acesso: ' . $e->getMessage());
        return 0;
    }
}

function tentativas_limpar(string $tipo, string $chave): void {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM tentativas_acesso WHERE tipo = ? AND chave = ?');
        $stmt->execute([$tipo, strtolower($chave)]);
    } catch (\PDOException $e) {
        error_log('[Math Game] Erro ao limpar tentativas de acesso: ' . $e->getMessage());
    }
}