<?php
// Definir o fuso horário para America/Sao_Paulo
date_default_timezone_set('America/Sao_Paulo');

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Obter configurações do sistema
$config = getConfiguracoes();

// Definir o fuso horário do MySQL para -03:00 (America/Sao_Paulo)
$pdo->exec("SET time_zone = '-03:00'");

// Obter data atual no fuso horário correto
$data_atual = date('Y-m-d');
$nome_dia = getNomeDiaSemana($data_atual);
$data_formatada = formatarData($data_atual);

// Resto do código permanece o mesmo...
