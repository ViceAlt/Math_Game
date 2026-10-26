<?php
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../includes/secret_id.php';
require_once __DIR__ . '/../conexao.php';
sessao_iniciar();
$usuario = $_SESSION['usuario'] ?? null;
$logado = $usuario !== null;
$secretId = null;
$secretError = '';

if ($logado) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT recovery_secret_encrypted FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $usuario['id']]);
        $encryptedSecret = $stmt->fetchColumn();

        if (!$encryptedSecret) {
            $secretId = secret_id_criar($pdo, (int) $usuario['id']);
        } else {
            $secretId = secret_id_decifrar((string) $encryptedSecret);
        }

        if ($secretId === null) {
            throw new RuntimeException('Não foi possível descriptografar o segredo.');
        }
    } catch (Throwable $e) {
        error_log('[Math Game] Erro ao carregar identificador de recuperação: ' . $e->getMessage());
        $secretError = 'O identificador ainda não está disponível. Verifique a configuração do servidor.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Configurações de acessibilidade e aparência - Mantham">
    <title>Configurações | Mantham</title>
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

        <?php if ($logado): ?>
        <section aria-labelledby="titulo-secret-id">
            <h2 id="titulo-secret-id">Identificador de recuperação</h2>
            <p class="secret-id-explicacao">Este código é único e fixo para sua conta. Use-o junto com seu nome de usuário e e-mail em “Esqueci minha senha” para abrir a redefinição sem depender de envio de e-mail. Guarde-o em local seguro e não compartilhe.</p>
            <?php if ($secretId !== null): ?>
                <div class="secret-id-linha">
                    <code id="secret-id-valor"><?= htmlspecialchars($secretId, ENT_QUOTES, 'UTF-8') ?></code>
                    <button type="button" id="copiar-secret-id" aria-label="Copiar identificador de recuperação" title="Copiar identificador">Copiar</button>
                </div>
                <span id="secret-id-status" role="status" aria-live="polite"></span>
            <?php else: ?>
                <p class="secret-id-erro" role="alert"><?= htmlspecialchars($secretError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </section>
        <?php endif; ?>

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
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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

                <!-- Crimson (Rubi vibrante + Coral intenso) -->
                <button type="button" class="tonalidade-card" id="ton-crimson"
                        role="radio" aria-checked="false" aria-label="Tonalidade Crimson"
                        onclick="selecionarTonalidade('crimson')">
                    <div class="mini-page mp-crimson">
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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
                    <span class="tonalidade-nome">Crimson</span>
                </button>

                <!-- Arctic (Azul Petróleo + Ciano Gelo) -->
                <button type="button" class="tonalidade-card" id="ton-arctic"
                        role="radio" aria-checked="false" aria-label="Tonalidade Arctic"
                        onclick="selecionarTonalidade('arctic')">
                    <div class="mini-page mp-arctic">
                        <div class="mp-topbar">
                            <div class="mp-brand"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-nav-item mp-nav-active"></div>
                            <div class="mp-nav-item"></div>
                            <div class="mp-avatar"></div>
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
                    <span class="tonalidade-nome">Arctic</span>
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
    <?php if ($logado && $secretId !== null): ?>
    <script>
        document.getElementById('copiar-secret-id')?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(document.getElementById('secret-id-valor').textContent);
                document.getElementById('secret-id-status').textContent = 'Identificador copiado.';
            } catch (_) {
                document.getElementById('secret-id-status').textContent = 'Não foi possível copiar. Selecione o código manualmente.';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
