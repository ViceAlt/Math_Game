<?php
/**
 * api/auth/login.php
 * POST – Autenticação com password_verify().
 * Inicia $_SESSION com os dados do usuário autenticado.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/../../conexao.php';

session_start();

$input    = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$senha    = $input['senha']          ?? '';

// ── Validação básica ─────────────────────────────────────────────────────────
if (empty($username) || empty($senha)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Preencha todos os campos.']);
    exit;
}

// ── Autenticação ──────────────────────────────────────────────────────────────
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT id, username, email, senha_hash, foto_url, criado_em FROM usuarios WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $usuario = $stmt->fetch();

    // Usa timing-safe password_verify para evitar timing attacks
    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'erro' => 'Usuário ou senha incorretos.']);
        exit;
    }

    // Rehash automático se o custo do algoritmo mudar
    if (password_needs_rehash($usuario['senha_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $novoHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([$novoHash, $usuario['id']]);
    }

    // Armazena sessão (sem senha)
    $_SESSION['usuario'] = [
        'id'        => (int) $usuario['id'],
        'username'  => $usuario['username'],
        'email'     => $usuario['email'],
        'foto_url'  => $usuario['foto_url'],
        'criado_em' => $usuario['criado_em'],
    ];

    echo json_encode([
        'ok'      => true,
        'usuario' => $_SESSION['usuario'],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro interno ao autenticar.']);
}
