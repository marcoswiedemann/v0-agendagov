<?php
// Iniciar a sessão
session_start();

// Definir o fuso horário para America/Sao_Paulo
date_default_timezone_set('America/Sao_Paulo');

// Configurações do banco de dados
$db_host = 'localhost';
$db_name = 'u414602466_prefeitoagenda';
$db_user = 'seu_usuario';
$db_pass = 'sua_senha';

// Mensagens e erros
$mensagens = [];
$erros = [];
$logs = [];

// Função para registrar logs
function log_message($message) {
    global $logs;
    $logs[] = date('H:i:s') . " - " . $message;
}

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_message("Conexão com o banco de dados estabelecida");
} catch (PDOException $e) {
    $erros[] = "Erro na conexão com o banco de dados: " . $e->getMessage();
    log_message("Erro na conexão: " . $e->getMessage());
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Ação: Corrigir fuso horário
    if ($action === 'fix_timezone') {
        try {
            // Definir o fuso horário do PHP
            date_default_timezone_set('America/Sao_Paulo');
            $php_timezone = date_default_timezone_get();
            log_message("Fuso horário do PHP definido para: " . $php_timezone);
            
            // Definir o fuso horário do MySQL
            $pdo->exec("SET GLOBAL time_zone = '-03:00'");
            $pdo->exec("SET time_zone = '-03:00'");
            log_message("Fuso horário do MySQL definido para: -03:00");
            
            // Verificar o fuso horário do MySQL
            $stmt = $pdo->query("SELECT @@global.time_zone, @@session.time_zone");
            $timezone_info = $stmt->fetch(PDO::FETCH_ASSOC);
            log_message("Fuso horário global do MySQL: " . $timezone_info['@@global.time_zone']);
            log_message("Fuso horário da sessão MySQL: " . $timezone_info['@@session.time_zone']);
            
            $mensagens[] = "Fuso horário corrigido com sucesso para America/Sao_Paulo (-03:00)";
        } catch (Exception $e) {
            $erros[] = "Erro ao corrigir fuso horário: " . $e->getMessage();
            log_message("Erro ao corrigir fuso horário: " . $e->getMessage());
        }
    }
    
    // Ação: Recriar tabela de compromissos
    if ($action === 'recreate_table') {
        try {
            // Fazer backup dos dados existentes
            $pdo->exec("CREATE TABLE IF NOT EXISTS compromissos_backup LIKE compromissos");
            $pdo->exec("INSERT INTO compromissos_backup SELECT * FROM compromissos");
            log_message("Backup da tabela compromissos criado");
            
            // Remover a tabela existente
            $pdo->exec("DROP TABLE IF EXISTS compromissos");
            log_message("Tabela compromissos removida");
            
            // Criar a tabela novamente
            $sql = "
            CREATE TABLE compromissos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(255) NOT NULL,
                data DATE NOT NULL,
                hora TIME NOT NULL,
                local VARCHAR(255),
                responsavel VARCHAR(255),
                observacoes TEXT,
                status VARCHAR(20) NOT NULL DEFAULT 'pendente',
                publico TINYINT(1) NOT NULL DEFAULT 1,
                criado_por INT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($sql);
            log_message("Tabela compromissos recriada");
            
            $mensagens[] = "Tabela de compromissos recriada com sucesso";
        } catch (Exception $e) {
            $erros[] = "Erro ao recriar tabela: " . $e->getMessage();
            log_message("Erro ao recriar tabela: " . $e->getMessage());
        }
    }
    
    // Ação: Importar compromissos de exemplo
    if ($action === 'import_sample') {
        try {
            // Obter IDs do prefeito e vice-prefeito
            $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
            $id_prefeito = $stmt->fetchColumn();
            
            if (!$id_prefeito) {
                // Criar usuário prefeito se não existir
                $senha_hash = password_hash('prefeito123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Prefeito', 'prefeito@exemplo.com', $senha_hash, 'prefeito', 1]);
                $id_prefeito = $pdo->lastInsertId();
                log_message("Usuário prefeito criado com ID: " . $id_prefeito);
            }
            
            $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
            $id_vice = $stmt->fetchColumn();
            
            if (!$id_vice) {
                // Criar usuário vice-prefeito se não existir
                $senha_hash = password_hash('vice123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Vice-Prefeito', 'vice@exemplo.com', $senha_hash, 'vice', 1]);
                $id_vice = $pdo->lastInsertId();
                log_message("Usuário vice-prefeito criado com ID: " . $id_vice);
            }
            
            // Data atual e próximos dias
            $data_hoje = date('Y-m-d');
            $data_amanha = date('Y-m-d', strtotime('+1 day'));
            
            // Compromissos para hoje
            $compromissos = [
                // Compromissos do prefeito para hoje
                [
                    'titulo' => 'REUNIÃO MATINAL',
                    'data' => $data_hoje,
                    'hora' => '08:00:00',
                    'local' => 'Gabinete do Prefeito',
                    'responsavel' => 'Prefeito',
                    'observacoes' => 'Reunião com a equipe',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_prefeito
                ],
                [
                    'titulo' => 'ALMOÇO COM EMPRESÁRIOS',
                    'data' => $data_hoje,
                    'hora' => '12:00:00',
                    'local' => 'Restaurante Central',
                    'responsavel' => 'Prefeito',
                    'observacoes' => 'Discussão sobre novos investimentos',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_prefeito
                ],
                [
                    'titulo' => 'VISITA À OBRA',
                    'data' => $data_hoje,
                    'hora' => '15:00:00',
                    'local' => 'Bairro Novo',
                    'responsavel' => 'Prefeito',
                    'observacoes' => 'Vistoria da nova praça',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_prefeito
                ],
                [
                    'titulo' => 'REUNIÃO PRIVADA',
                    'data' => $data_hoje,
                    'hora' => '17:00:00',
                    'local' => 'Gabinete do Prefeito',
                    'responsavel' => 'Prefeito',
                    'observacoes' => 'Assuntos confidenciais',
                    'status' => 'pendente',
                    'publico' => 0,
                    'criado_por' => $id_prefeito
                ],
                
                // Compromissos do vice-prefeito para hoje
                [
                    'titulo' => 'VISITA À ESCOLA',
                    'data' => $data_hoje,
                    'hora' => '09:00:00',
                    'local' => 'Escola Municipal',
                    'responsavel' => 'Vice-Prefeito',
                    'observacoes' => 'Entrega de material escolar',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_vice
                ],
                [
                    'titulo' => 'REUNIÃO COM SECRETÁRIOS',
                    'data' => $data_hoje,
                    'hora' => '14:00:00',
                    'local' => 'Sala de Reuniões',
                    'responsavel' => 'Vice-Prefeito',
                    'observacoes' => 'Planejamento semanal',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_vice
                ],
                
                // Compromissos para amanhã
                [
                    'titulo' => 'AUDIÊNCIA PÚBLICA',
                    'data' => $data_amanha,
                    'hora' => '10:00:00',
                    'local' => 'Câmara Municipal',
                    'responsavel' => 'Prefeito',
                    'observacoes' => 'Apresentação do orçamento',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_prefeito
                ],
                [
                    'titulo' => 'EVENTO CULTURAL',
                    'data' => $data_amanha,
                    'hora' => '19:00:00',
                    'local' => 'Centro Cultural',
                    'responsavel' => 'Vice-Prefeito',
                    'observacoes' => 'Abertura da exposição',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_vice
                ]
            ];
            
            // Inserir os compromissos
            $stmt = $pdo->prepare("
                INSERT INTO compromissos 
                (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $count = 0;
            foreach ($compromissos as $c) {
                $stmt->execute([
                    $c['titulo'],
                    $c['data'],
                    $c['hora'],
                    $c['local'],
                    $c['responsavel'],
                    $c['observacoes'],
                    $c['status'],
                    $c['publico'],
                    $c['criado_por']
                ]);
                $count++;
                log_message("Compromisso inserido: {$c['titulo']} - {$c['data']} {$c['hora']}");
            }
            
            $mensagens[] = "$count compromissos de exemplo importados com sucesso";
        } catch (Exception $e) {
            $erros[] = "Erro ao importar compromissos: " . $e->getMessage();
            log_message("Erro ao importar compromissos: " . $e->getMessage());
        }
    }
    
    // Ação: Corrigir agenda pública
    if ($action === 'fix_agenda') {
        try {
            // Conteúdo do arquivo agenda_publica.php
            $agenda_content = '<?php
// Definir o fuso horário para America/Sao_Paulo
date_default_timezone_set("America/Sao_Paulo");

require_once "includes/db.php";
require_once "includes/functions.php";

// Definir o fuso horário do MySQL para -03:00 (America/Sao_Paulo)
$pdo->exec("SET time_zone = \'-03:00\'");

// Obter data atual no fuso horário correto
$data_atual = date("Y-m-d");
$nome_dia = getNomeDiaSemana($data_atual);
$data_formatada = formatarData($data_atual);

// Obter configurações do sistema
$config = getConfiguracoes();

// Título da página
$titulo_pagina = "Agenda Pública - " . $config["nome_prefeitura"];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <style>
        .agenda-card {
            border-left: 4px solid var(--bs-primary);
            margin-bottom: 15px;
        }
        .agenda-time {
            font-weight: bold;
            color: var(--bs-primary);
        }
        .agenda-title {
            font-weight: bold;
            text-transform: uppercase;
        }
        .agenda-location {
            color: #666;
        }
        .agenda-date-header {
            background-color: var(--bs-primary);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .no-events {
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="text-center mb-4"><?php echo $config["nome_prefeitura"]; ?></h1>
                <div class="agenda-date-header text-center">
                    <h2 class="mb-0">Agenda Pública</h2>
                    <p class="mb-0"><?php echo $nome_dia . ", " . $data_formatada; ?></p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Agenda do Prefeito</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Buscar compromissos do prefeito para a data atual
                        $stmt = $pdo->prepare("
                            SELECT c.* 
                            FROM compromissos c
                            JOIN usuarios u ON c.criado_por = u.id
                            WHERE u.papel = \'prefeito\'
                            AND c.data = ?
                            AND c.publico = 1
                            ORDER BY c.hora ASC
                        ");
                        $stmt->execute([$data_atual]);
                        $compromissos_prefeito = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($compromissos_prefeito) > 0) {
                            foreach ($compromissos_prefeito as $compromisso) {
                                $hora_formatada = date("H:i", strtotime($compromisso["hora"]));
                                ?>
                                <div class="agenda-card p-3">
                                    <div class="agenda-time"><?php echo $hora_formatada; ?></div>
                                    <div class="agenda-title"><?php echo $compromisso["titulo"]; ?></div>
                                    <div class="agenda-location">
                                        <i class="bi bi-geo-alt"></i> <?php echo $compromisso["local"]; ?>
                                    </div>
                                    <?php if (!empty($compromisso["observacoes"])): ?>
                                        <div class="agenda-description mt-2">
                                            <?php echo $compromisso["observacoes"]; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<div class=\"no-events\">Não há compromissos públicos para hoje.</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Agenda do Vice-Prefeito</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Buscar compromissos do vice-prefeito para a data atual
                        $stmt = $pdo->prepare("
                            SELECT c.* 
                            FROM compromissos c
                            JOIN usuarios u ON c.criado_por = u.id
                            WHERE u.papel = \'vice\'
                            AND c.data = ?
                            AND c.publico = 1
                            ORDER BY c.hora ASC
                        ");
                        $stmt->execute([$data_atual]);
                        $compromissos_vice = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($compromissos_vice) > 0) {
                            foreach ($compromissos_vice as $compromisso) {
                                $hora_formatada = date("H:i", strtotime($compromisso["hora"]));
                                ?>
                                <div class="agenda-card p-3">
                                    <div class="agenda-time"><?php echo $hora_formatada; ?></div>
                                    <div class="agenda-title"><?php echo $compromisso["titulo"]; ?></div>
                                    <div class="agenda-location">
                                        <i class="bi bi-geo-alt"></i> <?php echo $compromisso["local"]; ?>
                                    </div>
                                    <?php if (!empty($compromisso["observacoes"])): ?>
                                        <div class="agenda-description mt-2">
                                            <?php echo $compromisso["observacoes"]; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<div class=\"no-events\">Não há compromissos públicos para hoje.</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12 text-center">
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-house"></i> Página Inicial
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
            
            // Salvar o arquivo
            file_put_contents('agenda_publica.php', $agenda_content);
            log_message("Arquivo agenda_publica.php atualizado");
            
            $mensagens[] = "Arquivo agenda_publica.php atualizado com sucesso";
        } catch (Exception $e) {
            $erros[] = "Erro ao atualizar agenda_publica.php: " . $e->getMessage();
            log_message("Erro ao atualizar agenda_publica.php: " . $e->getMessage());
        }
    }
    
    // Ação: Corrigir includes/functions.php
    if ($action === 'fix_functions') {
        try {
            // Conteúdo do arquivo functions.php
            $functions_content = '<?php
// Definir o fuso horário para America/Sao_Paulo
date_default_timezone_set("America/Sao_Paulo");

// Função para formatar data
function formatarData($data) {
    return date("d/m/Y", strtotime($data));
}

// Função para obter o nome do dia da semana
function getNomeDiaSemana($data) {
    $diasSemana = array(
        "Domingo", "Segunda-feira", "Terça-feira", 
        "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"
    );
    
    $numeroSemana = date("w", strtotime($data));
    return $diasSemana[$numeroSemana];
}

// Função para obter o nome do mês
function getNomeMes($mes) {
    $meses = array(
        1 => "Janeiro", 2 => "Fevereiro", 3 => "Março", 
        4 => "Abril", 5 => "Maio", 6 => "Junho", 
        7 => "Julho", 8 => "Agosto", 9 => "Setembro", 
        10 => "Outubro", 11 => "Novembro", 12 => "Dezembro"
    );
    
    return $meses[(int)$mes];
}

// Função para obter as configurações do sistema
function getConfiguracoes() {
    global $pdo;
    
    // Valores padrão
    $config = array(
        "nome_prefeitura" => "Prefeitura Municipal",
        "nome_prefeito" => "Prefeito Municipal",
        "nome_vice" => "Vice-Prefeito Municipal",
        "cor_primaria" => "#007bff",
        "cor_secundaria" => "#6c757d",
        "logo_url" => ""
    );
    
    try {
        // Verificar se a tabela configuracoes existe
        $stmt = $pdo->query("SHOW TABLES LIKE \'configuracoes\'");
        if ($stmt->rowCount() > 0) {
            // Buscar configurações do banco de dados
            $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
            $db_config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($db_config) {
                // Mesclar com os valores padrão
                $config = array_merge($config, $db_config);
            }
        }
    } catch (PDOException $e) {
        // Em caso de erro, usar os valores padrão
        error_log("Erro ao obter configurações: " . $e->getMessage());
    }
    
    return $config;
}

// Função para obter o usuário pelo ID
function getUserById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar usuário: " . $e->getMessage());
        return false;
    }
}

// Função para obter todos os usuários
function getAllUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar usuários: " . $e->getMessage());
        return [];
    }
}

// Função para obter o prefeito
function getPrefeito() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM usuarios WHERE papel = \'prefeito\' LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar prefeito: " . $e->getMessage());
        return false;
    }
}

// Função para obter o vice-prefeito
function getVicePrefeito() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM usuarios WHERE papel = \'vice\' LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar vice-prefeito: " . $e->getMessage());
        return false;
    }
}

// Função para obter compromissos por data
function getCompromissosByData($data, $usuario_id = null, $apenas_publicos = false) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM compromissos WHERE data = ?";
        $params = [$data];
        
        if ($usuario_id) {
            $sql .= " AND criado_por = ?";
            $params[] = $usuario_id;
        }
        
        if ($apenas_publicos) {
            $sql .= " AND publico = 1";
        }
        
        $sql .= " ORDER BY hora ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar compromissos: " . $e->getMessage());
        return [];
    }
}

