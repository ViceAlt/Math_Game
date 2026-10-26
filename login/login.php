<?php
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/csrf.php';
sessao_iniciar();
if (!empty($_SESSION['usuario'])) {
    header('Location: ../home/home.php');
    exit;
}
$redirect    = htmlspecialchars($_GET['redirect'] ?? '../home/home.php');
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

        <p>Não tem uma conta? <a href="../cadastro/cadastro.php" id="link-ir-cadastro">Crie uma agora</a></p>
    </main>

    <script src="login.js"></script>
</body>
</html>
