<?php
/**
 * esqueci_senha.php
 * Formulário de solicitação de recuperação de senha.
 * Segue o mesmo padrão visual do login e cadastro, com logs seguros e mensagens genéricas.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/recuperacao.php';
require_once __DIR__ . '/includes/email.php';

sessao_iniciar();

// Se o usuário já estiver logado, redireciona para a home
if (!empty($_SESSION['usuario'])) {
    header('Location: home/home.php');
    exit;
}

$csrfToken = csrf_gerar();
$mensagem = '';
$tipoMensagem = 'sucesso'; // 'sucesso' ou 'erro'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfEnviado = $_POST['csrf_token'] ?? '';

    // Valida CSRF
    if (!csrf_validar($csrfEnviado)) {
        recuperacao_log('Tentativa de solicitação de senha rejeitada: CSRF inválido', 'WARNING');
        $mensagem = 'Requisição expirada ou inválida. Recarregue a página e tente novamente.';
        $tipoMensagem = 'erro';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $ip = recuperacao_obter_ip();

        recuperacao_log("Recebida solicitação de redefinição para o e-mail: '{$email}'", 'INFO');

        // Valida formato do email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            recuperacao_log("Formato de e-mail inválido recebido: '{$email}'", 'WARNING');
            // Mantém mensagem genérica por segurança
            $mensagem = 'Se esse e-mail estiver cadastrado, enviaremos um link para redefinir a senha.';
        } else {
            try {
                // Rate limit: verifica se o limite foi excedido
                if (recuperacao_rate_limit_atingido($email, $ip)) {
                    // Mensagem genérica para não revelar existência nem alertar atacantes
                    $mensagem = 'Se esse e-mail estiver cadastrado, enviaremos um link para redefinir a senha.';
                } else {
                    // Registra a tentativa para AMBOS (e-mail e IP)
                    recuperacao_registrar_tentativa('recuperacao', $email);
                    recuperacao_registrar_tentativa('recuperacao_ip', $ip);

                    // Busca o usuário via prepared statement
                    $pdo = getDB();
                    $stmt = $pdo->prepare(
                        'SELECT id, username, email FROM usuarios WHERE email = ? LIMIT 1'
                    );
                    $stmt->execute([$email]);
                    $usuario = $stmt->fetch();

                    if ($usuario) {
                        recuperacao_log("Usuário localizado: id={$usuario['id']}, username='{$usuario['username']}'", 'INFO');

                        // Gera token seguro (apenas o hash é gravado no banco)
                        $token = recuperacao_gerar_token((int) $usuario['id'], $ip);
                        $link = rtrim(APP_URL, '/') . '/redefinir_senha.php?token=' . urlencode($token);

                        recuperacao_log("Link de redefinição montado: " . preg_replace('/token=[a-f0-9]{32}/', 'token=***REDACTED***', $link), 'INFO');

                        // Dispara o e-mail via PHPMailer + SMTP
                        $enviado = email_enviar_recuperacao($usuario['email'], $usuario['username'], $link);

                        if (!$enviado) {
                            recuperacao_log("ATENÇÃO: A função de envio de e-mail retornou FALSE para {$usuario['email']}. Verifique as configurações de SMTP no .env", 'ERROR');
                        }
                    } else {
                        recuperacao_log("E-mail '{$email}' não encontrado na base de usuários. Nenhuma mensagem enviada.", 'INFO');
                    }

                    // Limpeza periódica de registros antigos
                    recuperacao_limpar_dados_antigos();

                    // Resposta sempre genérica na interface
                    $mensagem = 'Se esse e-mail estiver cadastrado, enviaremos um link para redefinir a senha.';
                }
            } catch (\PDOException $e) {
                recuperacao_log("Erro de banco de dados em esqueci_senha.php: " . $e->getMessage(), 'ERROR');
                $mensagem = 'Não foi possível processar sua solicitação no momento. Tente novamente mais tarde.';
                $tipoMensagem = 'erro';
            } catch (\Throwable $e) {
                recuperacao_log("Erro geral em esqueci_senha.php: " . $e->getMessage(), 'ERROR');
                $mensagem = 'Não foi possível processar sua solicitação no momento. Tente novamente mais tarde.';
                $tipoMensagem = 'erro';
            }
        }

        // Regenera o token CSRF após o processamento
        $csrfToken = csrf_regenerar();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recupere o acesso à sua conta no Math Game">
    <title>Esqueci a Senha | Math Game</title>
    <link rel="stylesheet" href="tema.css">
    <link rel="stylesheet" href="login/login.css">
    <script src="tema.js"></script>
    <style>
        .aviso-sucesso {
            background: #f0fff4;
            border: 2px solid var(--duo-green, #58cc02);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: #2d6a2d;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .aviso-erro-card {
            background: #fff0f0;
            border: 2px solid var(--duo-red, #ff4b4b);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: #8b1a1a;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 1.25rem;
            text-align: left;
        }

        body.tema-escuro .aviso-sucesso {
            background: #1a2e1a;
            color: #7ed67e;
        }

        body.tema-escuro .aviso-erro-card {
            background: #2e1a1a;
            color: #ff9e9e;
        }
    </style>
</head>

<body>

    <main class="main" id="main-content">
        <h1>Recuperar Senha</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
            Informe seu e-mail cadastrado e enviaremos um link seguro para criar uma nova senha.
        </p>

        <?php if ($mensagem): ?>
            <?php if ($tipoMensagem === 'sucesso'): ?>
                <div class="aviso-sucesso" role="status" aria-live="polite">
                    ✉️ <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <div class="aviso-erro-card" role="alert" aria-live="assertive">
                    ⚠️ <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$mensagem || $tipoMensagem === 'erro'): ?>
            <form id="form-esqueci" method="POST" action="esqueci_senha.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" autocomplete="email" required aria-required="true"
                        placeholder="Seu e-mail cadastrado">
                </div>

                <span class="erro" id="erro-geral" role="alert" aria-live="assertive"></span>

                <button type="submit" id="btn-enviar">Enviar link</button>
            </form>
        <?php endif; ?>

        <p><a href="login/login.php" id="link-voltar-login">← Voltar para o login</a></p>
    </main>

    <script>
        (function () {
            const tema = localStorage.getItem('mg_tema');
            const daltonismo = localStorage.getItem('mg_daltonismo');
            const tonalidade = localStorage.getItem('mg_tonalidade');
            if (tema === 'escuro') document.body.classList.add('tema-escuro');
            if (daltonismo) document.body.classList.add('daltonismo-' + daltonismo);
            if (tonalidade && tonalidade !== 'padrao') document.body.classList.add('tonalidade-' + tonalidade);
        })();
    </script>
</body>

</html>