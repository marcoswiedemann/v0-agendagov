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

// Função para mapear o status
function mapearStatus($status) {
    switch ($status) {
        case 'Pendente': return 'pendente';
        case 'Realizado': return 'realizado';
        case 'Não Realizado': return 'cancelado';
        default: return 'pendente';
    }
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Inserir todos os compromissos do arquivo SQL
        $compromissos = [
            // ID 2
            [
                'data' => '2025-04-30', 'hora' => '08:00:00',
                'titulo' => 'REUNIÃO DE ALINHAMENTO COM TODOS OS SECRETÁRIOS',
                'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim',
                'pessoas' => 'Todos Scretários', 'contato' => '', 'local' => 'Gabinete',
                'status' => 'Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 4
            [
                'data' => '2025-04-30', 'hora' => '14:00:00',
                'titulo' => 'POSSE DO NÚCLEO DO BAIRRO SÃO JOÃO',
                'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => '', 'contato' => '', 'local' => 'Bairro São João',
                'status' => 'Realizado', 'criado_por' => $id_vice
            ],
            // ID 5
            [
                'data' => '2025-04-30', 'hora' => '15:00:00',
                'titulo' => 'REUNIÃO COM O SECRETÁRIO CLEBERSON',
                'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não',
                'pessoas' => 'Cleberson Taborda', 'contato' => '', 'local' => 'Gabinete',
                'status' => 'Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 6
            [
                'data' => '2025-04-30', 'hora' => '16:00:00',
                'titulo' => 'REUNIÃO COM RODRIGO FLORES E ANDRÉ RUSCHEL',
                'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim',
                'pessoas' => 'Rodrigo Flores, Carlos Gonçalves, Rolando Burgel, André Ruschel', 
                'contato' => '55997000995', 'local' => 'Gabinete do Vice-Prefeito',
                'status' => 'Pendente', 'criado_por' => $id_vice
            ],
            // ID 7
            [
                'data' => '2025-04-30', 'hora' => '19:00:00',
                'titulo' => 'Live com o secretário Cleberson',
                'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Não',
                'pessoas' => 'Cleberson', 'contato' => '', 'local' => 'Gabinete',
                'status' => 'Pendente', 'criado_por' => $id_prefeito
            ],
            // ID 8
            [
                'data' => '2025-05-02', 'hora' => '09:00:00',
                'titulo' => 'CÂMARA DE VEREADORES SOBRE OS 400 ANOS DAS MISSÕES',
                'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não',
                'pessoas' => 'Vereadores, Sec.Turismo/Vinicius Makvitiz. Sr. Carlos Gonçalves participou.', 
                'contato' => '', 'local' => 'R. Antunes Ribas, 1111 - Centro, Santo Ângelo - RS, 98801-630',
                'status' => 'Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 9
            [
                'data' => '2025-05-02', 'hora' => '16:00:00',
                'titulo' => 'Visita na Unimed logo após janta com Dr Madureira e Tiago da UPA',
                'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => 'Dr. Madureira e Tiago UPA', 'contato' => '', 
                'local' => 'Av. Getúlio Vargas, 1079 - Missões, Santo Ângelo - RS, 98801-703',
                'status' => 'Pendente', 'criado_por' => $id_vice
            ],
            // ID 10
            [
                'data' => '2025-05-02', 'hora' => '19:00:00',
                'titulo' => 'LIVE CANCELADA',
                'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => '', 'contato' => '', 'local' => 'Gabinete',
                'status' => 'Não Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 11
            [
                'data' => '2025-05-03', 'hora' => '15:30:00',
                'titulo' => 'MISSA NA CATEDRAL',
                'tipo' => 'Evento', 'prioridade' => 'Média', 'sigiloso' => 'Não',
                'pessoas' => 'Vinícius Makivitz', 'contato' => '', 'local' => 'Praça Pinheiro Machado',
                'status' => 'Pendente', 'criado_por' => $id_prefeito
            ],
            // ID 12
            [
                'data' => '2025-05-04', 'hora' => '12:00:00',
                'titulo' => 'ALMOÇO NO LAGEADO MICUIM',
                'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => '', 'contato' => '', 'local' => 'Lajeado Micuim',
                'status' => 'Pendente', 'criado_por' => $id_vice
            ],
            // ID 15
            [
                'data' => '2025-05-05', 'hora' => '08:00:00',
                'titulo' => 'REUNIÃO C/LEANDRA-RH E MARISA/SINDICATO FUNCIONÁRIOS MUNICIPAIS, REF. IPE',
                'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => 'Srª. Leandra/RH; Srª. Marisa/Sindicato; Sr. Nívio/Prefeito', 
                'contato' => '', 'local' => 'no GAB',
                'status' => 'Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 16
            [
                'data' => '2025-05-06', 'hora' => '08:30:00',
                'titulo' => 'REUNIÃO C/PRODUTOR RURAL, FRANCISCO E CULTURA.',
                'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => 'Sr. Nívio; Sr. Carlos Gonçalves; Cleberson; Edimilson e Assessor; Vinicius e Cris.', 
                'contato' => '', 'local' => 'no GAB',
                'status' => 'Realizado', 'criado_por' => $id_vice
            ],
            // ID 17
            [
                'data' => '2025-05-05', 'hora' => '10:00:00',
                'titulo' => 'REUNIÃO DE ALINHAMENTO C/VEREADORES',
                'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => 'Prefeito; Vice-Prefeito; Chefe de Gabinete e Vereadores', 
                'contato' => '', 'local' => 'no GAB',
                'status' => 'Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 18
            [
                'data' => '2025-05-05', 'hora' => '14:00:00',
                'titulo' => 'REUNIAO AMM',
                'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim',
                'pessoas' => '', 'contato' => '', 'local' => 'Em Cerro Largo',
                'status' => 'Não Realizado', 'criado_por' => $id_vice
            ],
            // ID 19
            [
                'data' => '2025-05-06', 'hora' => '14:00:00',
                'titulo' => 'REUNIÃO C/ADV. NICO MARCHIONATTI',
                'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim',
                'pessoas' => 'Sr. Nívio; Sr. Nico Marchionatti; Srª Rosimere/Sec. Assistência Social', 
                'contato' => '', 'local' => 'no GAB',
                'status' => 'Realizado', 'criado_por' => $id_prefeito
            ],
            // ID 20
            [
                'data' => '2025-05-06', 'hora' => '15:00:00',
                'titulo' => 'REUNIÃO C/VINICIUS MAKVITIZ E PESSOAL SISTEMA DE VIGILÂNCIA SANITÁRIA E DEPTO DE TRÂNSITO',
                'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não',
                'pessoas' => 'Sec.Turismo/Sr. Vinicius Makvitz; Coord. Trânsito/Sr. Nelson Koch;', 
                'contato' => '', 'local' => 'no GAB',
                'status' => 'Realizado', 'criado_por' => $id_vice
            ]
        ];
        
        // Inserir os dados
        $stmt = $pdo->prepare("
            INSERT INTO compromissos 
            (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($compromissos as $c) {
            // Preparar observações
            $obs = '';
            if (!empty($c['pessoas'])) {
                $obs .= "Participantes: " . $c['pessoas'] . "\n";
            }
            if (!empty($c['contato'])) {
                $obs .= "Contato: " . $c['contato'] . "\n";
            }
            $obs .= "Tipo: " . $c['tipo'] . "\n";
            $obs .= "Prioridade: " . $c['prioridade'];
            
            // Mapear status
            $status = mapearStatus($c['status']);
            
            // Determinar se é público
            $publico = ($c['sigiloso'] == 'Sim') ? 0 : 1;
            
            $stmt->execute([
                $c['titulo'],
                $c['data'],
                $c['hora'],
                $c['local'],
                $c['contato'],
                $obs,
                $status,
                $publico,
                $c['criado_por']
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
                            <p>Este script importará 15 compromissos de exemplo para o sistema.</p>
                            
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
