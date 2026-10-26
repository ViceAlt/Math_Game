<?php
/**
 * jogo_4/gerar_questao.php
 * Endpoint backend para gerar questões de Sucessores e Antecessores.
 * Toda a resposta correta é calculada matematicamente a partir dos números sorteados.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/sessao.php';
sessao_iniciar();

// Helpers para formatação brasileira
function formatarNumeroBR($num) {
    if (is_int($num) || floor($num) == $num) {
        return (string)(int)$num;
    }
    // Decimal formatado com vírgula
    return number_format($num, 1, ',', '');
}

/**
 * Nível Fácil: 1 campo de escolha ("sucessor" / "antecessor").
 * Enunciados explicados, com justificativa embutida.
 * Números inteiros pequenos e positivos.
 */
function gerarQuestaoFacil() {
    $n = rand(2, 50);
    $tipo = (rand(0, 1) === 0) ? 'sucessor' : 'antecessor';

    if ($tipo === 'sucessor') {
        $vizinho = $n + 1;
        $correta = 'sucessor';
        $templates = [
            "O número " . formatarNumeroBR($vizinho) . " é o {campo_1} do número " . formatarNumeroBR($n) . ", porque vem logo depois dele.",
            "Sabendo que " . formatarNumeroBR($vizinho) . " vem imediatamente após o " . formatarNumeroBR($n) . ", ele é seu {campo_1}.",
            "Ao somar 1 ao número " . formatarNumeroBR($n) . ", obtemos seu {campo_1}, que é o número " . formatarNumeroBR($vizinho) . "."
        ];
        $dica = "O sucessor é o número que vem logo depois (soma 1).";
    } else {
        $vizinho = $n - 1;
        $correta = 'antecessor';
        $templates = [
            "O número " . formatarNumeroBR($vizinho) . " é o {campo_1} do número " . formatarNumeroBR($n) . ", porque vem logo antes dele.",
            "Como " . formatarNumeroBR($vizinho) . " vem imediatamente antes de " . formatarNumeroBR($n) . ", dizemos que ele é seu {campo_1}.",
            "Subtraindo 1 do número " . formatarNumeroBR($n) . ", encontramos o número " . formatarNumeroBR($vizinho) . ", que é seu {campo_1}."
        ];
        $dica = "O antecessor é o número que vem logo antes (subtrai 1).";
    }

    $frase = $templates[array_rand($templates)];

    return [
        'enunciadoTemplate' => $frase,
        'dica' => $dica,
        'campos' => [
            [
                'id' => 'campo_1',
                'opcoes' => ['sucessor', 'antecessor'],
                'respostaCorreta' => $correta
            ]
        ]
    ];
}

/**
 * Nível Médio: 2 campos de escolha:
 * Campo 1: "sucessor" / "antecessor"
 * Campo 2: "maior" / "menor"
 * Números maiores e transições de ordem (99 -> 100, 199 -> 200, 999 -> 1000).
 */
function gerarQuestaoMedio() {
    $transicoes = [
        99, 100, 199, 200, 299, 300, 499, 500, 899, 900, 999, 1000
    ];

    // 50% de chance de transição desafiadora de ordem
    if (rand(0, 1) === 0) {
        $base = $transicoes[array_rand($transicoes)];
    } else {
        $base = rand(51, 850);
    }

    $tipo = (rand(0, 1) === 0) ? 'sucessor' : 'antecessor';

    if ($tipo === 'sucessor') {
        $vizinho = $base + 1;
        $correta1 = 'sucessor';
        $correta2 = 'maior'; // sucessor é sempre maior
        $templates = [
            "O {campo_1} de " . formatarNumeroBR($base) . " é " . formatarNumeroBR($vizinho) . ", por ser {campo_2} que " . formatarNumeroBR($base) . ".",
            "Como " . formatarNumeroBR($vizinho) . " é {campo_2} que " . formatarNumeroBR($base) . " e vem logo após ele, " . formatarNumeroBR($vizinho) . " é o seu {campo_1}.",
            "O número " . formatarNumeroBR($vizinho) . " é o {campo_1} de " . formatarNumeroBR($base) . ", logo ele é {campo_2} que " . formatarNumeroBR($base) . "."
        ];
        $dica = "O sucessor vem logo depois e é sempre maior que o número original.";
    } else {
        $vizinho = $base - 1;
        $correta1 = 'antecessor';
        $correta2 = 'menor'; // antecessor é sempre menor
        $templates = [
            "O {campo_1} de " . formatarNumeroBR($base) . " é " . formatarNumeroBR($vizinho) . ", por ser {campo_2} que " . formatarNumeroBR($base) . ".",
            "Como " . formatarNumeroBR($vizinho) . " é {campo_2} que " . formatarNumeroBR($base) . " e vem logo antes dele, " . formatarNumeroBR($vizinho) . " é o seu {campo_1}.",
            "O número " . formatarNumeroBR($vizinho) . " é o {campo_1} de " . formatarNumeroBR($base) . ", logo ele é {campo_2} que " . formatarNumeroBR($base) . "."
        ];
        $dica = "O antecessor vem logo antes e é sempre menor que o número original.";
    }

    $frase = $templates[array_rand($templates)];

    return [
        'enunciadoTemplate' => $frase,
        'dica' => $dica,
        'campos' => [
            [
                'id' => 'campo_1',
                'opcoes' => ['sucessor', 'antecessor'],
                'respostaCorreta' => $correta1
            ],
            [
                'id' => 'campo_2',
                'opcoes' => ['maior', 'menor'],
                'respostaCorreta' => $correta2
            ]
        ]
    ];
}

