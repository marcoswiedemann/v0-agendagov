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

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Definir fuso horário do MySQL
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    $erros[] = "Erro na conexão com o banco de dados: " . $e->getMessage();
}

// Obter IDs do prefeito e vice-prefeito
try {
    $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
    $id_prefeito = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
    $id_vice = $stmt->fetchColumn();
    
    if (!$id_prefeito || !$id_vice) {
        $erros[] = "Prefeito ou Vice-Prefeito não encontrados. Por favor, crie os usuários primeiro.";
    }
} catch (PDOException $e) {
    $erros[] = "Erro ao obter IDs do prefeito e vice-prefeito: " . $e->getMessage();
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erros)) {
    try {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Compromissos para importar
        $compromissos = [
            // Compromissos do prefeito
            [
                'titulo' => 'REUNIÃO COM SECRETÁRIOS',
                'data' => '2025-05-20',
                'hora' => '09:00:00',
                'local' => 'Gabinete do Prefeito',
                'responsavel' => 'Secretário de Administração',
                'observacoes' => 'Reunião para discutir o orçamento anual',
                'status' => 'pendente',
                'publico' => 1,
                'criado_por' => $id_prefeito
            ],
            [
                'titulo' => 'VISITA À ESCOLA MUNICIPAL',
                'data' => '2025-05-20',
                'hora' => '14:00:00',
                'local' => 'Escola Municipal Central',
                'responsavel' => 'Secretário de Educação',
                'observacoes' => 'Visita para inauguração da nova biblioteca',
                'status' => 'pendente',
                'publico' => 1,
                'criado_por' => $id_prefeito
            ],
            [
                'titulo' => 'AUDIÊNCIA PÚBLICA',
                'data' => '2025-05-21',
                'hora' => '10:00:00',
                'local' => 'Câmara Municipal',
                'responsavel' => 'Prefeito',
                'observacoes' => 'Apresentação do plano de obras para 2025',
                'status' => 'pendente',
                'publico' => 1,
                'criado_por' => $id_prefeito
            ],
            [
                'titulo' => 'REUNIÃO RESERVADA',
                'data' => '2025-05-21',
                'hora' => '15:00:00',
                'local' => 'Gabinete do Prefeito',
                'responsavel' => 'Prefeito',
                'observacoes' => 'Reunião com assessoria jurídica',
                'status' => 'pendente',
                'publico' => 0,
                'criado_por' => $id_prefeito
            ],
            
            // Compromissos do vice-prefeito
            [
                'titulo' => 'REUNIÃO COM EMPRESÁRIOS',
                'data' => '2025-05-20',
                'hora' => '10:00:00',
                'local' => 'Câmara de Comércio',
                'responsavel' => 'Secretário de Desenvolvimento',
                'observacoes' => 'Discussão sobre incentivos fiscais',
                'status' => 'pendente',
                'publico' => 0,
                'criado_por' => $id_vice
            ],
            [
                'titulo' => 'EVENTO BENEFICENTE',
                'data' => '2025-05-20',
                'hora' => '19:00:00',
                'local' => 'Centro de Convenções',
                'responsavel' => 'Primeira-dama',
                'observacoes' => 'Arrecadação de fundos para hospital infantil',
                'status' => 'pendente',
                'publico' => 1,
                'criado_por' => $id_vice
            ],
            [
                'titulo' => 'VISITA AO HOSPITAL',
                'data' => '2025-05-21',
                'hora' => '09:00:00',
                'local' => 'Hospital Municipal',
                'responsavel' => 'Vice-Prefeito',
                'observacoes' => 'Visita às novas instalações da ala pediátrica',
                'status' => 'pendente',
                'publico' => 1,
                'criado_por' => $id_vice
            ],
            [
                'titulo' => 'REUNIÃO COM SECRETÁRIOS',
                'data' => '2025-05-21',
                'hora' => '14:00:00',
                'local' => 'Gabinete do Vice-Prefeito',
                'responsavel' => 'Vice-Prefeito',
                'observacoes' => 'Alinhamento de projetos',
                'status' => 'pendente',
                'publico' => 0,
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
        
        // Commit da transação
        $pdo->commit();
        
        $mensagens[] = "$count compromissos importados com sucesso.";
    } catch (Exception $e) {
        // Rollback em caso de erro
        $pdo->rollBack();
        $erros[] = "Erro na importação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importação Simplificada de Compromissos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Importação Simplificada de Compromissos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagens)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo implode('<br>', $mensagens); ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="agenda_publica.php" class="btn btn-primary">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Ver Agenda Pública
                                </a>
                                <a href="index.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Início
                                </a>
                            </div>
                        <?php elseif (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo implode('<br>', $erros); ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="corrigir_sistema.php" class="btn btn-warning">
                                    <i class="bi bi-tools me-2"></i>
                                    Corrigir Sistema
                                </a>
                                <a href="index.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Início
                                </a>
                            </div>
                        <?php else: ?>
                            <p>Este script importará 8 compromissos de exemplo para o sistema:</p>
                            
                            <ul class="mb-4">
                                <li>4 compromissos para o prefeito</li>
                                <li>4 compromissos para o vice-prefeito</li>
                                <li>Compromissos públicos e privados</li>
                                <li>Datas para 20 e 21 de maio de 2025</li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Importante:</strong> Este processo adicionará novos compromissos ao sistema. Certifique-se de que deseja prosseguir.
                            </div>
                            
                            <form method="post" action="">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>
                                    Importar Compromissos
                                </button>
                                <a href="index.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancelar
                                </a>
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
