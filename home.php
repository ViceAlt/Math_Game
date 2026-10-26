<?php
// Redirecionamento defensivo caso o usuário ou histórico acerte a rota raiz /home.php
header('Location: home/home.php');
exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=home/home.php">
    <title>Redirecionando...</title>
</head>
<body>
    <p>Redirecionando... <a href="home/home.php">Clique aqui</a> se nada acontecer.</p>
</body>
</html>