/**
 * Nível Experiente:
 * 1) Números negativos (sucessor de -4 é -3, antecessor de -4 é -5)
 * 2) Números decimais (de 0,1 em 0,1; ex: 2,5 -> 2,6 ou 2,4)
 * 3) Contas aritméticas (+, -, ×, ÷) com verificação rigorosa de solução única
 */
function gerarQuestaoExperiente() {
    $subtipo = rand(1, 3);

    // Subtipo 1: Números Negativos
    if ($subtipo === 1) {
        $base = rand(-20, -1);
        $tipo = (rand(0, 1) === 0) ? 'sucessor' : 'antecessor';

        if ($tipo === 'sucessor') {
            $vizinho = $base + 1;
            $correta1 = 'sucessor';
            $correta2 = 'maior'; // Em negativos: -3 é maior que -4
            $dica = "Nos números negativos, o sucessor fica mais próximo de zero (é maior): soma 1.";
            $templates = [
                "Na reta numérica inteira, o {campo_1} de (" . formatarNumeroBR($base) . ") é (" . formatarNumeroBR($vizinho) . "), sendo {campo_2} que (" . formatarNumeroBR($base) . ").",
                "O número (" . formatarNumeroBR($vizinho) . ") é o {campo_1} de (" . formatarNumeroBR($base) . "), pois vem imediatamente à direita e é {campo_2} que ele."
            ];
        } else {
            $vizinho = $base - 1;
            $correta1 = 'antecessor';
            $correta2 = 'menor'; // Em negativos: -5 é menor que -4
            $dica = "Nos números negativos, o antecessor fica mais distante de zero (é menor): subtrai 1.";
            $templates = [
                "Na reta numérica inteira, o {campo_1} de (" . formatarNumeroBR($base) . ") é (" . formatarNumeroBR($vizinho) . "), sendo {campo_2} que (" . formatarNumeroBR($base) . ").",
                "O número (" . formatarNumeroBR($vizinho) . ") é o {campo_1} de (" . formatarNumeroBR($base) . "), pois vem imediatamente à esquerda e é {campo_2} que ele."
            ];
        }

        return [
            'enunciadoTemplate' => $templates[array_rand($templates)],
            'dica' => $dica,
            'campos' => [
                [
                    'id' => 'campo_1',
                    'opcoes' => ['sucessor', 'antecessor'],
                    'respostaCorreta' => $correta1
                ],
                [
                    'id' => 'campo_2',
                    'opcoes' => ['maior', 'menor'],
                    'respostaCorreta' => $correta2
                ]
            ]
        ];
    }

    // Subtipo 2: Decimais de 0,1 em 0,1
    if ($subtipo === 2) {
        $inteiro = rand(1, 15);
        $decimal = rand(1, 8) / 10;
        $base = round($inteiro + $decimal, 1);
        $tipo = (rand(0, 1) === 0) ? 'sucessor' : 'antecessor';

        if ($tipo === 'sucessor') {
            $vizinho = round($base + 0.1, 1);
            $correta1 = 'sucessor';
            $correta2 = 'maior';
            $dica = "Contando de 0,1 em 0,1, o sucessor é obtido somando 0,1.";
        } else {
            $vizinho = round($base - 0.1, 1);
            $correta1 = 'antecessor';
            $correta2 = 'menor';
            $dica = "Contando de 0,1 em 0,1, o antecessor é obtido subtraindo 0,1.";
        }

        $templates = [
            "Considerando uma contagem de 0,1 em 0,1, o {campo_1} de " . formatarNumeroBR($base) . " é " . formatarNumeroBR($vizinho) . ", por ser {campo_2} que " . formatarNumeroBR($base) . ".",
            "Na sequência decimal de 0,1 em 0,1, o número " . formatarNumeroBR($vizinho) . " é {campo_2} que " . formatarNumeroBR($base) . " e representa o seu {campo_1}."
        ];

        return [
            'enunciadoTemplate' => $templates[array_rand($templates)],
            'dica' => $dica,
            'campos' => [
                [
                    'id' => 'campo_1',
                    'opcoes' => ['sucessor', 'antecessor'],
                    'respostaCorreta' => $correta1
                ],
                [
                    'id' => 'campo_2',
                    'opcoes' => ['maior', 'menor'],
                    'respostaCorreta' => $correta2
                ]
            ]
        ];
    }

    // Subtipo 3: Operação matemática rigorosa (+, -, ×, ÷)
    // Exatamente UMA das 4 operações deve satisfazer a igualdade
    $tentativas = 0;
    while ($tentativas < 50) {
        $tentativas++;
        $base = rand(5, 60);
        $passo = 1; // Sucessor/Antecessor padrão envolve passo 1
        $tipo = (rand(0, 1) === 0) ? 'sucessor' : 'antecessor';

        if ($tipo === 'sucessor') {
            $vizinho = $base + $passo;
            $operacaoAlvo = '+';
        } else {
            $vizinho = $base - $passo;
            $operacaoAlvo = '-';
        }

        // Testar quais das 4 operações tornam: base [OP] passo == vizinho
        $validas = [];
        // Teste +
        if (($base + $passo) == $vizinho) $validas[] = '+';
        // Teste -
        if (($base - $passo) == $vizinho) $validas[] = '-';
        // Teste ×
        if (($base * $passo) == $vizinho) $validas[] = '×';
        // Teste ÷
        if ($passo != 0 && ($base / $passo) == $vizinho) $validas[] = '÷';

        // Garantia de unicidade: EXATAMENTE uma operação válida
        if (count($validas) === 1 && $validas[0] === $operacaoAlvo) {
            $nomeTipo = $tipo;
            $templates = [
                "O {$nomeTipo} de " . formatarNumeroBR($base) . " é " . formatarNumeroBR($vizinho) . ", pois: " . formatarNumeroBR($base) . " {campo_operacao} " . formatarNumeroBR($passo) . " = " . formatarNumeroBR($vizinho) . ".",
                "Sabendo que o {$nomeTipo} de " . formatarNumeroBR($base) . " é " . formatarNumeroBR($vizinho) . ", a conta correta é: " . formatarNumeroBR($base) . " {campo_operacao} " . formatarNumeroBR($passo) . " = " . formatarNumeroBR($vizinho) . "."
            ];
            $dica = ($tipo === 'sucessor')
                ? "Para encontrar o sucessor, adicionamos (somamos) 1."
                : "Para encontrar o antecessor, subtraímos (diminuímos) 1.";

            return [
                'enunciadoTemplate' => $templates[array_rand($templates)],
                'dica' => $dica,
                'campos' => [
                    [
                        'id' => 'campo_operacao',
                        'opcoes' => ['+', '-', '×', '÷'],
                        'respostaCorreta' => $operacaoAlvo
                    ]
                ]
            ];
        }
    }

    // Fallback seguro caso atinja limite
    return gerarQuestaoMedio();
}

// Parâmetros de entrada
$dificuldade = $_GET['dificuldade'] ?? 'facil';
if (!in_array($dificuldade, ['facil', 'medio', 'experiente'])) {
    $dificuldade = 'facil';
}

// Quantidade de questões por partida:
// Fácil: 3 questões | Médio: 4 questões | Experiente: 5 questões
$qtdQuestoes = 3;
if ($dificuldade === 'medio') $qtdQuestoes = 4;
if ($dificuldade === 'experiente') $qtdQuestoes = 5;

$questoes = [];
for ($i = 0; $i < $qtdQuestoes; $i++) {
    if ($dificuldade === 'facil') {
        $q = gerarQuestaoFacil();
    } elseif ($dificuldade === 'medio') {
        $q = gerarQuestaoMedio();
    } else {
        $q = gerarQuestaoExperiente();
    }
    $q['numero'] = $i + 1;
    $questoes[] = $q;
}

// Salva na sessão
$_SESSION['gabarito_jogo_4'] = [
    'dificuldade' => $dificuldade,
    'questoes' => $questoes
];

echo json_encode([
    'ok' => true,
    'dificuldade' => $dificuldade,
    'totalQuestoes' => count($questoes),
    'questoes' => $questoes
]);
