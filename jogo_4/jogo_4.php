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
    <meta name="description" content="Sucessores e Antecessores - Mantham">
    <title>Sucessores e Antecessores | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="jogo_4.css">
    <script src="../tema.js"></script>
</head>

<body>
    <!-- Top bar: Sair + Progresso -->
    <header class="top-bar">
        <button id="btn-sair-jogo" onclick="voltarPaginaAnterior()">Sair</button>
        <div class="barra-progresso-container">
            <div class="barra-progresso" id="barra-progresso"></div>
        </div>
    </header>

    <!-- Main game layout centralizado -->
    <main class="game-layout centralizado">
        <section class="card-questao-container">
            <!-- Dica flutuante -->
            <div class="dica-flutuante" id="dica-flutuante" role="status" aria-live="polite" style="display: none;">
                <span class="dica-texto" id="dica-texto"></span>
            </div>
            <!-- Box do Enunciado Principal em tamanho grande -->
            <div class="enunciado-card">
                <p class="instrucao-topo" id="instrucao-topo">Complete a frase escolhendo as opções corretas:</p>
                
                <div class="enunciado-texto" id="enunciado-texto">
                    Carregando questão...
                </div>
            </div>

            <!-- Botão Confirmar posicionado logo abaixo do enunciado -->
            <div class="acao-principal">
                <button type="button" class="btn-confirmar" id="btn-confirmar" onclick="confirmarResposta()" disabled>
                    Confirmar
                </button>
            </div>

            <!-- Botão de Dica -->
            <div class="acao-dica">
                <button type="button" class="dica btn-dica" id="btn-dica" onclick="solicitarDica()">
                    Dica
                </button>
            </div>
        </section>
    </main>

    <!-- Popover flutuante para as opções de escolha (Renderizado fora de limites de overflow) -->
    <div id="popover-escolha" class="popover-escolha" style="display: none;" role="listbox" aria-hidden="true">
        <div class="popover-lista" id="popover-lista"></div>
    </div>

    <!-- Modal de Resultado Final (Padrão do site) -->
    <dialog id="modal-resultado" aria-labelledby="modal-resultado-titulo">
        <article class="modal-resultado-conteudo">
            <h2 id="modal-resultado-titulo">Fim de Jogo!</h2>
            <div class="resultado-estrelas" id="modal-estrelas-display" aria-label="Estrelas conquistadas"></div>
            <div class="resultado-stats">
                <p>Pontuação: <strong id="modal-pontuacao-valor">0</strong> pts</p>
                <p>Acertos: <strong id="modal-acertos-valor">0/0</strong></p>
            </div>
            <p class="resultado-comentario" id="modal-comentario-valor"></p>
            <footer>
                <button type="button" id="btn-modal-ok" onclick="voltarPaginaAnterior()">OK</button>
            </footer>
        </article>
    </dialog>

    <script src="jogo_4.js"></script>
</body>

</html>
