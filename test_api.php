<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * test_api.php
 * Executa testes básicos nas rotas de API para garantir funcionamento de login, cadastro, etc.
 * Rode com: php test_api.php
 */

require_once 'conexao.php';

$pdo = getDB();

echo "--- Iniciando Testes de API ---\n";

// 1. Limpeza
echo "Limpando usuário de teste... ";
$pdo->exec("DELETE FROM usuarios WHERE email = 'test_api@mathgame.com'");
echo "OK\n";

// Helper request
function request($url, $method = 'GET', $data = []) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => $method,
            'content' => json_encode($data),
            'ignore_errors' => true // Pega o body mesmo em caso de erro 4xx/5xx
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents("http://localhost/Math_Game/" . $url, false, $context);
    
    // extrai http code
    preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
    $status = $match[1];
    
    return ['status' => $status, 'body' => json_decode($result, true)];
}

// 2. Teste: Cadastro
echo "Testando Cadastro... ";
$res = request('api/auth/register.php', 'POST', [
    'username' => 'testUserAPI',
    'email' => 'test_api@mathgame.com',
    'senha' => 'senha_forte_123'
]);

if ($res['status'] == 201 && $res['body']['ok'] == true) {
    echo "PASS\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
    print_r($res['body']);
}

// 3. Teste: Login Invalido
echo "Testando Login Inválido... ";
$res = request('api/auth/login.php', 'POST', [
    'username' => 'testUserAPI',
    'senha' => 'senha_errada'
]);

if ($res['status'] == 401 && $res['body']['ok'] == false) {
    echo "PASS\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
    print_r($res['body']);
}

// 4. Teste: Login Válido
echo "Testando Login Válido... ";
$res = request('api/auth/login.php', 'POST', [
    'username' => 'testUserAPI',
    'senha' => 'senha_forte_123'
]);

if ($res['status'] == 200 && $res['body']['ok'] == true) {
    echo "PASS\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
    print_r($res['body']);
}

echo "--- Fim dos Testes ---\n";
