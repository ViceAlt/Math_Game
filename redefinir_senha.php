<?php
/**
 * redefinir_senha.php
 * Página de redefinição de senha com validação de token, mensagens claras de status e atomicidade.
 *
 * Segurança:
 * - Header Referrer-Policy: no-referrer (previne vazamento do token na URL)
 * - Validação segura via hash SHA-256 (tokens nunca são salvos ou comparados em texto puro)
 * - Transação atômica no banco (uso único com checagem de rowCount)
 * - Sem login automático após redefinição
 * - Notificação por e-mail de confirmação pós-troca
 */

// Envia cabeçalho antes de qualquer saída
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/senha.php';
require_once __DIR__ . '/includes/recuperacao.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/icones.php';

sessao_iniciar();

$tokenUrl = trim($_GET['token'] ?? '');
$csrfToken = csrf_gerar();
$mensagemErro = '';
$tokenValido = null;

// Verifica o status do token informado na URL
$statusToken = recuperacao_verificar_status_token($tokenUrl);

switch ($statusToken['status']) {
    case 'valido':
        $tokenValido = $statusToken['dados'];
        break;
    case 'expirado':
        $mensagemErro = 'Este link de recuperação expirou (a validade é de 1 hora). Por favor, solicite um novo link abaixo.';
        break;
    case 'ja_usado':
        $mensagemErro = 'Este link de recuperação já foi utilizado. Se precisar redefinir sua senha novamente, solicite um novo link.';
        break;
    case 'inexistente':
    default:
        $mensagemErro = 'Link de recuperação inválido ou inexistente. Verifique o link copiado ou solicite um novo.';
        break;
}

// ── Processamento do POST (Submissão da nova senha) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfEnviado   = $_POST['csrf_token']     ?? '';
    $tokenPost     = trim($_POST['token_recupera'] ?? '');
    $novaSenha     = $_POST['nova_senha']      ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';

    // Valida CSRF
    if (!csrf_validar($csrfEnviado)) {
        recuperacao_log('Tentativa de redefinição de senha rejeitada: CSRF inválido', 'WARNING');
        $mensagemErro = 'Sessão expirada. Recarregue a página e tente novamente.';
        $tokenValido = null;
    } else {
        // Revalida o token no momento do POST (proteção contra race conditions)
        $statusPost = recuperacao_verificar_status_token($tokenPost);

        if ($statusPost['status'] !== 'valido') {
            if ($statusPost['status'] === 'expirado') {
                $mensagemErro = 'Este link de recuperação expirou. Por favor, solicite um novo link.';
            } elseif ($statusPost['status'] === 'ja_usado') {
                $mensagemErro = 'Este link de recuperação já foi utilizado.';
            } else {
                $mensagemErro = 'Link de recuperação inválido ou inexistente.';
            }
            $tokenValido = null;
        } else {
            $tokenValido = $statusPost['dados'];

            // Valida requisitos e tamanho da senha (8 a 72 bytes)
            $erroSenha = validar_forca_senha($novaSenha);

            if ($erroSenha !== null) {
                $mensagemErro = $erroSenha;
            } elseif ($novaSenha !== $confirmaSenha) {
                $mensagemErro = 'As senhas informadas não conferem. Digite a mesma senha em ambos os campos.';
            } else {
                // Executa a troca de senha em transação atômica
                $sucesso = recuperacao_redefinir_senha(
                    (int) $tokenValido['usuario_id'],
                    (int) $tokenValido['id'],
                    $novaSenha
                );

                if (!$sucesso) {
                    $mensagemErro = 'Não foi possível redefinir sua senha (o link pode ter sido utilizado em outra aba). Solicite um novo link.';
                    $tokenValido = null;
                } else {
                    // Envia e-mail de aviso confirmando a alteração
                    email_enviar_aviso_senha_alterada(
                        $tokenValido['email'],
                        $tokenValido['username']
                    );

                    // Destrói / regenera a sessão
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_regenerate_id(true);
                    }

                    // Redireciona com aviso para a página de login
                    header('Location: ' . rtrim(APP_URL, '/') . '/login/login.php?aviso=senha_redefinida');
                    exit;
                }
            }
        }
    }

    // Regenera CSRF
    $csrfToken = csrf_regenerar();
}

$exibirFormulario = ($tokenValido !== null);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crie uma nova senha para sua conta no Math Game">
    <title>Redefinir Senha | Math Game</title>
    <link rel="stylesheet" href="tema.css">
    <link rel="stylesheet" href="login/login.css">
    <script src="tema.js"></script>
    <style>
        .aviso-erro {
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

        body.tema-escuro .aviso-erro {
            background: #2e1a1a;
            color: #ff9e9e;
        }

        .btn-solicitar-novo {
            display: block;
            text-align: center;
            padding: .9rem 1.5rem;
            background: var(--duo-blue, #1cb0f6);
            color: #fff;
            border-radius: 16px;
            font-weight: 800;
            text-decoration: none;
            font-size: 1.05rem;
            letter-spacing: .5px;
            margin-top: 1.5rem;
            transition: filter 0.2s;
        }

        .btn-solicitar-novo:hover {
            filter: brightness(0.95);
        }
    </style>
</head>

<body>

    <main class="main" id="main-content">
        <h1>Nova Senha</h1>

        <?php if ($mensagemErro): ?>
            <div class="aviso-erro" role="alert" aria-live="assertive">
                <?= icone_svg('warning', 'icone-inline', 18) ?> <?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$exibirFormulario): ?>
            <!-- Token inválido, expirado ou já utilizado -->
            <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem;">
                Por motivos de segurança, cada link de redefinição só pode ser utilizado uma única vez e possui tempo limite de validade.
            </p>
            <a href="esqueci_senha.php" id="link-novo-link" class="btn-solicitar-novo">
                Solicitar novo link
            </a>
        <?php else: ?>
            <!-- Formulário com token válido -->
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                Olá, <strong><?= htmlspecialchars($tokenValido['username'], ENT_QUOTES, 'UTF-8') ?></strong>!
                Defina uma nova senha segura para acessar sua conta.
            </p>

            <form id="form-redefinir" method="POST" action="redefinir_senha.php?token=<?= urlencode($tokenUrl) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token_recupera" value="<?= htmlspecialchars($tokenUrl, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="nova-senha">Nova senha</label>
                    <input type="password" id="nova-senha" name="nova_senha"
                        autocomplete="new-password" required minlength="8" maxlength="72"
                        aria-required="true" aria-describedby="dica-senha"
                        placeholder="Mínimo de 8 caracteres">
                    <span id="dica-senha" style="font-size:.82rem; color: var(--text-muted);">
                        A senha deve ter entre 8 e 72 caracteres.
                    </span>
                </div>

                <div class="form-group">
                    <label for="confirma-senha">Confirmar nova senha</label>
                    <input type="password" id="confirma-senha" name="confirma_senha"
                        autocomplete="new-password" required minlength="8" maxlength="72"
                        aria-required="true"
                        placeholder="Repita a nova senha">
                </div>

                <button type="submit" id="btn-redefinir">Salvar nova senha</button>
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

            // Validação client-side das senhas
            const form = document.getElementById('form-redefinir');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const nova = document.getElementById('nova-senha');
                    const confirma = document.getElementById('confirma-senha');
                    if (nova && confirma && nova.value !== confirma.value) {
                        e.preventDefault();
                        confirma.setCustomValidity('As senhas não conferem.');
                        confirma.reportValidity();
                    } else if (confirma) {
                        confirma.setCustomValidity('');
                    }
                });
            }
        })();
    </script>
</body>

</html>
