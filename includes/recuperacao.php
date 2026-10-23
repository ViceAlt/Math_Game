<?php
/**
 * includes/recuperacao.php
 * Lógica de negócio do fluxo de recuperação de senha:
 * - Rate limiting (tentativas_acesso)
 * - Geração e invalidação de tokens
 * - Verificação detalhada de status (válido, expirado, já usado, inexistente)
 * - Redefinição da senha com transação atômica
 * - Registro em log seguro sem expor senhas ou tokens puros
 */

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/senha.php';
require_once __DIR__ . '/logger.php';

/**
 * Retorna o IP real do visitante (normalizado, máx 45 chars para IPv6).
 *
 * @return string IP normalizado.
 */
function recuperacao_obter_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return strtolower(substr(trim($ip), 0, 45));
}

/**
 * Registra uma tentativa de acesso (login ou recuperação) na tabela tentativas_acesso.
 *
 * @param string $tipo  'login', 'recuperacao' ou 'recuperacao_ip'
 * @param string $chave Email ou IP em minúsculas.
 */
function recuperacao_registrar_tentativa(string $tipo, string $chave): void {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO tentativas_acesso (tipo, chave, criado_em) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$tipo, strtolower($chave)]);
    } catch (\PDOException $e) {
        recuperacao_log("Erro ao registrar tentativa_acesso ({$tipo}): " . $e->getMessage(), 'ERROR');
    }
}

/**
 * Conta tentativas de acesso dentro de um intervalo de minutos.
 *
 * @param string $tipo     'login', 'recuperacao' ou 'recuperacao_ip'
 * @param string $chave    Email ou IP em minúsculas.
 * @param int    $minutos  Janela de tempo.
 * @return int Número de tentativas.
 */
function recuperacao_contar_tentativas(string $tipo, string $chave, int $minutos = 60): int {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_acesso
             WHERE tipo = ? AND chave = ?
               AND criado_em >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$tipo, strtolower($chave), $minutos]);
        return (int) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        recuperacao_log("Erro ao contar tentativas_acesso ({$tipo}): " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Remove todas as tentativas de acesso de uma chave específica.
 *
 * @param string $tipo  'login', 'recuperacao' ou 'recuperacao_ip'
 * @param string $chave Email ou IP em minúsculas.
 */
function recuperacao_limpar_tentativas(string $tipo, string $chave): void {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare('DELETE FROM tentativas_acesso WHERE tipo = ? AND chave = ?');
        $stmt->execute([$tipo, strtolower($chave)]);
    } catch (\PDOException $e) {
        recuperacao_log("Erro ao limpar tentativas_acesso ({$tipo}): " . $e->getMessage(), 'ERROR');
    }
}

/**
 * Verifica se um email ou IP excedeu o rate limit para recuperação de senha.
 * - Máx. 3 solicitações por email por hora.
 * - Máx. 10 solicitações por IP por hora.
 *
 * @param string $email Email da solicitação.
 * @param string $ip    IP da solicitação.
 * @return bool True se o limite foi excedido.
 */
function recuperacao_rate_limit_atingido(string $email, string $ip): bool {
    $tentativasEmail = recuperacao_contar_tentativas('recuperacao', $email, 60);
    if ($tentativasEmail >= 3) {
        recuperacao_log("Rate limit excedido para o email '{$email}' ({$tentativasEmail} tentativas na última hora)", 'WARNING');
        return true;
    }

    $tentativasIp = recuperacao_contar_tentativas('recuperacao_ip', $ip, 60);
    if ($tentativasIp >= 10) {
        recuperacao_log("Rate limit excedido para o IP '{$ip}' ({$tentativasIp} tentativas na última hora)", 'WARNING');
        return true;
    }

    return false;
}

/**
 * Gera um token de recuperação seguro e salva apenas seu hash SHA-256 no banco.
 * Invalida tokens anteriores ainda ativos deste usuário.
 *
 * @param int    $usuarioId ID do usuário.
 * @param string $ip        IP solicitante.
 * @return string Token puro gerado (para envio no link).
 */
function recuperacao_gerar_token(int $usuarioId, string $ip): string {
    $pdo = getDB();
    $minutosExpiracao = defined('TOKEN_EXPIRACAO_MINUTOS') ? TOKEN_EXPIRACAO_MINUTOS : 60;

    // Invalida todos os tokens anteriores ativos deste usuário
    $stmtInvalida = $pdo->prepare(
        "UPDATE recuperacao_senha
         SET usado_em = NOW()
         WHERE usuario_id = ? AND usado_em IS NULL"
    );
    $stmtInvalida->execute([$usuarioId]);
    $tokensInvalidados = $stmtInvalida->rowCount();

    // Gera token seguro com 64 caracteres hexadecimais (32 bytes criptográficos)
    $tokenPuro = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $tokenPuro);

    $stmtInsert = $pdo->prepare(
        "INSERT INTO recuperacao_senha
             (usuario_id, token_hash, expira_em, criado_em, ip_solicitacao)
         VALUES
             (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW(), ?)"
    );
    $stmtInsert->execute([$usuarioId, $tokenHash, $minutosExpiracao, $ip]);

    recuperacao_log(sprintf(
        "Token gerado para usuario_id=%d (hash=%s..., expiração=%d min, tokens anteriores invalidados=%d)",
        $usuarioId,
        substr($tokenHash, 0, 12),
        $minutosExpiracao,
        $tokensInvalidados
    ), 'INFO');

    return $tokenPuro;
}

