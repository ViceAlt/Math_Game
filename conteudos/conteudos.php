<?php
/**
 * conteudos.php – Trilha de Aprendizado
 * Redireciona para login se não autenticado (trilha requer progresso salvo).
 * Para visitantes, exibe a trilha mas bloqueia o início de jogo via modal.
 */
session_start();
$usuario = $_SESSION['usuario'] ?? null;
$logado  = $usuario !== null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conteúdos e trilha de fases por ano letivo - Math Game">
    <title>Conteúdos | Math Game</title>
    <link rel="stylesheet" href="conteudos.css">
</head>

<body>

    <nav class="sidebar" aria-label="Navegação principal">
        <ul>
            <li><a href="../home/home.php"><button type="button" id="nav-home">Home</button></a></li>
            <li><a href="conteudos.php" aria-current="page"><button type="button" id="nav-conteudos">Conteúdos</button></a></li>
            <li><a href="../jogos/jogos.php"><button type="button" id="nav-jogos">Jogos</button></a></li>
            <li><button type="button" id="nav-perfil" onclick="navegarPerfil()">Perfil</button></li>
            <li><a href="../configuracoes/configuracoes.php"><button type="button" id="nav-configuracoes">Configurações</button></a></li>
        </ul>
    </nav>

    <main class="main" id="main-content">

        <h1>Trilha de Conteúdos</h1>

        <div class="anos-letivos" role="group" aria-label="Selecione o ano letivo">
            <button type="button" class="btn-ano" id="btn-ano-6" data-ano="6"
                    onclick="selecionarAno(6)" aria-pressed="true">6° Ano</button>
            <button type="button" class="btn-ano" id="btn-ano-7" data-ano="7"
                    onclick="selecionarAno(7)" aria-pressed="false">7° Ano</button>
            <button type="button" class="btn-ano" id="btn-ano-8" data-ano="8"
                    onclick="selecionarAno(8)" aria-pressed="false">8° Ano</button>
            <button type="button" class="btn-ano" id="btn-ano-9" data-ano="9"
                    onclick="selecionarAno(9)" aria-pressed="false">9° Ano</button>
        </div>

        <section class="trilha" id="trilha-fases" aria-label="Fases da trilha">
            <!-- Gerado por conteudos.js -->
        </section>

    </main>

    <!-- Modal: Detalhes da Fase -->
    <dialog id="modal-fase" aria-labelledby="modal-fase-titulo">
        <article>
            <header>
                <h2 id="modal-fase-titulo">Fase <span id="modal-fase-numero"></span></h2>
                <button type="button" id="btn-fechar-fase" aria-label="Fechar" onclick="fecharModalFase()">×</button>
            </header>
            <div class="fase-stats">
                <p>Pontuação máxima: <strong id="modal-pontuacao-max">0</strong></p>
                <p>Estrelas: <strong id="modal-estrelas">☆☆☆</strong></p>
            </div>
            <div class="dificuldade-group" role="group" aria-label="Selecione a dificuldade">
                <button type="button" class="btn-dificuldade" id="btn-facil"
                        data-dificuldade="facil" onclick="selecionarDificuldade('facil')" aria-pressed="true">Fácil</button>
                <button type="button" class="btn-dificuldade" id="btn-medio"
                        data-dificuldade="medio" onclick="selecionarDificuldade('medio')" aria-pressed="false">Médio</button>
                <button type="button" class="btn-dificuldade" id="btn-experiente"
                        data-dificuldade="experiente" onclick="selecionarDificuldade('experiente')" aria-pressed="false">Experiente</button>
            </div>
            <footer>
                <button type="button" id="btn-jogar-fase" onclick="iniciarFase()">Jogar</button>
            </footer>
        </article>
    </dialog>

    <!-- Modal: Sugestão de Login -->
    <dialog id="modal-login-sugestao" aria-labelledby="modal-login-titulo">
        <article>
            <h2 id="modal-login-titulo">Acesso restrito</h2>
            <p>Faça login para salvar seu progresso e desbloquear fases.</p>
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
    <script src="conteudos.js"></script>

</body>
</html>
