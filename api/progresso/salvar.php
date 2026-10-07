<?php
/**
 * api/progresso/salvar.php
 * POST – Grava ou atualiza o progresso de uma fase para o usuário logado.
 * Body JSON: { fase_id, pontuacao, estrelas }
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/../../conexao.php';

$input     = json_decode(file_get_contents('php://input'), true);
$faseId    = (int) ($input['fase_id']   ?? 0);
$pontuacao = (int) ($input['pontuacao'] ?? 0);
$estrelas  = (int) ($input['estrelas']  ?? 0);
$usuarioId = (int) $_SESSION['usuario']['id'];

if ($faseId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'fase_id inválido.']);
    exit;
}

$estrelas = max(0, min(3, $estrelas)); // garante 0–3

try {
    $pdo = getDB();

    // Upsert: atualiza apenas se melhorou (pontuação ou estrelas)
    $stmt = $pdo->prepare(
        'INSERT INTO progresso_usuario
             (usuario_id, fase_id, pontuacao_max, estrelas, desbloqueado, ultimo_acesso)
         VALUES (?, ?, ?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE
             pontuacao_max  = GREATEST(pontuacao_max, VALUES(pontuacao_max)),
             estrelas       = GREATEST(estrelas,      VALUES(estrelas)),
             desbloqueado   = 1,
             ultimo_acesso  = NOW()'
    );
    $stmt->execute([$usuarioId, $faseId, $pontuacao, $estrelas]);

    // Desbloqueia a próxima fase da mesma sequência
    $stmt = $pdo->prepare(
        'SELECT ano_letivo, numero_fase FROM fases WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$faseId]);
    $fase = $stmt->fetch();

    if ($fase) {
        $stmt = $pdo->prepare(
            'SELECT id FROM fases WHERE ano_letivo = ? AND numero_fase = ? LIMIT 1'
        );
        $stmt->execute([$fase['ano_letivo'], $fase['numero_fase'] + 1]);
        $proxima = $stmt->fetch();

        if (!$proxima && $fase['numero_fase'] >= 3) {
            $stmt->execute([$fase['ano_letivo'] + 1, 1]);
            $proxima = $stmt->fetch();
        }

        if ($proxima) {
            $pdo->prepare(
                'INSERT IGNORE INTO progresso_usuario (usuario_id, fase_id, desbloqueado)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE desbloqueado = 1'
            )->execute([$usuarioId, $proxima['id']]);
        }
    }

    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao salvar progresso.']);
}
