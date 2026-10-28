<?php
/**
 * api/avatar/comprar.php
 * POST – Realiza a compra de um avatar por 10 estrelas e equipa imediatamente.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/sessao.php';
sessao_iniciar();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || empty($usuario['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Faça login para comprar avatares.']);
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
if (!preg_match('/^foto([0-9]+)\.webp$/i', $fotoNome, $matches)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Avatar inválido selecionado.']);
    exit;
}

$fotoNome = strtolower($fotoNome);
$numeroFoto = (int) $matches[1];

// Foto 1 é a foto padrão gratuita de todos os alunos
if ($numeroFoto === 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'A Foto 1 é o avatar padrão e já é gratuita para todos os alunos.']);
    exit;
}

$pastaFotos = __DIR__ . '/../../fotos';
if (!file_exists($pastaFotos . '/' . $fotoNome)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'erro' => 'Arquivo do avatar não encontrado no servidor.']);
    exit;
}

$custoEstrelas = 10;
$usuarioId = (int) $usuario['id'];

require_once __DIR__ . '/../../conexao.php';

try {
    $pdo = getDB();

    // Garante tabela de compras
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios_avatares (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id  INT UNSIGNED NOT NULL,
            foto_nome   VARCHAR(50)  NOT NULL,
            preco_pago  INT UNSIGNED NOT NULL DEFAULT 10,
            comprado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ua_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            CONSTRAINT uq_user_foto UNIQUE (usuario_id, foto_nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Verifica se já comprou esta foto
    $stmtJaComprou = $pdo->prepare('SELECT id FROM usuarios_avatares WHERE usuario_id = ? AND foto_nome = ? LIMIT 1');
    $stmtJaComprou->execute([$usuarioId, $fotoNome]);
    if ($stmtJaComprou->fetch()) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Você já possui este avatar! Basta selecioná-lo para equipar.']);
        exit;
    }

    // Calcula saldo atual de estrelas
    $stmtEstrelas = $pdo->prepare('SELECT SUM(COALESCE(estrelas, 0)) AS total FROM progresso_usuario WHERE usuario_id = ?');
    $stmtEstrelas->execute([$usuarioId]);
    $totalConquistadas = (int) ($stmtEstrelas->fetch()['total'] ?? 0);

    $stmtGasto = $pdo->prepare('SELECT SUM(preco_pago) AS total FROM usuarios_avatares WHERE usuario_id = ?');
    $stmtGasto->execute([$usuarioId]);
    $totalGasto = (int) ($stmtGasto->fetch()['total'] ?? 0);

    $saldoAtual = max(0, $totalConquistadas - $totalGasto);

    if ($saldoAtual < $custoEstrelas) {
        http_response_code(400);
        echo json_encode([
            'ok'             => false,
            'erro'           => "Estrelas insuficientes! O avatar custa {$custoEstrelas} estrelas, mas seu saldo atual é de {$saldoAtual} estrelas. Jogue fases para conquistar mais estrelas!",
            'saldo_atual'    => $saldoAtual,
            'estrelas_custo' => $custoEstrelas
        ]);
        exit;
    }

    // Executa a transação de compra e equipamento da foto
    $pdo->beginTransaction();

    // 1. Registra a compra
    $stmtInsert = $pdo->prepare('INSERT INTO usuarios_avatares (usuario_id, foto_nome, preco_pago) VALUES (?, ?, ?)');
    $stmtInsert->execute([$usuarioId, $fotoNome, $custoEstrelas]);

    // 2. Atualiza a foto de perfil do usuário
    $caminhoRelativo = 'fotos/' . $fotoNome;
    $stmtUpdate = $pdo->prepare('UPDATE usuarios SET foto_url = ? WHERE id = ?');
    $stmtUpdate->execute([$caminhoRelativo, $usuarioId]);

    $pdo->commit();

    // 3. Atualiza a sessão ativa
    $_SESSION['usuario']['foto_url'] = $caminhoRelativo;

    $novoSaldo = max(0, $saldoAtual - $custoEstrelas);

    echo json_encode([
        'ok'             => true,
        'mensagem'       => "Avatar #{$numeroFoto} adquirido e equipado com sucesso!",
        'foto_atual'     => $fotoNome,
        'foto_url'       => '../' . $caminhoRelativo,
        'saldo_estrelas' => $novoSaldo,
        'total_gasto'    => $totalGasto + $custoEstrelas
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao processar compra: ' . $e->getMessage()]);
}
