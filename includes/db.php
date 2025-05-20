<?php
// Configurações do banco de dados
$host = 'srv1576.hstgr.io';
$db = 'u414602466_agenda1';
$user = 'u414602466_agenda1';
$pass = 'gntM9d#Qo123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}

// Função para obter as configurações do sistema
function getConfiguracoes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
    return $stmt->fetch();
}


?>
