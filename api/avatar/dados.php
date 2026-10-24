<?php
/**
 * api/avatar/dados.php
 * GET – Retorna a lista de avatares, status de desbloqueio, foto atual
 * e o saldo de estrelas disponível para gastar como moeda.
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || empty($usuario['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Usuário não autenticado.']);
    exit;
}

$usuarioId = (int) $usuario['id'];

require_once __DIR__ . '/../../conexao.php';

try {
    $pdo = getDB();

    // 1. Garante que a tabela de compras existe (auto-migração resiliente)
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

    // 2. Busca foto atual do usuário no banco
    $stmtUser = $pdo->prepare('SELECT foto_url FROM usuarios WHERE id = ? LIMIT 1');
    $stmtUser->execute([$usuarioId]);
    $userDb = $stmtUser->fetch();
    $fotoUrlDb = $userDb['foto_url'] ?? $usuario['foto_url'] ?? '';

    // Extrai o nome do arquivo da foto atual (ex: 'foto1.webp')
    $fotoAtualNome = 'foto1.webp';
    if (!empty($fotoUrlDb)) {
        $basename = basename($fotoUrlDb);
        if (preg_match('/^foto[1-9]\.webp$/i', $basename)) {
            $fotoAtualNome = strtolower($basename);
        }
    }

    // 3. Calcula total de estrelas conquistadas pelo usuário nas fases (progresso pedagógico)
    $stmtEstrelas = $pdo->prepare('SELECT SUM(COALESCE(estrelas, 0)) AS total FROM progresso_usuario WHERE usuario_id = ?');
    $stmtEstrelas->execute([$usuarioId]);
    $rowEstrelas = $stmtEstrelas->fetch();
    $totalEstrelasConquistadas = (int) ($rowEstrelas['total'] ?? 0);

    // 4. Busca todas as fotos compradas pelo usuário
    $stmtCompras = $pdo->prepare('SELECT foto_nome, preco_pago FROM usuarios_avatares WHERE usuario_id = ?');
    $stmtCompras->execute([$usuarioId]);
    $compras = $stmtCompras->fetchAll();

    $fotosDesbloqueadas = ['foto1.webp' => true]; // foto1 é SEMPRE padrão e grátis
    $totalGasto = 0;
    foreach ($compras as $c) {
        $fotosDesbloqueadas[strtolower($c['foto_nome'])] = true;
        $totalGasto += (int) $c['preco_pago'];
    }

    // Saldo disponível como moeda de jogo
    $saldoEstrelas = max(0, $totalEstrelasConquistadas - $totalGasto);

    // 5. Escaneia a pasta de fotos (foto1.webp até foto9.webp)
    $pastaFotos = __DIR__ . '/../../fotos';
    $listaFotos = [];

    for ($i = 1; $i <= 9; $i++) {
        $nomeArquivo = "foto{$i}.webp";
        $caminhoFisico = $pastaFotos . '/' . $nomeArquivo;

        $existe = file_exists($caminhoFisico);
        $isPadrao = ($i === 1);
        $desbloqueada = $isPadrao || !empty($fotosDesbloqueadas[$nomeArquivo]);
        $equipada = ($nomeArquivo === $fotoAtualNome);

        $listaFotos[] = [
            'id'           => $nomeArquivo,
            'numero'       => $i,
            'titulo'       => $isPadrao ? 'Foto Padrão' : "Avatar #{$i}",
            'url'          => '../fotos/' . $nomeArquivo,
            'preco'        => $isPadrao ? 0 : 10,
            'padrao'       => $isPadrao,
            'desbloqueada' => $desbloqueada,
            'equipada'     => $equipada,
            'disponivel'   => $existe
        ];
    }

    echo json_encode([
        'ok'                         => true,
        'saldo_estrelas'             => $saldoEstrelas,
        'total_estrelas_conquistadas'=> $totalEstrelasConquistadas,
        'total_gasto'                => $totalGasto,
        'foto_atual'                 => $fotoAtualNome,
        'fotos'                      => $listaFotos
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao carregar dados dos avatares: ' . $e->getMessage()]);
}
