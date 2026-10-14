<?php session_start(); $usuario = $_SESSION['usuario'] ?? null; $logado = $usuario !== null; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Catálogo de jogos de Matemática - Math Game">
    <title>Jogos | Math Game</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="jogos.css">
    <script src="../tema.js"></script>
</head>
<body>

    <nav class="sidebar" aria-label="Navegação principal">
        <ul>
            <li><a href="../home/home.php"><button type="button" id="nav-home">Home</button></a></li>
            <li><a href="../conteudos/conteudos.php"><button type="button" id="nav-conteudos">Conteúdos</button></a></li>
            <li><a href="jogos.php" aria-current="page"><button type="button" id="nav-jogos">Jogos</button></a></li>
            <li><button type="button" id="nav-perfil" onclick="navegarPerfil()">Perfil</button></li>
            <li><a href="../configuracoes/configuracoes.php"><button type="button" id="nav-configuracoes">Configurações</button></a></li>
        </ul>
    </nav>

    <main class="main" id="main-content">
        <h1>Jogos</h1>
        <p>Jogue livremente para treinar. A pontuação aqui é local e não afeta seu progresso na trilha.</p>

        <section class="grade-jogos" id="grade-jogos" aria-label="Catálogo de jogos disponíveis">

            <article class="card-jogo" id="card-jogo-1" aria-labelledby="jogo-1-nome">
                <h2 id="jogo-1-nome">Frações com Pizza</h2>
                <p>Selecione as fatias corretas para representar a fração pedida.</p>
                <p>Seu recorde: <strong id="recorde-jogo-1">0 pts</strong></p>

                <div class="dificuldade-group" role="group" aria-label="Dificuldade do jogo 1">
                    <button type="button" class="btn-dificuldade" id="jogo1-facil"
                            data-dificuldade="facil" onclick="selecionarDificuldadeJogo(1,'facil')" aria-pressed="true">Fácil</button>
                    <button type="button" class="btn-dificuldade" id="jogo1-medio"
                            data-dificuldade="medio" onclick="selecionarDificuldadeJogo(1,'medio')" aria-pressed="false">Médio</button>
                    <button type="button" class="btn-dificuldade" id="jogo1-experiente"
                            data-dificuldade="experiente" onclick="selecionarDificuldadeJogo(1,'experiente')" aria-pressed="false">Experiente</button>
                </div>

                <button type="button" class="btn-jogar-card" id="btn-jogar-jogo-1" onclick="iniciarJogoLivre(1)">Jogar</button>
            </article>

            <article class="card-jogo" id="card-jogo-2" aria-labelledby="jogo-2-nome">
                <h2 id="jogo-2-nome">Gráfico de Barras</h2>
                <p>Ajuste as barras do gráfico conforme as quantidades indicadas no problema.</p>
                <p>Seu recorde: <strong id="recorde-jogo-2">0 pts</strong></p>

                <div class="dificuldade-group" role="group" aria-label="Dificuldade do jogo 2">
                    <button type="button" class="btn-dificuldade" id="jogo2-facil"
                            data-dificuldade="facil" onclick="selecionarDificuldadeJogo(2,'facil')" aria-pressed="true">Fácil</button>
                    <button type="button" class="btn-dificuldade" id="jogo2-medio"
                            data-dificuldade="medio" onclick="selecionarDificuldadeJogo(2,'medio')" aria-pressed="false">Médio</button>
                    <button type="button" class="btn-dificuldade" id="jogo2-experiente"
                            data-dificuldade="experiente" onclick="selecionarDificuldadeJogo(2,'experiente')" aria-pressed="false">Experiente</button>
                </div>

                <button type="button" class="btn-jogar-card" id="btn-jogar-jogo-2" onclick="iniciarJogoLivre(2)">Jogar</button>
            </article>

            <article class="card-jogo" id="card-jogo-3" aria-labelledby="jogo-3-nome">
                <h2 id="jogo-3-nome">Geometria e Formas</h2>
                <p>Desenhe figuras geométricas no plano e teste seus conhecimentos sobre perímetro e área.</p>
                <p>Seu recorde: <strong id="recorde-jogo-3">0 pts</strong></p>

                <div class="dificuldade-group" role="group" aria-label="Dificuldade do jogo 3">
                    <button type="button" class="btn-dificuldade" id="jogo3-facil"
                            data-dificuldade="facil" onclick="selecionarDificuldadeJogo(3,'facil')" aria-pressed="true">Fácil</button>
                    <button type="button" class="btn-dificuldade" id="jogo3-medio"
                            data-dificuldade="medio" onclick="selecionarDificuldadeJogo(3,'medio')" aria-pressed="false">Médio</button>
                    <button type="button" class="btn-dificuldade" id="jogo3-experiente"
                            data-dificuldade="experiente" onclick="selecionarDificuldadeJogo(3,'experiente')" aria-pressed="false">Experiente</button>
                </div>

                <button type="button" class="btn-jogar-card" id="btn-jogar-jogo-3" onclick="iniciarJogoLivre(3)">Jogar</button>
            </article>

            <article class="card-jogo" id="card-jogo-4" aria-labelledby="jogo-4-nome">
                <h2 id="jogo-4-nome">Sucessores e Antecessores</h2>
                <p>Complete as sentenças com o sucessor ou antecessor correto e teste sua lógica matemática.</p>
                <p>Seu recorde: <strong id="recorde-jogo-4">0 pts</strong></p>

                <div class="dificuldade-group" role="group" aria-label="Dificuldade do jogo 4">
                    <button type="button" class="btn-dificuldade" id="jogo4-facil"
                            data-dificuldade="facil" onclick="selecionarDificuldadeJogo(4,'facil')" aria-pressed="true">Fácil</button>
                    <button type="button" class="btn-dificuldade" id="jogo4-medio"
                            data-dificuldade="medio" onclick="selecionarDificuldadeJogo(4,'medio')" aria-pressed="false">Médio</button>
                    <button type="button" class="btn-dificuldade" id="jogo4-experiente"
                            data-dificuldade="experiente" onclick="selecionarDificuldadeJogo(4,'experiente')" aria-pressed="false">Experiente</button>
                </div>

                <button type="button" class="btn-jogar-card" id="btn-jogar-jogo-4" onclick="iniciarJogoLivre(4)">Jogar</button>
            </article>

        </section>
    </main>

    <dialog id="modal-login-sugestao" aria-labelledby="modal-login-titulo">
        <article>
            <h2 id="modal-login-titulo">Acesso restrito</h2>
            <p>Crie uma conta ou faça login para ver seu perfil.</p>
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
    <script src="jogos.js"></script>
</body>
</html>
