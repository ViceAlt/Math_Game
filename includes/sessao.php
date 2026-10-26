<?php
/**
 * includes/sessao.php
 * Configuração segura de sessão e funções de guarda para páginas protegidas.
 * Inclua este arquivo antes de session_start() nas páginas que exigem autenticação.
 */

require_once __DIR__ . '/../config.php';

/**
 * Inicializa a sessão com cookies seguros (httponly, samesite=Lax, secure em produção).
 * Deve ser chamada UMA VEZ antes de qualquer saída HTML.
 */
function sessao_iniciar(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // Sessão já ativa, não reinicia
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        if (APP_ENV === 'production' && sessao_cookie_seguro()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

        $eHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (defined('APP_ENV') && APP_ENV === 'production');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $eHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (empty($_SESSION['usuario'])) {
        sessao_restaurar_login_persistente();
    }
}

function sessao_cookie_seguro(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (defined('APP_ENV') && APP_ENV === 'production');
}

function sessao_cookie_persistente(string $valor, int $expiraEm): void {
    setcookie('mg_remember', $valor, [
        'expires' => $expiraEm,
        'path' => '/',
        'secure' => sessao_cookie_seguro(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function sessao_limpar_cookie_persistente(): void {
    if (!isset($_COOKIE['mg_remember'])) {
        return;
    }

    sessao_cookie_persistente('', time() - 3600);
    unset($_COOKIE['mg_remember']);
}

function sessao_criar_login_persistente(PDO $pdo, int $usuarioId): void {
    $seletor = bin2hex(random_bytes(16));
    $validador = bin2hex(random_bytes(32));
    $expiraEm = time() + (30 * 24 * 60 * 60);
    $expiraSql = date('Y-m-d H:i:s', $expiraEm);

    $stmt = $pdo->prepare(
        'INSERT INTO login_persistente (usuario_id, seletor, validador_hash, expira_em)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$usuarioId, $seletor, hash('sha256', $validador), $expiraSql]);

    $_SESSION['remember_selector'] = $seletor;
    sessao_cookie_persistente($seletor . ':' . $validador, $expiraEm);
}

function sessao_restaurar_login_persistente(): void {
    $cookie = $_COOKIE['mg_remember'] ?? '';
    if (!preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/', $cookie, $partes)) {
        if ($cookie !== '') {
            sessao_limpar_cookie_persistente();
        }
        return;
    }

    try {
        require_once __DIR__ . '/../conexao.php';
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT lp.id AS token_id, lp.usuario_id, lp.validador_hash, lp.criado_em, lp.expira_em,
                    u.id, u.username, u.email, u.foto_url, u.criado_em AS usuario_criado_em,
                    u.senha_alterada_em
             FROM login_persistente lp
             JOIN usuarios u ON u.id = lp.usuario_id
             WHERE lp.seletor = ? LIMIT 1'
        );
        $stmt->execute([$partes[1]]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        $valido = $registro
            && strtotime($registro['expira_em']) > time()
            && hash_equals($registro['validador_hash'], hash('sha256', $partes[2]));

        if ($valido && !empty($registro['senha_alterada_em'])) {
            $valido = strtotime($registro['senha_alterada_em']) <= strtotime($registro['criado_em']);
        }

        if (!$valido) {
            if ($registro) {
                $pdo->prepare('DELETE FROM login_persistente WHERE id = ?')->execute([$registro['token_id']]);
            }
            sessao_limpar_cookie_persistente();
            return;
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => (int) $registro['usuario_id'],
            'username' => $registro['username'],
            'email' => $registro['email'],
            'foto_url' => $registro['foto_url'],
            'criado_em' => $registro['usuario_criado_em'],
        ];
        $_SESSION['logado_em'] = time();

        $novoSeletor = bin2hex(random_bytes(16));
        $novoValidador = bin2hex(random_bytes(32));
        $expiraEm = time() + (30 * 24 * 60 * 60);
        $pdo->prepare(
            'UPDATE login_persistente
             SET seletor = ?, validador_hash = ?, criado_em = NOW(), expira_em = ?
             WHERE id = ?'
        )->execute([
            $novoSeletor,
            hash('sha256', $novoValidador),
            date('Y-m-d H:i:s', $expiraEm),
            $registro['token_id'],
        ]);

        $_SESSION['remember_selector'] = $novoSeletor;
        sessao_cookie_persistente($novoSeletor . ':' . $novoValidador, $expiraEm);
    } catch (\Throwable $e) {
        error_log('[Math Game] Falha ao restaurar login persistente: ' . $e->getMessage());
    }
}

function sessao_revogar_login_persistente(PDO $pdo): void {
    $cookie = $_COOKIE['mg_remember'] ?? '';
    if (preg_match('/^([a-f0-9]{32}):[a-f0-9]{64}$/', $cookie, $partes)) {
        $pdo->prepare('DELETE FROM login_persistente WHERE seletor = ?')->execute([$partes[1]]);
    }

    $seletorSessao = $_SESSION['remember_selector'] ?? '';
    if (preg_match('/^[a-f0-9]{32}$/', $seletorSessao)) {
        $pdo->prepare('DELETE FROM login_persistente WHERE seletor = ?')->execute([$seletorSessao]);
    }

    sessao_limpar_cookie_persistente();
}

/**
 * Exige que o usuário esteja autenticado.
 * Se não estiver, redireciona para o login.
 * Também verifica se a senha foi alterada depois do login (invalida sessão antiga).
 *
 * @param string $redirectApos URL para onde redirecionar após login.
 */
function sessao_exigir_login(string $redirectApos = ''): void {
    if (empty($_SESSION['usuario'])) {
        $destino = rtrim(APP_URL, '/') . '/login/login.php';
        if ($redirectApos) {
            $destino .= '?redirect=' . urlencode($redirectApos);
        }
        header('Location: ' . $destino);
        exit;
    }

    // Verifica se a senha foi alterada após o início desta sessão
    sessao_verificar_validade();
}

/**
 * Verifica se a sessão está obsoleta comparando o horário de login
 * com o campo senha_alterada_em do banco. Se a senha foi alterada
 * depois do login, a sessão é destruída e o usuário é redirecionado.
 */
function sessao_verificar_validade(): void {
    // Se a sessão não tem horário de login registrado, registra agora
    if (empty($_SESSION['logado_em'])) {
        $_SESSION['logado_em'] = time();
        return;
    }

    $usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
    if ($usuarioId <= 0) {
        return;
    }

    try {
        require_once __DIR__ . '/../conexao.php';
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT senha_alterada_em FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch();

        if (!$row || $row['senha_alterada_em'] === null) {
            return;
        }

        $alteradaEm = strtotime($row['senha_alterada_em']);
        $logadoEm   = (int) $_SESSION['logado_em'];

        // Sessão foi iniciada antes da troca de senha → invalida
        if ($alteradaEm > $logadoEm) {
            sessao_destruir();
            header('Location: ' . rtrim(APP_URL, '/') . '/login/login.php?aviso=senha_alterada');
            exit;
        }

    } catch (\PDOException) {
        // Falha silenciosa: não derruba a sessão por erro de banco
    }
}

/**
 * Destrói a sessão atual e invalida o cookie no navegador.
 */
function sessao_destruir(): void {
    session_unset();
    session_destroy();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 86400,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
}
