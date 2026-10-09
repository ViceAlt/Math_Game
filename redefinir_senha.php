<?php
/**
 * redefinir_senha.php
 * Página de redefinição de senha via link do e-mail.
 *
 * Segurança:
 * - Header Referrer-Policy: no-referrer (token fica na URL)
 * - Sem scripts de terceiros
 * - Token validado via hash SHA-256 (nunca armazenado em texto puro)
 * - Transação atômica (uso único com UPDATE WHERE usado_em IS NULL + rowCount)
 * - session_regenerate_id após reset
 * - Não faz login automático
 * - Envia e-mail de aviso após troca
 */

// Envia cabeçalho antes de qualquer saída
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/senha.php';
require_once __DIR__ . '/includes/recuperacao.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/config.php';

sessao_iniciar();

$tokenUrl     = $_GET['token'] ?? '';
$csrfToken    = csrf_gerar();
$mensagem     = '';
$erro         = false;
$tokenValido  = null;

// ── Valida o token na chegada (GET ou POST) ───────────────────────────────────
if (!empty($tokenUrl)) {
    $tokenValido = recuperacao_buscar_token_valido($tokenUrl);
}

// ── Processamento do POST (nova senha) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfEnviado  = $_POST['csrf_token']     ?? '';
    $tokenPost    = $_POST['token_recupera'] ?? '';
    $novaSenha    = $_POST['nova_senha']      ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';

    // Valida CSRF
    if (!csrf_validar($csrfEnviado)) {
        $mensagem = 'Requisição inválida. Recarregue a página e tente novamente.';
        $erro     = true;
        $tokenValido = null;
    } else {
        // Revalida o token no momento do POST (evita race condition)
        $tokenValido = recuperacao_buscar_token_valido($tokenPost);

        if (!$tokenValido) {
            $mensagem = 'Link inválido ou expirado. Solicite um novo link de recuperação.';
            $erro     = true;
        } else {
            // Valida a nova senha
            $erroSenha = validar_forca_senha($novaSenha);

            if ($erroSenha !== null) {
                $mensagem = $erroSenha;
                $erro     = true;
            } elseif ($novaSenha !== $confirmaSenha) {
                $mensagem = 'As senhas não conferem.';
                $erro     = true;
            } else {
                // Tenta redefinir a senha em transação atômica
                $sucesso = recuperacao_redefinir_senha(
                    (int) $tokenValido['usuario_id'],
                    (int) $tokenValido['id'],
                    $novaSenha
                );

                if (!$sucesso) {
                    // Outro processo usou o token simultaneamente ou erro de banco
                    $mensagem = 'Este link já foi utilizado ou expirou. Solicite um novo.';
                    $erro     = true;
                    $tokenValido = null;
                } else {
                    // Envia e-mail de aviso (falha silenciosa)
                    email_enviar_aviso_senha_alterada(
                        $tokenValido['email'],
                        $tokenValido['username']
                    );

                    // Invalida a sessão atual e regenera o ID
                    session_regenerate_id(true);

                    // Redireciona para o login com mensagem de sucesso — NÃO faz login automático
                    header('Location: ' . rtrim(APP_URL, '/') . '/login/login.php?aviso=senha_redefinida');
                    exit;
                }
            }
        }
    }
}

// ── Verifica o token inicial (GET sem token ou token inválido) ────────────────
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
    </style>
</head>
<body>

    <main class="main" id="main-content">
        <h1>Nova Senha</h1>

        <?php if ($mensagem): ?>
            <div class="aviso-erro" role="alert" aria-live="assertive">
                ⚠️ <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$exibirFormulario): ?>
            <!-- Token inválido ou expirado: só mostra opção de pedir novo link -->
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                O link de redefinição é inválido ou expirou. Solicite um novo abaixo.
            </p>
            <a href="esqueci_senha.php" id="link-novo-link" style="
                display: block; text-align: center; padding: .9rem 1.5rem;
                background: var(--duo-blue,#1cb0f6); color:#fff; border-radius:16px;
                font-weight:800; text-decoration:none; font-size:1.05rem; letter-spacing:.5px;
            ">Solicitar novo link</a>

        <?php else: ?>
            <!-- Formulário de redefinição -->
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                Olá, <strong><?= htmlspecialchars($tokenValido['username'], ENT_QUOTES, 'UTF-8') ?></strong>!
                Crie uma nova senha para a sua conta.
            </p>

            <form id="form-redefinir" method="POST" action="redefinir_senha.php?token=<?= urlencode($tokenUrl) ?>" novalidate>
                <!-- CSRF e token de recuperação -->
                <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token_recupera" value="<?= htmlspecialchars($tokenUrl,  ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="nova-senha">Nova senha</label>
                    <input type="password" id="nova-senha" name="nova_senha"
                           autocomplete="new-password" required minlength="8"
                           aria-required="true" aria-describedby="dica-senha"
                           placeholder="Mínimo 8 caracteres">
                    <span id="dica-senha" style="font-size:.82rem; color: var(--text-muted);">
                        A senha deve ter entre 8 e 72 caracteres.
                    </span>
                </div>

                <div class="form-group">
                    <label for="confirma-senha">Confirmar nova senha</label>
                    <input type="password" id="confirma-senha" name="confirma_senha"
                           autocomplete="new-password" required minlength="8"
                           aria-required="true"
                           placeholder="Repita a nova senha">
                </div>

                <?php if ($erro && $mensagem): ?>
                    <span class="erro" role="alert"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>

                <button type="submit" id="btn-redefinir">Salvar nova senha</button>
            </form>
        <?php endif; ?>

        <p><a href="login/login.php" id="link-voltar-login">← Voltar para o login</a></p>
    </main>

    <script>
        // Aplica preferências visuais sem carregar scripts externos
        (function() {
            const tema = localStorage.getItem('mg_tema');
            const daltonismo = localStorage.getItem('mg_daltonismo');
            const tonalidade = localStorage.getItem('mg_tonalidade');
            if (tema === 'escuro') document.body.classList.add('tema-escuro');
            if (daltonismo) document.body.classList.add('daltonismo-' + daltonismo);
            if (tonalidade && tonalidade !== 'padrao') document.body.classList.add('tonalidade-' + tonalidade);

            // Validação client-side das senhas
            const form = document.getElementById('form-redefinir');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const nova     = document.getElementById('nova-senha');
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
