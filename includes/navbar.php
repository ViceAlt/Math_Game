<?php
/**
 * includes/navbar.php
 * Barra de navegação superior horizontal fixa compartilhada por todas as páginas.
 * Estilo Duolingo-like: limpo, amigável, arredondado, 3D tátil e 100% livre de emojis.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/icones.php';

$usuarioAtual = $_SESSION['usuario'] ?? null;
$estaLogado = $usuarioAtual !== null;
$paginaAtual = basename($_SERVER['SCRIPT_NAME'] ?? '');

$urlBase = rtrim(APP_URL, '/');
$urlHome = $urlBase . '/home/home.php';
$urlConteudos = $urlBase . '/conteudos/conteudos.php';
$urlJogos = $urlBase . '/jogos/jogos.php';
$urlPerfil = $urlBase . '/perfil/perfil.php';
$urlConfig = $urlBase . '/configuracoes/configuracoes.php';
$urlLogin = $urlBase . '/login/login.php';
?>
<!-- ===== BARRA SUPERIOR FIXA (NAVBAR) ===== -->
<header class="top-navbar" aria-label="Navegação superior principal">
    <div class="top-navbar-container">

        <!-- Logo / Marca -->
        <a href="<?= $urlHome ?>" class="top-navbar-brand" aria-label="Mantham - Página inicial">
            <span class="brand-badge-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </span>
            <span class="brand-text"><span class="brand-man">Man</span><span class="brand-tham">tham</span></span>
        </a>

        <!-- Botão Mobile Hamburger -->
        <button type="button" class="mobile-toggle" id="btn-toggle-menu" aria-label="Abrir menu de navegação" aria-expanded="false" onclick="toggleMenuMobile()">
            <?= icone_svg('menu', 'icone-hamburger', 24) ?>
        </button>

        <!-- Links de Navegação -->
        <nav aria-label="Menu principal">
            <ul class="top-navbar-nav" id="menu-navegacao">
                <li>
                    <a href="<?= $urlHome ?>" <?= ($paginaAtual === 'home.php') ? 'aria-current="page"' : '' ?>>
                        <button type="button" class="top-navbar-btn" id="nav-home">
                            <span class="btn-icon" aria-hidden="true"><?= icone_svg('home', '', 20) ?></span>
                            <span class="btn-label">Home</span>
                        </button>
                    </a>
                </li>
                <li>
                    <a href="<?= $urlConteudos ?>" <?= ($paginaAtual === 'conteudos.php') ? 'aria-current="page"' : '' ?>>
                        <button type="button" class="top-navbar-btn" id="nav-conteudos">
                            <span class="btn-icon" aria-hidden="true"><?= icone_svg('map', '', 20) ?></span>
                            <span class="btn-label">Conteúdos</span>
                        </button>
                    </a>
                </li>
                <li>
                    <a href="<?= $urlJogos ?>" <?= ($paginaAtual === 'jogos.php') ? 'aria-current="page"' : '' ?>>
                        <button type="button" class="top-navbar-btn" id="nav-jogos">
                            <span class="btn-icon" aria-hidden="true"><?= icone_svg('game', '', 20) ?></span>
                            <span class="btn-label">Jogos</span>
                        </button>
                    </a>
                </li>
                <li>
                    <a href="<?= $urlConfig ?>" <?= ($paginaAtual === 'configuracoes.php') ? 'aria-current="page"' : '' ?>>
                        <button type="button" class="top-navbar-btn" id="nav-configuracoes">
                            <span class="btn-icon" aria-hidden="true"><?= icone_svg('settings', '', 20) ?></span>
                            <span class="btn-label">Configurações</span>
                        </button>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Área do Usuário / Login (Extremidade Direita) -->
        <div class="top-navbar-user">
            <?php if ($estaLogado): ?>
                <a href="<?= $urlPerfil ?>" class="user-chip" id="nav-perfil-chip" aria-label="Perfil do usuário <?= htmlspecialchars($usuarioAtual['username'], ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars($usuarioAtual['foto_url'] ?? ($urlBase . '/assets/avatar-padrao.png'), ENT_QUOTES, 'UTF-8') ?>"
                         alt="Foto de perfil de <?= htmlspecialchars($usuarioAtual['username'], ENT_QUOTES, 'UTF-8') ?>" width="34" height="34"
                         onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?= urlencode($usuarioAtual['username']) ?>'">
                    <span class="user-chip-name"><?= htmlspecialchars($usuarioAtual['username'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $urlLogin ?>" class="btn-duo btn-duo-green btn-nav-login-duo" id="nav-btn-entrar" aria-label="Entrar ou criar conta">
                    <?= icone_svg('key', '', 18) ?> Entrar
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<script>
    function toggleMenuMobile() {
        const menu = document.getElementById('menu-navegacao');
        const btn = document.getElementById('btn-toggle-menu');
        if (!menu || !btn) return;
        const aberto = menu.classList.toggle('aberto');
        btn.setAttribute('aria-expanded', String(aberto));
    }
</script>
