<?php
/**
 * jogo_5/test_equacoes.php
 * Script automatizado que valida o banco de equações de A Balança de Equilíbrio.
 */

$jsonFile = __DIR__ . '/equacoes.json';
if (!file_exists($jsonFile)) {
    die("ERRO: equacoes.json não encontrado.\n");
}

$equacoes = json_decode(file_get_contents($jsonFile), true);
if (!is_array($equacoes)) {
    die("ERRO: Formato JSON inválido.\n");
}

echo "=== VALIDAÇÃO MATEMÁTICA DAS EQUAÇÕES (JOGO 5) ===\n\n";

$total = count($equacoes);
$erros = 0;
$fasesMapeadas = [7 => 0, 9 => 0, 13 => 0, 15 => 0, 17 => 0, 19 => 0];

foreach ($equacoes as $idx => $eq) {
    $id = $eq['id'] ?? "idx_$idx";
    $x = $eq['solucao'];
    $esq = $eq['esq'];
    $dir = $eq['dir'];
    $nivel = $eq['nivel'];

    // 1. Solução deve ser inteira
    if (!is_int($x)) {
        echo "[FAIL] Eq $id: Solução $x não é inteira!\n";
        $erros++;
        continue;
    }

    // O jogo não oferece respostas negativas nem zero para a incógnita.
    if ($x <= 0) {
        echo "[FAIL] Eq $id: Solução $x deve ser positiva!\n";
        $erros++;
        continue;
    }

    // 2. Peso esquerdo vs direito com o valor oculto de x
    $pesoEsq = ($esq['x'] * $x) + $esq['blocos'] - $esq['baloes'];
    $pesoDir = ($dir['x'] * $x) + $dir['blocos'] - $dir['baloes'];

    if ($pesoEsq !== $pesoDir) {
        echo "[FAIL] Eq $id: Desbalanceada! Esq = $pesoEsq, Dir = $pesoDir (para x = $x)\n";
        $erros++;
        continue;
    }

    // 3. Limites de valores
    $limite = ($nivel === 'experiente') ? 30 : 20;
    if ($pesoEsq > $limite || $pesoDir > $limite || $x > $limite) {
        echo "[FAIL] Eq $id: Valores excedem limite ($limite): Peso=$pesoEsq, x=$x\n";
        $erros++;
        continue;
    }

    // 4. Contagem por fase
    if (!empty($eq['fases'])) {
        foreach ($eq['fases'] as $f) {
            if (isset($fasesMapeadas[$f])) {
                $fasesMapeadas[$f]++;
            }
        }
    }

    echo "✔ Eq {$id} [{$nivel}] -> {$esq['x']}x + {$esq['blocos']} - {$esq['baloes']} = {$dir['x']}x + {$dir['blocos']} - {$dir['baloes']} | x = {$x} (Peso: {$pesoEsq}kg, Passos: {$eq['passos_minimos']})\n";
}

echo "\n--- COBERTURA POR FASE DA TRILHA DO 7º ANO ---\n";
foreach ($fasesMapeadas as $fase => $qtd) {
    echo "Fase {$fase}: {$qtd} equações mapeadas\n";
    if ($qtd < 2) {
        echo " [ALERTA] Fase {$fase} tem poucas equações!\n";
        $erros++;
    }
}

echo "\n============================================\n";
if ($erros === 0) {
    echo "RESULTADO: TODAS AS {$total} EQUAÇÕES PASSARAM COM SUCESSO!\n";
} else {
    echo "RESULTADO: ENCONTRADOS {$erros} ERROS!\n";
}
echo "============================================\n";
