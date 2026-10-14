<?php
/**
 * jogo_3/gerar_questao.php
 * Endpoint backend para gerar/sortear as fases e validar perguntas do Jogo 3 (Geometria).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/sessao.php';
sessao_iniciar();

// Banco de Formas com propriedades geométricas, fórmulas e alternativas erradas
function obterBancoFormas() {
    return [
        'triangulo_equilatero' => [
            'id' => 'triangulo_equilatero',
            'nome' => 'Triângulo Equilátero',
            'lados' => 3,
            'dificuldades' => ['facil', 'medio', 'experiente'],
            'perimetro' => [
                'correta' => 'P = 3 × l (ou l + l + l)',
                'erradas' => [
                    'P = 4 × l',
                    'P = 2 × (b + h)',
                    'P = (b × h) / 2'
                ]
            ],
            'area' => [
                'correta' => 'A = (l² × √3) / 4 ou (b × h) / 2',
                'erradas' => [
                    'A = b × h',
                    'A = 3 × l',
                    'A = l²'
                ]
            ]
        ],
        'quadrado' => [
            'id' => 'quadrado',
            'nome' => 'Quadrado',
            'lados' => 4,
            'dificuldades' => ['facil', 'medio', 'experiente'],
            'perimetro' => [
                'correta' => 'P = 4 × l',
                'erradas' => [
                    'P = l²',
                    'P = 2 × l',
                    'P = 2 × (b + h)'
                ]
            ],
            'area' => [
                'correta' => 'A = l² (lado × lado)',
                'erradas' => [
                    'A = 4 × l',
                    'A = 2 × l',
                    'A = (b × h) / 2'
                ]
            ]
        ],
        'retangulo' => [
            'id' => 'retangulo',
            'nome' => 'Retângulo',
            'lados' => 4,
            'dificuldades' => ['facil', 'medio', 'experiente'],
            'perimetro' => [
                'correta' => 'P = 2 × (b + h)',
                'erradas' => [
                    'P = b × h',
                    'P = 4 × b',
                    'P = b + h'
                ]
            ],
            'area' => [
                'correta' => 'A = b × h (base × altura)',
                'erradas' => [
                    'A = 2 × (b + h)',
                    'A = (b × h) / 2',
                    'A = b²'
                ]
            ]
        ],
        'losango' => [
            'id' => 'losango',
            'nome' => 'Losango',
            'lados' => 4,
            'dificuldades' => ['medio', 'experiente'],
            'perimetro' => [
                'correta' => 'P = 4 × l',
                'erradas' => [
                    'P = (D × d) / 2',
                    'P = 2 × (D + d)',
                    'P = D × d'
                ]
            ],
            'area' => [
                'correta' => 'A = (D × d) / 2',
                'erradas' => [
                    'A = 4 × l',
                    'A = D × d',
                    'A = D + d'
                ]
            ]
        ],
        'paralelogramo' => [
            'id' => 'paralelogramo',
            'nome' => 'Paralelogramo',
            'lados' => 4,
            'dificuldades' => ['medio', 'experiente'],
            'perimetro' => [
                'correta' => 'P = 2 × (a + b)',
                'erradas' => [
                    'P = b × h',
                    'P = 4 × a',
                    'P = a + b + h'
                ]
            ],
            'area' => [
                'correta' => 'A = b × h',
                'erradas' => [
                    'A = 2 × (b + h)',
                    'A = (b × h) / 2',
                    'A = b²'
                ]
            ]
        ],
        'trapezio' => [
            'id' => 'trapezio',
            'nome' => 'Trapézio',
            'lados' => 4,
            'dificuldades' => ['medio', 'experiente'],
            'perimetro' => [
                'correta' => 'P = B + b + L1 + L2',
                'erradas' => [
                    'P = (B + b) × h',
                    'P = 2 × (B + b)',
                    'P = 4 × B'
                ]
            ],
            'area' => [
                'correta' => 'A = ((B + b) × h) / 2',
                'erradas' => [
                    'A = (B + b) × h',
                    'A = B × b × h',
                    'A = B + b + h'
                ]
            ]
        ],
        'triangulo_retangulo' => [
            'id' => 'triangulo_retangulo',
            'nome' => 'Triângulo Retângulo',
            'lados' => 3,
            'dificuldades' => ['experiente'],
            'perimetro' => [
                'correta' => 'P = a + b + c',
                'erradas' => [
                    'P = 3 × a',
                    'P = (a × b) / 2',
                    'P = a² + b²'
                ]
            ],
            'area' => [
                'correta' => 'A = (b × h) / 2 (ou cateto₁ × cateto₂ / 2)',
                'erradas' => [
                    'A = b × h',
                    'A = a + b + c',
                    'A = (b + h) / 2'
                ]
            ]
        ],
        'pentagono_regular' => [
            'id' => 'pentagono_regular',
            'nome' => 'Pentágono Regular',
            'lados' => 5,
            'dificuldades' => ['experiente'],
            'perimetro' => [
                'correta' => 'P = 5 × l',
                'erradas' => [
                    'P = 6 × l',
                    'P = (5 × l) / 2',
                    'P = l⁵'
                ]
            ],
            'area' => [
                'correta' => 'A = (P × a) / 2 (semiperímetro × apótema)',
                'erradas' => [
                    'A = 5 × l²',
                    'A = P × a',
                    'A = 5 × l'
                ]
            ]
        ],
        'hexagono_regular' => [
            'id' => 'hexagono_regular',
            'nome' => 'Hexágono Regular',
            'lados' => 6,
            'dificuldades' => ['experiente'],
            'perimetro' => [
                'correta' => 'P = 6 × l',
                'erradas' => [
                    'P = 5 × l',
                    'P = 6 × l²',
                    'P = 3 × l'
                ]
            ],
            'area' => [
                'correta' => 'A = (3 × l² × √3) / 2 (6 triângulos equiláteros)',
                'erradas' => [
                    'A = 6 × l',
                    'A = l² × 6',
                    'A = (6 × l) / 2'
                ]
            ]
        ]
    ];
}

// Gera retângulo de delimitação para os níveis Médio e Experiente
// Dimensões do plano SVG são 700x450
function gerarRetanguloDelimitador() {
    $largura = rand(260, 360);
    $altura  = rand(200, 280);
    $x       = rand(170, 700 - $largura - 40);
    $y       = rand(50, 450 - $altura - 40);

    return [
        'x' => $x,
        'y' => $y,
        'largura' => $largura,
        'altura' => $altura
    ];
}

// Parâmetros de entrada
$dificuldade = $_GET['dificuldade'] ?? 'facil';
if (!in_array($dificuldade, ['facil', 'medio', 'experiente'])) {
    $dificuldade = 'facil';
}

$banco = obterBancoFormas();

// Filtra formas disponíveis para a dificuldade
$formasDisponiveis = array_filter($banco, function($item) use ($dificuldade) {
    return in_array($dificuldade, $item['dificuldades']);
});

// Regras de rodadas por nível
// Fácil: 3 questões
// Médio: 4 questões (3 de perímetro e a 4ª de área)
// Experiente: 5 questões (a maioria de área, ex: 1 de perímetro e 4 de área)
$qtdQuestoes = 3;
if ($dificuldade === 'medio') $qtdQuestoes = 4;
if ($dificuldade === 'experiente') $qtdQuestoes = 5;

// Sorteio sem repetir a mesma forma em sequência
$formasChaves = array_keys($formasDisponiveis);
shuffle($formasChaves);

$questoes = [];
$ultimaFormaId = null;

for ($i = 0; $i < $qtdQuestoes; $i++) {
    // Escolhe forma evitando repetição consecutiva
    $candidatas = array_filter($formasChaves, function($k) use ($ultimaFormaId) {
        return $k !== $ultimaFormaId;
    });
    if (empty($candidatas)) {
        $candidatas = $formasChaves;
    }
    $formaEscolhidaKey = $candidatas[array_rand($candidatas)];
    $ultimaFormaId = $formaEscolhidaKey;
    $formaObj = $formasDisponiveis[$formaEscolhidaKey];

    // Determina o tipo de pergunta (perimetro ou area)
    $tipoPergunta = 'perimetro';
    if ($dificuldade === 'facil') {
        // Fácil: mistura balanceada
        $tipoPergunta = (rand(0, 1) === 0) ? 'perimetro' : 'area';
    } elseif ($dificuldade === 'medio') {
        // Médio: 3 de perímetro e a última (4ª) de área
        $tipoPergunta = ($i === 3) ? 'area' : 'perimetro';
    } elseif ($dificuldade === 'experiente') {
        // Experiente: maioria sobre área (1ª perímetro, demais 4 de área)
        $tipoPergunta = ($i === 0) ? 'perimetro' : 'area';
    }

    $dadosPergunta = $formaObj[$tipoPergunta];
    $alternativas = array_merge([$dadosPergunta['correta']], $dadosPergunta['erradas']);
    shuffle($alternativas);

    $retangulo = null;
    if ($dificuldade === 'medio' || $dificuldade === 'experiente') {
        $retangulo = gerarRetanguloDelimitador();
    }

    $questoes[] = [
        'id' => $formaObj['id'],
        'nome' => $formaObj['nome'],
        'lados' => $formaObj['lados'],
        'tipoPergunta' => $tipoPergunta,
        'enunciadoPergunta' => ($tipoPergunta === 'perimetro')
            ? "Qual é a fórmula do PERÍMETRO do " . mb_strtolower($formaObj['nome']) . "?"
            : "Qual é a fórmula da ÁREA do " . mb_strtolower($formaObj['nome']) . "?",
        'alternativas' => $alternativas,
        'respostaCorreta' => $dadosPergunta['correta'],
        'retangulo' => $retangulo
    ];
}

// Salva o gabarito na sessão para validação posterior se necessário
$_SESSION['gabarito_jogo_3'] = [
    'dificuldade' => $dificuldade,
    'questoes' => $questoes
];

echo json_encode([
    'ok' => true,
    'dificuldade' => $dificuldade,
    'totalQuestoes' => count($questoes),
    'questoes' => $questoes
]);
