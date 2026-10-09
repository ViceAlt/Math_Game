<?php
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/csrf.php';
sessao_iniciar();
if (!empty($_SESSION['usuario'])) {
    header('Location: ../home/home.php');
    exit;
}
$redirect    = htmlspecialchars($_GET['redirect'] ?? '../home/home.php');
$aviso       = htmlspecialchars($_GET['aviso'] ?? '');
$csrfToken   = csrf_gerar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acesse sua conta na plataforma Math Game">
    <title>Login | Math Game</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="login.css">
    <script src="../tema.js"></script>
</head>
<body>

    <main class="main" id="main-content">
        <h1>Entrar na Conta</h1>

        <?php if ($aviso === 'senha_alterada'): ?>
        <p id="aviso-sessao" style="color: var(--duo-red); font-weight: 700; margin-bottom: 1rem;">
            ⚠️ Sua senha foi alterada. Por favor, faça login novamente.
        </p>
        <?php elseif ($aviso === 'senha_redefinida'): ?>
        <p id="aviso-sucesso" style="color: #2d6a2d; font-weight: 700; margin-bottom: 1rem; background:#f0fff4; border-radius:10px; padding:.75rem 1rem;">
            ✅ Sua senha foi redefinida com sucesso! Faça login com a nova senha.
        </p>
        <?php endif; ?>

        <form id="form-login" novalidate aria-label="Formulário de login">
            <input type="hidden" id="redirect-url" value="<?= $redirect ?>">
            <input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="login-email">E-mail</label>
                <input type="email" id="login-email" name="email"
                       autocomplete="email" required
                       aria-required="true" aria-describedby="erro-login-email"
                       placeholder="Seu e-mail">
                <span class="erro" id="erro-login-email" role="alert" aria-live="polite"></span>
            </div>

            <div class="form-group">
                <label for="login-senha">Senha</label>
                <input type="password" id="login-senha" name="senha"
                       autocomplete="current-password" required
                       aria-required="true" aria-describedby="erro-login-senha"
                       placeholder="Sua senha">
                <span class="erro" id="erro-login-senha" role="alert" aria-live="polite"></span>
            </div>

            <span class="erro" id="erro-login-geral" role="alert" aria-live="assertive"></span>

            <button type="submit" id="btn-entrar">Entrar</button>
        </form>

        <p><a href="../esqueci_senha.php" id="link-esqueci-senha">Esqueci minha senha</a></p>
        <p>Não tem uma conta? <a href="../cadastro/cadastro.php" id="link-ir-cadastro">Crie uma agora</a></p>
    </main>

    <script src="login.js"></script>
</body>
</html>
