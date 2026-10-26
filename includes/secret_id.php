<?php
require_once __DIR__ . '/../config.php';

function secret_id_chave(): string {
    if (!preg_match('/^[a-f0-9]{64}$/i', RECOVERY_SECRET_KEY)) {
        throw new RuntimeException('RECOVERY_SECRET_KEY deve conter 64 caracteres hexadecimais.');
    }

    return hex2bin(RECOVERY_SECRET_KEY);
}

function secret_id_gerar(): string {
    return 'MG-' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

function secret_id_cifrar(string $secret): string {
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(
        $secret,
        'aes-256-gcm',
        secret_id_chave(),
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        'math-game-recovery-secret-v1',
        16
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Não foi possível proteger o identificador de recuperação.');
    }

    return base64_encode($nonce . $tag . $ciphertext);
}

function secret_id_decifrar(string $encrypted): ?string {
    $payload = base64_decode($encrypted, true);
    if ($payload === false || strlen($payload) <= 28) {
        return null;
    }

    $secret = openssl_decrypt(
        substr($payload, 28),
        'aes-256-gcm',
        secret_id_chave(),
        OPENSSL_RAW_DATA,
        substr($payload, 0, 12),
        substr($payload, 12, 16),
        'math-game-recovery-secret-v1'
    );

    return $secret === false ? null : $secret;
}

function secret_id_criar(PDO $pdo, int $usuarioId): string {
    $secret = secret_id_gerar();
    $encrypted = secret_id_cifrar($secret);
    $hash = hash('sha256', $secret);
    $stmt = $pdo->prepare(
        'UPDATE usuarios SET recovery_secret_encrypted = ?, recovery_secret_hash = ? WHERE id = ? AND recovery_secret_hash IS NULL'
    );
    $stmt->execute([$encrypted, $hash, $usuarioId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('O identificador secreto já existe ou não foi salvo.');
    }

    return $secret;
}