<?php
/**
 * config.example.php
 * Modelo de configuração para o ambiente da aplicação.
 * Copie este arquivo para config.php e ajuste com suas credenciais.
 */

// URL base da aplicação (sem barra no final)
// Usado na geração de links seguros de recuperação de senha
define('APP_URL', 'http://localhost/Math_Game');

// Configurações do Banco de Dados
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3308');
define('DB_NAME', 'math_game');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações SMTP (PHPMailer)
// Para testes locais com Mailpit / MailHog:
// Host: '127.0.0.1', Port: 1025, User: '', Pass: '', Secure: ''
// Para servidores reais (ex: Gmail, SendGrid):
// Host: 'smtp.gmail.com', Port: 587, Secure: 'tls'
define('SMTP_HOST', '127.0.0.1');
define('SMTP_PORT', 1025);
define('SMTP_AUTH', false);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', ''); // 'tls', 'ssl' ou ''
define('SMTP_FROM_EMAIL', 'nao-responda@mathgame.local');
define('SMTP_FROM_NAME', 'Math Game');

// Configuração de ambiente ('development' ou 'production')
define('APP_ENV', 'development');
