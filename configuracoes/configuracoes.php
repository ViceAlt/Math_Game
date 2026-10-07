<?php session_start(); $usuario = $_SESSION['usuario'] ?? null; $logado = $usuario !== null; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Configurações de acessibilidade e aparência - Math Game">
    <title>Configurações | Math Game</title>
    <link rel="stylesheet" href="configuracoes.css">
</head>
<body>

    <nav class="sidebar" aria-label="Navegação principal">
        <ul>
            <li><a href="../home/home.php"><button type="button" id="nav-home">Home</button></a></li>
            <li><a href="../conteudos/conteudos.php"><button type="button" id="nav-conteudos">Conteúdos</button></a></li>
            <li><a href="../jogos/jogos.php"><button type="button" id="nav-jogos">Jogos</button></a></li>
            <li><button type="button" id="nav-perfil" onclick="navegarPerfil()">Perfil</button></li>
            <li><a href="configuracoes.php" aria-current="page"><button type="button" id="nav-configuracoes">Configurações</button></a></li>
        </ul>
    </nav>

    <main class="main" id="main-content">
        <h1>Configurações</h1>

        <section aria-labelledby="titulo-aparencia">
            <h2 id="titulo-aparencia">Aparência</h2>
            <div class="config-item">
                <label for="toggle-tema" id="label-tema">Modo Escuro</label>
                <input type="checkbox" role="switch" id="toggle-tema"
                       aria-labelledby="label-tema"
                       onchange="alternarTema(this.checked)">
            </div>
        </section>

        <section aria-labelledby="titulo-daltonismo">
            <h2 id="titulo-daltonismo">Modo de Daltonismo</h2>
            <div class="config-item" role="group" aria-labelledby="titulo-daltonismo">
                <label><input type="radio" name="daltonismo" id="dal-nenhum" value="nenhum"
                              onchange="selecionarDaltonismo('nenhum')"> Nenhum (padrão)</label>
                <label><input type="radio" name="daltonismo" id="dal-protanopia" value="protanopia"
                              onchange="selecionarDaltonismo('protanopia')"> Protanopia (dificuldade com vermelho)</label>
                <label><input type="radio" name="daltonismo" id="dal-deuteranopia" value="deuteranopia"
                              onchange="selecionarDaltonismo('deuteranopia')"> Deuteranopia (dificuldade com verde)</label>
                <label><input type="radio" name="daltonismo" id="dal-tritanopia" value="tritanopia"
                              onchange="selecionarDaltonismo('tritanopia')"> Tritanopia (dificuldade com azul)</label>
            </div>
        </section>
    </main>

    <dialog id="modal-login-sugestao" aria-labelledby="modal-login-titulo">
        <article>
            <h2 id="modal-login-titulo">Acesso restrito</h2>
            <p>Faça login para ver seu perfil.</p>
            <footer>
                <a href="../login/login.php"><button type="button" id="btn-ir-login">Fazer Login</button></a>
                <a href="../cadastro/cadastro.php"><button type="button" id="btn-ir-cadastro">Criar Conta</button></a>
                <button type="button" id="btn-cancelar-login" onclick="fecharModalLogin()">Agora não</button>
            </footer>
        </article>
    </dialog>

    <script>
        const LOGADO  = <?= $logado ? 'true' : 'false' ?>;
        const USUARIO = <?= $logado ? json_encode($usuario) : 'null' ?>;
    </script>
    <script src="configuracoes.js"></script>
</body>
</html>
