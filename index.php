<?php
$cssVersion = file_exists(__DIR__ . '/index.css') ? filemtime(__DIR__ . '/index.css') : time();
$jsVersion = file_exists(__DIR__ . '/index.js') ? filemtime(__DIR__ . '/index.js') : time();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aprenda matemática se divertindo no Mantham. Explore jogos e trilhas do 6º ao 9º ano.">
    <meta name="theme-color" content="#ffffff">
    <title>Mantham | Matemática para aprender jogando</title>
    <link rel="stylesheet" href="tema.css">
    <link rel="stylesheet" href="index.css?v=<?= $cssVersion ?>">
    <script src="tema.js"></script>
</head>

<body class="pagina-apresentacao">
    <header class="barra-apresentacao">
        <span class="barra-espacador" aria-hidden="true"></span>
        <a class="marca-apresentacao" href="index.php" aria-label="Mantham - página inicial">
            <img src="icons/logo.webp" alt="Mantham" width="160" height="40">
        </a>
        <a class="link-entrar" href="login/login.php">Entrar</a>
    </header>

    <main>
        <section class="apresentacao-hero" aria-labelledby="titulo-principal">
            <img class="imagem-grupo" src="icons/group.webp" alt="Personagens do Mantham celebrando uma conquista matemática" width="600" height="400" fetchpriority="high">
            <div class="texto-hero">
                <p class="sobretitulo">Matemática para explorar</p>
                <h1 id="titulo-principal">Aprenda matemática se divertindo no Mantham.</h1>
                <p class="descricao-hero">Transforme cada desafio em uma conquista. Aprenda no seu ritmo com jogos e trilhas feitos para acompanhar você.</p>
                <div class="acoes-hero">
                    <a class="btn-duo btn-duo-green" href="cadastro/cadastro.php">Criar conta</a>
                    <a class="btn-duo btn-duo-outline" href="home/home.php">Jogar agora</a>
                </div>
            </div>
        </section>

        <section class="secao-anos" id="conteudos" aria-labelledby="titulo-anos">
            <div class="cabecalho-secao">
                <p class="sobretitulo">Uma trilha para cada etapa</p>
                <h2 id="titulo-anos">Do 6º ao 9º ano, no seu ritmo</h2>
                <p>Explore conteúdos alinhados aos anos finais do Ensino Fundamental.</p>
            </div>

            <div class="grade-anos">
                <article class="card-ano card-ano-verde" data-reveal>
                    <span class="numero-ano">6º</span>
                    <div>
                        <h3>6º ano</h3>
                        <p>Operações, frações, múltiplos, divisores e números inteiros.</p>
                    </div>
                </article>
                <article class="card-ano card-ano-laranja" data-reveal>
                    <span class="numero-ano">7º</span>
                    <div>
                        <h3>7º ano</h3>
                        <p>Números racionais, proporções, equações e porcentagens.</p>
                    </div>
                </article>
                <article class="card-ano card-ano-azul" data-reveal>
                    <span class="numero-ano">8º</span>
                    <div>
                        <h3>8º ano</h3>
                        <p>Áreas, perímetros, teoremas geométricos e álgebra.</p>
                    </div>
                </article>
                <article class="card-ano card-ano-amarelo" data-reveal>
                    <span class="numero-ano">9º</span>
                    <div>
                        <h3>9º ano</h3>
                        <p>Funções, estatística e probabilidade em desafios avançados.</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="faixa-progresso" aria-label="Informações sobre as trilhas">
            <div class="dado-progresso">
                <strong>4 anos</strong>
                <span>do 6º ao 9º ano</span>
            </div>
            <div class="dado-progresso">
                <strong>20 fases</strong>
                <span>em cada trilha anual</span>
            </div>
            <div class="dado-progresso">
                <strong>Aprender jogando</strong>
                <span>desafios com progresso e conquistas</span>
            </div>
        </section>
    </main>

    <footer class="rodape-apresentacao">
        <p>Mantham — aprender matemática também pode ser uma aventura.</p>
    </footer>

    <script src="index.js?v=<?= $jsVersion ?>" defer></script>
</body>

</html>