<?php
require_once __DIR__ . '/../includes/validador_fase.php';
$ano  = isset($_GET['ano']) ? (int) $_GET['ano'] : null;
$fase = isset($_GET['fase']) ? (int) $_GET['fase'] : null;
if ($ano !== null && $fase !== null && !validarAcessoFaseServidor($ano, $fase)) {
    header('Location: ../conteudos/conteudos.php?aviso=fase_bloqueada');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gráfico de Barras Interativo - Math Game">
    <title>Gráfico de Barras | Math Game</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="jogo_2.css">
    <script src="../tema.js"></script>
</head>

<body>
    <!-- Top bar: Exit + Progress -->
    <header class="top-bar">
        <button id="btn-sair-jogo" onclick="voltarPaginaAnterior()">Sair</button>
        <div class="barra-progresso-container">
            <div class="barra-progresso" id="barra-progresso"></div>
        </div>
    </header>

    <!-- Main game layout -->
    <main class="game-layout">
        <!-- Area do Gráfico em SVG -->
        <section class="mesa-grafico">
            <div class="grafico-container" id="graficoContainer">
                <svg id="svgGrafico" viewBox="0 0 700 450" preserveAspectRatio="xMidYMid meet"></svg>
            </div>
            <!-- Legenda das Cores e Itens -->
            <div class="legenda-container" id="legendaContainer"></div>
        </section>

        <!-- Right sidebar: Question + Actions -->
        <aside class="sidebar">
            <div class="pergunta_div">
                <p class="pergunta-instrucao">Monte o gráfico de acordo com o texto abaixo:</p>
                <div class="texto_questao_div">
                    <p class="pergunta" id="pergunta-texto">Carregando questão...</p>
                </div>
                <div class="button_position">
                    <button class="confirmar" id="btn-confirmar" onclick="verificarResposta()">Verificar</button>
                </div>
            </div>
            <div class="button_position">
                <button class="dica" id="btn-dica" onclick="mostrarDica()">Dica</button>
            </div>
        </aside>
    </main>

    <!-- Modal de Resultado Final -->
    <dialog id="modal-resultado" aria-labelledby="modal-resultado-titulo">
        <article class="modal-resultado-conteudo">
            <h2 id="modal-resultado-titulo">Fim de Jogo!</h2>
            <div class="resultado-estrelas" id="modal-estrelas-display" aria-label="Estrelas conquistadas"></div>
            <div class="resultado-stats">
                <p>Pontuação: <strong id="modal-pontuacao-valor">0</strong> pts</p>
                <p>Acertos: <strong id="modal-acertos-valor">0/0</strong></p>
            </div>
            <footer>
                <button type="button" id="btn-modal-ok" onclick="voltarPaginaAnterior()">OK</button>
            </footer>
        </article>
    </dialog>

    <script src="jogo_2.js"></script>
</body>

</html>
