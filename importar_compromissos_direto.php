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

// Obter IDs do prefeito e vice-prefeito
$stmt_prefeito = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
$stmt_prefeito->execute();
$id_prefeito = $stmt_prefeito->fetchColumn() ?: 1;

$stmt_vice = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
$stmt_vice->execute();
$id_vice = $stmt_vice->fetchColumn() ?: 2;

$mensagens = [];
$erros = [];
$registros_importados = 0;

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Inserir dados diretamente
        $dados = [
            // ID 2 - Reunião de alinhamento com secretários
            [
                'data' => '2025-04-30',
                'hora' => '08:00:00',
                'titulo' => 'REUNIÃO DE ALINHAMENTO COM TODOS OS SECRETÁRIOS',
                'local' => 'Gabinete',
                'responsavel' => '',
                'observacoes' => "Tipo: Reunião\nPrioridade: Alta\nParticipantes: Todos Secretários",
                'status' => 'realizado',
                'publico' => 0, // Sigiloso = Sim
                'criado_por' => $id_prefeito
            ],
            // ID 4 - Posse do núcleo do bairro São João
            [
                'data' => '2025-04-30',
                'hora' => '14:00:00',
                'titulo' => 'POSSE DO NÚCLEO DO BAIRRO SÃO JOÃO',
                'local' => 'Bairro São João',
                'responsavel' => '',
                'observacoes' => "Tipo: Evento\nPrioridade: Normal",
                'status' => 'realizado',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_vice
            ],
            // ID 5 - Reunião com secretário Cleberson
            [
                'data' => '2025-04-30',
                'hora' => '15:00:00',
                'titulo' => 'REUNIÃO COM O SECRETÁRIO CLEBERSON',
                'local' => 'Gabinete',
                'responsavel' => 'Cleberson Taborda',
                'observacoes' => "Tipo: Reunião\nPrioridade: Média",
                'status' => 'realizado',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_prefeito
            ],
            // ID 6 - Reunião com Rodrigo Flores e André Ruschel
            [
                'data' => '2025-04-30',
                'hora' => '16:00:00',
                'titulo' => 'REUNIÃO COM RODRIGO FLORES E ANDRÉ RUSCHEL',
                'local' => 'Gabinete do Vice-Prefeito',
                'responsavel' => '55997000995',
                'observacoes' => "Tipo: Reunião\nPrioridade: Alta\nParticipantes: Rodrigo Flores, Carlos Gonçalves, Rolando Burgel, André Ruschel",
                'status' => 'pendente',
                'publico' => 0, // Sigiloso = Sim
                'criado_por' => $id_vice
            ],
            // ID 7 - Live com o secretário Cleberson
            [
                'data' => '2025-04-30',
                'hora' => '19:00:00',
                'titulo' => 'Live com o secretário Cleberson',
                'local' => 'Gabinete',
                'responsavel' => 'Cleberson',
                'observacoes' => "Tipo: Evento\nPrioridade: Alta",
                'status' => 'pendente',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_prefeito
            ],
            // ID 8 - Câmara de vereadores sobre os 400 anos das missões
            [
                'data' => '2025-05-02',
                'hora' => '09:00:00',
                'titulo' => 'CÂMARA DE VEREADORES SOBRE OS 400 ANOS DAS MISSÕES',
                'local' => 'R. Antunes Ribas, 1111 - Centro, Santo Ângelo - RS, 98801-630',
                'responsavel' => '',
                'observacoes' => "Tipo: Reunião\nPrioridade: Média\nParticipantes: Vereadores, Sec.Turismo/Vinicius Makvitiz. Sr. Carlos Gonçalves participou.",
                'status' => 'realizado',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_prefeito
            ],
            // ID 9 - Visita na Unimed
            [
                'data' => '2025-05-02',
                'hora' => '16:00:00',
                'titulo' => 'Visita na Unimed logo após janta com Dr Madureira e Tiago da UPA',
                'local' => 'Av. Getúlio Vargas, 1079 - Missões, Santo Ângelo - RS, 98801-703',
                'responsavel' => 'Dr. Madureira e Tiago UPA',
                'observacoes' => "Tipo: Evento\nPrioridade: Normal",
                'status' => 'pendente',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_vice
            ],
            // ID 10 - Live cancelada
            [
                'data' => '2025-05-02',
                'hora' => '19:00:00',
                'titulo' => 'LIVE CANCELADA',
                'local' => 'Gabinete',
                'responsavel' => '',
                'observacoes' => "Tipo: Evento\nPrioridade: Normal",
                'status' => 'cancelado',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_prefeito
            ],
            // ID 11 - Missa na catedral
            [
                'data' => '2025-05-03',
                'hora' => '15:30:00',
                'titulo' => 'MISSA NA CATEDRAL',
                'local' => 'Praça Pinheiro Machado',
                'responsavel' => 'Vinícius Makivitz',
                'observacoes' => "Tipo: Evento\nPrioridade: Média",
                'status' => 'pendente',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_prefeito
            ],
            // ID 12 - Almoço no Lageado Micuim
            [
                'data' => '2025-05-04',
                'hora' => '12:00:00',
                'titulo' => 'ALMOÇO NO LAGEADO MICUIM',
                'local' => 'Lajeado Micuim',
                'responsavel' => '',
                'observacoes' => "Tipo: Evento\nPrioridade: Normal",
                'status' => 'pendente',
                'publico' => 1, // Sigiloso = Não
                'criado_por' => $id_vice
            ]
        ];
        
        // Inserir os dados
        $stmt = $pdo->prepare("
            INSERT INTO compromissos 
            (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($dados as $compromisso) {
            $stmt->execute([
                $compromisso['titulo'],
                $compromisso['data'],
                $compromisso['hora'],
                $compromisso['local'],
                $compromisso['responsavel'],
                $compromisso['observacoes'],
                $compromisso['status'],
                $compromisso['publico'],
                $compromisso['criado_por']
            ]);
            $registros_importados++;
        }
        
        // Commit da transação
        $pdo->commit();
        
        $mensagens[] = "Importação concluída com sucesso! $registros_importados registros importados.";
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
                        <?php if (!empty($mensagens)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo implode('<br>', $mensagens); ?>
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
                            <p>Este script importará 10 compromissos de exemplo do arquivo SQL para o sistema.</p>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Importante:</strong> Este processo adicionará novos compromissos ao sistema. Certifique-se de que deseja prosseguir.
                            </div>
                            
                            <form method="post" action="">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>
                                    Importar Compromissos
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
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
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
