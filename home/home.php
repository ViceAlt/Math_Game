<?php
/**
 * home.php – Página inicial
 * Verifica sessão PHP no servidor e injeta os dados diretamente no HTML,
 * evitando flash de conteúdo que acontece com fetch assíncrono.
 */
session_start();
$usuario = $_SESSION['usuario'] ?? null;
$logado = $usuario !== null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plataforma de jogos de Matemática para o Ensino Fundamental 2">
    <title>Home | Math Game</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="home.css">
    <script src="../tema.js"></script>
</head>

<body>

    <!-- ===== SIDEBAR FIXA ===== -->
    <nav class="sidebar" aria-label="Navegação principal">
        <ul>
            <li><a href="home.php" aria-current="page"><button type="button" id="nav-home">Home</button></a>
            </li>
            <li><a href="../conteudos/conteudos.php"><button type="button" id="nav-conteudos">Conteúdos</button></a>
            </li>
            <li><a href="../jogos/jogos.php"><button type="button" id="nav-jogos">Jogos</button></a></li>
            <li><button type="button" id="nav-perfil" onclick="navegarPerfil()">Perfil</button></li>
            <li><a href="../configuracoes/configuracoes.php"><button type="button"
                        id="nav-configuracoes">Configurações</button></a></li>
        </ul>
    </nav>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <main class="main" id="main-content">

        <?php if ($logado): ?>
            <!-- Estado: Usuário LOGADO – renderizado pelo PHP, sem flash -->
            <section id="section-logado" aria-label="Área do usuário logado">

                <div class="perfil-resumo" aria-label="Resumo do perfil">
                    <img id="foto-perfil" src="<?= htmlspecialchars($usuario['foto_url'] ?? 'assets/avatar-padrao.png') ?>"
                        alt="Foto de perfil de <?= htmlspecialchars($usuario['username']) ?>" width="48" height="48">
                    <span id="nome-usuario"><?= htmlspecialchars($usuario['username']) ?></span>
                </div>

                <div class="ultimo-jogo" id="ultimo-jogo" aria-label="Último jogo acessado">
                    <h2>Último Jogo Acessado</h2>
                    <p id="ultimo-jogo-nome">—</p>
                </div>

                <div class="card-jogar" id="card-jogar" role="button" tabindex="0" aria-label="Ir para a página de jogos"
                    onclick="window.location.href='../jogos/jogos.php'"
                    onkeydown="if(event.key==='Enter')window.location.href='../jogos/jogos.php'">
                    <h2>Jogar</h2>
                    <p>Clique para explorar os jogos</p>
                </div>

                <aside class="progresso-trilha" id="progresso-trilha" aria-label="Progresso na trilha">
                    <h3>Trilha: <span id="trilha-ano">6° Ano</span></h3>
                    <p>Fase atual: <strong id="trilha-fase">1</strong></p>
                    <p>Estrelas: <strong id="trilha-estrelas">0 ⭐</strong></p>
                </aside>

            </section>

        <?php else: ?>
            <!-- Estado: Visitante -->
            <section id="section-visitante" aria-label="Área de visitante">

                <p class="bg-texto-visitante" aria-hidden="true">Faça login para ter mais opções</p>

                <div class="card-jogar" id="card-jogar-visitante" role="button" tabindex="0"
                    aria-label="Ir para a página de jogos" onclick="window.location.href='../jogos/jogos.php'"
                    onkeydown="if(event.key==='Enter')window.location.href='../jogos/jogos.php'">
                    <h2>Jogar</h2>
                    <p>Experimente sem criar conta</p>
                </div>

            </section>
        <?php endif; ?>

    </main>

    <!-- ===== MODAL: Sugestão de Login/Cadastro ===== -->
    <dialog id="modal-login-sugestao" aria-labelledby="modal-titulo">
        <article>
            <h2 id="modal-titulo">Crie sua conta!</h2>
            <p>Faça login ou cadastre-se para acompanhar seu progresso, ganhar estrelas e desbloquear novas fases.</p>
            <footer>
                <a href="../login/login.php"><button type="button" id="btn-ir-login">Fazer Login</button></a>
                <a href="../cadastro/cadastro.php"><button type="button" id="btn-ir-cadastro">Criar Conta</button></a>
                <button type="button" id="btn-fechar-modal" onclick="fecharModal()">Agora não</button>
            </footer>
        </article>
    </dialog>

    <!-- Injeta estado de login para o JS sem nova requisição -->
    <script>
        const LOGADO = <?= $logado ? 'true' : 'false' ?>;
        const USUARIO = <?= $logado ? json_encode($usuario) : 'null' ?>;
    </script>
    <script src="home.js"></script>

</body>

</html>