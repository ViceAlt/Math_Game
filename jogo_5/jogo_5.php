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
    <meta name="description" content="A Balança de Equilíbrio - Equações do 1º Grau - Mantham">
    <title>A Balança de Equilíbrio | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="jogo_5.css">
    <script src="../tema.js"></script>
    <script src="../includes/icones.js"></script>
</head>

<body>
    <!-- Top bar padrão idêntica aos outros jogos: Sair + Progresso -->
    <header class="top-bar">
        <button id="btn-sair-jogo" onclick="voltarPaginaAnterior()">Sair</button>
        <div class="barra-progresso-container" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Progresso da partida">
            <div class="barra-progresso" id="barra-progresso"></div>
        </div>
    </header>

    <!-- Layout padrão: Mesa de jogo na esquerda + Sidebar na direita -->
    <main class="game-layout">

        <!-- Mesa da Gangorra (Esquerda) -->
        <section class="mesa-jogo" aria-label="Gangorra de Equilíbrio Interativa">
            
            <!-- Toast de Dica Flutuante -->
            <!-- Dica flutuante -->
            <div class="dica-flutuante" id="dica-flutuante" role="status" aria-live="polite" style="display: none;">
                <span class="dica-texto" id="dica-texto">Mantenha a gangorra sempre equilibrada!</span>
            </div>

            <!-- Painel de Divisão Algébrica Acima da Balança -->
            <div class="painel-divisao-balanca" id="painel-divisao-balanca" style="display: none;" role="region" aria-label="Operação de Divisão">
                <div class="divisao-texto-container" id="divisao-texto-container">
                    <!-- Preenchido via JS conforme nível: texto no médio, texto com input no experiente -->
                </div>
                <button type="button" class="btn-dividir-balanca" id="btn-dividir-balanca">
                    Dividir
                </button>
            </div>

            <!-- Cenário da Gangorra -->
            <div class="gangorra-cenario" id="gangorra-cenario">
                
                <!-- Divisão em duas partes da Arena: Lado Esquerdo e Lado Direito -->
                <div class="gangorra-metade metade-esquerda" id="zona-drop-esq" data-lado="esq" role="region" aria-label="1º Membro (Lado Esquerdo)">
                    <span class="etiqueta-lado">1º Membro</span>
                </div>
                <div class="gangorra-divisor-central" aria-hidden="true">
                    <div class="divisor-linha"></div>
                    <div class="divisor-badge">=</div>
                </div>
                <div class="gangorra-metade metade-direita" id="zona-drop-dir" data-lado="dir" role="region" aria-label="2º Membro (Lado Direito)">
                    <span class="etiqueta-lado">2º Membro</span>
                </div>

                <!-- Prancha oscilante da Gangorra (gira em torno do centro) -->
                <div class="gangorra-prancha" id="gangorra-prancha">
                    
                    <!-- Lado Esquerdo da Prancha -->
                    <div class="gangorra-lado lado-esquerdo" id="prato-esquerdo" data-lado="esq" tabindex="0" role="region" aria-label="Lado Esquerdo da Gangorra">
                        <div class="gangorra-pesos-container" id="itens-esquerdo">
                            <!-- Quadrados / Pesos posicionados em cima da prancha -->
                        </div>
                        <div class="gangorra-assento-marca"></div>
                    </div>

                    <!-- Lado Direito da Prancha -->
                    <div class="gangorra-lado lado-direito" id="prato-direito" data-lado="dir" tabindex="0" role="region" aria-label="Lado Direito da Gangorra">
                        <div class="gangorra-pesos-container" id="itens-direito">
                            <!-- Quadrados / Pesos posicionados em cima da prancha -->
                        </div>
                        <div class="gangorra-assento-marca"></div>
                    </div>

                </div>

                <!-- Triângulo Central / Apoio Fixo no Chão -->
                <div class="gangorra-apoio-central" aria-hidden="true">
                    <div class="gangorra-pivo-ponto"></div>
                    <div class="gangorra-triangulo"></div>
                    <div class="gangorra-base-chao"></div>
                </div>

            </div>

            <!-- Indicador sutil de equilíbrio abaixo da gangorra -->
            <div class="gangorra-status-bar" id="gangorra-status-bar" aria-live="polite">
                <span class="badge-status-equilibrio" id="badge-status-equilibrio">Em Equilíbrio</span>
            </div>
            <div class="sr-only" id="sr-status-balanca" aria-live="polite">Gangorra em perfeito equilíbrio</div>

        </section>

        <!-- Sidebar na lateral direita (Padrão idêntico aos outros jogos) -->
        <aside class="sidebar" aria-label="Instruções e Operações">
            <div class="pergunta_div">
                <p class="pergunta-instrucao">Isole a incógnita x mantendo o equilíbrio:</p>
                
                <div class="texto_questao_div">
                    <p class="pergunta" id="pergunta-texto">Carregando equação...</p>
                </div>

                <!-- Contêiner de Operações Algébricas Táteis -->
                <div class="operacoes-botoes-container" id="operacoes-botoes-container">
                    <!-- Instruções táteis geradas via JS -->
                </div>

                <!-- Múltipla Escolha quando x está isolado -->
                <div class="escolha-x-container" id="area-resposta-escolha" style="display: none;">
                    <p class="escolha-instrucao">A incógnita está isolada! Quanto vale <strong class="destaque-x">x</strong>?</p>
                    <div class="alternativas-grid" id="alternativas-grid" role="group" aria-label="Selecione o valor de x">
                        <!-- 4 botões de alternativas gerados via JS -->
                    </div>
                </div>

                <!-- Botão Confirmar Resposta posicionado na DIV lateral -->
                <div class="confirmar-container" id="container-btn-confirmar" style="display: none; margin-top: 14px;">
                    <button type="button" class="btn-confirmar" id="btn-confirmar" onclick="confirmarResposta()" disabled>
                        Confirmar
                    </button>
                </div>

                <!-- Botão Desfazer posicionado na área de pergunta -->
                <div class="button_position" style="margin-top: 10px;">
                    <button type="button" class="btn-desfazer" id="btn-desfazer" onclick="desfazerUltimaAcao()" disabled>
                        ↺ Desfazer Jogada
                    </button>
                </div>
            </div>

            <!-- Botão de Dica Padrão na base da Sidebar -->
            <div class="button_position">
                <button type="button" class="dica" id="btn-dica" onclick="solicitarDica()">Dica</button>
            </div>
        </aside>

    </main>

    <!-- Modal de Resultado Final (Padrão Math Game) -->
    <dialog id="modal-resultado" aria-labelledby="modal-resultado-titulo">
        <article class="modal-resultado-conteudo">
            <h2 id="modal-resultado-titulo">Fim de Jogo!</h2>
            <div class="resultado-estrelas" id="modal-estrelas-display" aria-label="Estrelas conquistadas"></div>
            <div class="resultado-stats">
                <p>Pontuação: <strong id="modal-pontuacao-valor">0</strong> pts</p>
                <p>Acertos: <strong id="modal-acertos-valor">0/0</strong></p>
                <p class="resultado-comentario" id="modal-comentario-valor"></p>
            </div>
            <footer>
                <button type="button" id="btn-modal-ok" onclick="voltarPaginaAnterior()">
                    OK
                </button>
            </footer>
        </article>
    </dialog>

    <script src="jogo_5.js"></script>
</body>

</html>