// Função para obter compromissos por mês
function getCompromissosByMes($ano, $mes, $usuario_id = null, $apenas_publicos = false) {
    global $pdo;
    
    try {
        $primeiro_dia = sprintf("%04d-%02d-01", $ano, $mes);
        $ultimo_dia = date("Y-m-t", strtotime($primeiro_dia));
        
        $sql = "SELECT * FROM compromissos WHERE data BETWEEN ? AND ?";
        $params = [$primeiro_dia, $ultimo_dia];
        
        if ($usuario_id) {
            $sql .= " AND criado_por = ?";
            $params[] = $usuario_id;
        }
        
        if ($apenas_publicos) {
            $sql .= " AND publico = 1";
        }
        
        $sql .= " ORDER BY data ASC, hora ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar compromissos do mês: " . $e->getMessage());
        return [];
    }
}

// Função para obter compromissos por semana
function getCompromissosBySemana($data_inicio, $data_fim, $usuario_id = null, $apenas_publicos = false) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM compromissos WHERE data BETWEEN ? AND ?";
        $params = [$data_inicio, $data_fim];
        
        if ($usuario_id) {
            $sql .= " AND criado_por = ?";
            $params[] = $usuario_id;
        }
        
        if ($apenas_publicos) {
            $sql .= " AND publico = 1";
        }
        
        $sql .= " ORDER BY data ASC, hora ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar compromissos da semana: " . $e->getMessage());
        return [];
    }
}

