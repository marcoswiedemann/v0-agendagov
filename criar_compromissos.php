<?php
// Iniciar a sessão
session_start();

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Verificar se existe o arquivo de configuração
if (file_exists('includes/db_config.php')) {
    require_once 'includes/db_config.php';
} elseif (isset($_SESSION['db_config'])) {
    // Usar configurações da sessão
    $db_host = $_SESSION['db_config']['host'];
    $db_name = $_SESSION['db_config']['name'];
    $db_user = $_SESSION['db_config']['user'];
    $db_pass = $_SESSION['db_config']['pass'];
    
    try {
        // Conectar ao banco de dados
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Definir o fuso horário do MySQL
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        die("Erro na conexão com o banco de dados: " . $e->getMessage());
    }
} else {
    // Redirecionar para a página de configuração
    header('Location: configurar_db.php');
    exit;
}

// Mensagens
$mensagem = '';
$erro = '';
$sucesso = false;

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar se a tabela usuários existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() == 0) {
            // Criar tabela usuários
            $sql = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                papel ENUM('admin', 'prefeito', 'vice', 'secretario', 'assessor') NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                ultimo_login DATETIME,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($sql);
        }
        
        // Verificar se a tabela compromissos existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'compromissos'");
        if ($stmt->rowCount() == 0) {
            // Criar tabela compromissos
            $sql = "
            CREATE TABLE IF NOT EXISTS compromissos (
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
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($sql);
        }
        
        // Verificar se já existe um prefeito
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
        $id_prefeito = $stmt->fetchColumn();
        
        if (!$id_prefeito) {
            // Criar usuário prefeito
            $senha_hash = password_hash('prefeito123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Prefeito', 'prefeito@exemplo.com', $senha_hash, 'prefeito', 1]);
            $id_prefeito = $pdo->lastInsertId();
        }
        
        // Verificar se já existe um vice-prefeito
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
        $id_vice = $stmt->fetchColumn();
        
        if (!$id_vice) {
            // Criar usuário vice-prefeito
            $senha_hash = password_hash('vice123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Vice-Prefeito', 'vice@exemplo.com', $senha_hash, 'vice', 1]);
            $id_vice = $pdo->lastInsertId();
        }
        
        // Data atual e próximos dias
        $data_hoje = date('Y-m-d');
        $data_amanha = date('Y-m-d', strtotime('+1 day'));
        
        // Compromissos para hoje e amanhã
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
        }
        
        $sucesso = true;
        $mensagem = "$count compromissos criados com sucesso!";
    } catch (Exception $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Compromissos de Teste</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Criar Compromissos de Teste</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($sucesso): ?>
                            <div class="alert alert-success">
                                <?php echo $mensagem; ?>
                            </div>
                            <div class="mb-4">
                                <p>O que você deseja fazer agora?</p>
                                <a href="agenda_config.php" class="btn btn-primary">Ver Agenda Pública</a>
                                <a href="index.php" class="btn btn-secondary ms-2">Ir para Página Inicial</a>
                            </div>
                        <?php elseif ($erro): ?>
                            <div class="alert alert-danger">
                                <?php echo $erro; ?>
                            </div>
                            <div class="mb-4">
                                <a href="configurar_db.php" class="btn btn-primary">Configurar Banco de Dados</a>
                                <a href="index.php" class="btn btn-secondary ms-2">Ir para Página Inicial</a>
                            </div>
                        <?php else: ?>
                            <p>Este script irá criar:</p>
                            <ul>
                                <li>Tabelas necessárias (se não existirem)</li>
                                <li>Usuários prefeito e vice-prefeito (se não existirem)</li>
                                <li>7 compromissos de exemplo (3 para o prefeito hoje, 2 para o vice-prefeito hoje, e 2 para amanhã)</li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <strong>Nota:</strong> Todos os compromissos serão marcados como públicos para que apareçam na agenda pública.
                            </div>
                            
                            <form method="post" action="">
                                <button type="submit" class="btn btn-success">Criar Compromissos</button>
                                <a href="agenda_config.php" class="btn btn-secondary ms-2">Cancelar</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
