<?php
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/csrf.php';
sessao_iniciar();
// Se já logado, redireciona para home
if (!empty($_SESSION['usuario'])) {
    header('Location: ../home/home.php');
    exit;
}
$redirect  = htmlspecialchars($_GET['redirect'] ?? '../home/home.php');
$csrfToken = csrf_gerar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crie sua conta na plataforma Mantham">
    <title>Cadastro | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="cadastro.css">
    <script src="../tema.js"></script>
</head>
<body>

    <main class="main" id="main-content">
        <div class="auth-brand-logo">
            <a href="../home/home.php" class="auth-brand-link" aria-label="Mantham - Início">
                <img src="../icons/logo.webp" alt="Mantham" class="auth-brand-logo-img">
            </a>
        </div>

        <h1>Criar Conta</h1>

        <form id="form-cadastro" novalidate aria-label="Formulário de cadastro">
            <input type="hidden" id="redirect-url" value="<?= $redirect ?>">
            <input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="cadastro-username">Nome de usuário</label>
                <input type="text" id="cadastro-username" name="username"
                       autocomplete="username" required minlength="3" maxlength="50"
                       aria-required="true" aria-describedby="erro-username"
                       placeholder="Ex: aluno123">
                <span class="erro" id="erro-username" role="alert" aria-live="polite"></span>
            </div>

            <div class="form-group">
                <label for="cadastro-email">E-mail</label>
                <input type="email" id="cadastro-email" name="email"
                       autocomplete="email" required
                       aria-required="true" aria-describedby="erro-email"
                       placeholder="Ex: aluno@escola.edu.br">
                <span class="erro" id="erro-email" role="alert" aria-live="polite"></span>
            </div>

            <div class="form-group">
                <label for="cadastro-senha">Senha</label>
                <input type="password" id="cadastro-senha" name="senha"
                       autocomplete="new-password" required minlength="8"
                       aria-required="true" aria-describedby="erro-senha"
                       placeholder="Mínimo 8 caracteres">
                <span class="erro" id="erro-senha" role="alert" aria-live="polite"></span>
            </div>

            <span class="erro" id="erro-geral" role="alert" aria-live="assertive"></span>

            <button type="submit" id="btn-cadastrar">Criar Conta</button>
        </form>

        <p>Já tem uma conta? <a href="../login/login.php" id="link-ir-login">Faça login</a></p>
    </main>

    <script src="cadastro.js"></script>
</body>
</html>
