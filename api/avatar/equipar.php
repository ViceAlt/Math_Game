<?php
/**
 * api/avatar/equipar.php
 * POST – Permite ao usuário equipar qualquer foto que ele já possua
 * (incluindo a Foto 1 padrão a qualquer momento gratuitamente).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/sessao.php';
sessao_iniciar();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || empty($usuario['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Faça login para alterar o avatar.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$fotoNome = trim($input['foto'] ?? '');

// Sanitização e validação rígida do nome do arquivo
if (!preg_match('/^foto([1-9])\.webp$/i', $fotoNome, $matches)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Avatar inválido selecionado.']);
    exit;
}

$fotoNome = strtolower($fotoNome);
$numeroFoto = (int) $matches[1];
$isPadrao = ($numeroFoto === 1);

$pastaFotos = __DIR__ . '/../../fotos';
if (!file_exists($pastaFotos . '/' . $fotoNome)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'erro' => 'Arquivo do avatar não encontrado no servidor.']);
    exit;
}

$usuarioId = (int) $usuario['id'];

require_once __DIR__ . '/../../conexao.php';

try {
    $pdo = getDB();

    // Se não for a foto 1 (padrão gratuita), verifica se o usuário realmente comprou
    if (!$isPadrao) {
        $stmtCheca = $pdo->prepare('SELECT id FROM usuarios_avatares WHERE usuario_id = ? AND foto_nome = ? LIMIT 1');
        $stmtCheca->execute([$usuarioId, $fotoNome]);
        if (!$stmtCheca->fetch()) {
            http_response_code(403);
            echo json_encode([
                'ok'   => false,
                'erro' => 'Você ainda não comprou este avatar. Compre por 10 estrelas para poder equipá-lo.'
            ]);
            exit;
        }
    }

    // Atualiza a foto de perfil do usuário no banco
    $caminhoRelativo = 'fotos/' . $fotoNome;
    $stmtUpdate = $pdo->prepare('UPDATE usuarios SET foto_url = ? WHERE id = ?');
    $stmtUpdate->execute([$caminhoRelativo, $usuarioId]);

    // Atualiza a sessão ativa
    $_SESSION['usuario']['foto_url'] = $caminhoRelativo;

    echo json_encode([
        'ok'         => true,
        'mensagem'   => $isPadrao ? 'Foto padrão restaurada com sucesso!' : "Avatar #{$numeroFoto} equipado com sucesso!",
        'foto_atual' => $fotoNome,
        'foto_url'   => '../' . $caminhoRelativo
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao equipar avatar: ' . $e->getMessage()]);
}
