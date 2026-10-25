<?php
/**
 * api/progresso/obter.php
 * GET – Retorna todo o progresso do usuário logado (ou estado padrão para visitante),
 * incluindo últimas trilhas acessadas e o último jogo jogado para a Home.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/sessao.php';
sessao_iniciar();

require_once __DIR__ . '/../../conexao.php';

$usuario = $_SESSION['usuario'] ?? null;
$usuarioId = $usuario ? (int) $usuario['id'] : null;

try {
    $pdo = getDB();

    // 1. Busca todas as fases do sistema para mapear os 4 mundos
    $stmtTodas = $pdo->query('SELECT id, ano_letivo, numero_fase, jogo_id FROM fases ORDER BY ano_letivo, numero_fase');
    $todasFases = $stmtTodas->fetchAll();

    $progresso = [];

    // Preenche estado padrão: Fase 1 de cada ano (6, 7, 8, 9) desbloqueada por padrão (Opção B)
    foreach ($todasFases as $f) {
        $ano = (int) $f['ano_letivo'];
        $num = (int) $f['numero_fase'];
        $chaveStr = "ano_{$ano}_fase_{$num}";

        $item = [
            'fase_id'       => (int) $f['id'],
            'ano_letivo'    => $ano,
            'numero_fase'   => $num,
            'jogo_id'       => (int) $f['jogo_id'],
            'pontuacao_max' => 0,
            'estrelas'      => 0,
            'vezes_jogadas' => 0,
            'desbloqueado'  => ($num === 1), // Fase 1 de cada ano começa desbloqueada
            'ultimo_acesso' => null,
            'jogada'        => false
        ];

        $progresso[$f['id']] = $item;
        $progresso[$chaveStr] = $item;
    }

    $ultimasTrilhas = [];
    $ultimoJogo = null;

    // Se o usuário estiver logado, busca o progresso real gravado no MySQL
    if ($usuarioId) {
        $stmtUser = $pdo->prepare(
            'SELECT pu.fase_id, pu.pontuacao_max, pu.estrelas, pu.vezes_jogadas, pu.desbloqueado, pu.ultimo_acesso,
                    f.ano_letivo, f.numero_fase, f.jogo_id
             FROM progresso_usuario pu
             JOIN fases f ON f.id = pu.fase_id
             WHERE pu.usuario_id = ?'
        );
        $stmtUser->execute([$usuarioId]);
        $rows = $stmtUser->fetchAll();

        foreach ($rows as $r) {
            $ano = (int) $r['ano_letivo'];
            $num = (int) $r['numero_fase'];
            $fId = (int) $r['fase_id'];
            $chaveStr = "ano_{$ano}_fase_{$num}";

            $desbloq = (bool) $r['desbloqueado'] || ($num === 1);
            $jogada = ((int) $r['estrelas'] > 0 || (int) $r['vezes_jogadas'] > 0);

            $item = [
                'fase_id'       => $fId,
                'ano_letivo'    => $ano,
                'numero_fase'   => $num,
                'jogo_id'       => (int) $r['jogo_id'],
                'pontuacao_max' => (int) $r['pontuacao_max'],
                'estrelas'      => (int) $r['estrelas'],
                'vezes_jogadas' => (int) $r['vezes_jogadas'],
                'desbloqueado'  => $desbloq,
                'ultimo_acesso' => $r['ultimo_acesso'],
                'jogada'        => $jogada
            ];

            $progresso[$fId] = $item;
            $progresso[$chaveStr] = $item;
        }

        // 2. Calcula progresso consolidado de cada ano/mundo para as últimas trilhas
        $stmtMundos = $pdo->prepare(
            'SELECT f.ano_letivo,
                    COUNT(DISTINCT CASE WHEN pu.estrelas >= 1 THEN f.id END) AS fases_concluidas,
                    SUM(COALESCE(pu.estrelas, 0)) AS total_estrelas,
                    MAX(COALESCE(pu.ultimo_acesso, "2000-01-01")) AS data_recente
             FROM fases f
             LEFT JOIN progresso_usuario pu ON pu.fase_id = f.id AND pu.usuario_id = ?
             GROUP BY f.ano_letivo
             ORDER BY data_recente DESC, f.ano_letivo ASC'
        );
        $stmtMundos->execute([$usuarioId]);
        $mundosRows = $stmtMundos->fetchAll();

        $nomesMundos = [
            6 => 'Floresta dos Números (6° Ano)',
            7 => 'Deserto das Frações (7° Ano)',
            8 => 'Galáxia da Geometria (8° Ano)',
            9 => 'Cidadela dos Mestres (9° Ano)'
        ];

        foreach ($mundosRows as $m) {
            $ano = (int) $m['ano_letivo'];
            $fasesConcluidas = (int) $m['fases_concluidas'];
            // Procura a próxima fase a ser jogada dentro desse ano
            $proximaFaseNum = min(20, $fasesConcluidas + 1);

            $ultimasTrilhas[] = [
                'ano_letivo'       => $ano,
                'nome_mundo'       => $nomesMundos[$ano] ?? "{$ano}° Ano",
                'fases_concluidas' => $fasesConcluidas,
                'total_fases'      => 20,
                'total_estrelas'   => (int) $m['total_estrelas'],
                'proxima_fase'     => $proximaFaseNum,
                'tem_progresso'    => ($fasesConcluidas > 0 || (int)$m['total_estrelas'] > 0)
            ];
        }

        // Filtra para exibir 1 a 3 mais recentes (se nenhuma jogada, exibe a do 6º ano)
        $trilhasComProgresso = array_filter($ultimasTrilhas, fn($t) => $t['tem_progresso']);
        if (empty($trilhasComProgresso)) {
            $ultimasTrilhas = array_slice($ultimasTrilhas, 0, 1);
        } else {
            $ultimasTrilhas = array_slice($trilhasComProgresso, 0, 3);
        }

        // 3. Busca o último jogo/fase acessado no histórico
        $stmtUltimo = $pdo->prepare(
            'SELECT h.*, j.nome AS nome_jogo, j.pasta_path
             FROM historico_acessos h
             LEFT JOIN jogos j ON j.id = h.jogo_id
             WHERE h.usuario_id = ?
             ORDER BY h.acessado_em DESC
             LIMIT 1'
        );
        $stmtUltimo->execute([$usuarioId]);
        $ultimoJogo = $stmtUltimo->fetch();
    }

    echo json_encode([
        'ok'               => true,
        'logado'           => ($usuarioId !== null),
        'progresso'        => $progresso,
        'ultimas_trilhas'  => $ultimasTrilhas,
        'ultimo_jogo'      => $ultimoJogo
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao carregar progresso: ' . $e->getMessage()]);
}
