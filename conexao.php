<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "Math_db";
$port = "3308";

$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco,
    $port
);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "Conexão OK";
?>

<!-- esse é o primeiro documento  -->