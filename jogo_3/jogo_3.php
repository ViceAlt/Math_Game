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
    <meta name="description" content="Formas Geométricas e Fórmulas - Mantham">
    <title>Formas e Geometria | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="jogo_3.css">
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
        <!-- Área de Desenho da Forma (SVG) -->
        <section class="mesa-desenho">
            <!-- Dica flutuante -->
            <div class="dica-flutuante" id="dica-flutuante" role="status" aria-live="polite" style="display: none;">
                <span class="dica-texto" id="dica-texto"></span>
            </div>
            <!-- Barra de ferramentas do plano de desenho -->
            <div class="desenho-toolbar">
                <div class="info-forma-badge" id="infoFormaBadge">
                    <span class="cor-indicador" id="corIndicador"></span>
                    <span id="nomeFormaAlvo">Carregando...</span>
                </div>
                <div class="acoes-desenho">
                    <button type="button" id="btn-desfazer" class="btn-desfazer" onclick="desfazerUltimoMovimento()" title="Desfazer última linha desenhada">Desfazer Linha</button>
                    <button type="button" id="btn-limpar" onclick="limparDesenho()" title="Limpar todo o plano">Limpar</button>
                </div>
            </div>

            <!-- Canvas SVG Interativo -->
            <div class="canvas-container" id="canvasContainer">
                <svg id="svgPlano" viewBox="0 0 700 450" preserveAspectRatio="xMidYMid meet">
                    <!-- Grade sutil de fundo -->
                    <defs>
                        <pattern id="gridPattern" width="35" height="35" patternUnits="userSpaceOnUse">
                            <circle cx="17.5" cy="17.5" r="1.5" class="grid-dot" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#gridPattern)" />

                    <!-- Retângulo de Limite do Nível Médio/Experiente -->
                    <g id="layerRetanguloLimite"></g>

                    <!-- Polígono Preenchido -->
                    <polygon id="poligonoPreenchido" class="poligono-preenchido" points="" />

                    <!-- Linhas Já Desenhadas -->
                    <g id="layerLinhas"></g>

                    <!-- Linha em Criação (Preview ao arrastar) -->
                    <line id="linhaPreview" class="linha-preview" x1="0" y1="0" x2="0" y2="0" />

                    <!-- Vértices / Pontos de Snap -->
                    <g id="layerVertices"></g>

                    <!-- Indicador visual de Snap ativo -->
                    <circle id="snapIndicator" class="snap-indicator" cx="-50" cy="-50" r="14" />
                </svg>
            </div>

            <!-- Dica pedagógica e status do desenho -->
            <div class="status-desenho" id="statusDesenho">
                <span id="statusTexto">Clique e arraste para desenhar as linhas da forma.</span>
            </div>
        </section>

        <!-- Right sidebar: Question + Actions (Etapa 1 e Etapa 2) -->
        <aside class="sidebar">
            <div class="pergunta_div" id="perguntaDiv">
                <!-- Enunciado superior da Sidebar -->
                <p class="pergunta-instrucao" id="perguntaInstrucao">Etapa 1: Crie a forma geométrica</p>

                <!-- Box de Conteúdo da Questão -->
                <div class="texto_questao_div" id="textoQuestaoDiv">
                    <p class="pergunta" id="pergunta-texto">Crie a forma pedida no plano de desenho.</p>
                </div>

                <!-- Painel de Feedback e Mensagens de Validação -->
                <div class="mensagem-validacao" id="mensagemValidacao" role="alert" aria-live="polite"></div>

                <!-- Painel de Múltipla Escolha (Etapa 2) -->
                <div class="alternativas-container" id="alternativasContainer" style="display: none;">
                    <!-- Preenchido via JavaScript -->
                </div>

                <!-- Ações principais do jogo -->
                <div class="button_position">
                    <!-- Botão Confirmar (Etapa 1) -->
                    <button class="confirmar" id="btn-confirmar" onclick="confirmarForma()" disabled>Confirmar Forma</button>
                    <!-- Botão Confirmar resposta (Etapa 2) -->
                    <button class="confirmar" id="btn-confirmar-resposta" onclick="confirmarRespostaPergunta()" style="display: none;" disabled>Confirmar Resposta</button>
                    <!-- Botão Próxima Questão (Etapa 2) -->
                    <button class="proxima" id="btn-proxima" onclick="avancarQuestao()" style="display: none;">Próxima Questão</button>
                </div>
            </div>

            <!-- Botão de Dica -->
            <div class="button_position">
                <button class="dica" id="btn-dica" onclick="solicitarDica()">Dica Geométrica</button>
            </div>
        </aside>
    </main>

    <!-- Modal de Resultado Final (Padrão de todos os jogos do site) -->
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

    <script src="../includes/timer_pontuacao.js"></script>
    <script src="jogo_3.js"></script>
</body>

</html>
