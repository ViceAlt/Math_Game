<?php
/**
 * includes/email.php
 * Centraliza o envio de e-mails via PHPMailer + SMTP com suporte a diagnóstico e logs seguros.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/logger.php';

/**
 * Envia e-mail de recuperação de senha com o link de redefinição.
 *
 * @param string $emailDestino E-mail do destinatário.
 * @param string $nomeDestino  Nome do destinatário.
 * @param string $linkRedefinicao Link completo para redefinição.
 * @return bool True se o e-mail foi enviado com sucesso, false caso contrário.
 */
function email_enviar_recuperacao(string $emailDestino, string $nomeDestino, string $linkRedefinicao): bool {
    $assunto = '[Math Game] Redefinição de senha';
    $minutosExpiracao = defined('TOKEN_EXPIRACAO_MINUTOS') ? TOKEN_EXPIRACAO_MINUTOS : 60;
    $tempoTexto = ($minutosExpiracao >= 60 && $minutosExpiracao % 60 === 0)
        ? ($minutosExpiracao / 60) . ' hora' . ($minutosExpiracao > 60 ? 's' : '')
        : $minutosExpiracao . ' minutos';

    $htmlCorpo = "
<!DOCTYPE html>
<html lang='pt-br'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Redefinição de Senha</title>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f7f7f7; padding: 20px; color: #3c3c3c; margin: 0; }
    .card { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); border: 1px solid #e5e5e5; }
    h2 { color: #1cb0f6; margin-top: 0; font-size: 22px; }
    p { font-size: 15px; line-height: 1.5; margin: 14px 0; }
    .btn { display: inline-block; margin: 20px 0; padding: 14px 28px; background-color: #1cb0f6; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; }
    .link-box { word-break: break-all; background: #f0f4f8; padding: 12px; border-radius: 8px; font-size: 13px; color: #1c74d9; margin-top: 10px; }
    .aviso { font-size: 13px; color: #777777; margin-top: 24px; border-top: 1px solid #eeeeee; padding-top: 14px; }
  </style>
</head>
<body>
  <div class='card'>
    <h2>🔐 Redefinição de senha – Math Game</h2>
    <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
    <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Math Game</strong>. Clique no botão abaixo para criar sua nova senha:</p>
    <p style='text-align: center;'>
      <a href='" . htmlspecialchars($linkRedefinicao, ENT_QUOTES, 'UTF-8') . "' class='btn' target='_blank'>Redefinir Minha Senha</a>
    </p>
    <p>Caso o botão acima não funcione, copie e cole o link abaixo diretamente no seu navegador:</p>
    <div class='link-box'>" . htmlspecialchars($linkRedefinicao, ENT_QUOTES, 'UTF-8') . "</div>
    <p class='aviso'>
      ⏳ Este link expira em <strong>" . htmlspecialchars($tempoTexto, ENT_QUOTES, 'UTF-8') . "</strong> e é de uso único.<br>
      Se você não solicitou a troca de senha, desconsidere este e-mail com segurança.
    </p>
  </div>
</body>
</html>";

    $textoPlano = "Redefinição de senha – Math Game\n\n"
        . "Olá, {$nomeDestino}!\n\n"
        . "Recebemos uma solicitação para redefinir a senha da sua conta no Math Game.\n"
        . "Acesse o link abaixo para criar uma nova senha (válido por {$tempoTexto}):\n\n"
        . $linkRedefinicao . "\n\n"
        . "Se o link não for clicável, copie e cole no seu navegador.\n\n"
        . "Se você não solicitou a alteração, ignore este e-mail com segurança.";

    return _enviar_email($emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano);
}

/**
 * Envia e-mail de confirmação informando que a senha foi alterada com sucesso.
 *
 * @param string $emailDestino E-mail do destinatário.
 * @param string $nomeDestino  Nome do destinatário.
 * @return bool True se o e-mail foi enviado com sucesso.
 */
function email_enviar_aviso_senha_alterada(string $emailDestino, string $nomeDestino): bool {
    $assunto = '[Math Game] Sua senha foi alterada com sucesso';

    $htmlCorpo = "
<!DOCTYPE html>
<html lang='pt-br'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f7f7f7; padding: 20px; color: #3c3c3c; margin: 0; }
    .card { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h2 { color: #58cc02; margin-top: 0; }
    .alerta { background: #fff8f0; border-left: 4px solid #ff9f43; padding: 12px 16px; border-radius: 8px; margin-top: 18px; font-size: 14px; }
  </style>
</head>
<body>
  <div class='card'>
    <h2>✅ Senha alterada – Math Game</h2>
    <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
    <p>A senha da sua conta no <strong>Math Game</strong> foi alterada com sucesso.</p>
    <div class='alerta'>
      ⚠️ <strong>Não reconhece essa alteração?</strong><br>
      Se você não realizou essa mudança, entre em contato imediatamente com o suporte ou utilize a opção \"Esqueci a senha\" para recuperar seu acesso.
    </div>
  </div>
</body>
</html>";

    $textoPlano = "Senha alterada – Math Game\n\n"
        . "Olá, {$nomeDestino}!\n\n"
        . "A senha da sua conta no Math Game foi alterada com sucesso.\n\n"
        . "Se você não realizou essa alteração, acesse imediatamente o site e utilize 'Esqueci a senha' para recuperar sua conta.";

    return _enviar_email($emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano);
}

/**
 * Função interna de envio via PHPMailer.
 * Captura diagnósticos de SMTP e grava em logs/recuperacao.log.
 *
 * @param string $emailDestino
 * @param string $nomeDestino
 * @param string $assunto
 * @param string $htmlCorpo
 * @param string $textoPlano
 * @return bool
 */
function _enviar_email(
    string $emailDestino,
    string $nomeDestino,
    string $assunto,
    string $htmlCorpo,
    string $textoPlano
): bool {
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

    if (!file_exists($autoloadPath)) {
        recuperacao_log('PHPMailer não encontrado em vendor/autoload.php. Execute composer install.', 'ERROR');
        return false;
    }

    require_once $autoloadPath;

    $mail = new PHPMailer(true);

    try {
        $host   = defined('SMTP_HOST') ? SMTP_HOST : '127.0.0.1';
        $port   = defined('SMTP_PORT') ? (int) SMTP_PORT : 1025;
        $auth   = defined('SMTP_AUTH') ? (bool) SMTP_AUTH : false;
        $user   = defined('SMTP_USER') ? SMTP_USER : '';
        $pass   = defined('SMTP_PASS') ? SMTP_PASS : '';
        $secure = defined('SMTP_SECURE') ? strtolower((string) SMTP_SECURE) : '';
        $debug  = defined('SMTP_DEBUG') ? (bool) SMTP_DEBUG : false;

        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'nao-responda@mathgame.local';
        $fromName  = defined('SMTP_FROM_NAME')  ? SMTP_FROM_NAME  : 'Math Game';

        // Configurações do SMTP
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = $auth;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10; // Timeout de conexão (evita congelar a requisição)

        // Criptografia TLS/SSL
        if ($secure === 'tls' || $secure === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secure === 'ssl' || $secure === 'smtps') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        if ($auth) {
            $mail->Username = $user;
            $mail->Password = $pass;
        }

        // Depuração detalhada do SMTP direcionada para o arquivo de log
        if ($debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) {
                recuperacao_log(rtrim($str), 'SMTP_DEBUG');
            };
        } else {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
        }

        recuperacao_log(sprintf(
            "Iniciando envio para '%s' via SMTP (%s:%d, auth=%s, secure='%s', from='%s')",
            $emailDestino,
            $host,
            $port,
            $auth ? 'sim' : 'nao',
            $secure ?: 'nenhuma',
            $fromEmail
        ), 'INFO');

        // Remetente e destinatário
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($emailDestino, $nomeDestino);
        $mail->addReplyTo($fromEmail, $fromName);

        // Formato multipart (HTML + Texto Puro)
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $htmlCorpo;
        $mail->AltBody = $textoPlano;

        $mail->send();

        recuperacao_log("E-mail enviado com sucesso para '{$emailDestino}'!", 'SUCCESS');
        return true;

    } catch (MailException $e) {
        $detalhesErro = $mail->ErrorInfo ?: $e->getMessage();
        recuperacao_log("Falha no envio de e-mail para '{$emailDestino}': {$detalhesErro}", 'ERROR');
        return false;
    } catch (\Throwable $e) {
        recuperacao_log("Erro inesperado no envio de e-mail: " . $e->getMessage(), 'ERROR');
        return false;
    }
}
