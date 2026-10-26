<?php
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/csrf.php';
sessao_iniciar();
if (!empty($_SESSION['usuario'])) {
    header('Location: ../home/home.php');
    exit;
}
$redirect    = $_GET['redirect'] ?? '../home/home.php';
if (!is_string($redirect) || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|\\\\)#i', $redirect) || preg_match('/[\r\n]/', $redirect)) {
    $redirect = '../home/home.php';
}
$redirect = htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8');
$aviso       = htmlspecialchars($_GET['aviso'] ?? '');
$csrfToken   = csrf_gerar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acesse sua conta na plataforma Mantham">
    <title>Login | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="login.css">
    <script src="../tema.js"></script>
</head>
<body>

    <main class="main" id="main-content">
        <div class="auth-brand-logo">
            <a href="../index.php" class="auth-brand-link" aria-label="Mantham - Início">
                <img src="../icons/logo.webp" alt="Mantham" class="auth-brand-logo-img">
            </a>
        </div>

        <h1>Entrar na Conta</h1>

        <?php if ($aviso === 'senha_alterada'): ?>
        <p id="aviso-sessao" style="color: var(--duo-red); font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Sua senha foi alterada. Por favor, faça login novamente.
        </p>
        <?php elseif ($aviso === 'senha_redefinida'): ?>
        <p id="aviso-sucesso" style="color: #2d6a2d; font-weight: 700; margin-bottom: 1rem; background:#f0fff4; border-radius:10px; padding:.75rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            Sua senha foi redefinida com sucesso! Faça login com a nova senha.
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
            <p class="aviso-sessao-persistente">Você permanecerá conectado neste dispositivo por até 30 dias.</p>
        </form>

        <p><a href="../esqueci_senha.php" id="link-esqueci-senha">Esqueci minha senha</a></p>
        <p>Não tem uma conta? <a href="../cadastro/cadastro.php" id="link-ir-cadastro">Crie uma agora</a></p>
    </main>

    <script src="login.js"></script>
</body>
</html>
