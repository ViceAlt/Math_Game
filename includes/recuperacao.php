<?php
/**
 * includes/recuperacao.php
 * Lógica de negócio do fluxo de recuperação de senha:
 * - Rate limiting (tentativas_acesso)
 * - Geração e invalidação de tokens
 * - Redefinição da senha com transação atômica
 */

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/senha.php';

/**
 * Retorna o IP real do visitante (suporta proxies reversos simples).
 * Nunca confie cegamente em X-Forwarded-For sem validar a origem.
 *
 * @return string IP normalizado (máx 45 chars para IPv6).
 */
function recuperacao_obter_ip(): string {
    // Em produção com proxy confiável, você pode descomentar X-Forwarded-For:
    // $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    // if ($ip) return trim(explode(',', $ip)[0]);
    return strtolower(substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45));
}

/**
 * Registra uma tentativa de acesso (login ou recuperação) na tabela tentativas_acesso.
 *
 * @param string $tipo  'login' ou 'recuperacao'
 * @param string $chave Email ou IP em minúsculas.
 */
function recuperacao_registrar_tentativa(string $tipo, string $chave): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO tentativas_acesso (tipo, chave, criado_em) VALUES (?, ?, NOW())'
    );
    $stmt->execute([$tipo, strtolower($chave)]);
}

/**
 * Conta tentativas de acesso dentro de um intervalo de minutos.
 *
 * @param string $tipo     'login' ou 'recuperacao'
 * @param string $chave    Email ou IP em minúsculas.
 * @param int    $minutos  Janela de tempo.
 * @return int Número de tentativas.
 */
function recuperacao_contar_tentativas(string $tipo, string $chave, int $minutos = 60): int {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM tentativas_acesso
         WHERE tipo = ? AND chave = ?
           AND criado_em >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->execute([$tipo, strtolower($chave), $minutos]);
    return (int) $stmt->fetchColumn();
}

/**
 * Remove todas as tentativas de acesso de uma chave específica.
 * Chamada após login bem-sucedido para limpar o contador.
 *
 * @param string $tipo  'login' ou 'recuperacao'
 * @param string $chave Email ou IP em minúsculas.
 */
function recuperacao_limpar_tentativas(string $tipo, string $chave): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare('DELETE FROM tentativas_acesso WHERE tipo = ? AND chave = ?');
    $stmt->execute([$tipo, strtolower($chave)]);
}

/**
 * Verifica se um email ou IP excedeu o rate limit para recuperação de senha.
 * - Máx. 3 solicitações por email por hora.
 * - Máx. 10 solicitações por IP por hora.
 *
 * @param string $email Email da solicitação (já em minúsculas).
 * @param string $ip    IP da solicitação.
 * @return bool True se o limite foi excedido.
 */
function recuperacao_rate_limit_atingido(string $email, string $ip): bool {
    $tentativasEmail = recuperacao_contar_tentativas('recuperacao', $email, 60);
    if ($tentativasEmail >= 3) {
        return true;
    }

    $tentativasIp = recuperacao_contar_tentativas('recuperacao_ip', $ip, 60);
    if ($tentativasIp >= 10) {
        return true;
    }

    return false;
}

/**
 * Gera um token de recuperação e salva apenas seu hash (SHA-256) no banco.
 * Invalida tokens anteriores ainda ativos deste usuário.
 * Usa o relógio do MySQL para calcular expiração de 30 minutos.
 *
 * @param int    $usuarioId     ID do usuário no banco.
 * @param string $ip            IP da solicitação.
 * @return string Token puro (a ser enviado no link de e-mail).
 */
function recuperacao_gerar_token(int $usuarioId, string $ip): string {
    $pdo = getDB();

    // Invalida todos os tokens anteriores do usuário (marca como usados)
    $pdo->prepare(
        "UPDATE recuperacao_senha
         SET usado_em = NOW()
         WHERE usuario_id = ? AND usado_em IS NULL"
    )->execute([$usuarioId]);

    // Gera token seguro e armazena apenas seu hash
    $token     = bin2hex(random_bytes(32)); // 64 chars hex
    $tokenHash = hash('sha256', $token);

    $pdo->prepare(
        "INSERT INTO recuperacao_senha
             (usuario_id, token_hash, expira_em, criado_em, ip_solicitacao)
         VALUES
             (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW(), ?)"
    )->execute([$usuarioId, $tokenHash, $ip]);

    return $token;
}

/**
 * Busca o registro de recuperação pelo token, validando que está ativo e não expirado.
 * Usa NOW() do MySQL para comparação de tempo.
 *
 * @param string $token Token puro recebido da URL.
 * @return array|null Linha do banco ou null se inválido/expirado.
 */
function recuperacao_buscar_token_valido(string $token): ?array {
    $tokenHash = hash('sha256', $token);
    $pdo       = getDB();

    $stmt = $pdo->prepare(
        "SELECT r.id, r.usuario_id, r.expira_em, r.usado_em, u.username, u.email
         FROM recuperacao_senha r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.token_hash = ?
           AND r.usado_em IS NULL
           AND r.expira_em > NOW()
         LIMIT 1"
    );
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Redefine a senha do usuário de forma atômica:
 * 1. Abre uma transação.
 * 2. Marca o token como usado (UPDATE com WHERE usado_em IS NULL, verifica rowCount).
 * 3. Invalida outros tokens pendentes do usuário.
 * 4. Atualiza o hash da senha e registra senha_alterada_em.
 * 5. Confirma a transação.
 *
 * @param int    $usuarioId   ID do usuário.
 * @param int    $tokenId     ID do registro em recuperacao_senha.
 * @param string $novaSenha   Nova senha em texto puro.
 * @return bool True se a operação foi concluída com sucesso.
 */
function recuperacao_redefinir_senha(int $usuarioId, int $tokenId, string $novaSenha): bool {
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // Marca o token como usado (uso único: WHERE usado_em IS NULL)
        $stmt = $pdo->prepare(
            "UPDATE recuperacao_senha
             SET usado_em = NOW()
             WHERE id = ? AND usado_em IS NULL"
        );
        $stmt->execute([$tokenId]);

        // Se nenhuma linha foi afetada, outro processo usou o token simultaneamente
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        // Invalida outros tokens pendentes do mesmo usuário
        $pdo->prepare(
            "UPDATE recuperacao_senha
             SET usado_em = NOW()
             WHERE usuario_id = ? AND usado_em IS NULL"
        )->execute([$usuarioId]);

        // Atualiza a senha com bcrypt e registra data de alteração
        $novoHash = gerar_hash_senha($novaSenha);
        $pdo->prepare(
            "UPDATE usuarios
             SET senha_hash = ?, senha_alterada_em = NOW()
             WHERE id = ?"
        )->execute([$novoHash, $usuarioId]);

        $pdo->commit();
        return true;

    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log('[Math Game] Erro ao redefinir senha (usuario_id=' . $usuarioId . '): ' . $e->getMessage());
        return false;
    }
}

/**
 * Apaga tokens expirados e tentativas de acesso com mais de 24 horas.
 * Pode ser chamada oportunisticamente ou via cron.
 */
function recuperacao_limpar_dados_antigos(): void {
    try {
        $pdo = getDB();
        $pdo->exec("DELETE FROM recuperacao_senha WHERE expira_em < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $pdo->exec("DELETE FROM tentativas_acesso WHERE criado_em < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    } catch (\PDOException $e) {
        error_log('[Math Game] Erro ao limpar dados antigos: ' . $e->getMessage());
    }
}
