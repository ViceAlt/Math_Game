<?php
/**
 * config.php
 * Configurações da aplicação e credenciais seguras.
 * Carrega automaticamente variáveis do arquivo .env (se existir).
 */

// ── 1. Carregador de variáveis de ambiente (.env) ───────────────────────────
(function () {
    $envFiles = [__DIR__ . '/.env', __DIR__ . '/.env.local'];
    foreach ($envFiles as $envFile) {
        if (!file_exists($envFile) || !is_readable($envFile)) {
            continue;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Ignora comentários e linhas vazias
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove aspas simples ou duplas ao redor do valor
                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
})();

// Helper para ler variável de ambiente com valor padrão
if (!function_exists('app_env')) {
    function app_env(string $key, $default = null) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return $val;
    }
}

// ── 2. URL Base da Aplicação (APP_URL) ───────────────────────────────────────
if (!defined('APP_URL')) {
    $configUrl = app_env('APP_URL');
    if ($configUrl) {
        define('APP_URL', rtrim($configUrl, '/'));
    } else {
        // Detecção dinâmica de URL caso não configurada
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (PHP_SAPI === 'cli') {
            define('APP_URL', 'http://localhost/Math_Game');
        } else {
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $baseDir = preg_replace('#/(api|login|cadastro|home|conteudos|jogos|configuracoes|perfil|includes|cron).*$#', '', $scriptDir);
            $baseDir = rtrim($baseDir, '/');
            if ($baseDir === '.' || $baseDir === '/') {
                $baseDir = '';
            }
            define('APP_URL', $protocol . '://' . $host . $baseDir);
        }
    }
}

// ── 3. Banco de Dados MySQL ─────────────────────────────────────────────────
if (!defined('DB_HOST')) define('DB_HOST', app_env('DB_HOST', '127.0.0.1'));
if (!defined('DB_PORT')) define('DB_PORT', app_env('DB_PORT', '3308'));
if (!defined('DB_NAME')) define('DB_NAME', app_env('DB_NAME', 'math_game'));
if (!defined('DB_USER')) define('DB_USER', app_env('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', app_env('DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', app_env('DB_CHARSET', 'utf8mb4'));

// ── 4. Configurações SMTP (PHPMailer) ────────────────────────────────────────
// Padrão: Mailpit local (127.0.0.1:1025) ou SMTP externo configurado via .env
if (!defined('SMTP_HOST')) define('SMTP_HOST', app_env('SMTP_HOST', '127.0.0.1'));
if (!defined('SMTP_PORT')) define('SMTP_PORT', (int) app_env('SMTP_PORT', 1025));
if (!defined('SMTP_AUTH')) {
    $smtpAuthVal = app_env('SMTP_AUTH', 'false');
    define('SMTP_AUTH', filter_var($smtpAuthVal, FILTER_VALIDATE_BOOLEAN));
}
if (!defined('SMTP_USER')) define('SMTP_USER', app_env('SMTP_USER', ''));
if (!defined('SMTP_PASS')) define('SMTP_PASS', app_env('SMTP_PASS', ''));
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', strtolower((string) app_env('SMTP_SECURE', ''))); // 'tls', 'ssl' ou ''
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', app_env('SMTP_FROM_EMAIL', 'nao-responda@mathgame.local'));
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', app_env('SMTP_FROM_NAME', 'Math Game'));

// Ativa log detalhado do SMTP para diagnóstico (grava em logs/recuperacao.log)
if (!defined('SMTP_DEBUG')) {
    $debugVal = app_env('SMTP_DEBUG', 'false');
    define('SMTP_DEBUG', filter_var($debugVal, FILTER_VALIDATE_BOOLEAN));
}

// Tempo de expiração do token de recuperação de senha (em minutos)
if (!defined('TOKEN_EXPIRACAO_MINUTOS')) {
    define('TOKEN_EXPIRACAO_MINUTOS', (int) app_env('TOKEN_EXPIRACAO_MINUTOS', 60));
}

// ── 5. Ambiente e Tratamento de Erros ───────────────────────────────────────
if (!defined('APP_ENV')) define('APP_ENV', app_env('APP_ENV', 'development'));

if (defined('APP_ENV') && APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
