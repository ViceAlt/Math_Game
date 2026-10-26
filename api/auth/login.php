<?php
/**
 * api/auth/login.php
 * POST – Autenticação por email + senha com bcrypt.
 *
 * Melhorias de segurança:
 * - Login por email (não mais por username)
 * - Rate limiting: máx. 5 falhas por email+IP em 15 min
 * - Mensagem de erro genérica para email inexistente ou senha errada
 * - dummy hash para mitigar timing attack em usuário inexistente
 * - session_regenerate_id após login bem-sucedido
 * - password_needs_rehash automático
 * - Registro de horário de login na sessão (para invalidação por troca de senha)
 * - Token CSRF validado
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/../../conexao.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/senha.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/recuperacao.php';
require_once __DIR__ . '/../../includes/sessao.php';

sessao_iniciar();

$input = json_decode(file_get_contents('php://input'), true);

// Lê campos do JSON (mantém compatibilidade legada: aceita 'email' ou 'username')
$emailOuUsername = trim($input['email'] ?? $input['username'] ?? '');
$senha           = $input['senha'] ?? '';
$csrfToken       = $input['csrf_token'] ?? '';

// ── Validação de CSRF ─────────────────────────────────────────────────────────
if (!csrf_validar($csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Requisição inválida. Recarregue a página.']);
    exit;
}

// ── Validação básica ──────────────────────────────────────────────────────────
if (empty($emailOuUsername) || empty($senha)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Preencha todos os campos.']);
    exit;
}

// Normaliza: trata como email se contiver '@', senão como username (retrocompatibilidade)
$ehEmail = filter_var($emailOuUsername, FILTER_VALIDATE_EMAIL) !== false;
$chaveLogin = $ehEmail ? strtolower($emailOuUsername) : $emailOuUsername;
$ip = recuperacao_obter_ip();
$chaveRateLimit = strtolower($chaveLogin) . '|' . $ip;

// ── Rate limiting de login ─────────────────────────────────────────────────────
try {
    $tentativas = recuperacao_contar_tentativas('login', $chaveRateLimit, 15);
    if ($tentativas >= 5) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'erro' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.']);
        exit;
    }

    // ── Autenticação ──────────────────────────────────────────────────────────
    $pdo = getDB();

    // Busca por email ou username dependendo do que foi informado
    if ($ehEmail) {
        $stmt = $pdo->prepare(
            'SELECT id, username, email, senha_hash, foto_url, criado_em FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$chaveLogin]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, email, senha_hash, foto_url, criado_em FROM usuarios WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$chaveLogin]);
    }

    $usuario = $stmt->fetch();

    // Se o usuário não existir, executa dummy hash para igualar tempo de resposta
    if (!$usuario) {
        verificar_dummy_hash($senha);
        recuperacao_registrar_tentativa('login', $chaveRateLimit);
        http_response_code(401);
        echo json_encode(['ok' => false, 'erro' => 'Email ou senha incorretos.']);
        exit;
    }

    // Verifica a senha (suporta bcrypt + migração de hashes legados)
    $senhaCorreta = verificar_senha($senha, $usuario['senha_hash']);

    if (!$senhaCorreta) {
        recuperacao_registrar_tentativa('login', $chaveRateLimit);
        http_response_code(401);
        echo json_encode(['ok' => false, 'erro' => 'Email ou senha incorretos.']);
        exit;
    }

    // ── Login bem-sucedido ────────────────────────────────────────────────────

    // Migração transparente: se a senha estava em formato legado, regera o bcrypt
    if (precisa_rehash($usuario['senha_hash'])) {
        $novoHash = gerar_hash_senha($senha);
        $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([$novoHash, $usuario['id']]);
    }

    // Limpa as tentativas de login desta chave após sucesso
    recuperacao_limpar_tentativas('login', $chaveRateLimit);

    // Regenera o ID de sessão para prevenir session fixation
    session_regenerate_id(true);

    // Armazena sessão (sem senha, sem hash)
    $_SESSION['usuario'] = [
        'id'        => (int) $usuario['id'],
        'username'  => $usuario['username'],
        'email'     => $usuario['email'],
        'foto_url'  => $usuario['foto_url'],
        'criado_em' => $usuario['criado_em'],
    ];
    // Marca o horário do login para validação de sessão pós-troca de senha
    $_SESSION['logado_em'] = time();
    sessao_revogar_login_persistente($pdo);
    sessao_criar_login_persistente($pdo, (int) $usuario['id']);

    echo json_encode([
        'ok'      => true,
        'usuario' => $_SESSION['usuario'],
    ]);

} catch (\PDOException $e) {
    error_log('[Math Game] Erro no login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Serviço de autenticação indisponível. Verifique a conexão e a configuração do banco de dados.']);
} catch (\Throwable $e) {
    error_log('[Math Game] Falha de configuração no login: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'erro' => 'Servidor sem configuração de banco de dados. Configure DB_HOST, DB_NAME, DB_USER e DB_PASS no ambiente privado.']);
}
