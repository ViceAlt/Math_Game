<?php
/**
 * includes/email.php
 * Centraliza o envio de e-mails com suporte a múltiplos transportes:
 * 1. APIs HTTPS (Brevo / Resend via porta 443 - imune a bloqueios de portas em hospedagens como ByetHost)
 * 2. PHPMailer via SMTP (TLS/SSL nas portas 587 ou 465)
 * 3. Função nativa mail() do PHP (fallback automático com cabeçalhos MIME multipart/alternative)
 * 4. Logs de diagnóstico detalhados em logs/recuperacao.log
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
    $assunto = '[Mantham] Redefinição de senha';
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
    <h2>Redefinição de senha – Mantham</h2>
    <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
    <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Mantham</strong>. Clique no botão abaixo para criar sua nova senha:</p>
    <p style='text-align: center;'>
      <a href='" . htmlspecialchars($linkRedefinicao, ENT_QUOTES, 'UTF-8') . "' class='btn' target='_blank'>Redefinir Minha Senha</a>
    </p>
    <p>Caso o botão acima não funcione, copie e cole o link abaixo diretamente no seu navegador:</p>
    <div class='link-box'>" . htmlspecialchars($linkRedefinicao, ENT_QUOTES, 'UTF-8') . "</div>
    <p class='aviso'>
      Este link expira em <strong>" . htmlspecialchars($tempoTexto, ENT_QUOTES, 'UTF-8') . "</strong> e é de uso único.<br>
      Se você não solicitou a troca de senha, desconsidere este e-mail com segurança.
    </p>
  </div>
</body>
</html>";

    $textoPlano = "Redefinição de senha – Mantham\n\n"
        . "Olá, {$nomeDestino}!\n\n"
        . "Recebemos uma solicitação para redefinir a senha da sua conta no Mantham.\n"
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
    $assunto = '[Mantham] Sua senha foi alterada com sucesso';

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
    <h2>Senha alterada – Mantham</h2>
    <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
    <p>A senha da sua conta no <strong>Mantham</strong> foi alterada com sucesso.</p>
    <div class='alerta'>
      <strong>Não reconhece essa alteração?</strong><br>
      Se você não realizou essa mudança, entre em contato imediatamente com o suporte ou utilize a opção \"Esqueci a senha\" para recuperar seu acesso.
    </div>
  </div>
</body>
</html>";

    $textoPlano = "Senha alterada – Mantham\n\n"
        . "Olá, {$nomeDestino}!\n\n"
        . "A senha da sua conta no Mantham foi alterada com sucesso.\n\n"
        . "Se você não realizou essa alteração, acesse imediatamente o site e utilize 'Esqueci a senha' para recuperar sua conta.";

    return _enviar_email($emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano);
}

/**
 * Orquestrador principal de envio de e-mails com fallback inteligente:
 * 1º - Tenta API HTTPS (Brevo / Resend) se houver chave configurada (100% imune a bloqueios de porta).
 * 2º - Tenta PHPMailer SMTP se houver host e vendor/autoload configurados.
 * 3º - Tenta mail() nativo do PHP se os métodos anteriores falharem ou não estiverem configurados.
 */