// Função para obter compromissos do prefeito
function getCompromissosPrefeito($data, $apenas_publicos = true) {
    global $pdo;
    
    try {
        $sql = "
            SELECT c.* 
            FROM compromissos c
            JOIN usuarios u ON c.criado_por = u.id
            WHERE u.papel = \'prefeito\'
            AND c.data = ?
        ";
        $params = [$data];
        
        if ($apenas_publicos) {
            $sql .= " AND c.publico = 1";
        }
        
        $sql .= " ORDER BY c.hora ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar compromissos do prefeito: " . $e->getMessage());
        return [];
    }
}

// Função para obter compromissos do vice-prefeito
function getCompromissosVice($data, $apenas_publicos = true) {
    global $pdo;
    
    try {
        $sql = "
            SELECT c.* 
            FROM compromissos c
            JOIN usuarios u ON c.criado_por = u.id
            WHERE u.papel = \'vice\'
            AND c.data = ?
        ";
        $params = [$data];
        
        if ($apenas_publicos) {
            $sql .= " AND c.publico = 1";
        }
        
        $sql .= " ORDER BY c.hora ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar compromissos do vice-prefeito: " . $e->getMessage());
        return [];
    }
}

// Função para verificar se uma data tem compromissos
function dataTemCompromissos($data, $usuario_id = null, $apenas_publicos = false) {
    global $pdo;
    
    try {
        $sql = "SELECT COUNT(*) FROM compromissos WHERE data = ?";
        $params = [$data];
        
        if ($usuario_id) {
            $sql .= " AND criado_por = ?";
            $params[] = $usuario_id;
        }
        
        if ($apenas_publicos) {
            $sql .= " AND publico = 1";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao verificar compromissos da data: " . $e->getMessage());
        return false;
    }
}
';
            
            // Salvar o arquivo
            file_put_contents('includes/functions.php', $functions_content);
            log_message("Arquivo includes/functions.php atualizado");
            
            $mensagens[] = "Arquivo includes/functions.php atualizado com sucesso";
        } catch (Exception $e) {
            $erros[] = "Erro ao atualizar includes/functions.php: " . $e->getMessage();
            log_message("Erro ao atualizar includes/functions.php: " . $e->getMessage());
        }
    }
    
    // Ação: Corrigir includes/db.php
    if ($action === 'fix_db') {
        try {
            // Conteúdo do arquivo db.php
            $db_content = '<?php
// Definir o fuso horário para America/Sao_Paulo
date_default_timezone_set("America/Sao_Paulo");

// Configurações do banco de dados
$db_host = "localhost";
$db_name = "u414602466_prefeitoagenda";
$db_user = "seu_usuario";
$db_pass = "sua_senha";

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Configurar o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Definir o fuso horário do MySQL para -03:00 (America/Sao_Paulo)
    $pdo->exec("SET time_zone = \'-03:00\'");
    
} catch (PDOException $e) {
    // Em caso de erro na conexão
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
';
            
            // Salvar o arquivo
            file_put_contents('includes/db.php', $db_content);
            log_message("Arquivo includes/db.php atualizado");
            
            $mensagens[] = "Arquivo includes/db.php atualizado com sucesso";
        } catch (Exception $e) {
            $erros[] = "Erro ao atualizar includes/db.php: " . $e->getMessage();
            log_message("Erro ao atualizar includes/db.php: " . $e->getMessage());
        }
    }
    
    // Ação: Testar consulta de compromissos
    if ($action === 'test_query') {
        try {
            // Data atual
            $data_atual = date('Y-m-d');
            log_message("Data atual: " . $data_atual);
            
            // Consulta direta
            $stmt = $pdo->prepare("
                SELECT c.*, u.papel 
                FROM compromissos c
                JOIN usuarios u ON c.criado_por = u.id
                WHERE c.data = ?
                AND c.publico = 1
                ORDER BY c.hora ASC
            ");
            $stmt->execute([$data_atual]);
            $compromissos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_message("Total de compromissos encontrados: " . count($compromissos));
            
            foreach ($compromissos as $index => $c) {
                log_message("Compromisso #" . ($index + 1) . ": " . $c['titulo'] . " - " . $c['data'] . " " . $c['hora'] . " (" . $c['papel'] . ")");
            }
            
            $mensagens[] = "Teste de consulta realizado com sucesso. " . count($compromissos) . " compromissos encontrados para hoje.";
        } catch (Exception $e) {
            $erros[] = "Erro ao testar consulta: " . $e->getMessage();
            log_message("Erro ao testar consulta: " . $e->getMessage());
        }
    }
}

