<?php
// Iniciar a sessão
session_start();

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Data atual
$data_atual = date('Y-m-d');
$data_formatada = date('d/m/Y');

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

// Variáveis para armazenar os compromissos
$compromissos_prefeito = [];
$compromissos_vice = [];
$erro_consulta = '';

// Buscar compromissos
try {
    // Buscar compromissos do prefeito
    $stmt = $pdo->prepare("
        SELECT c.* 
        FROM compromissos c
        JOIN usuarios u ON c.criado_por = u.id
        WHERE u.papel = 'prefeito'
        AND c.data = ?
        AND c.publico = 1
        ORDER BY c.hora ASC
    ");
    $stmt->execute([$data_atual]);
    $compromissos_prefeito = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar compromissos do vice-prefeito
    $stmt = $pdo->prepare("
        SELECT c.* 
        FROM compromissos c
        JOIN usuarios u ON c.criado_por = u.id
        WHERE u.papel = 'vice'
        AND c.data = ?
        AND c.publico = 1
        ORDER BY c.hora ASC
    ");
    $stmt->execute([$data_atual]);
    $compromissos_vice = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro_consulta = "Erro ao buscar compromissos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Pública</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .agenda-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
            padding: 10px;
        }
        .agenda-time {
            font-weight: bold;
            color: #0d6efd;
        }
        .agenda-title {
            font-weight: bold;
            text-transform: uppercase;
        }
        .agenda-location {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h1 class="mb-3">Agenda Pública</h1>
                <h4 class="text-muted"><?php echo $data_formatada; ?></h4>
            </div>
        </div>
        
        <?php if ($erro_consulta): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger">
                        <?php echo $erro_consulta; ?>
                    </div>
                    <div class="text-center mb-4">
                        <a href="configurar_db.php" class="btn btn-primary">Configurar Banco de Dados</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0">Agenda do Prefeito</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($compromissos_prefeito) > 0): ?>
                                <?php foreach ($compromissos_prefeito as $compromisso): ?>
                                    <div class="agenda-card">
                                        <div class="agenda-time"><?php echo date("H:i", strtotime($compromisso["hora"])); ?></div>
                                        <div class="agenda-title"><?php echo $compromisso["titulo"]; ?></div>
                                        <div class="agenda-location"><?php echo $compromisso["local"]; ?></div>
                                        <?php if (!empty($compromisso["observacoes"])): ?>
                                            <div class="mt-2"><?php echo $compromisso["observacoes"]; ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Não há compromissos públicos para hoje.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0">Agenda do Vice-Prefeito</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($compromissos_vice) > 0): ?>
                                <?php foreach ($compromissos_vice as $compromisso): ?>
                                    <div class="agenda-card">
                                        <div class="agenda-time"><?php echo date("H:i", strtotime($compromisso["hora"])); ?></div>
                                        <div class="agenda-title"><?php echo $compromisso["titulo"]; ?></div>
                                        <div class="agenda-location"><?php echo $compromisso["local"]; ?></div>
                                        <?php if (!empty($compromisso["observacoes"])): ?>
                                            <div class="mt-2"><?php echo $compromisso["observacoes"]; ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Não há compromissos públicos para hoje.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-12 text-center">
                <a href="index.php" class="btn btn-primary">Voltar para a Página Inicial</a>
                
                <?php if (!$erro_consulta): ?>
                    <a href="criar_compromissos.php" class="btn btn-success ms-2">Criar Compromissos de Teste</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
