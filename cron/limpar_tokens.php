<?php
/**
 * cron/limpar_tokens.php
 * Script de limpeza oportunista ou via cron job.
 * Remove tokens de recuperação expirados e tentativas de acesso com mais de 24 horas.
 *
 * Uso com cron (exemplo — a cada hora):
 *   0 * * * * php /caminho/para/Math_Game/cron/limpar_tokens.php >> /var/log/mathgame_cron.log 2>&1
 *
 * Uso manual via CLI:
 *   php cron/limpar_tokens.php
 */

// Segurança: bloqueia execução via HTTP em produção
if (PHP_SAPI !== 'cli' && (!defined('APP_ENV') || APP_ENV !== 'development')) {
    http_response_code(403);
    exit('Acesso negado.');
}

require_once __DIR__ . '/../conexao.php';

$pdo = getDB();

try {
    // Remove tokens de recuperação expirados há mais de 24 horas
    $stmtTokens = $pdo->exec(
        "DELETE FROM recuperacao_senha WHERE expira_em < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );

    // Remove tentativas de acesso com mais de 24 horas
    $stmtTentativas = $pdo->exec(
        "DELETE FROM tentativas_acesso WHERE criado_em < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );

    $agora = date('Y-m-d H:i:s');
    echo "[{$agora}] Limpeza concluída — tokens removidos: {$stmtTokens}, tentativas removidas: {$stmtTentativas}" . PHP_EOL;

} catch (\PDOException $e) {
    // Registra sem expor dados sensíveis
    error_log('[Math Game] Erro no cron de limpeza: ' . $e->getMessage());
    echo "[ERRO] Falha ao executar limpeza. Verifique o log do servidor." . PHP_EOL;
    exit(1);
}