// Verificar o estado atual do sistema
$diagnostico = [];

// Verificar o fuso horário do PHP
$diagnostico[] = "Fuso horário do PHP: " . date_default_timezone_get();
$diagnostico[] = "Data e hora atual do PHP: " . date('d/m/Y H:i:s');

// Verificar o fuso horário do MySQL
try {
    $stmt = $pdo->query("SELECT @@time_zone AS timezone, NOW() AS current_time");
    $mysql_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $diagnostico[] = "Fuso horário do MySQL: " . $mysql_info['timezone'];
    $diagnostico[] = "Data e hora atual do MySQL: " . $mysql_info['current_time'];
} catch (PDOException $e) {
    $erros[] = "Erro ao verificar o fuso horário do MySQL: " . $e->getMessage();
}

// Verificar a tabela compromissos
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM compromissos");
    $total_compromissos = $stmt->fetchColumn();
    $diagnostico[] = "Total de compromissos: " . $total_compromissos;
    
    // Verificar compromissos para hoje
    $data_atual = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM compromissos WHERE data = ?");
    $stmt->execute([$data_atual]);
    $compromissos_hoje = $stmt->fetchColumn();
    $diagnostico[] = "Compromissos para hoje (" . $data_atual . "): " . $compromissos_hoje;
    
    // Verificar compromissos públicos para hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM compromissos WHERE data = ? AND publico = 1");
    $stmt->execute([$data_atual]);
    $compromissos_publicos_hoje = $stmt->fetchColumn();
    $diagnostico[] = "Compromissos públicos para hoje: " . $compromissos_publicos_hoje;
} catch (PDOException $e) {
    $erros[] = "Erro ao verificar compromissos: " . $e->getMessage();
}

