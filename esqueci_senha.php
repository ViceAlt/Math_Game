<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/recuperacao.php';
require_once __DIR__ . '/includes/senha.php';
require_once __DIR__ . '/includes/icones.php';

sessao_iniciar();
if (!empty($_SESSION['usuario'])) {
    header('Location: home/home.php');
    exit;
}

$csrfToken = csrf_gerar();
$mensagem = '';
$tipoMensagem = 'sucesso';
$etapaSenha = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $mensagem = 'Requisição expirada ou inválida. Recarregue a página e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif (($_POST['acao'] ?? '') === 'validar') {
        $username = trim($_POST['username'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $secret = trim($_POST['secret_id'] ?? '');
        $ip = recuperacao_obter_ip();
        $etapaSenha = false;

        if (strlen($username) < 3 || strlen($username) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($secret) < 20 || strlen($secret) > 100) {
            $mensagem = 'Os dados não correspondem a uma conta válida. Confira as informações e tente novamente.';
            $tipoMensagem = 'erro';
        } elseif (recuperacao_rate_limit_atingido($email, $ip)) {
            $mensagem = 'Muitas tentativas. Aguarde e tente novamente mais tarde.';
            $tipoMensagem = 'erro';
        } else {
            recuperacao_registrar_tentativa('recuperacao', $email);
            recuperacao_registrar_tentativa('recuperacao_ip', $ip);

            try {
                $pdo = getDB();
                $stmt = $pdo->prepare(
                    'SELECT id, recovery_secret_hash FROM usuarios WHERE username = ? AND LOWER(email) = ? LIMIT 1'
                );
                $stmt->execute([$username, $email]);
                $usuario = $stmt->fetch();
                $hashInformado = hash('sha256', $secret);
                $hashArmazenado = is_array($usuario) && is_string($usuario['recovery_secret_hash'] ?? null)
                    ? $usuario['recovery_secret_hash']
                    : str_repeat('0', 64);
                $segredoValido = hash_equals($hashArmazenado, $hashInformado);

                if (!$usuario || empty($usuario['recovery_secret_hash']) || !$segredoValido) {
                    $mensagem = 'Os dados não correspondem a uma conta válida. Confira as informações e tente novamente.';
                    $tipoMensagem = 'erro';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['recovery_user_id'] = (int) $usuario['id'];
                    $_SESSION['recovery_expires_at'] = time() + 600;
                    $etapaSenha = true;
                    $csrfToken = csrf_regenerar();
                }
            } catch (Throwable $e) {
                recuperacao_log('Erro ao validar identificador de recuperação: ' . $e->getMessage(), 'ERROR');
                $mensagem = 'Não foi possível validar os dados agora. Tente novamente mais tarde.';
                $tipoMensagem = 'erro';
            }
        }
    } elseif (($_POST['acao'] ?? '') === 'redefinir') {
        $usuarioId = (int) ($_SESSION['recovery_user_id'] ?? 0);
        $expiraEm = (int) ($_SESSION['recovery_expires_at'] ?? 0);
        $etapaSenha = $usuarioId > 0 && $expiraEm >= time();
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmaSenha = $_POST['confirma_senha'] ?? '';

        if (!$etapaSenha) {
            unset($_SESSION['recovery_user_id'], $_SESSION['recovery_expires_at']);
            $mensagem = 'A validação expirou. Informe novamente os dados e o identificador de recuperação.';
            $tipoMensagem = 'erro';
        } else {
            $erroSenha = validar_forca_senha($novaSenha);
            if ($erroSenha !== null) {
                $mensagem = $erroSenha;
                $tipoMensagem = 'erro';
            } elseif ($novaSenha !== $confirmaSenha) {
                $mensagem = 'As senhas informadas não conferem.';
                $tipoMensagem = 'erro';
            } else {
                try {
                    $pdo = getDB();
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = ?, senha_alterada_em = NOW() WHERE id = ?');
                    $stmt->execute([gerar_hash_senha($novaSenha), $usuarioId]);
                    $pdo->prepare('DELETE FROM login_persistente WHERE usuario_id = ?')->execute([$usuarioId]);
                    $pdo->prepare('UPDATE recuperacao_senha SET usado_em = NOW() WHERE usuario_id = ? AND usado_em IS NULL')
                        ->execute([$usuarioId]);
                    $pdo->commit();

                    unset($_SESSION['recovery_user_id'], $_SESSION['recovery_expires_at']);
                    session_regenerate_id(true);
                    header('Location: login/login.php?aviso=senha_redefinida');
                    exit;
                } catch (Throwable $e) {
                    if (isset($pdo) && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    recuperacao_log('Erro ao redefinir senha via identificador: ' . $e->getMessage(), 'ERROR');
                    $mensagem = 'Não foi possível alterar a senha agora. Tente novamente mais tarde.';
                    $tipoMensagem = 'erro';
                }
            }
        }
    } else {
        $mensagem = 'Requisição inválida. Tente novamente.';
        $tipoMensagem = 'erro';
    }

    if ($tipoMensagem === 'erro') {
        $etapaSenha = (int) ($_SESSION['recovery_user_id'] ?? 0) > 0
            && (int) ($_SESSION['recovery_expires_at'] ?? 0) >= time();
        $csrfToken = csrf_regenerar();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recupere o acesso à sua conta no Mantham">
    <title>Esqueci a Senha | Mantham</title>
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
        <div class="auth-brand-logo">
            <a href="index.php" class="auth-brand-link" aria-label="Mantham - Início">
                <img src="icons/logo.webp" alt="Mantham" class="auth-brand-logo-img">
            </a>
        </div>

        <h1>Recuperar Senha</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
            Informe seu nome de usuário, e-mail e identificador secreto para redefinir a senha nesta página.
        </p>

        <?php if ($mensagem): ?>
            <div class="<?= $tipoMensagem === 'sucesso' ? 'aviso-sucesso' : 'aviso-erro-card' ?>" role="<?= $tipoMensagem === 'sucesso' ? 'status' : 'alert' ?>" aria-live="polite">
                <?= icone_svg($tipoMensagem === 'sucesso' ? 'mail' : 'warning', 'icone-inline', 18) ?>
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($etapaSenha): ?>
            <form method="POST" action="esqueci_senha.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="acao" value="redefinir">
                <div class="form-group">
                    <label for="nova-senha">Nova senha</label>
                    <input type="password" id="nova-senha" name="nova_senha" autocomplete="new-password" minlength="8" maxlength="72" required placeholder="Mínimo de 8 caracteres">
                </div>
                <div class="form-group">
                    <label for="confirma-senha">Confirmar nova senha</label>
                    <input type="password" id="confirma-senha" name="confirma_senha" autocomplete="new-password" minlength="8" maxlength="72" required placeholder="Repita a nova senha">
                </div>
                <button type="submit">Salvar nova senha</button>
            </form>
        <?php elseif (!$mensagem || $tipoMensagem === 'erro'): ?>
            <form id="form-esqueci" method="POST" action="esqueci_senha.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="acao" value="validar">

                <div class="form-group">
                    <label for="username">Nome de usuário</label>
                    <input type="text" id="username" name="username" autocomplete="username" minlength="3" maxlength="50" required
                        placeholder="Seu nome de usuário">
                </div>

                <div class="form-group">
                    <label for="email">E-mail cadastrado</label>
                    <input type="email" id="email" name="email" autocomplete="email" required
                        placeholder="Seu e-mail cadastrado">
                </div>

                <div class="form-group">
                    <label for="secret-id">Identificador secreto</label>
                    <input type="text" id="secret-id" name="secret_id" autocomplete="off" required maxlength="100"
                        placeholder="MG-...">
                </div>

                <span class="erro" id="erro-geral" role="alert" aria-live="assertive"></span>

                <button type="submit" id="btn-enviar">Validar e continuar</button>
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