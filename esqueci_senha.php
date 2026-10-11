<?php
/**
 * esqueci_senha.php
 * Formulário de solicitação de recuperação de senha.
 * Segue o mesmo estilo visual do login e cadastro.
 */

require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/recuperacao.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/config.php';

sessao_iniciar();

// Se já logado, redireciona para home
if (!empty($_SESSION['usuario'])) {
    header('Location: home/home.php');
    exit;
}

$csrfToken = csrf_gerar();
$mensagem = '';
$erro = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfEnviado = $_POST['csrf_token'] ?? '';

    // Valida CSRF
    if (!csrf_validar($csrfEnviado)) {
        $mensagem = 'Requisição inválida. Recarregue a página e tente novamente.';
        $erro = true;
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        // Valida formato do email (sem revelar se existe ou não)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Exibe mensagem genérica e não revela se o email é inválido ou se existe
            $mensagem = 'Se esse e-mail estiver cadastrado, enviaremos um link para redefinir a senha.';
        } else {
            $ip = recuperacao_obter_ip();

            try {
                // Rate limit: verifica se o limite foi excedido
                if (recuperacao_rate_limit_atingido($email, $ip)) {
                    $mensagem = 'Se esse e-mail estiver cadastrado, enviaremos um link para redefinir a senha.';
                } else {
                    // Registra a tentativa para AMBOS (e-mail cadastrado ou não)
                    recuperacao_registrar_tentativa('recuperacao', $email);
                    recuperacao_registrar_tentativa('recuperacao_ip', $ip);

                    // Busca o usuário de forma segura
                    $pdo = getDB();
                    $stmt = $pdo->prepare(
                        'SELECT id, username, email FROM usuarios WHERE email = ? LIMIT 1'
                    );
                    $stmt->execute([$email]);
                    $usuario = $stmt->fetch();

                    if ($usuario) {
                        // Gera token e envia e-mail apenas se o usuário existir
                        $token = recuperacao_gerar_token((int) $usuario['id'], $ip);
                        $link = rtrim(APP_URL, '/') . '/redefinir_senha.php?token=' . urlencode($token);
                        email_enviar_recuperacao($usuario['email'], $usuario['username'], $link);
                    }

                    // Limpeza oportunista de dados antigos com mais de 24h
                    recuperacao_limpar_dados_antigos();

                    // Mesma mensagem genérica se o e-mail existir ou não
                    $mensagem = 'Se esse e-mail estiver cadastrado, enviaremos um link para redefinir a senha.';
                }
            } catch (\PDOException $e) {
                error_log('[Math Game] Erro de banco de dados em esqueci_senha.php: ' . $e->getMessage());
                $mensagem = 'Não foi possível processar sua solicitação agora. Tente novamente mais tarde.';
            } catch (\Throwable $e) {
                error_log('[Math Game] Erro em esqueci_senha.php: ' . $e->getMessage());
                $mensagem = 'Não foi possível processar sua solicitação agora. Tente novamente mais tarde.';
            }
        }

        // Regenera o token CSRF após o POST
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
        /* Estilo adicional para o aviso de sucesso */
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

        body.tema-escuro .aviso-sucesso {
            background: #1a2e1a;
            color: #7ed67e;
        }
    </style>
</head>

<body>

    <main class="main" id="main-content">
        <h1>Recuperar Senha</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
            Informe seu e-mail cadastrado e enviaremos um link para criar uma nova senha.
        </p>

        <?php if ($mensagem): ?>
            <div class="aviso-sucesso" role="status" aria-live="polite">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$mensagem): ?>
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
        // Aplica preferências visuais salvas no localStorage
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