function _enviar_email(
    string $emailDestino,
    string $nomeDestino,
    string $assunto,
    string $htmlCorpo,
    string $textoPlano
): bool {
    $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'nao-responda@mantham.web1337.net';
    $fromName  = defined('SMTP_FROM_NAME')  ? SMTP_FROM_NAME  : 'Mantham';

    // ── 1. Tenta API Brevo (Sendinblue) via HTTPS (Porta 443) ───────────────
    $brevoKey = defined('BREVO_API_KEY') ? trim((string) BREVO_API_KEY) : '';
    if (!empty($brevoKey)) {
        recuperacao_log("Tentando envio via API Brevo (HTTPS porta 443) para '{$emailDestino}'...", 'INFO');
        if (_enviar_via_brevo_api($brevoKey, $emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano, $fromEmail, $fromName)) {
            return true;
        }
        recuperacao_log("Falha no transporte Brevo API. Passando para o próximo método...", 'WARNING');
    }

    // ── 2. Tenta API Resend via HTTPS (Porta 443) ───────────────────────────
    $resendKey = defined('RESEND_API_KEY') ? trim((string) RESEND_API_KEY) : '';
    if (!empty($resendKey)) {
        recuperacao_log("Tentando envio via API Resend (HTTPS porta 443) para '{$emailDestino}'...", 'INFO');
        if (_enviar_via_resend_api($resendKey, $emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano, $fromEmail, $fromName)) {
            return true;
        }
        recuperacao_log("Falha no transporte Resend API. Passando para o próximo método...", 'WARNING');
    }

    // ── 3. Tenta PHPMailer via SMTP ─────────────────────────────────────────
    $smtpHost = defined('SMTP_HOST') ? trim((string) SMTP_HOST) : '';
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

    if (!empty($smtpHost) && file_exists($autoloadPath)) {
        recuperacao_log("Tentando envio via PHPMailer SMTP ({$smtpHost}) para '{$emailDestino}'...", 'INFO');
        if (_enviar_via_phpmailer($autoloadPath, $emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano, $fromEmail, $fromName)) {
            return true;
        }
        recuperacao_log("Falha no transporte SMTP. Passando para o fallback nativo...", 'WARNING');
    } elseif (!empty($smtpHost) && !file_exists($autoloadPath)) {
        recuperacao_log("SMTP configurado mas vendor/autoload.php não encontrado no servidor. Pulando PHPMailer...", 'WARNING');
    }

    // ── 4. Fallback: Função nativa mail() do PHP ────────────────────────────
    $permitirFallbackMail = !defined('FALLBACK_PHP_MAIL') || FALLBACK_PHP_MAIL;
    if ($permitirFallbackMail) {
        recuperacao_log("Tentando envio via função nativa mail() do PHP para '{$emailDestino}'...", 'INFO');
        if (_enviar_via_php_mail($emailDestino, $nomeDestino, $assunto, $htmlCorpo, $textoPlano, $fromEmail, $fromName)) {
            return true;
        }
    }

    recuperacao_log("ERRO CRÍTICO: Todos os métodos de envio de e-mail falharam para '{$emailDestino}'.", 'ERROR');
    return false;
}

/**
 * Envia e-mail via PHPMailer SMTP.
 */
