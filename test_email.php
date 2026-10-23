<?php
/**
 * test_email.php
 * Painel de Diagnóstico e Teste de Envio de E-mails
 * Permite verificar a conectividade do servidor (portas SMTP, cURL, mail() nativo)
 * e realizar testes de disparo em tempo real.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/includes/logger.php';

$resultadoTeste = null;
$logsRecentes = [];
$testeConexao = [];

// Diagnóstico de rede e portas
function testar_porta(string $host, int $port, int $timeout = 3): array {
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return ['ok' => true, 'msg' => "Porta {$port} acessível (aberta)"];
    }
    return ['ok' => false, 'msg' => "Bloqueada ou inacessível ({$errno}: {$errstr})"];
}

$testeConexao['gmail_587'] = testar_porta('smtp.gmail.com', 587);
$testeConexao['gmail_465'] = testar_porta('smtp.gmail.com', 465);
$testeConexao['brevo_443'] = testar_porta('api.brevo.com', 443);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email_teste'])) {
    $emailDestino = filter_var(trim($_POST['email_teste']), FILTER_VALIDATE_EMAIL);

    if (!$emailDestino) {
        $resultadoTeste = ['sucesso' => false, 'msg' => 'Por favor, informe um endereço de e-mail válido.'];
    } else {
        $linkTeste = rtrim(APP_URL, '/') . '/redefinir_senha.php?token=' . bin2hex(random_bytes(16));
        $enviou = email_enviar_recuperacao($emailDestino, 'Teste Diagnóstico', $linkTeste);

        if ($enviou) {
            $resultadoTeste = [
                'sucesso' => true,
                'msg' => "E-mail de teste enviado com sucesso para {$emailDestino}! Verifique sua caixa de entrada e a pasta de Spam."
            ];
        } else {
            $resultadoTeste = [
                'sucesso' => false,
                'msg' => "Falha no envio de e-mail. Consulte os detalhes nos logs abaixo para identificar a causa."
            ];
        }
    }
}

// Lê os últimos logs
$logPath = __DIR__ . '/logs/recuperacao.log';
if (file_exists($logPath)) {
    $linhas = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($linhas) {
        $logsRecentes = array_slice($linhas, -20);
        $logsRecentes = array_reverse($logsRecentes);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de E-mail | Mantham</title>
    <link rel="stylesheet" href="tema.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f7f9fa; color: #3c3c3c; padding: 2rem 1rem; margin: 0; }
        .diag-container { max-width: 800px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 2px solid #e5e5e5; }
        h1 { margin-top: 0; color: #1cb0f6; font-size: 1.6rem; display: flex; align-items: center; gap: 0.5rem; }
        .diag-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .diag-card { border: 2px solid #e5e5e5; border-radius: 12px; padding: 1rem; background: #fafafa; }
        .diag-card.ok { border-color: #58cc02; background: #f0fff4; }
        .diag-card.fail { border-color: #ff4b4b; background: #fff0f0; }
        .diag-card strong { display: block; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .diag-status { font-size: 0.85rem; font-weight: 600; }
        .diag-status.ok { color: #2d6a2d; }
        .diag-status.fail { color: #8b1a1a; }
        .form-teste { background: #f0f4f8; border-radius: 12px; padding: 1.25rem; margin: 1.5rem 0; border: 1px solid #d0e2f2; }
        .form-row { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
        input[type="email"] { flex: 1; padding: 0.75rem 1rem; border-radius: 10px; border: 2px solid #ccc; font-size: 1rem; }
        input[type="email"]:focus { outline: none; border-color: #1cb0f6; }
        button { background: #58cc02; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 1rem; box-shadow: 0 3px 0 #46a302; }
        button:hover { background: #61e002; }
        button:active { transform: translateY(2px); box-shadow: none; }
        .alerta { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; font-weight: bold; font-size: 0.95rem; }
        .alerta.ok { background: #f0fff4; color: #2d6a2d; border: 2px solid #58cc02; }
        .alerta.fail { background: #fff0f0; color: #8b1a1a; border: 2px solid #ff4b4b; }
        .logs-box { background: #1e1e1e; color: #d4d4d4; padding: 1rem; border-radius: 10px; font-family: monospace; font-size: 0.85rem; max-height: 280px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; }
        .log-line { margin-bottom: 4px; border-bottom: 1px solid #2d2d2d; padding-bottom: 2px; }
        .btn-voltar { display: inline-block; margin-top: 1.5rem; color: #1cb0f6; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="diag-container">
    <h1>Diagnóstico do Sistema de E-mails</h1>
    <p>Verificação de conectividade de rede e transportes de e-mail no servidor atual.</p>

    <?php if ($resultadoTeste): ?>
        <div class="alerta <?= $resultadoTeste['sucesso'] ? 'ok' : 'fail' ?>">
            <?= htmlspecialchars($resultadoTeste['msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Grid de Verificação -->
    <div class="diag-grid">
        <div class="diag-card <?= function_exists('mail') ? 'ok' : 'fail' ?>">
            <strong>Função mail() do PHP</strong>
            <span class="diag-status <?= function_exists('mail') ? 'ok' : 'fail' ?>">
                <?= function_exists('mail') ? 'Habilitada (Fallback Ativo)' : 'Desabilitada' ?>
            </span>
        </div>

        <div class="diag-card <?= file_exists(__DIR__ . '/vendor/autoload.php') ? 'ok' : 'fail' ?>">
            <strong>Biblioteca PHPMailer</strong>
            <span class="diag-status <?= file_exists(__DIR__ . '/vendor/autoload.php') ? 'ok' : 'fail' ?>">
                <?= file_exists(__DIR__ . '/vendor/autoload.php') ? 'Instalada (vendor/autoload.php)' : 'Ausente (Necessita composer install)' ?>
            </span>
        </div>

        <div class="diag-card <?= $testeConexao['brevo_443']['ok'] ? 'ok' : 'fail' ?>">
            <strong>HTTPS cURL (Porta 443)</strong>
            <span class="diag-status <?= $testeConexao['brevo_443']['ok'] ? 'ok' : 'fail' ?>">
                <?= $testeConexao['brevo_443']['ok'] ? 'Aberta (APIs HTTPS permitidas)' : $testeConexao['brevo_443']['msg'] ?>
            </span>
        </div>

        <div class="diag-card <?= $testeConexao['gmail_587']['ok'] ? 'ok' : 'fail' ?>">
            <strong>Porta SMTP 587 (TLS)</strong>
            <span class="diag-status <?= $testeConexao['gmail_587']['ok'] ? 'ok' : 'fail' ?>">
                <?= $testeConexao['gmail_587']['ok'] ? 'Aberta (SMTP liberado)' : 'Bloqueada pela hospedagem' ?>
            </span>
        </div>

        <div class="diag-card <?= $testeConexao['gmail_465']['ok'] ? 'ok' : 'fail' ?>">
            <strong>Porta SMTP 465 (SSL)</strong>
            <span class="diag-status <?= $testeConexao['gmail_465']['ok'] ? 'ok' : 'fail' ?>">
                <?= $testeConexao['gmail_465']['ok'] ? 'Aberta (SMTP liberado)' : 'Bloqueada pela hospedagem' ?>
            </span>
        </div>

        <div class="diag-card">
            <strong>Configuração Atual</strong>
            <span class="diag-status">
                Host: <?= htmlspecialchars(SMTP_HOST ?: '(Vazio - usando APIs / mail())') ?><br>
                Porta: <?= (int) SMTP_PORT ?><br>
                Brevo API: <?= !empty(BREVO_API_KEY) ? 'Configurada' : 'Não configurada' ?>
            </span>
        </div>
    </div>

    <!-- Formulário de Teste -->
    <div class="form-teste">
        <strong style="font-size: 1.05rem;">Disparar E-mail de Teste</strong>
        <p style="margin: 0.25rem 0 0.75rem 0; font-size: 0.9rem; color: #666;">
            Insira seu e-mail abaixo para disparar um e-mail de recuperação de teste e avaliar a entrega.
        </p>
        <form method="POST" action="test_email.php">
            <div class="form-row">
                <input type="email" name="email_teste" placeholder="seu-email@exemplo.com" required
                       value="<?= htmlspecialchars($_POST['email_teste'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Testar Envio</button>
            </div>
        </form>
    </div>

    <!-- Logs Recentes -->
    <strong>Últimos registros de envio (logs/recuperacao.log):</strong>
    <div class="logs-box">
        <?php if (empty($logsRecentes)): ?>
            Nenhum log registrado até o momento.
        <?php else: ?>
            <?php foreach ($logsRecentes as $linha): ?>
                <div class="log-line"><?= htmlspecialchars($linha, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <p><a href="esqueci_senha.php" class="btn-voltar">← Voltar para Esqueci a Senha</a></p>
</div>

</body>
</html>
