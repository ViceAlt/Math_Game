<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;
$logado  = $usuario !== null;

// Perfil requer login – redireciona visitantes
if (!$logado) {
    header('Location: ../login/login.php?redirect=../perfil/perfil.php');
    exit;
}

require_once __DIR__ . '/../includes/icones.php';

$cssVersion = file_exists(__DIR__ . '/perfil.css') ? filemtime(__DIR__ . '/perfil.css') : time();
$jsVersion  = file_exists(__DIR__ . '/perfil.js') ? filemtime(__DIR__ . '/perfil.js') : time();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Perfil e Dashboard de Desempenho do Aluno - Mantham">
    <title>Perfil & Desempenho | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="../includes/navbar.css">
    <link rel="stylesheet" href="perfil.css?v=<?= $cssVersion ?>">
    <script src="../tema.js"></script>
</head>
<body>

    <?php require __DIR__ . '/../includes/navbar.php'; ?>

    <main class="main perfil-main" id="main-content">
        <!-- Cabeçalho da Página com Título e Ação Rápida -->
        <header class="perfil-topo">
            <div class="perfil-topo-titulos">
                <span class="perfil-badge-topo">Painel do Estudante</span>
                <h1 class="perfil-titulo-pagina">Meu Perfil & Desempenho</h1>
                <p class="perfil-subtitulo-pagina">Acompanhe sua evolução, diagnostique seus pontos fortes e descubra no que focar para dominar a matemática.</p>
            </div>
            <div class="perfil-topo-acoes">
                <a href="../conteudos/conteudos.php" class="btn-acao-topo btn-trilha">
                    <?= icone_svg('map', 'icone-btn', 20) ?>
                    <span>Ir para a Trilha</span>
                </a>
            </div>
        </header>

        <!-- Layout Assimétrico: Coluna Lateral de Identidade + Área Principal de Dashboard -->
        <div class="perfil-layout-grid">

            <!-- COLUNA ESQUERDA: Identidade do Aluno e Dados da Conta -->
            <aside class="coluna-identidade" aria-label="Informações do aluno">
                <div class="cartao-identidade-3d">
                    <!-- Avatar com Selo de Patente -->
                    <div class="avatar-container">
                        <img id="perfil-foto"
                             src="<?= htmlspecialchars($usuario['foto_url'] ?? '../perfil/assets/avatar-padrao.png') ?>"
                             alt="Foto de perfil de <?= htmlspecialchars($usuario['username']) ?>"
                             onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?= urlencode($usuario['username']) ?>'"
                             width="112" height="112">
                        <span class="badge-patente-flutuante" id="badge-patente-icone" title="Patente atual">
                            <?= icone_svg('trophy-fill', 'icone-trofeu-ouro', 18) ?>
                        </span>
                    </div>

                    <!-- Nome, Patente e Dados -->
                    <div class="identidade-cabecalho">
                        <h2 class="identidade-nome" id="perfil-username"><?= htmlspecialchars($usuario['username']) ?></h2>
                        <span class="identidade-patente" id="perfil-patente-nome">Carregando patente...</span>
                        <span class="identidade-email" id="perfil-email"><?= htmlspecialchars($usuario['email'] ?? '—') ?></span>
                    </div>

                    <div class="divisor-identidade" aria-hidden="true"></div>

                    <!-- Ficha Técnica do Jogador -->
                    <ul class="lista-detalhes-aluno">
                        <li class="item-detalhe">
                            <span class="detalhe-rotulo">Membro desde</span>
                            <span class="detalhe-valor" id="perfil-criado-em">
                                <?php
                                $criadoEm = $usuario['criado_em'] ?? null;
                                echo $criadoEm ? (new DateTime($criadoEm))->format('d/m/Y') : '—';
                                ?>
                            </span>
                        </li>
                        <li class="item-detalhe">
                            <span class="detalhe-rotulo">Status da Conta</span>
                            <span class="detalhe-valor badge-status-ativo">Ativo</span>
                        </li>
                        <li class="item-detalhe">
                            <span class="detalhe-rotulo">Aproveitamento Geral</span>
                            <span class="detalhe-valor destaque-aproveitamento" id="perfil-aproveitamento-geral">0%</span>
                        </li>
                    </ul>

                    <!-- Botões de Ação na Coluna Lateral -->
                    <div class="identidade-acoes">
                        <a href="../configuracoes/configuracoes.php" class="btn-identidade btn-secundario" id="btn-config">
                            <?= icone_svg('settings', 'icone-btn', 18) ?>
                            <span>Personalizar Tema</span>
                        </a>
                        <button type="button" class="btn-identidade btn-perigo" id="btn-sair" onclick="fazerLogout()">
                            <?= icone_svg('close', 'icone-btn', 18) ?>
                            <span>Sair da Conta</span>
                        </button>
                    </div>
                </div>

                <!-- Card de Dica de Estudos -->
                <div class="cartao-dica-estudo">
                    <div class="dica-icone">
                        <?= icone_svg('bulb', 'icone-lampada', 24) ?>
                    </div>
                    <div class="dica-conteudo">
                        <h3 class="dica-titulo">Dica de Aprendizagem</h3>
                        <p class="dica-texto" id="dica-estudo-texto">Resolver 2 fases com calma e revisar erros acelera 3x mais sua retenção em frações e equações!</p>
                    </div>
                </div>
            </aside>

            <!-- COLUNA DIREITA: O Dashboard Completo de Desempenho e Evolução -->
            <section class="coluna-dashboard" aria-label="Painel de desempenho e evolução">

                <!-- 1. CARDS TÁTEIS DE KPI (MÉTRICAS RÁPIDAS) -->
                <div class="grid-kpis">
                    <!-- Pontuação Total -->
                    <div class="card-kpi kpi-pontos" id="card-kpi-pontos">
                        <div class="kpi-icone-box">
                            <?= icone_svg('lightning', 'icone-kpi-svg', 26) ?>
                        </div>
                        <div class="kpi-info">
                            <span class="kpi-rotulo">Pontos Totais</span>
                            <strong class="kpi-valor" id="perfil-pontuacao">0 pts</strong>
                            <span class="kpi-legenda">Acumulado em fases</span>
                        </div>
                    </div>

                    <!-- Estrelas Conquistadas -->
                    <div class="card-kpi kpi-estrelas" id="card-kpi-estrelas">
                        <div class="kpi-icone-box">
                            <?= icone_svg('star', 'icone-kpi-svg', 26) ?>
                        </div>
                        <div class="kpi-info">
                            <span class="kpi-rotulo">Estrelas Totais</span>
                            <strong class="kpi-valor" id="perfil-estrelas">0 / 240</strong>
                            <span class="kpi-legenda" id="perfil-estrelas-percent">0% do total</span>
                        </div>
                    </div>

                    <!-- Fases Dominadas -->
                    <div class="card-kpi kpi-fases" id="card-kpi-fases">
                        <div class="kpi-icone-box">
                            <?= icone_svg('check', 'icone-kpi-svg', 26) ?>
                        </div>
                        <div class="kpi-info">
                            <span class="kpi-rotulo">Fases Concluídas</span>
                            <strong class="kpi-valor" id="perfil-fases-concluidas">0 / 80</strong>
                            <span class="kpi-legenda" id="perfil-fases-percent">0% da trilha</span>
                        </div>
                    </div>

                    <!-- Fases Perfeitas (3 estrelas) -->
                    <div class="card-kpi kpi-perfeitas" id="card-kpi-perfeitas">
                        <div class="kpi-icone-box">
                            <?= icone_svg('trophy', 'icone-kpi-svg', 26) ?>
                        </div>
                        <div class="kpi-info">
                            <span class="kpi-rotulo">Fases Perfeitas</span>
                            <strong class="kpi-valor" id="perfil-fases-perfeitas">0</strong>
                            <span class="kpi-legenda">Nota máxima (3 ★)</span>
                        </div>
                    </div>
                </div>

                <!-- 2. DIAGNÓSTICO INTELIGENTE: NO QUE O ALUNO PRECISA EVOLUIR (CARD DE DESTAQUE) -->
                <section class="card-destaque-evolucao" id="secao-diagnostico-evolucao" aria-labelledby="titulo-evolucao">
                    <div class="evolucao-cabecalho">
                        <div class="evolucao-badge-alerta">
                            <?= icone_svg('warning', 'icone-alerta-svg', 20) ?>
                            <span>Diagnóstico Pedagógico</span>
                        </div>
                        <span class="evolucao-tempo-atualizacao">Atualizado em tempo real</span>
                    </div>

                    <div class="evolucao-corpo">
                        <div class="evolucao-conteudo-texto">
                            <h2 class="evolucao-titulo" id="titulo-evolucao">Onde você precisa evoluir agora:</h2>
                            <h3 class="evolucao-topico-nome" id="evolucao-topico-destaque">Analisando suas jogadas...</h3>
                            <p class="evolucao-motivo" id="evolucao-explicacao">
                                Identificamos quais conteúdos possuem menor aproveitamento ou fases com menos estrelas para você avançar com mais facilidade.
                            </p>
                        </div>
                        <div class="evolucao-acao-cta">
                            <div class="evolucao-meta-box">
                                <span class="meta-box-label">Taxa de Domínio</span>
                                <span class="meta-box-valor" id="evolucao-taxa-dominio">0%</span>
                            </div>
                            <a href="../conteudos/conteudos.php" class="btn-praticar-reforco" id="btn-praticar-reforco">
                                <?= icone_svg('play', 'icone-play-branco', 22) ?>
                                <span>Praticar Conteúdo Agora</span>
                            </a>
                        </div>
                    </div>

                    <!-- Mini Lista de Pontos de Atenção -->
                    <div class="evolucao-pontos-atencao" id="evolucao-pontos-lista">
                        <!-- Itens inseridos dinamicamente por perfil.js -->
                    </div>
                </section>

                <!-- 3. MAESTRIA POR CONTEÚDO (OS 5 PILARES MATEMÁTICOS DO SITE) -->
                <section class="secao-dashboard secao-maestria" aria-labelledby="titulo-maestria">
                    <div class="secao-dashboard-header">
                        <div>
                            <h2 class="secao-dashboard-titulo" id="titulo-maestria">Maestria por Conteúdo do Site</h2>
                            <p class="secao-dashboard-sub">Desempenho detalhado em cada habilidade matemática trabalhada nos jogos.</p>
                        </div>
                        <span class="badge-total-pilares">5 Habilidades Fundamentais</span>
                    </div>

                    <div class="lista-pilares-maestria" id="container-pilares-maestria">
                        <!-- Esqueleto carregando (será preenchido pelo perfil.js) -->
                        <div class="pilar-card-loading">Carregando habilidades matemáticas...</div>
                    </div>
                </section>

                <!-- 4. PROGRESSO POR ANO LETIVO (MUNDOS 6º AO 9º ANO) -->
                <section class="secao-dashboard secao-mundos" aria-labelledby="titulo-mundos">
                    <div class="secao-dashboard-header">
                        <div>
                            <h2 class="secao-dashboard-titulo" id="titulo-mundos">Desempenho nos Mundos (6º ao 9º Ano)</h2>
                            <p class="secao-dashboard-sub">Acompanhe seu avanço em cada etapa do Ensino Fundamental 2.</p>
                        </div>
                    </div>

                    <div class="grid-mundos-desempenho" id="container-mundos-desempenho">
                        <!-- Preenchido dinamicamente pelo perfil.js -->
                    </div>
                </section>

                <!-- 5. MISSÕES DE REFORÇO GAMIFICADAS -->
                <section class="secao-dashboard secao-missoes" aria-labelledby="titulo-missoes">
                    <div class="secao-dashboard-header">
                        <div>
                            <h2 class="secao-dashboard-titulo" id="titulo-missoes">Missões de Reforço Recomendadas</h2>
                            <p class="secao-dashboard-sub">Cumpra os desafios para nivelar seus conhecimentos e desbloquear novas patentes.</p>
                        </div>
                        <span class="badge-missoes-ativas">Metas Ativas</span>
                    </div>

                    <div class="grid-missoes-reforco" id="container-missoes-reforco">
                        <!-- Preenchido dinamicamente pelo perfil.js -->
                    </div>
                </section>

            </section>
        </div>
    </main>

    <script>
        const LOGADO  = true;
        const USUARIO = <?= json_encode($usuario) ?>;
    </script>
    <script src="perfil.js?v=<?= $jsVersion ?>"></script>
</body>
</html>
