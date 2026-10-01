<?php
/**
 * api/auth/register.php
 * POST – Cadastro de novo usuário.
 * Usa password_hash(PASSWORD_BCRYPT) para armazenar a senha com segurança.
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

$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$email    = trim($input['email']    ?? '');
$senha    = $input['senha']          ?? '';

// ── Validação ────────────────────────────────────────────────────────────────
$erros = [];

if (strlen($username) < 3 || strlen($username) > 50) {
    $erros['username'] = 'O nome de usuário deve ter entre 3 e 50 caracteres.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros['email'] = 'Informe um e-mail válido.';
}
if (strlen($senha) < 6) {
    $erros['senha'] = 'A senha deve ter pelo menos 6 caracteres.';
}

if (!empty($erros)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erros' => $erros]);
    exit;
}

// ── Persistência ─────────────────────────────────────────────────────────────
try {
    $pdo = getDB();

    // Verifica duplicidade
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    $existente = $stmt->fetch();

    if ($existente) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'erro' => 'Usuário ou e-mail já cadastrado.']);
        exit;
    }

    // Hash seguro com bcrypt (custo 12)
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (username, email, senha_hash, criado_em) VALUES (?, ?, ?, NOW())'
    );
    $stmt->execute([$username, $email, $senhaHash]);
    $novoId = (int) $pdo->lastInsertId();

    // Inicia sessão automaticamente após cadastro
    session_start();
    $_SESSION['usuario'] = [
        'id'        => $novoId,
        'username'  => $username,
        'email'     => $email,
        'foto_url'  => null,
    ];

    http_response_code(201);
    echo json_encode([
        'ok'      => true,
        'usuario' => $_SESSION['usuario'],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro interno ao cadastrar usuário.']);
}
