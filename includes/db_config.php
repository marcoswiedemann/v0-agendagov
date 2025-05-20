<?php
// Arquivo de configuração do banco de dados
// Gerado automaticamente em 2025-05-19 21:20:58

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Configurações do banco de dados
$db_host = 'srv1576.hstgr.io';
$db_name = 'u414602466_agenda1';
$db_user = 'u414602466_agenda1';
$db_pass = 'gntM9d#Qo123';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Configurar o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Definir o fuso horário do MySQL para -03:00 (America/Sao_Paulo)
    $pdo->exec("SET time_zone = '-03:00'");
    
} catch (PDOException $e) {
    // Em caso de erro na conexão
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
