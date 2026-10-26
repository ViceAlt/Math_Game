<?php
/**
 * api/auth/register.php
 * POST – Cadastro de novo usuário.
 * Usa password_hash(PASSWORD_BCRYPT) com custo 12.
 * Validação de senha: mínimo 8 e máximo 72 bytes.
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
require_once __DIR__ . '/../../includes/senha.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/sessao.php';
require_once __DIR__ . '/../../includes/secret_id.php';

sessao_iniciar();

$input = json_decode(file_get_contents('php://input'), true);

$username  = trim($input['username'] ?? '');
$email     = strtolower(trim($input['email'] ?? ''));
$senha     = $input['senha'] ?? '';           // Sem trim() na senha (intencional)
$csrfToken = $input['csrf_token'] ?? '';

// ── Validação de CSRF ─────────────────────────────────────────────────────────
if (!csrf_validar($csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Requisição inválida. Recarregue a página.']);
    exit;
}

// ── Validação dos campos ──────────────────────────────────────────────────────
$erros = [];

if (strlen($username) < 3 || strlen($username) > 50) {
    $erros['username'] = 'O nome de usuário deve ter entre 3 e 50 caracteres.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros['email'] = 'Informe um e-mail válido.';
}

// Validação de senha usando a função centralizada em includes/senha.php
$erroSenha = validar_forca_senha($senha);
if ($erroSenha !== null) {
    $erros['senha'] = $erroSenha;
}

if (!empty($erros)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erros' => $erros]);
    exit;
}

// ── Persistência ──────────────────────────────────────────────────────────────
try {
    $pdo = getDB();

    // Verifica duplicidade (email ou username)
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    $existente = $stmt->fetch();

    if ($existente) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'erro' => 'Usuário ou e-mail já cadastrado.']);
        exit;
    }

    // Hash seguro com bcrypt (custo 12) — nunca armazena senha em texto puro
    $senhaHash = gerar_hash_senha($senha);
    $secretId = secret_id_gerar();
    $secretEncrypted = secret_id_cifrar($secretId);
    $secretHash = hash('sha256', $secretId);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios
            (username, email, senha_hash, recovery_secret_encrypted, recovery_secret_hash, criado_em)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$username, $email, $senhaHash, $secretEncrypted, $secretHash]);
    $novoId = (int) $pdo->lastInsertId();

    // Inicia sessão automaticamente após cadastro com regeneração de ID
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'        => $novoId,
        'username'  => $username,
        'email'     => $email,
        'foto_url'  => null,
    ];
    $_SESSION['logado_em'] = time();
    sessao_criar_login_persistente($pdo, $novoId);

    http_response_code(201);
    echo json_encode([
        'ok'      => true,
        'usuario' => $_SESSION['usuario'],
    ]);

} catch (\PDOException $e) {
    error_log('[Math Game] Erro ao cadastrar usuário: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Serviço de cadastro indisponível. Verifique a configuração do banco de dados.']);
} catch (\Throwable $e) {
    error_log('[Math Game] Erro ao preparar cadastro: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'erro' => 'Servidor sem configuração ou dependência necessária para o cadastro. Verifique os logs PHP.']);
}
