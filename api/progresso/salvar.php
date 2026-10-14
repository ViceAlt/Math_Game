<?php
/**
 * api/progresso/salvar.php
 * POST – Registra o progresso de uma fase ou jogo livre para o usuário logado.
 *
 * Regras de Negócio:
 * - Se estrelas >= 1: desbloqueia a próxima fase do ano letivo.
 * - Se estrelas == 0: a próxima fase continua bloqueada.
 * - Se concluiu a fase 20 com >= 1 estrela: desbloqueia o próximo mundo.
 * - Mantém sempre o MELHOR resultado de estrelas (nunca diminui).
 * - Incrementa vezes_jogadas e registra histórico de acesso.
 * - Se visitante (deslogado): não quebra e responde com flag visitante = true.
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

$usuario = $_SESSION['usuario'] ?? null;
$usuarioId = $usuario ? (int) $usuario['id'] : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/../../conexao.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$anoLetivo   = isset($input['ano']) ? (int) $input['ano'] : null;
$numeroFase  = isset($input['fase']) ? (int) $input['fase'] : null;
$faseId      = isset($input['fase_id']) ? (int) $input['fase_id'] : 0;
$jogoId      = isset($input['jogo_id']) ? (int) $input['jogo_id'] : 1;
$pontuacao   = isset($input['pontuacao']) ? max(0, (int) $input['pontuacao']) : 0;
$estrelas    = isset($input['estrelas']) ? max(0, min(3, (int) $input['estrelas'])) : 0;
$tipoAcesso  = ($input['tipo'] ?? 'trilha') === 'jogo_livre' ? 'jogo_livre' : 'trilha';
$tituloFase  = trim($input['titulo_fase'] ?? '');

// Se for visitante, responde com sucesso informando que o progresso não foi persistido no banco
if (!$usuarioId) {
    echo json_encode([
        'ok' => true,
        'visitante' => true,
        'mensagem' => 'Progresso temporário. Faça login para salvar suas estrelas.'
    ]);
    exit;
}

try {
    $pdo = getDB();

    // Se fase_id não veio mas ano e fase vieram, busca o ID da fase correspondente
    if ($faseId <= 0 && $anoLetivo !== null && $numeroFase !== null) {
        $stmtFase = $pdo->prepare(
            'SELECT id, jogo_id FROM fases WHERE ano_letivo = ? AND numero_fase = ? LIMIT 1'
        );
        $stmtFase->execute([$anoLetivo, $numeroFase]);
        $faseRow = $stmtFase->fetch();
        if ($faseRow) {
            $faseId = (int) $faseRow['id'];
            if (!$jogoId) $jogoId = (int) $faseRow['jogo_id'];
        }
    }

    $desbloqueouProxima = false;

    if ($tipoAcesso === 'trilha' && $faseId > 0) {
        // Busca os dados da fase para confirmar ano e número
        $stmtInfo = $pdo->prepare('SELECT id, ano_letivo, numero_fase, jogo_id FROM fases WHERE id = ? LIMIT 1');
        $stmtInfo->execute([$faseId]);
        $infoFase = $stmtInfo->fetch();

        if ($infoFase) {
            $anoLetivo  = (int) $infoFase['ano_letivo'];
            $numeroFase = (int) $infoFase['numero_fase'];
            if (!$jogoId) $jogoId = (int) $infoFase['jogo_id'];

            // Upsert do progresso na fase atual
            $stmtUpsert = $pdo->prepare(
                'INSERT INTO progresso_usuario
                     (usuario_id, fase_id, pontuacao_max, estrelas, vezes_jogadas, desbloqueado, ultimo_acesso)
                 VALUES (?, ?, ?, ?, 1, 1, NOW())
                 ON DUPLICATE KEY UPDATE
                     pontuacao_max = GREATEST(pontuacao_max, VALUES(pontuacao_max)),
                     estrelas      = GREATEST(estrelas, VALUES(estrelas)),
                     vezes_jogadas = vezes_jogadas + 1,
                     desbloqueado  = 1,
                     ultimo_acesso = NOW()'
            );
            $stmtUpsert->execute([$usuarioId, $faseId, $pontuacao, $estrelas]);

            // REGRA: Somente desbloqueia a próxima se estrelas >= 1
            if ($estrelas >= 1) {
                // Caso A: Próxima fase no mesmo ano (ex: fase 1 -> fase 2)
                if ($numeroFase < 20) {
                    $stmtProx = $pdo->prepare(
                        'SELECT id FROM fases WHERE ano_letivo = ? AND numero_fase = ? LIMIT 1'
                    );
                    $stmtProx->execute([$anoLetivo, $numeroFase + 1]);
                    $proxRow = $stmtProx->fetch();

                    if ($proxRow) {
                        $proxId = (int) $proxRow['id'];
                        $pdo->prepare(
                            'INSERT INTO progresso_usuario
                                 (usuario_id, fase_id, pontuacao_max, estrelas, vezes_jogadas, desbloqueado, ultimo_acesso)
                             VALUES (?, ?, 0, 0, 0, 1, NOW())
                             ON DUPLICATE KEY UPDATE desbloqueado = 1'
                        )->execute([$usuarioId, $proxId]);
                        $desbloqueouProxima = true;
                    }
                }
                // Caso B: Fase 20 concluída -> Desbloqueia a Fase 1 do próximo ano (ex: 6º ano -> 7º ano)
                elseif ($numeroFase === 20 && $anoLetivo < 9) {
                    $stmtProxAno = $pdo->prepare(
                        'SELECT id FROM fases WHERE ano_letivo = ? AND numero_fase = 1 LIMIT 1'
                    );
                    $stmtProxAno->execute([$anoLetivo + 1]);
                    $proxAnoRow = $stmtProxAno->fetch();

                    if ($proxAnoRow) {
                        $proxAnoId = (int) $proxAnoRow['id'];
                        $pdo->prepare(
                            'INSERT INTO progresso_usuario
                                 (usuario_id, fase_id, pontuacao_max, estrelas, vezes_jogadas, desbloqueado, ultimo_acesso)
                             VALUES (?, ?, 0, 0, 0, 1, NOW())
                             ON DUPLICATE KEY UPDATE desbloqueado = 1'
                        )->execute([$usuarioId, $proxAnoId]);
                        $desbloqueouProxima = true;
                    }
                }
            }
        }
    }

    // Registra na tabela de histórico de acessos
    $stmtHist = $pdo->prepare(
        'INSERT INTO historico_acessos
             (usuario_id, tipo, ano_letivo, fase_id, jogo_id, titulo_fase, estrelas, acessado_em)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmtHist->execute([
        $usuarioId,
        $tipoAcesso,
        $anoLetivo,
        ($faseId > 0 ? $faseId : null),
        $jogoId,
        ($tituloFase ?: "Fase {$numeroFase}"),
        $estrelas
    ]);

    echo json_encode([
        'ok'                  => true,
        'desbloqueou_proxima' => $desbloqueouProxima,
        'estrelas_ganhas'     => $estrelas,
        'pontuacao'           => $pontuacao
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao salvar progresso: ' . $e->getMessage()]);
}
