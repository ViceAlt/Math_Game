<?php session_start(); $usuario = $_SESSION['usuario'] ?? null; $logado = $usuario !== null; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Configurações de acessibilidade e aparência - Math Game">
    <title>Configurações | Math Game</title>
    <link rel="stylesheet" href="../tema.css">
    <link rel="stylesheet" href="configuracoes.css">
    <link rel="stylesheet" href="../includes/navbar.css">
    <link rel="stylesheet" href="../includes/modal-perfil.css">
    <script src="../tema.js"></script>
</head>
<body>

    <?php require __DIR__ . '/../includes/navbar.php'; ?>

    <main class="main" id="main-content">
        <h1>Configurações</h1>

        <section aria-labelledby="titulo-aparencia">
            <h2 id="titulo-aparencia">Aparência</h2>
            <div class="config-item">
                <label for="toggle-tema" id="label-tema">Modo Escuro</label>
                <input type="checkbox" role="switch" id="toggle-tema"
                       aria-labelledby="label-tema"
                       onchange="alternarTema(this.checked)">
            </div>

            <div class="tonalidade-separador"></div>
            <h3 id="titulo-tonalidade" class="tonalidade-titulo">Tonalidade</h3>
            <p class="tonalidade-desc">Escolha a combinação de cores do site</p>

            <div class="tonalidade-grid" role="radiogroup" aria-labelledby="titulo-tonalidade">

                <!-- Padrão (Azul + Verde) -->
                <button type="button" class="tonalidade-card" id="ton-padrao"
                        role="radio" aria-checked="true" aria-label="Tonalidade Padrão"
                        onclick="selecionarTonalidade('padrao')">
                    <div class="mini-page mp-padrao">
                        <div class="mp-sidebar">
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                        </div>
                        <div class="mp-content">
                            <div class="mp-header"></div>
                            <div class="mp-cards">
                                <div class="mp-card"></div>
                                <div class="mp-card"></div>
                            </div>
                            <div class="mp-btn-primary"></div>
                            <div class="mp-btn-secondary"></div>
                        </div>
                    </div>
                    <span class="tonalidade-nome">Padrão</span>
                </button>

                <!-- Sunset (Laranja + Coral) -->
                <button type="button" class="tonalidade-card" id="ton-sunset"
                        role="radio" aria-checked="false" aria-label="Tonalidade Sunset"
                        onclick="selecionarTonalidade('sunset')">
                    <div class="mini-page mp-sunset">
                        <div class="mp-sidebar">
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                        </div>
                        <div class="mp-content">
                            <div class="mp-header"></div>
                            <div class="mp-cards">
                                <div class="mp-card"></div>
                                <div class="mp-card"></div>
                            </div>
                            <div class="mp-btn-primary"></div>
                            <div class="mp-btn-secondary"></div>
                        </div>
                    </div>
                    <span class="tonalidade-nome">Sunset</span>
                </button>

                <!-- Ocean (Teal + Menta) -->
                <button type="button" class="tonalidade-card" id="ton-ocean"
                        role="radio" aria-checked="false" aria-label="Tonalidade Ocean"
                        onclick="selecionarTonalidade('ocean')">
                    <div class="mini-page mp-ocean">
                        <div class="mp-sidebar">
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                        </div>
                        <div class="mp-content">
                            <div class="mp-header"></div>
                            <div class="mp-cards">
                                <div class="mp-card"></div>
                                <div class="mp-card"></div>
                            </div>
                            <div class="mp-btn-primary"></div>
                            <div class="mp-btn-secondary"></div>
                        </div>
                    </div>
                    <span class="tonalidade-nome">Ocean</span>
                </button>

                <!-- Earth (Âmbar + Terracota) -->
                <button type="button" class="tonalidade-card" id="ton-earth"
                        role="radio" aria-checked="false" aria-label="Tonalidade Earth"
                        onclick="selecionarTonalidade('earth')">
                    <div class="mini-page mp-earth">
                        <div class="mp-sidebar">
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                        </div>
                        <div class="mp-content">
                            <div class="mp-header"></div>
                            <div class="mp-cards">
                                <div class="mp-card"></div>
                                <div class="mp-card"></div>
                            </div>
                            <div class="mp-btn-primary"></div>
                            <div class="mp-btn-secondary"></div>
                        </div>
                    </div>
                    <span class="tonalidade-nome">Earth</span>
                </button>

                <!-- Berry (Cereja + Rosa quente) -->
                <button type="button" class="tonalidade-card" id="ton-berry"
                        role="radio" aria-checked="false" aria-label="Tonalidade Berry"
                        onclick="selecionarTonalidade('berry')">
                    <div class="mini-page mp-berry">
                        <div class="mp-sidebar">
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                        </div>
                        <div class="mp-content">
                            <div class="mp-header"></div>
                            <div class="mp-cards">
                                <div class="mp-card"></div>
                                <div class="mp-card"></div>
                            </div>
                            <div class="mp-btn-primary"></div>
                            <div class="mp-btn-secondary"></div>
                        </div>
                    </div>
                    <span class="tonalidade-nome">Berry</span>
                </button>

            </div>
        </section>

        <section aria-labelledby="titulo-daltonismo">
            <h2 id="titulo-daltonismo">Modo de Daltonismo</h2>
            <div class="config-item" role="group" aria-labelledby="titulo-daltonismo">
                <label><input type="radio" name="daltonismo" id="dal-nenhum" value="nenhum"
                              onchange="selecionarDaltonismo('nenhum')"> Nenhum (padrão)</label>
                <label><input type="radio" name="daltonismo" id="dal-protanopia" value="protanopia"
                              onchange="selecionarDaltonismo('protanopia')"> Protanopia (dificuldade com vermelho)</label>
                <label><input type="radio" name="daltonismo" id="dal-deuteranopia" value="deuteranopia"
                              onchange="selecionarDaltonismo('deuteranopia')"> Deuteranopia (dificuldade com verde)</label>
                <label><input type="radio" name="daltonismo" id="dal-tritanopia" value="tritanopia"
                              onchange="selecionarDaltonismo('tritanopia')"> Tritanopia (dificuldade com azul)</label>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../includes/modal_perfil.php'; ?>

    <script>
        const LOGADO  = <?= $logado ? 'true' : 'false' ?>;
        const USUARIO = <?= $logado ? json_encode($usuario) : 'null' ?>;
    </script>
    <script src="../includes/modal-perfil.js"></script>
    <script src="configuracoes.js"></script>
</body>
</html>
