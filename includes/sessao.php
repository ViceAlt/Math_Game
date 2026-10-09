<?php
/**
 * includes/sessao.php
 * Configuração segura de sessão e funções de guarda para páginas protegidas.
 * Inclua este arquivo antes de session_start() nas páginas que exigem autenticação.
 */

/**
 * Inicializa a sessão com cookies seguros (httponly, samesite=Lax, secure em produção).
 * Deve ser chamada UMA VEZ antes de qualquer saída HTML.
 */
function sessao_iniciar(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // Sessão já ativa, não reinicia
    }

    $eHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
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
        $destino = '/login/login.php';
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
            header('Location: /login/login.php?aviso=senha_alterada');
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
