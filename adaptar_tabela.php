<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || getCurrentUser()['papel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obter configurações do sistema
$config = getConfiguracoes();

// Verificar a estrutura atual da tabela
$stmt = $pdo->query("DESCRIBE compromissos");
$colunas_atuais = $stmt->fetchAll(PDO::FETCH_COLUMN);

$alteracoes = [];
$mensagens = [];

// Verificar e adicionar colunas necessárias
if (!in_array('publico', $colunas_atuais)) {
    try {
        $pdo->exec("ALTER TABLE compromissos ADD COLUMN publico TINYINT(1) NOT NULL DEFAULT 1");
        $alteracoes[] = "Adicionada coluna 'publico'";
    } catch (Exception $e) {
        $mensagens[] = "Erro ao adicionar coluna 'publico': " . $e->getMessage();
    }
}

if (!in_array('criado_por', $colunas_atuais)) {
    try {
        $pdo->exec("ALTER TABLE compromissos ADD COLUMN criado_por INT");
        $alteracoes[] = "Adicionada coluna 'criado_por'";
    } catch (Exception $e) {
        $mensagens[] = "Erro ao adicionar coluna 'criado_por': " . $e->getMessage();
    }
}

if (!in_array('criado_em', $colunas_atuais)) {
    try {
        $pdo->exec("ALTER TABLE compromissos ADD COLUMN criado_em DATETIME DEFAULT CURRENT_TIMESTAMP");
        $alteracoes[] = "Adicionada coluna 'criado_em'";
    } catch (Exception $e) {
        $mensagens[] = "Erro ao adicionar coluna 'criado_em': " . $e->getMessage();
    }
}

// Verificar se há alterações pendentes
if (empty($alteracoes) && empty($mensagens)) {
    $mensagens[] = "A estrutura da tabela já está adequada. Nenhuma alteração necessária.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_aplicacao']); ?> - Adaptação da Tabela</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Adaptação da Tabela de Compromissos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($alteracoes)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Alterações realizadas com sucesso:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($alteracoes as $alteracao): ?>
                                        <li><?php echo $alteracao; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($mensagens)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <?php echo implode('<br>', $mensagens); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="importar_compromissos.php" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>
                                Prosseguir para Importação
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary ms-2">
                                <i class="bi bi-house me-2"></i>
                                Voltar ao Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