/**
 * Verifica detalhadamente o status do token de recuperação.
 * Permite que a interface informe ao usuário exatamente o que ocorreu (expirado, já usado, inexistente ou válido).
 *
 * @param string $token Token puro recebido pela URL.
 * @return array ['status' => 'valido'|'expirado'|'ja_usado'|'inexistente', 'dados' => array|null]
 */
function recuperacao_verificar_status_token(string $token): array {
    if (empty($token) || strlen($token) !== 64) {
        return ['status' => 'inexistente', 'dados' => null];
    }

    $tokenHash = hash('sha256', $token);
    $pdo = getDB();

    $stmt = $pdo->prepare(
        "SELECT r.id, r.usuario_id, r.expira_em, r.usado_em,
                (r.expira_em <= NOW()) AS expirou,
                u.username, u.email
         FROM recuperacao_senha r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.token_hash = ?
         LIMIT 1"
    );
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        recuperacao_log("Verificação de token: token hash " . substr($tokenHash, 0, 12) . "... não encontrado", 'WARNING');
        return ['status' => 'inexistente', 'dados' => null];
    }

    if (!empty($row['usado_em'])) {
        recuperacao_log("Verificação de token: token ID {$row['id']} já foi utilizado em {$row['usado_em']}", 'INFO');
        return ['status' => 'ja_usado', 'dados' => $row];
    }

    if ((int) $row['expirou'] === 1) {
        recuperacao_log("Verificação de token: token ID {$row['id']} expirou em {$row['expira_em']}", 'INFO');
        return ['status' => 'expirado', 'dados' => $row];
    }

    return ['status' => 'valido', 'dados' => $row];
}

/**
 * Busca o registro do token somente se for estritamente válido e ativo.
 *
 * @param string $token Token puro.
 * @return array|null Dados do registro ou null se inválido/expirado/usado.
 */
function recuperacao_buscar_token_valido(string $token): ?array {
    $resultado = recuperacao_verificar_status_token($token);
    return ($resultado['status'] === 'valido') ? $resultado['dados'] : null;
}

/**
 * Redefine a senha do usuário de forma atômica:
 * 1. Abre transação PDO.
 * 2. Marca o token como usado (UPDATE com WHERE usado_em IS NULL e checa rowCount).
 * 3. Invalida outros tokens pendentes deste usuário.
 * 4. Atualiza o hash bcrypt na tabela usuarios e registra senha_alterada_em.
 * 5. Limpa tentativas de login anteriores.
 * 6. Confirma a transação.
 *
 * @param int    $usuarioId ID do usuário.
 * @param int    $tokenId   ID do registro em recuperacao_senha.
 * @param string $novaSenha Nova senha em texto puro.
 * @return bool True se a senha foi redefinida com sucesso.
 */
function recuperacao_redefinir_senha(int $usuarioId, int $tokenId, string $novaSenha): bool {
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // 1. Marca este token específico como usado garantindo concorrência segura
        $stmt = $pdo->prepare(
            "UPDATE recuperacao_senha
             SET usado_em = NOW()
             WHERE id = ? AND usado_em IS NULL"
        );
        $stmt->execute([$tokenId]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            recuperacao_log("Tentativa concorrente ou token já consumido: token_id={$tokenId}", 'WARNING');
            return false;
        }

        // 2. Invalida quaisquer outras solicitações pendentes deste usuário
        $pdo->prepare(
            "UPDATE recuperacao_senha
             SET usado_em = NOW()
             WHERE usuario_id = ? AND usado_em IS NULL"
        )->execute([$usuarioId]);

        // 3. Atualiza a senha do usuário com hash seguro (bcrypt)
        $novoHash = gerar_hash_senha($novaSenha);
        $stmtUser = $pdo->prepare(
            "UPDATE usuarios
             SET senha_hash = ?, senha_alterada_em = NOW()
             WHERE id = ?"
        );
        $stmtUser->execute([$novoHash, $usuarioId]);

        $pdo->commit();

        recuperacao_log("Senha redefinida com sucesso para usuario_id={$usuarioId} via token_id={$tokenId}", 'SUCCESS');
        return true;

    } catch (\PDOException $e) {
        $pdo->rollBack();
        recuperacao_log("Erro no banco de dados ao redefinir senha (usuario_id={$usuarioId}): " . $e->getMessage(), 'ERROR');
        return false;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        recuperacao_log("Erro inesperado ao redefinir senha (usuario_id={$usuarioId}): " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Limpa periodicamente tokens expirados e tentativas com mais de 24 horas.
 */
function recuperacao_limpar_dados_antigos(): void {
    try {
        $pdo = getDB();
        $pdo->exec("DELETE FROM recuperacao_senha WHERE expira_em < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $pdo->exec("DELETE FROM tentativas_acesso WHERE criado_em < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    } catch (\PDOException $e) {
        recuperacao_log("Erro na limpeza de dados antigos: " . $e->getMessage(), 'ERROR');
    }
}
