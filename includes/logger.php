<?php
/**
 * includes/logger.php
 * Registra logs detalhados e seguros do fluxo de recuperação de senha e envio de e-mails.
 * Nunca grava senhas nem segredos em texto puro.
 */

if (!function_exists('recuperacao_log')) {
    /**
     * Grava uma mensagem no arquivo logs/recuperacao.log.
     *
     * @param string $mensagem Mensagem descritiva do evento.
     * @param string $nivel    Nível do log (INFO, WARNING, ERROR, SMTP_DEBUG, SUCCESS).
     */
    function recuperacao_log(string $mensagem, string $nivel = 'INFO'): void {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/recuperacao.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $dataHora = date('Y-m-d H:i:s');
        $linha = sprintf("[%s] [%s] [%s] %s\n", $dataHora, strtoupper($nivel), $ip, trim($mensagem));

        @file_put_contents($logFile, $linha, FILE_APPEND | LOCK_EX);
    }
}
