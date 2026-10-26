<?php
/**
 * jogo_2/gerar_questao.php
 * Endpoint backend que sorteia o eixo Y e a questão para o Jogo 2.
 * Salva o gabarito de forma segura na $_SESSION['gabarito_jogo_2'].
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/sessao.php';
sessao_iniciar();

// Constantes configuráveis
define('MIN_EIXO', 0);
define('PASSO_EIXO', 5);
define('MAX_EIXO_MINIMO', 20);
define('MAX_EIXO_MAXIMO', 50);

/**
 * Gera o array do eixo Y com base num valor MAX sorteado (múltiplo de PASSO_EIXO).
 *
 * @param int $min
 * @param int $passo
 * @return array Contém 'arrayEixo', 'min', 'max', 'passo'
 */
function gerarEixoY($min = MIN_EIXO, $passo = PASSO_EIXO) {
    // Sorteia um MAX entre 20 e 50 que é múltiplo de 5
    $passosPossiveis = range(MAX_EIXO_MINIMO / $passo, MAX_EIXO_MAXIMO / $passo);
    $maxSorteado = $passosPossiveis[array_rand($passosPossiveis)] * $passo;

    $eixoY = [];
    for ($val = $min; $val <= $maxSorteado; $val += $passo) {
        $eixoY[] = $val;
    }

    return [
        'min' => $min,
        'max' => $maxSorteado,
        'passo' => $passo,
        'arrayEixo' => $eixoY
    ];
}

/**
 * Banco com pelo menos 10 questões de contextos variados (2, 3 e 4 itens).
 * 
 * @return array
 */
function obterBancoQuestoes() {
    return [
        // 2 itens
        [
            'texto' => 'O mercadinho tem {a} bananas e {b} maçãs.',
            'itens' => ['Bananas', 'Maçãs']
        ],
        [
            'texto' => 'Na biblioteca há {a} livros de aventura e {b} de ciências.',
            'itens' => ['Aventura', 'Ciências']
        ],
        [
            'texto' => 'O estacionamento possui {a} carros azuis e {b} carros vermelhos.',
            'itens' => ['Carros Azuis', 'Carros Vermelhos']
        ],
        [
            'texto' => 'Na quadra da escola jogam {a} meninos e {b} meninas.',
            'itens' => ['Meninos', 'Meninas']
        ],
        // 3 itens
        [
            'texto' => 'A doceria vendeu {a} brigadeiros, {b} beijinhos e {c} cajuzinhos.',
            'itens' => ['Brigadeiros', 'Beijinhos', 'Cajuzinhos']
        ],
        [
            'texto' => 'No zoológico vivem {a} leões, {b} girafas e {c} zebras.',
            'itens' => ['Leões', 'Girafas', 'Zebras']
        ],
        [
            'texto' => 'Na estante da sala temos {a} gibis, {b} revistas e {c} jornais.',
            'itens' => ['Gibis', 'Revistas', 'Jornais']
        ],
        [
            'texto' => 'Na fruteira do restaurante há {a} laranjas, {b} uvas e {c} morangos.',
            'itens' => ['Laranjas', 'Uvas', 'Morangos']
        ],
        // 4 itens
        [
            'texto' => 'O estojo do aluno tem {a} lápis, {b} canetas, {c} borrachas e {d} apontadores.',
            'itens' => ['Lápis', 'Canetas', 'Borrachas', 'Apontadores']
        ],
        [
            'texto' => 'No jardim botânico floresceram {a} rosas, {b} tulipa(s), {c} margarida(s) e {d} orquídea(s).',
            'itens' => ['Rosas', 'Tulipas', 'Margaridas', 'Orquídeas']
        ],
        [
            'texto' => 'A fazenda colheu {a} sacos de milho, {b} de trigo, {c} de soja e {d} de café.',
            'itens' => ['Milho', 'Trigo', 'Soja', 'Café']
        ],
        [
            'texto' => 'No torneio da escola foram entregues {a} medalhas de ouro, {b} de prata, {c} de bronze e {d} troféus.',
            'itens' => ['Ouro', 'Prata', 'Bronze', 'Troféus']
        ]
    ];
}

/**
 * Sortear uma questão evitando a última sorteada.
 *
 * @param array $bancoQuestoes
 * @param int|null $ultimaQuestaoIndex
 * @return array Contém 'questao', 'index'
 */
function sortearQuestao($bancoQuestoes, $ultimaQuestaoIndex = null) {
    $indicesPossiveis = array_keys($bancoQuestoes);

    if ($ultimaQuestaoIndex !== null && count($bancoQuestoes) > 1) {
        $indicesPossiveis = array_values(array_filter($indicesPossiveis, function($idx) use ($ultimaQuestaoIndex) {
            return $idx !== $ultimaQuestaoIndex;
        }));
    }

    $idxSorteado = $indicesPossiveis[array_rand($indicesPossiveis)];
    return [
        'questao' => $bancoQuestoes[$idxSorteado],
        'index' => $idxSorteado
    ];
}

/**
 * Sortear valores do gabarito dentro do array do eixo Y (múltiplos de 5, >= min e <= max).
 *
 * @param array $itens
 * @param array $arrayEixoY
 * @return array
 */
function sortearValores($itens, $arrayEixoY) {
    $gabarito = [];
    foreach ($itens as $item) {
        $gabarito[$item] = $arrayEixoY[array_rand($arrayEixoY)];
    }
    return $gabarito;
}

// Execução principal
$eixo = gerarEixoY();
$banco = obterBancoQuestoes();

$ultimaQuestaoIndex = $_SESSION['ultima_questao_jogo_2'] ?? null;
$sorteio = sortearQuestao($banco, $ultimaQuestaoIndex);
$questaoSelecionada = $sorteio['questao'];
$_SESSION['ultima_questao_jogo_2'] = $sorteio['index'];

$gabarito = sortearValores($questaoSelecionada['itens'], $eixo['arrayEixo']);

// Preencher texto com as variáveis {a}, {b}, {c}, {d}...
$variaveis = ['{a}', '{b}', '{c}', '{d}'];
$textoMontado = $questaoSelecionada['texto'];

$i = 0;
foreach ($questaoSelecionada['itens'] as $item) {
    if (isset($variaveis[$i])) {
        $val = $gabarito[$item];
        $textoMontado = str_replace($variaveis[$i], (string)$val, $textoMontado);
    }
    $i++;
}

// Armazenar gabarito de forma segura na sessão para ser comparado no verificar.php
$_SESSION['gabarito_jogo_2'] = [
    'itens' => $questaoSelecionada['itens'],
    'gabarito' => $gabarito,
    'eixo_max' => $eixo['max'],
    'eixo_min' => $eixo['min']
];

// Resposta pública para o cliente (SEM EXPOR O GABARITO!)
echo json_encode([
    'ok' => true,
    'texto' => $textoMontado,
    'itens' => $questaoSelecionada['itens'],
    'eixo' => [
        'min' => $eixo['min'],
        'max' => $eixo['max'],
        'passo' => $eixo['passo'],
        'arrayEixo' => $eixo['arrayEixo']
    ]
]);
