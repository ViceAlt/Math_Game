<?php
/**
 * includes/validador_fase.php
 * Validação no servidor (PHP) do desbloqueio de fases.
 * Impede que fases bloqueadas sejam acessadas digitando diretamente a URL.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexao.php';

if (!function_exists('validarAcessoFaseServidor')) {
    /**
     * Valida se o usuário tem permissão para acessar a fase informada.
     *
     * @param int|null $anoLetivo Número do ano letivo (6, 7, 8 ou 9)
     * @param int|null $numeroFase Número da fase (1 a 20)
     * @return bool True se permitido, false se bloqueado
     */
    function validarAcessoFaseServidor(?int $anoLetivo, ?int $numeroFase): bool {
        // Se for jogo livre (sem ano ou fase especificada), o acesso é livre
        if (!$anoLetivo || !$numeroFase) {
            return true;
        }

        // Fase 1 de qualquer mundo começa sempre desbloqueada (Opção B)
        if ($numeroFase === 1) {
            return true;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuario = $_SESSION['usuario'] ?? null;
        // Visitantes só podem jogar a Fase 1 (degustação - Opção A)
        if (!$usuario) {
            return false;
        }

        $usuarioId = (int) $usuario['id'];

        try {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                'SELECT pu.desbloqueado
                 FROM fases f
                 LEFT JOIN progresso_usuario pu ON pu.fase_id = f.id AND pu.usuario_id = ?
                 WHERE f.ano_letivo = ? AND f.numero_fase = ?
                 LIMIT 1'
            );
            $stmt->execute([$usuarioId, $anoLetivo, $numeroFase]);
            $row = $stmt->fetch();

            return !empty($row['desbloqueado']);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
