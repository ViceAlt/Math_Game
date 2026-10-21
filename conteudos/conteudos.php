<?php session_start();
$usuario = $_SESSION['usuario'] ?? null;
$logado  = $usuario !== null;

require_once __DIR__ . '/../includes/icones.php';

$cssVersion = file_exists(__DIR__ . '/conteudos.css') ? filemtime(__DIR__ . '/conteudos.css') : time();
$jsVersion  = file_exists(__DIR__ . '/conteudos.js') ? filemtime(__DIR__ . '/conteudos.js') : time();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Trilha de fases gamificada em estilo mapa vertical - Math Game">
    <title>Trilha de Conteúdos | Math Game</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="../includes/navbar.css">
    <link rel="stylesheet" href="conteudos.css?v=<?= $cssVersion ?>">
    <link rel="stylesheet" href="../includes/modal-perfil.css">
    <script src="../tema.js"></script>
</head>

<body>

    <?php require __DIR__ . '/../includes/navbar.php'; ?>

    <!-- ===== CABEÇALHO FIXO DO MUNDO / ANO LETIVO (fora do main para position:fixed correto) ===== -->
    <header class="mundo-header-sticky" id="mundo-header">
        <div class="mundo-header-inner">

            <div class="mundo-header-conteudo">
                
                <div class="mundo-identificacao">
                    <span class="mundo-icone-destaque" id="mundo-icone" aria-hidden="true">
                        <?= icone_svg('forest', '', 32) ?>
                    </span>
                    <div class="mundo-textos">
                        <span class="mundo-unidade-tag" id="mundo-tag">Unidade 1</span>
                        <h1 class="mundo-titulo" id="mundo-titulo">6° Ano – Floresta dos Números</h1>
                        <p class="mundo-descricao" id="mundo-descricao">Domine operações, frações, múltiplos e divisores</p>
                    </div>
                </div>

                <!-- Progresso e Estrelas da Unidade -->
                <div class="mundo-stats-card">
                    <div class="mundo-stats-row">
                        <div class="stat-bloco">
                            <span class="stat-rotulo">Fases</span>
                            <strong class="stat-numero" id="mundo-progresso-texto">0 / 20</strong>
                        </div>
                        <div class="stat-divisor" aria-hidden="true"></div>
                        <div class="stat-bloco">
                            <span class="stat-rotulo">Estrelas</span>
                            <div class="stat-numero stat-estrelas-destaque">
                                <span id="mundo-estrelas-texto">0 / 60</span>
                                <?= icone_svg('star', 'estrela-amarela-mini', 18) ?>
                            </div>
                        </div>
                    </div>
                    <div class="barra-mundo-progresso" role="progressbar" id="barra-progresso-container" aria-valuenow="0" aria-valuemin="0" aria-valuemax="20" aria-label="Progresso no mundo atual">
                        <div class="barra-mundo-fill" id="barra-mundo-fill" style="width: 0%;"></div>
                    </div>
                </div>

            </div>

            <!-- Seletor de Mundos / Anos Letivos (Abas 3D) -->
            <nav class="seletor-mundos" role="tablist" aria-label="Selecione o ano letivo da trilha">
                <button type="button" role="tab" class="btn-mundo-aba ativa" id="btn-ano-6" data-ano="6"
                        onclick="selecionarAno(6)" aria-selected="true" aria-controls="mapa-trilha-area">
                    <span class="aba-icon" aria-hidden="true"><?= icone_svg('forest', '', 18) ?></span>
                    <span class="aba-label">6° Ano</span>
                </button>
                <button type="button" role="tab" class="btn-mundo-aba" id="btn-ano-7" data-ano="7"
                        onclick="selecionarAno(7)" aria-selected="false" aria-controls="mapa-trilha-area">
                    <span class="aba-icon" aria-hidden="true"><?= icone_svg('desert', '', 18) ?></span>
                    <span class="aba-label">7° Ano</span>
                </button>
                <button type="button" role="tab" class="btn-mundo-aba" id="btn-ano-8" data-ano="8"
                        onclick="selecionarAno(8)" aria-selected="false" aria-controls="mapa-trilha-area">
                    <span class="aba-icon" aria-hidden="true"><?= icone_svg('galaxy', '', 18) ?></span>
                    <span class="aba-label">8° Ano</span>
                </button>
                <button type="button" role="tab" class="btn-mundo-aba" id="btn-ano-9" data-ano="9"
                        onclick="selecionarAno(9)" aria-selected="false" aria-controls="mapa-trilha-area">
                    <span class="aba-icon" aria-hidden="true"><?= icone_svg('citadel', '', 18) ?></span>
                    <span class="aba-label">9° Ano</span>
                </button>
            </nav>

        </div>
    </header>

    <main class="main conteudos-main mundo-6" id="main-content">

        <!-- ===== ÁREA PRINCIPAL: MAPA VERTICAL + RESUMO LATERAL DESKTOP ===== -->
        <div class="conteudos-layout-grid">

            <!-- Mapa Vertical Sinuoso das Fases -->
            <section class="mapa-trilha-area" id="mapa-trilha-area" aria-label="Trilha vertical de fases">
                <div class="trilha-vertical-wrapper" id="trilha-wrapper">
                    
                    <!-- SVG Conector Dinâmico (Curvas Cúbicas de Bézier) -->
                    <svg class="trilha-svg-conector" id="trilha-svg" aria-hidden="true"></svg>

                    <!-- Lista Vertical de Fases (Renderizada por conteudos.js) -->
                    <div class="trilha-fases-coluna" id="trilha-fases" role="list">
                        <!-- Bolinhas injetadas via JS -->
                    </div>

                </div>
            </section>

            <!-- Painel Lateral Opcional no Desktop (Resumo Rápido do Aluno) -->
            <aside class="conteudos-col-lateral" aria-label="Resumo do progresso">
                <div class="card-lateral-resumo">
                    <div class="card-lateral-header">
                        <span class="icone-trofeu-resumo" aria-hidden="true"><?= icone_svg('trophy', '', 28) ?></span>
                        <h3>Suas Conquistas</h3>
                    </div>
                    <div class="lateral-metricas">
                        <div class="metrica-item">
                            <span class="metrica-label">Estrelas neste Mundo</span>
                            <div class="metrica-val-row">
                                <strong id="lateral-estrelas-mundo" class="metrica-num">0</strong>
                                <span class="metrica-max">/ 60</span>
                                <?= icone_svg('star', 'estrela-amarela-mini', 16) ?>
                            </div>
                        </div>
                        <div class="metrica-item">
                            <span class="metrica-label">Fases Completadas</span>
                            <div class="metrica-val-row">
                                <strong id="lateral-fases-concluidas" class="metrica-num">0</strong>
                                <span class="metrica-max">/ 20</span>
                            </div>
                        </div>
                    </div>
                    <div class="dica-conquista">
                        <span class="dica-icone"><?= icone_svg('lightning', '', 18) ?></span>
                        <p>Complete as 20 fases com 3 estrelas para conquistar o Troféu Dourado do Mundo!</p>
                    </div>
                </div>
            </aside>

        </div>

    </main>

    <!-- ===== POPOVER / MODAL DA FASE (DESIGN DUOLINGO) ===== -->
    <dialog id="modal-fase" aria-labelledby="modal-fase-titulo">
        <article class="modal-fase-conteudo">
            <header class="modal-fase-header">
                <div class="modal-fase-header-info">
                    <span class="modal-fase-badge" id="modal-fase-badge">Fase 1</span>
                    <h2 id="modal-fase-titulo">Fatia Maluca</h2>
                </div>
                <button type="button" id="btn-fechar-fase" aria-label="Fechar janela" onclick="fecharModalFase()">
                    <?= icone_svg('close', '', 20) ?>
                </button>
            </header>

            <div class="modal-fase-stats">
                <div class="stat-card-modal">
                    <span class="stat-card-label">Melhor Pontuação</span>
                    <strong class="stat-card-val" id="modal-pontuacao-max">0 pts</strong>
                </div>
                <div class="stat-card-modal">
                    <span class="stat-card-label">Estrelas</span>
                    <div class="stat-card-estrelas-container" id="modal-estrelas" aria-label="Estrelas conquistadas">
                        <!-- SVGs gerados via JS -->
                    </div>
                </div>
            </div>

            <div class="modal-dificuldade-bloco">
                <span class="dificuldade-titulo">Nível de Desafio:</span>
                <div class="dificuldade-group" role="group" aria-label="Selecione a dificuldade">
                    <button type="button" class="btn-dificuldade" id="btn-facil"
                            data-dificuldade="facil" onclick="selecionarDificuldade('facil')" aria-pressed="true">
                        Fácil (1x)
                    </button>
                    <button type="button" class="btn-dificuldade" id="btn-medio"
                            data-dificuldade="medio" onclick="selecionarDificuldade('medio')" aria-pressed="false">
                        Médio (1.5x)
                    </button>
                    <button type="button" class="btn-dificuldade" id="btn-experiente"
                            data-dificuldade="experiente" onclick="selecionarDificuldade('experiente')" aria-pressed="false">
                        Experiente (2x)
                    </button>
                </div>
            </div>

            <footer class="modal-fase-footer">
                <button type="button" class="btn-duo btn-duo-green btn-jogar-acao" id="btn-jogar-fase" onclick="iniciarFase()">
                    <span class="btn-icone-play" aria-hidden="true"><?= icone_svg('play', '', 18) ?></span>
                    <span id="btn-jogar-texto">Jogar</span>
                </button>
            </footer>
        </article>
    </dialog>

    <!-- ===== MODAL: Sugestão de Login para Visitante ===== -->
    <?php require __DIR__ . '/../includes/modal_perfil.php'; ?>

    <!-- Injeção de Estado -->
    <script>
        const LOGADO  = <?= $logado ? 'true' : 'false' ?>;
        const USUARIO = <?= $logado ? json_encode($usuario) : 'null' ?>;
    </script>
    <script src="../includes/icones.js"></script>
    <script src="../includes/modal-perfil.js"></script>

    <!-- Ajuste dinâmico do espaço abaixo do header fixo -->
    <script>
        (function () {
            var header = document.getElementById('mundo-header');
            var main   = document.getElementById('main-content');

            function ajustarEspacamento() {
                if (!header || !main) return;
                var h = header.offsetHeight;
                // Padding-top do main para que o conteúdo não fique atrás do header fixo
                main.style.paddingTop = h + 'px';
                // CSS custom property para o sticky da sidebar lateral
                document.documentElement.style.setProperty('--mundo-header-h', h + 'px');
            }

            // Executa imediatamente e ao redimensionar
            ajustarEspacamento();
            window.addEventListener('resize', ajustarEspacamento);

            // Observa mudanças de tamanho do próprio header (ano selecionado pode mudar conteúdo)
            if (window.ResizeObserver) {
                new ResizeObserver(ajustarEspacamento).observe(header);
            }
        })();
    </script>

    <script src="conteudos.js?v=<?= $jsVersion ?>"></script>

</body>
</html>
