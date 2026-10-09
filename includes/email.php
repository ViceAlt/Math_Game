<?php
/**
 * includes/email.php
 * Centraliza o envio de e-mails via PHPMailer + SMTP.
 * Em caso de falha, registra no log sem expor tokens ou senhas.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Envia e-mail de recuperação de senha com o link de redefinição.
 *
 * @param string $emailDestino E-mail do destinatário.
 * @param string $nomeDestino  Nome do destinatário.
 * @param string $linkRedefinicao Link completo (com token puro) para redefinição.
 * @return bool True se o e-mail foi enviado com sucesso.
 */
function email_enviar_recuperacao(string $emailDestino, string $nomeDestino, string $linkRedefinicao): bool {
    $assunto = '[Math Game] Redefinição de senha';

    $htmlCorpo = "
<!DOCTYPE html>
<html lang='pt-br'>
<head><meta charset='UTF-8'><style>
  body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 20px; color: #3c3c3c; }
  .card { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
  h2 { color: #1cb0f6; }
  .btn { display: inline-block; margin: 20px 0; padding: 14px 28px; background: #1cb0f6; color: #fff; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; }
  .aviso { font-size: 12px; color: #777; margin-top: 20px; }
</style></head>
<body>
  <div class='card'>
    <h2>🔐 Redefinição de senha – Math Game</h2>
    <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
    <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Math Game</strong>. Clique no botão abaixo para criar uma nova senha:</p>
    <a href='" . htmlspecialchars($linkRedefinicao, ENT_QUOTES, 'UTF-8') . "' class='btn'>Redefinir minha senha</a>
    <p>Se você não fez essa solicitação, ignore este e-mail. Sua senha permanece a mesma.</p>
    <p class='aviso'>Este link expira em <strong>30 minutos</strong>.</p>
  </div>
</body>
</html>";

    $textoPlano = "Redefinição de senha – Math Game\n\n"
        . "Olá, {$nomeDestino}!\n\n"
        . "Recebemos uma solicitação para redefinir a sua senha. Use o link abaixo (válido por 30 minutos):\n\n"
        . $linkRedefinicao . "\n\n"
        . "Se não foi você, ignore este e-mail.";

    return _enviar_email($emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano);
}

/**
 * Envia e-mail de aviso informando que a senha foi alterada com sucesso.
 *
 * @param string $emailDestino E-mail do destinatário.
 * @param string $nomeDestino  Nome do destinatário.
 * @return bool True se o e-mail foi enviado com sucesso.
 */
function email_enviar_aviso_senha_alterada(string $emailDestino, string $nomeDestino): bool {
    $assunto = '[Math Game] Sua senha foi alterada';

    $htmlCorpo = "
<!DOCTYPE html>
<html lang='pt-br'>
<head><meta charset='UTF-8'><style>
  body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 20px; color: #3c3c3c; }
  .card { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
  h2 { color: #58cc02; }
  .alerta { background: #fff8f0; border-left: 4px solid #ff9f43; padding: 12px 16px; border-radius: 8px; margin-top: 16px; }
</style></head>
<body>
  <div class='card'>
    <h2>✅ Senha alterada – Math Game</h2>
    <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
    <p>A senha da sua conta no <strong>Math Game</strong> foi alterada com sucesso.</p>
    <div class='alerta'>
      ⚠️ <strong>Não foi você?</strong> Entre em contato conosco imediatamente respondendo este e-mail ou acesse o site e clique em \"Esqueci minha senha\" para recuperar o acesso.
    </div>
  </div>
</body>
</html>";

    $textoPlano = "Senha alterada – Math Game\n\n"
        . "Olá, {$nomeDestino}!\n\n"
        . "A senha da sua conta no Math Game foi alterada com sucesso.\n\n"
        . "Se não foi você, entre em contato conosco imediatamente ou use a opção de recuperação de senha no site.";

    return _enviar_email($emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano);
}

/**
 * Função interna de envio via PHPMailer.
 * Não expõe tokens, links ou senhas no log de erro.
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
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($autoloadPath)) {
        error_log('[Math Game] PHPMailer não encontrado. Execute: composer install');
        return false;
    }

    require_once $autoloadPath;

    $mail = new PHPMailer(true);

    try {
        // Configuração do servidor SMTP
        $mail->isSMTP();
        $mail->Host     = defined('SMTP_HOST') ? SMTP_HOST : '127.0.0.1';
        $mail->Port     = defined('SMTP_PORT') ? (int) SMTP_PORT : 1025;
        $mail->SMTPAuth = defined('SMTP_AUTH') ? (bool) SMTP_AUTH : false;
        $mail->CharSet  = 'UTF-8';

        $secure = defined('SMTP_SECURE') ? SMTP_SECURE : '';
        if ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        if ($mail->SMTPAuth) {
            $mail->Username = defined('SMTP_USER') ? SMTP_USER : '';
            $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : '';
        }

        // Remetente e destinatário
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'nao-responda@mathgame.local';
        $fromName  = defined('SMTP_FROM_NAME')  ? SMTP_FROM_NAME  : 'Math Game';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($emailDestino, $nomeDestino);
        $mail->addReplyTo($fromEmail, $fromName);

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $htmlCorpo;
        $mail->AltBody = $textoPlano;

        $mail->send();
        return true;

    } catch (MailException $e) {
        // Registra apenas tipo e código do erro — sem expor endereços, tokens ou links
        error_log('[Math Game] Falha no envio de e-mail: ' . $mail->ErrorInfo);
        return false;
    }
}
