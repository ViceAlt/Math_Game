<?php
/**
 * jogo_2/verificar.php
 * Valida a resposta enviada pelo aluno comparando com o gabarito salvo na $_SESSION['gabarito_jogo_2'].
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/sessao.php';
sessao_iniciar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

$dadosSessao = $_SESSION['gabarito_jogo_2'] ?? null;

if (!$dadosSessao) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Nenhuma rodada ativa encontrada na sessão.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$respostasAluno = $input['respostas'] ?? [];

$gabarito = $dadosSessao['gabarito'];
$itens = $dadosSessao['itens'];

$resultadoBarras = [];
$totalItens = count($itens);
$acertos = 0;

foreach ($itens as $item) {
    $valorEsperado = (int) ($gabarito[$item] ?? 0);
    $valorAluno = (int) ($respostasAluno[$item] ?? 0);

    $correto = ($valorEsperado === $valorAluno);
    if ($correto) {
        $acertos++;
    }

    $resultadoBarras[$item] = [
        'correto' => $correto,
        'valorAluno' => $valorAluno,
        'valorEsperado' => $valorEsperado
    ];
}

$tudoCorreto = ($acertos === $totalItens);

echo json_encode([
    'ok' => true,
    'acertou' => $tudoCorreto,
    'acertosCount' => $acertos,
    'totalItens' => $totalItens,
    'detalhes' => $resultadoBarras
]);
