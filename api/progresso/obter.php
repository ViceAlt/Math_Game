<?php
/**
 * api/progresso/obter.php
 * GET – Retorna todo o progresso do usuário logado, indexado por fase_id.
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
    exit;
}

require_once __DIR__ . '/../../conexao.php';

$usuarioId = (int) $_SESSION['usuario']['id'];

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT
             pu.fase_id,
             pu.pontuacao_max,
             pu.estrelas,
             pu.desbloqueado,
             pu.ultimo_acesso,
             f.ano_letivo,
             f.numero_fase
         FROM progresso_usuario pu
         JOIN fases f ON f.id = pu.fase_id
         WHERE pu.usuario_id = ?
         ORDER BY f.ano_letivo, f.numero_fase'
    );
    $stmt->execute([$usuarioId]);
    $rows = $stmt->fetchAll();

    // Indexa por fase_id para facilitar o consumo no JS
    $progresso = [];
    foreach ($rows as $row) {
        $item = [
            'pontuacao_max' => (int)  $row['pontuacao_max'],
            'estrelas'      => (int)  $row['estrelas'],
            'desbloqueado'  => (bool) $row['desbloqueado'],
            'ultimo_acesso' => $row['ultimo_acesso'],
            'ano_letivo'    => (int)  $row['ano_letivo'],
            'numero_fase'   => (int)  $row['numero_fase'],
        ];
        $progresso[$row['fase_id']] = $item;
        $chaveStr = "ano_{$row['ano_letivo']}_fase_{$row['numero_fase']}";
        $progresso[$chaveStr] = $item;
    }

    echo json_encode(['ok' => true, 'progresso' => $progresso]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao obter progresso.']);
}
