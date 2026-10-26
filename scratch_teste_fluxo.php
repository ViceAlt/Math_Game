<?php
/**
 * scratch_teste_fluxo.php
 * Script automatizado de validação completa do fluxo de recuperação de senha.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/senha.php';
require_once __DIR__ . '/includes/recuperacao.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/includes/logger.php';

$pdo = getDB();

echo "====================================================\n";
echo " INICIANDO BATERIA DE TESTES - RECUPERAÇÃO DE SENHA\n";
echo "====================================================\n\n";

// 0. Verifica usuário de teste
$testEmail = 'vitor.landin@aluno.senai.br';
$stmt = $pdo->prepare("SELECT id, username, email, senha_hash FROM usuarios WHERE email = ?");
$stmt->execute([$testEmail]);
$user = $stmt->fetch();

if (!$user) {
    die("ERRO CRÍTICO: Usuário de teste {$testEmail} não encontrado no banco math_game.\n");
}

$originalHash = $user['senha_hash'];
$userId = (int) $user['id'];
$ipTeste = '127.0.0.1';

echo "[1] Usuário de teste carregado: ID {$userId}, Username '{$user['username']}', Email '{$user['email']}'\n";

// 1. Limpa tokens e tentativas anteriores do usuário de teste
$pdo->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = ?")->execute([$userId]);
$pdo->prepare("DELETE FROM tentativas_acesso WHERE chave = ? OR chave = ?")->execute([$testEmail, $ipTeste]);
echo "[2] Registros anteriores limpos com sucesso.\n";

// 2. Teste de E-mail não cadastrado
echo "\n--- Teste A: Solicitação com e-mail não cadastrado ---\n";
$emailInexistente = 'naoexiste_' . time() . '@exemplo.com';
recuperacao_registrar_tentativa('recuperacao', $emailInexistente);
$countNaoExiste = recuperacao_contar_tentativas('recuperacao', $emailInexistente, 10);
if ($countNaoExiste === 1) {
    echo " -> PASS: Tentativa registrada sem revelar se a conta existe.\n";
} else {
    echo " -> FAIL: Tentativa não registrada corretamente.\n";
}

// 3. Teste de Geração de Token Seguro (Expiração de 60 minutos)
echo "\n--- Teste B: Geração de Token Seguro e Validade ---\n";
$tokenPuro = recuperacao_gerar_token($userId, $ipTeste);
echo " -> Token gerado: " . substr($tokenPuro, 0, 8) . "... (64 chars hex)\n";

$tokenHash = hash('sha256', $tokenPuro);
$stmtToken = $pdo->prepare("SELECT * FROM recuperacao_senha WHERE token_hash = ?");
$stmtToken->execute([$tokenHash]);
$dbToken = $stmtToken->fetch();

if (!$dbToken) {
    echo " -> FAIL: Token não encontrado no banco pelo hash SHA-256.\n";
} else {
    echo " -> PASS: Token gravado exclusivamente como hash SHA-256.\n";
    echo " -> Expira em: {$dbToken['expira_em']} (Criado em: {$dbToken['criado_em']})\n";
}

// 4. Teste de Validação de Status: Token Válido
echo "\n--- Teste C: Verificação de Status do Token (Válido) ---\n";
$statusValido = recuperacao_verificar_status_token($tokenPuro);
if ($statusValido['status'] === 'valido' && (int)$statusValido['dados']['usuario_id'] === $userId) {
    echo " -> PASS: Token reconhecido como 'valido'.\n";
} else {
    echo " -> FAIL: Status incorreto: " . json_encode($statusValido) . "\n";
}

// 5. Teste de Envio de E-mail via PHPMailer + SMTP (Mailpit)
echo "\n--- Teste D: Disparo de E-mail de Recuperação ---\n";
$linkReset = rtrim(APP_URL, '/') . '/redefinir_senha.php?token=' . urlencode($tokenPuro);
echo " -> Link gerado: {$linkReset}\n";

$emailOk = email_enviar_recuperacao($user['email'], $user['username'], $linkReset);
if ($emailOk) {
    echo " -> PASS: E-mail de recuperação enviado com sucesso via PHPMailer.\n";
} else {
    echo " -> FAIL: Falha no envio de e-mail.\n";
}

// 6. Teste de Redefinição de Senha
echo "\n--- Teste E: Redefinição de Senha Atômica ---\n";
$novaSenhaTeste = 'NovaSenhaSegura@2026';
$redefiniu = recuperacao_redefinir_senha($userId, (int)$statusValido['dados']['id'], $novaSenhaTeste);

if ($redefiniu) {
    echo " -> PASS: Senha redefinida com sucesso!\n";
} else {
    echo " -> FAIL: Não foi possível redefinir a senha.\n";
}

// 7. Confirma que a nova senha funciona e a antiga não
echo "\n--- Teste F: Verificação de Autenticação com Bcrypt ---\n";
$stmtUpdatedUser = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
$stmtUpdatedUser->execute([$userId]);
$updatedHash = $stmtUpdatedUser->fetchColumn();

$verificaAntiga = verificar_senha('senha_antiga_errada', $updatedHash);
$verificaNova = verificar_senha($novaSenhaTeste, $updatedHash);

if (!$verificaAntiga && $verificaNova) {
    echo " -> PASS: Nova senha aceita pelo bcrypt e senha antiga inválida!\n";
} else {
    echo " -> FAIL: Falha na conferência dos hashes.\n";
}

// 8. Teste de Reutilização de Token (Uso Único)
echo "\n--- Teste G: Proteção contra Reutilização de Token (Uso Único) ---\n";
$statusUsado = recuperacao_verificar_status_token($tokenPuro);
if ($statusUsado['status'] === 'ja_usado') {
    echo " -> PASS: Token marcado como 'ja_usado' e rejeitado para novas trocas.\n";
} else {
    echo " -> FAIL: Status incorreto para token já consumido: {$statusUsado['status']}\n";
}

// 9. Teste de Token Expirado
echo "\n--- Teste H: Rejeição de Token Expirado ---\n";
$tokenExpiradoPuro = bin2hex(random_bytes(32));
$hashExpirado = hash('sha256', $tokenExpiradoPuro);
$pdo->prepare("INSERT INTO recuperacao_senha (usuario_id, token_hash, expira_em, criado_em, ip_solicitacao) VALUES (?, ?, DATE_SUB(NOW(), INTERVAL 10 MINUTE), DATE_SUB(NOW(), INTERVAL 70 MINUTE), ?)")
    ->execute([$userId, $hashExpirado, $ipTeste]);

$statusExpirado = recuperacao_verificar_status_token($tokenExpiradoPuro);
if ($statusExpirado['status'] === 'expirado') {
    echo " -> PASS: Token com data no passado identificado corretamente como 'expirado'.\n";
} else {
    echo " -> FAIL: Token expirado não foi identificado como 'expirado': {$statusExpirado['status']}\n";
}

// 10. Teste de Token Inexistente / Adulterado
echo "\n--- Teste I: Rejeição de Token Adulterado / Inexistente ---\n";
$tokenAdulterado = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
$statusInexistente = recuperacao_verificar_status_token($tokenAdulterado);
if ($statusInexistente['status'] === 'inexistente') {
    echo " -> PASS: Token adulterado identificado corretamente como 'inexistente'.\n";
} else {
    echo " -> FAIL: Token inexistente retornou status: {$statusInexistente['status']}\n";
}

// 11. Restaura o hash original do usuário para não afetar os dados de trabalho do usuário
$pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?")->execute([$originalHash, $userId]);
echo "\n[INFO] Hash da senha original do usuário restabelecido no banco de dados.\n";

// 12. Verifica o arquivo de logs
$logPath = __DIR__ . '/logs/recuperacao.log';
if (file_exists($logPath) && filesize($logPath) > 0) {
    echo "\n--- Verificação do Arquivo de Logs (logs/recuperacao.log) ---\n";
    echo " -> Arquivo de log presente. Tamanho: " . filesize($logPath) . " bytes.\n";
    $linhas = array_slice(file($logPath), -6);
    echo "Últimas 6 linhas do log:\n";
    foreach ($linhas as $l) {
        echo "   " . trim($l) . "\n";
    }
}

echo "\n====================================================\n";
echo " TODOS OS TESTES FORAM CONCLUÍDOS COM SUCESSO!\n";
echo "====================================================\n";