function _enviar_via_phpmailer(
    string $autoloadPath,
    string $emailDestino,
    string $nomeDestino,
    string $assunto,
    string $htmlCorpo,
    string $textoPlano,
    string $fromEmail,
    string $fromName
): bool {
    require_once $autoloadPath;

    $mail = new PHPMailer(true);

    try {
        $host   = defined('SMTP_HOST') ? SMTP_HOST : '127.0.0.1';
        $port   = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
        $auth   = defined('SMTP_AUTH') ? (bool) SMTP_AUTH : false;
        $user   = defined('SMTP_USER') ? SMTP_USER : '';
        $pass   = defined('SMTP_PASS') ? SMTP_PASS : '';
        $secure = defined('SMTP_SECURE') ? strtolower((string) SMTP_SECURE) : '';
        $debug  = defined('SMTP_DEBUG') ? (bool) SMTP_DEBUG : false;

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = $auth;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 8; // Timeout curto (8s) para evitar travar requisições se a porta estiver bloqueada

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

        if ($debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) {
                recuperacao_log(rtrim($str), 'SMTP_DEBUG');
            };
        } else {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($emailDestino, $nomeDestino);
        $mail->addReplyTo($fromEmail, $fromName);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $htmlCorpo;
        $mail->AltBody = $textoPlano;

        $mail->send();

        recuperacao_log("E-mail enviado com sucesso via PHPMailer SMTP para '{$emailDestino}'!", 'SUCCESS');
        return true;

    } catch (MailException $e) {
        $detalhesErro = $mail->ErrorInfo ?: $e->getMessage();
        recuperacao_log("Falha no PHPMailer SMTP para '{$emailDestino}': {$detalhesErro}", 'ERROR');
        return false;
    } catch (\Throwable $e) {
        recuperacao_log("Erro inesperado no PHPMailer SMTP: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Envia e-mail via API REST HTTPS da Brevo (antiga Sendinblue).
 * Porta 443 HTTPS - Funciona em qualquer hospedagem mesmo com portas SMTP bloqueadas.
 */
function _enviar_via_brevo_api(
    string $apiKey,
    string $emailDestino,
    string $nomeDestino,
    string $assunto,
    string $htmlCorpo,
    string $textoPlano,
    string $fromEmail,
    string $fromName
): bool {
    if (!function_exists('curl_init')) {
        recuperacao_log("Extensão cURL indisponível para Brevo API.", 'WARNING');
        return false;
    }

    $url = 'https://api.brevo.com/v3/smtp/email';
    $payload = [
        'sender' => ['name' => $fromName, 'email' => $fromEmail],
        'to' => [['email' => $emailDestino, 'name' => $nomeDestino ?: $emailDestino]],
        'subject' => $assunto,
        'htmlContent' => $htmlCorpo,
        'textContent' => $textoPlano,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . trim($apiKey),
            'content-type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        recuperacao_log("E-mail enviado com sucesso via Brevo API para '{$emailDestino}'!", 'SUCCESS');
        return true;
    }

    recuperacao_log("Falha no envio via Brevo API (HTTP {$httpCode}): {$response} | cURL erro: {$curlErr}", 'WARNING');
    return false;
}

/**
 * Envia e-mail via API REST HTTPS da Resend.
 * Porta 443 HTTPS.
 */
function _enviar_via_resend_api(
    string $apiKey,
    string $emailDestino,
    string $nomeDestino,
    string $assunto,
    string $htmlCorpo,
    string $textoPlano,
    string $fromEmail,
    string $fromName
): bool {
    if (!function_exists('curl_init')) {
        recuperacao_log("Extensão cURL indisponível para Resend API.", 'WARNING');
        return false;
    }

    $url = 'https://api.resend.com/emails';
    $payload = [
        'from'    => "{$fromName} <{$fromEmail}>",
        'to'      => [$emailDestino],
        'subject' => $assunto,
        'html'    => $htmlCorpo,
        'text'    => $textoPlano,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . trim($apiKey),
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        recuperacao_log("E-mail enviado com sucesso via Resend API para '{$emailDestino}'!", 'SUCCESS');
        return true;
    }

    recuperacao_log("Falha no envio via Resend API (HTTP {$httpCode}): {$response} | cURL erro: {$curlErr}", 'WARNING');
    return false;
}

/**
 * Envia e-mail utilizando a função nativa mail() do PHP.
 * Constrói corpo MIME multipart/alternative compatível com todos os clientes de e-mail.
 */
function _enviar_via_php_mail(
    string $emailDestino,
    string $nomeDestino,
    string $assunto,
    string $htmlCorpo,
    string $textoPlano,
    string $fromEmail,
    string $fromName
): bool {
    if (!function_exists('mail')) {
        recuperacao_log('Função mail() nativa do PHP desabilitada neste servidor.', 'WARNING');
        return false;
    }

    $boundary = '=_Mantham_' . md5(uniqid((string) time(), true));

    $encodedSubject  = '=?UTF-8?B?' . base64_encode($assunto) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
    $headers[] = "Reply-To: {$fromEmail}";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

    $corpo = "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $corpo .= chunk_split(base64_encode($textoPlano)) . "\r\n";

    $corpo .= "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/html; charset=UTF-8\r\n";
    $corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $corpo .= chunk_split(base64_encode($htmlCorpo)) . "\r\n";

    $corpo .= "--{$boundary}--\r\n";

    $destinatario = !empty($nomeDestino)
        ? '=?UTF-8?B?' . base64_encode($nomeDestino) . "?= <{$emailDestino}>"
        : $emailDestino;

    $extraParams = "-f{$fromEmail}";
    $sucesso = @mail($destinatario, $encodedSubject, $corpo, implode("\r\n", $headers), $extraParams);

    if (!$sucesso) {
        $sucesso = @mail($destinatario, $encodedSubject, $corpo, implode("\r\n", $headers));
    }

    if ($sucesso) {
        recuperacao_log("E-mail enviado com sucesso via mail() nativo para '{$emailDestino}'!", 'SUCCESS');
        return true;
    }

    recuperacao_log("Falha ao enviar via mail() nativo para '{$emailDestino}'.", 'WARNING');
    return false;
}
