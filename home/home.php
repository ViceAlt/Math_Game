<?php
/**
 * home.php – Página inicial (Math Game)
 * Design Duolingo-like: cores vivas, 3D tátil, cantos suaves, sem emojis.
 */
require_once __DIR__ . '/../includes/sessao.php';
sessao_iniciar();
$usuario = $_SESSION['usuario'] ?? null;
$logado = $usuario !== null;

require_once __DIR__ . '/../includes/icones.php';

$cssVersion = file_exists(__DIR__ . '/home.css') ? filemtime(__DIR__ . '/home.css') : time();
$jsVersion  = file_exists(__DIR__ . '/home.js') ? filemtime(__DIR__ . '/home.js') : time();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plataforma de jogos de Matemática para o Ensino Fundamental 2">
    <title>Home | Mantham</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="../includes/navbar.css">
    <link rel="stylesheet" href="home.css?v=<?= $cssVersion ?>">
    <link rel="stylesheet" href="../includes/modal-perfil.css">
    <script src="../tema.js"></script>
</head>

<body>

    <?php require __DIR__ . '/../includes/navbar.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <main class="main home-main" id="main-content">
        <div class="home-layout">

            <!-- Coluna Principal (Esquerda) -->
            <div class="home-col-main">

                <?php if ($logado): ?>
                    <!-- Card Modo Livre -->
                    <div class="card-jogar" id="card-jogar" role="button" tabindex="0"
                        aria-label="Ir para a página de jogos livres"
                        onclick="window.location.href='../jogos/jogos.php'"
                        onkeydown="if(event.key==='Enter'||event.key===' ')window.location.href='../jogos/jogos.php'">
                        <div class="card-jogar-header">
                            <div class="card-jogar-titulo-wrap">
                                <span class="card-jogar-icone" aria-hidden="true"><?= icone_svg('game', '', 26) ?></span>
                                <h2>Modo Livre</h2>
                            </div>
                            <span class="badge-tag">Explorar Jogos</span>
                        </div>
                        <p>Pratique frações, estatística, geometria e álgebra livremente em qualquer um dos 4 jogos!</p>
                    </div>

                    <!-- Card Unificado: Último Jogo + Trilha Atual -->
                    <section class="card-unificado" id="card-unificado-progresso" aria-label="Seu progresso e atividades recentes">
                        
                        <!-- 1. Último Jogo Acessado -->
                        <div class="secao-unificado secao-ultimo-jogo" id="secao-ultimo-jogo">
                            <div class="secao-header">
                                <span class="secao-icone" aria-hidden="true"><?= icone_svg('lightning', '', 24) ?></span>
                                <div>
                                    <h2 class="secao-titulo">Último Jogo Acessado</h2>
                                    <p class="secao-subtitulo">Retome sua atividade matemática mais recente</p>
                                </div>
                            </div>

                            <div class="ultimo-jogo-card" id="ultimo-jogo-card">
                                <div class="ultimo-jogo-detalhes">
                                    <h3 id="ultimo-jogo-nome">Carregando último acesso...</h3>
                                    <p id="ultimo-jogo-info" class="texto-mutado">—</p>
                                </div>
                                <button type="button" class="btn-duo btn-duo-green" id="btn-ultimo-jogo">
                                    <?= icone_svg('play', '', 18) ?> <span id="btn-ultimo-texto">Jogar</span>
                                </button>
                            </div>
                        </div>

                        <hr class="divisor-unificado" />

                        <!-- 2. Trilha Atual (1 a 3 mais recentes) -->
                        <div class="secao-unificado secao-trilhas" id="secao-trilhas">
                            <div class="secao-header">
                                <span class="secao-icone" aria-hidden="true"><?= icone_svg('map', '', 24) ?></span>
                                <div>
                                    <h2 class="secao-titulo">Trilha Atual</h2>
                                    <p class="secao-subtitulo">Seu avanço nos mundos matemáticos (20 fases por ano)</p>
                                </div>
                            </div>

                            <div class="trilhas-lista" id="trilhas-lista">
                                <div class="trilha-loading texto-mutado">Buscando seu progresso...</div>
                            </div>
                        </div>

                    </section>

                <?php else: ?>
                    <!-- Estado Visitante: Card Jogar com Boa Hierarquia -->
                    <div class="card-jogar card-jogar-visitante" id="card-jogar-visitante" role="button" tabindex="0"
                        aria-label="Ir para a página de jogos"
                        onclick="window.location.href='../jogos/jogos.php'"
                        onkeydown="if(event.key==='Enter'||event.key===' ')window.location.href='../jogos/jogos.php'">
                        <div class="card-jogar-header">
                            <div class="card-jogar-titulo-wrap">
                                <span class="card-jogar-icone" aria-hidden="true"><?= icone_svg('game', '', 28) ?></span>
                                <h2>Jogar Agora</h2>
                            </div>
                            <span class="badge-tag">Acesso Livre</span>
                        </div>
                        <p>Experimente os jogos matemáticos do Fundamental 2 sem precisar de cadastro imediato!</p>
                        <div class="card-jogar-cta-row">
                            <span class="btn-duo btn-duo-green">Explorar Desafios</span>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Coluna Lateral (Direita) -->
            <aside class="home-col-aside" aria-label="Painel lateral da conta">
                <?php if ($logado): ?>
                    <!-- Card Perfil Lateral -->
                    <div class="card-lateral card-perfil-lateral" id="card-perfil-lateral">
                        <?php
                        $fotoHome = $usuario['foto_url'] ?? '';
                        if (empty($fotoHome)) {
                            $fotoHomeUrl = '../fotos/foto1.webp';
                        } elseif (!str_starts_with($fotoHome, 'http') && !str_starts_with($fotoHome, '/')) {
                            $fotoHomeUrl = '../' . ltrim(str_replace('../', '', $fotoHome), '/');
                        } else {
                            $fotoHomeUrl = $fotoHome;
                        }
                        ?>
                        <div class="perfil-avatar-wrapper">
                            <img id="foto-perfil" src="<?= htmlspecialchars($fotoHomeUrl) ?>"
                                alt="Foto de perfil de <?= htmlspecialchars($usuario['username']) ?>" width="88" height="88"
                                onerror="this.src='../fotos/foto1.webp'">
                        </div>
                        <h2 id="nome-usuario"><?= htmlspecialchars($usuario['username']) ?></h2>
                        <span class="perfil-tag">Aluno(a) Mantham</span>

                        <!-- Mini Estatísticas do Usuário -->
                        <div class="perfil-stats-resumo">
                            <div class="perfil-stat-chip">
                                <span class="chip-icone-svg"><?= icone_svg('star', 'estrela-amarela', 18) ?></span>
                                <div class="chip-textos">
                                    <span class="chip-label">Estrelas</span>
                                    <strong class="chip-valor" id="perfil-total-estrelas">0</strong>
                                </div>
                            </div>
                            <div class="perfil-stat-chip">
                                <span class="chip-icone-svg"><?= icone_svg('trophy', '', 18) ?></span>
                                <div class="chip-textos">
                                    <span class="chip-label">Concluídas</span>
                                    <strong class="chip-valor" id="perfil-total-fases">0 / 80</strong>
                                </div>
                            </div>
                        </div>

                        <div class="perfil-botoes-acao">
                            <a href="../perfil/perfil.php" class="btn-duo btn-duo-outline">Meu Perfil</a>
                            <a href="../conteudos/conteudos.php" class="btn-duo btn-duo-blue">Ir para a Trilha</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Card Visitante: Convite de Login -->
                    <div class="card-lateral card-visitante-aviso" id="card-visitante-aviso">
                        <div class="visitante-icone-wrapper" aria-hidden="true">
                            <?= icone_svg('lock', '', 36) ?>
                        </div>
                        <h2>Sua Conta</h2>
                        <p class="msg-aviso-visitante">Faça login no site para salvar seu progresso nas 20 fases de cada ano e colecionar estrelas.</p>
                        <div class="visitante-botoes-acao">
                            <a href="../login/login.php" class="btn-duo btn-duo-green"><?= icone_svg('key', '', 18) ?> Entrar</a>
                            <a href="../cadastro/cadastro.php" class="btn-duo btn-duo-outline">Criar Conta</a>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>

        </div>
    </main>

    <!-- ===== MODAL: Sugestão de Login/Cadastro ===== -->
    <?php require __DIR__ . '/../includes/modal_perfil.php'; ?>

    <!-- Injeta estado de login para o JS sem nova requisição -->
    <script>
        const LOGADO = <?= $logado ? 'true' : 'false' ?>;
        const USUARIO = <?= $logado ? json_encode($usuario) : 'null' ?>;
    </script>
    <script src="../includes/icones.js"></script>
    <script src="../includes/modal-perfil.js"></script>
    <script src="home.js?v=<?= $jsVersion ?>"></script>

</body>

</html>