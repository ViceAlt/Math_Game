<?php
/**
 * config.php
 * Configurações da aplicação e credenciais seguras.
 * Este arquivo não deve ser commitado no controle de versão se contiver senhas reais.
 */

// URL base da aplicação (sem barra no final)
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/Math_Game');
}

// Configurações do Banco de Dados (se ainda não definidas)
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3308');
if (!defined('DB_NAME')) define('DB_NAME', 'math_game');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Configurações SMTP (PHPMailer)
// Padrão para testes em ambiente de desenvolvimento (Mailpit / MailHog na porta 1025)
if (!defined('SMTP_HOST')) define('SMTP_HOST', '127.0.0.1');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 1025);
if (!defined('SMTP_AUTH')) define('SMTP_AUTH', false);
if (!defined('SMTP_USER')) define('SMTP_USER', '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', ''); // 'tls', 'ssl' ou ''
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'nao-responda@mathgame.local');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Math Game');

// Configuração de ambiente
if (!defined('APP_ENV')) define('APP_ENV', 'development');
