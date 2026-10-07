<?php
session_start();
if (!empty($_SESSION['usuario'])) {
    header('Location: ../home/home.php');
    exit;
}
$redirect = htmlspecialchars($_GET['redirect'] ?? '../home/home.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acesse sua conta na plataforma Math Game">
    <title>Login | Math Game</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <main class="main" id="main-content">
        <h1>Entrar na Conta</h1>

        <form id="form-login" novalidate aria-label="Formulário de login">
            <input type="hidden" id="redirect-url" value="<?= $redirect ?>">

            <div class="form-group">
                <label for="login-username">Nome de usuário</label>
                <input type="text" id="login-username" name="username"
                       autocomplete="username" required
                       aria-required="true" aria-describedby="erro-login-username"
                       placeholder="Seu nome de usuário">
                <span class="erro" id="erro-login-username" role="alert" aria-live="polite"></span>
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
