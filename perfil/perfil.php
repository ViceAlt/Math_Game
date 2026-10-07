<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;
$logado  = $usuario !== null;

// Perfil requer login – redireciona visitantes
if (!$logado) {
    header('Location: ../login/login.php?redirect=../perfil/perfil.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Perfil do usuário - Math Game">
    <title>Perfil | Math Game</title>
    <link rel="stylesheet" href="perfil.css">
</head>
<body>

    <nav class="sidebar" aria-label="Navegação principal">
        <ul>
            <li><a href="../home/home.php"><button type="button" id="nav-home">Home</button></a></li>
            <li><a href="../conteudos/conteudos.php"><button type="button" id="nav-conteudos">Conteúdos</button></a></li>
            <li><a href="../jogos/jogos.php"><button type="button" id="nav-jogos">Jogos</button></a></li>
            <li><a href="perfil.php" aria-current="page"><button type="button" id="nav-perfil">Perfil</button></a></li>
            <li><a href="../configuracoes/configuracoes.php"><button type="button" id="nav-configuracoes">Configurações</button></a></li>
        </ul>
    </nav>

    <main class="main" id="main-content">
        <h1>Meu Perfil</h1>

        <div class="perfil-card" aria-label="Cartão de perfil">
            <img id="perfil-foto"
                 src="<?= htmlspecialchars($usuario['foto_url'] ?? 'assets/avatar-padrao.png') ?>"
                 alt="Foto de perfil" width="96" height="96">

            <dl class="perfil-info">
                <dt>Nome de usuário</dt>
                <dd id="perfil-username"><?= htmlspecialchars($usuario['username']) ?></dd>

                <dt>E-mail</dt>
                <dd id="perfil-email"><?= htmlspecialchars($usuario['email'] ?? '—') ?></dd>

                <dt>Conta criada em</dt>
                <dd id="perfil-criado-em">
                    <?php
                    $criadoEm = $usuario['criado_em'] ?? null;
                    echo $criadoEm
                        ? (new DateTime($criadoEm))->format('d/m/Y')
                        : '—';
                    ?>
                </dd>

                <dt>Pontuação total acumulada</dt>
                <dd id="perfil-pontuacao">Carregando…</dd>

                <dt>Total de estrelas</dt>
                <dd id="perfil-estrelas">Carregando…</dd>
            </dl>
        </div>

        <button type="button" id="btn-sair" onclick="fazerLogout()">Sair da Conta</button>
    </main>

    <script>
        const LOGADO  = true;
        const USUARIO = <?= json_encode($usuario) ?>;
    </script>
    <script src="perfil.js"></script>
</body>
</html>