// Verificar usuários
try {
    $stmt = $pdo->query("SELECT papel, COUNT(*) as total FROM usuarios GROUP BY papel");
    $usuarios_por_papel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usuarios_por_papel as $u) {
        $diagnostico[] = "Usuários com papel '" . $u['papel'] . "': " . $u['total'];
    }
} catch (PDOException $e) {
    $erros[] = "Erro ao verificar usuários: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset do Sistema</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .log-container {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .action-card {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="display-5 mb-4 text-center">Reset do Sistema</h1>
                
                <?php if (!empty($mensagens)): ?>
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle-fill me-2"></i>Ações realizadas com sucesso:</h5>
                        <ul class="mb-0">
                            <?php foreach ($mensagens as $mensagem): ?>
                                <li><?php echo $mensagem; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Erros encontrados:</h5>
                        <ul class="mb-0">
                            <?php foreach ($erros as $erro): ?>
                                <li><?php echo $erro; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Ações de Correção</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="action-card">
                                <h6><i class="bi bi-1-circle me-2"></i>Passo 1: Corrigir Fuso Horário</h6>
                                <p class="small text-muted">Ajusta o fuso horário do PHP e MySQL para America/Sao_Paulo (-03:00).</p>
                                <button type="submit" name="action" value="fix_timezone" class="btn btn-primary btn-sm">
                                    Corrigir Fuso Horário
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-2-circle me-2"></i>Passo 2: Corrigir Arquivos do Sistema</h6>
                                <p class="small text-muted">Atualiza os arquivos principais para garantir compatibilidade com o fuso horário.</p>
                                <button type="submit" name="action" value="fix_db" class="btn btn-primary btn-sm me-2 mb-2">
                                    Corrigir DB
                                </button>
                                <button type="submit" name="action" value="fix_functions" class="btn btn-primary btn-sm me-2 mb-2">
                                    Corrigir Functions
                                </button>
                                <button type="submit" name="action" value="fix_agenda" class="btn btn-primary btn-sm mb-2">
                                    Corrigir Agenda
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-3-circle me-2"></i>Passo 3: Recriar Tabela de Compromissos</h6>
                                <p class="small text-muted">Recria a tabela de compromissos com a estrutura correta (faz backup dos dados existentes).</p>
                                <button type="submit" name="action" value="recreate_table" class="btn btn-warning btn-sm" onclick="return confirm('Tem certeza? Esta ação irá recriar a tabela de compromissos.')">
                                    Recriar Tabela de Compromissos
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-4-circle me-2"></i>Passo 4: Importar Compromissos de Teste</h6>
                                <p class="small text-muted">Importa compromissos de exemplo para testar o sistema.</p>
                                <button type="submit" name="action" value="import_sample" class="btn btn-success btn-sm">
                                    Importar Compromissos de Teste
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-5-circle me-2"></i>Passo 5: Testar Consulta</h6>
                                <p class="small text-muted">Testa a consulta de compromissos para verificar se está funcionando corretamente.</p>
                                <button type="submit" name="action" value="test_query" class="btn btn-info btn-sm text-white">
                                    Testar Consulta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-link me-2"></i>Links Úteis</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="agenda_publica.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-calendar-event me-2"></i>Agenda Pública
                            </a>
                            <a href="index.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-house me-2"></i>Página Inicial
                            </a>
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Diagnóstico do Sistema</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group mb-4">
                            <?php foreach ($diagnostico as $item): ?>
                                <li class="list-group-item"><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-terminal me-2"></i>Logs</h5>
                    </div>
                    <div class="card-body">
                        <div class="log-container">
                            <?php foreach ($logs as $log): ?>
                                <div><?php echo $log; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
