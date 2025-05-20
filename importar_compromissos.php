<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || getCurrentUser()['papel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obter IDs do prefeito e vice-prefeito
$stmt_prefeito = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
$stmt_prefeito->execute();
$id_prefeito = $stmt_prefeito->fetchColumn() ?: 1;

$stmt_vice = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
$stmt_vice->execute();
$id_vice = $stmt_vice->fetchColumn() ?: 2;

// Função para mapear o status
function mapearStatus($status) {
    switch ($status) {
        case 'Pendente': return 'pendente';
        case 'Realizado': return 'realizado';
        case 'Não Realizado': return 'cancelado';
        default: return 'pendente';
    }
}

// Importar dados do arquivo SQL
$sql_file = file_get_contents('compromissos_dump.sql');
$matches = [];
preg_match_all("/INSERT INTO `compromissos` $$[^)]+$$ VALUES\s*($$[^;]+$$)/", $sql_file, $matches);

$registros_importados = 0;
$erros = [];

if (!empty($matches[1])) {
    // Iniciar transação
    $pdo->beginTransaction();
    
    try {
        foreach ($matches[1] as $values_block) {
            // Extrair os valores individuais
            preg_match_all("/$$([^)]+)$$/", $values_block, $value_sets);
            
            foreach ($value_sets[1] as $value_set) {
                $values = explode(',', $value_set);
                
                // Limpar e formatar os valores
                $id = trim($values[0]);
                $data = trim($values[1], "'");
                $hora = trim($values[2], "'");
                $titulo = trim($values[3], "'");
                $tipo = trim($values[4], "'");
                $prioridade = trim($values[5], "'");
                $sigiloso = trim($values[6], "'");
                $pessoas = trim($values[7], "'");
                $contato_responsavel = trim($values[8], "'");
                $localizacao = trim($values[9], "'");
                $status = trim($values[10], "'");
                $criado_em = trim($values[11], "'");
                $criado_por_id = trim($values[12]);
                $atualizado_em = trim($values[13], "'");
                $atualizado_por_id = trim($values[14]);
                
                // Pular registros com data inválida
                if ($data == '0000-00-00') {
                    continue;
                }
                
                // Determinar se é público ou não
                $publico = ($sigiloso == 'Sim') ? 0 : 1;
                
                // Determinar o criador (prefeito ou vice)
                // Vamos usar uma lógica simples: se o ID for par, é do vice, se for ímpar, é do prefeito
                $criado_por = ($id % 2 == 0) ? $id_vice : $id_prefeito;
                
                // Combinar pessoas e contato_responsavel para observações
                $observacoes = '';
                if (!empty($pessoas) && $pessoas != 'NULL') {
                    $observacoes .= "Participantes: " . str_replace("'", "", $pessoas) . "\n";
                }
                if (!empty($contato_responsavel) && $contato_responsavel != 'NULL') {
                    $observacoes .= "Contato: " . str_replace("'", "", $contato_responsavel) . "\n";
                }
                $observacoes .= "Tipo: " . str_replace("'", "", $tipo) . "\n";
                $observacoes .= "Prioridade: " . str_replace("'", "", $prioridade);
                
                // Inserir na tabela compromissos
                $stmt = $pdo->prepare("
                    INSERT INTO compromissos 
                    (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por, criado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    str_replace("'", "", $titulo),
                    $data,
                    $hora,
                    str_replace("'", "", $localizacao),
                    str_replace("'", "", $contato_responsavel),
                    $observacoes,
                    mapearStatus($status),
                    $publico,
                    $criado_por,
                    $criado_em != 'NULL' ? $criado_em : date('Y-m-d H:i:s')
                ]);
                
                $registros_importados++;
            }
        }
        
        // Commit da transação
        $pdo->commit();
        
        $mensagem = "Importação concluída com sucesso! $registros_importados registros importados.";
    } catch (Exception $e) {
        // Rollback em caso de erro
        $pdo->rollBack();
        $erros[] = "Erro na importação: " . $e->getMessage();
    }
} else {
    $erros[] = "Nenhum dado encontrado para importação.";
}

// Obter configurações do sistema
$config = getConfiguracoes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_aplicacao']); ?> - Importação de Compromissos</title>
    
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
                        <h5 class="mb-0">Importação de Compromissos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $mensagem; ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="compromissos.php" class="btn btn-primary">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Ver Compromissos
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        <?php elseif (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo implode('<br>', $erros); ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Processando importação...
                            </div>
                        <?php endif; ?>
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
