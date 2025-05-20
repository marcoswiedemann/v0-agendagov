<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Obter configurações do sistema
$config = getConfiguracoes();

// Obter data atual
$data_atual = date('Y-m-d');
$nome_dia = getNomeDiaSemana($data_atual);
$data_formatada = formatarData($data_atual);

// Obter ID do prefeito
$stmt_prefeito = $pdo->prepare("SELECT id, nome FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
$stmt_prefeito->execute();
$prefeito = $stmt_prefeito->fetch();
$id_prefeito = $prefeito ? $prefeito['id'] : 0;
$nome_prefeito = $prefeito ? $prefeito['nome'] : 'Prefeito';

// Verificar permissões
$pode_ver_todos = false;
$usuario_atual = null;

if (isLoggedIn()) {
    $usuario_atual = getCurrentUser();
    
    // Administrador pode ver todos
    if ($usuario_atual['papel'] === 'admin') {
        $pode_ver_todos = true;
    }
    
    // Prefeito pode ver os seus
    if ($usuario_atual['papel'] === 'prefeito' && $usuario_atual['id'] === $id_prefeito) {
        $pode_ver_todos = true;
    }
}

// Obter compromissos do prefeito para hoje
$sql = "SELECT id, titulo, data, hora, local, responsavel, observacoes, status 
        FROM compromissos 
        WHERE data = ? AND criado_por = ? ";

// Se não pode ver todos, mostrar apenas os públicos
if (!$pode_ver_todos) {
    $sql .= " AND publico = 1 ";
}

$sql .= "ORDER BY hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$data_atual, $id_prefeito]);
$compromissos_dia = $stmt->fetchAll();

$pageTitle = "Agenda do Prefeito - " . $data_formatada;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_aplicacao']); ?> - <?php echo $pageTitle; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo $config['cor_primaria'] ?? '#2563eb'; ?>;
            --primary-hover: <?php echo adjustBrightness($config['cor_primaria'] ?? '#2563eb', -15); ?>;
            --primary-light: <?php echo adjustBrightness($config['cor_primaria'] ?? '#2563eb', 90); ?>;
            --secondary-color: <?php echo $config['cor_secundaria'] ?? '#475569'; ?>;
            --secondary-hover: <?php echo adjustBrightness($config['cor_secundaria'] ?? '#475569', -15); ?>;
        }
        
        .header-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .event-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .event-time {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .event-location {
            color: #6c757d;
        }
        
        .event-status-pendente {
            background-color: #ffc107;
            color: #212529;
        }
        
        .event-status-realizado {
            background-color: #198754;
            color: white;
        }
        
        .footer-custom {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 0;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .empty-state-description {
            color: var(--gray-500);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--gray-600);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 0;
            margin-right: 0.5rem;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background-color: transparent;
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid var(--gray-300);
        }
        
        .private-badge {
            background-color: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--primary-color);">
        <div class="container">
            <?php if (!empty($config['logo_claro'])): ?>
                <a class="navbar-brand" href="agenda_publica.php">
                    <img src="<?php echo $config['logo_claro']; ?>" alt="Logo" height="40">
                </a>
            <?php else: ?>
                <a class="navbar-brand" href="agenda_publica.php">
                    <?php echo htmlspecialchars($config['nome_aplicacao']); ?>
                </a>
            <?php endif; ?>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-box-arrow-in-right"></i> Acessar Sistema
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header Banner -->
    <div class="header-banner">
        <div class="container text-center">
            <h1><i class="bi bi-person-badge"></i> Agenda do Prefeito</h1>
            <p class="lead">
                Compromissos de <?php echo htmlspecialchars($nome_prefeito); ?> para <?php echo $data_formatada; ?> (<?php echo $nome_dia; ?>)
                <?php if ($pode_ver_todos): ?>
                    <span class="badge bg-light text-dark">Visualização completa</span>
                <?php else: ?>
                    <span class="badge bg-light text-dark">Visualização pública</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Compromissos do Dia -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i> 
                            Compromissos de Hoje
                        </h5>
                        <span class="badge bg-primary rounded-pill">
                            <?php echo count($compromissos_dia); ?> compromisso(s)
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($compromissos_dia)): ?>
                            <div class="empty-state animate-fade-in">
                                <i class="bi bi-calendar-x empty-state-icon"></i>
                                <h4 class="empty-state-title">Nenhum compromisso agendado para hoje</h4>
                                <p class="empty-state-description">
                                    O prefeito não possui compromissos <?php echo $pode_ver_todos ? '' : 'públicos'; ?> agendados para esta data.
                                    Consulte novamente em outro momento.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($compromissos_dia as $index => $compromisso): ?>
                                    <div class="col-md-6 mb-3 animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                        <div class="card event-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h5 class="card-title mb-0">
                                                        <?php echo htmlspecialchars($compromisso['titulo']); ?>
                                                        <?php if ($pode_ver_todos && $compromisso['publico'] != 1): ?>
                                                            <span class="private-badge">Privado</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <span class="badge event-status-<?php echo $compromisso['status']; ?>">
                                                        <?php echo getNomeStatus($compromisso['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="card-text">
                                                    <p class="mb-2">
                                                        <i class="bi bi-clock me-2"></i>
                                                        <span class="event-time"><?php echo formatarHora($compromisso['hora']); ?></span>
                                                    </p>
                                                    
                                                    <?php if (!empty($compromisso['local'])): ?>
                                                        <p class="mb-2">
                                                            <i class="bi bi-geo-alt me-2"></i>
                                                            <span class="event-location"><?php echo htmlspecialchars($compromisso['local']); ?></span>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($compromisso['responsavel'])): ?>
                                                        <p class="mb-2">
                                                            <i class="bi bi-person me-2"></i>
                                                            <span><?php echo htmlspecialchars($compromisso['responsavel']); ?></span>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($compromisso['observacoes'])): ?>
                                                        <div class="mt-3">
                                                            <h6 class="mb-2">Observações:</h6>
                                                            <p class="small text-muted">
                                                                <?php echo nl2br(htmlspecialchars(limitarTexto($compromisso['observacoes'], 150))); ?>
                                                                <?php if (strlen($compromisso['observacoes']) > 150): ?>
                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalDetalhes<?php echo $compromisso['id']; ?>">
                                                                        ...ver mais
                                                                    </a>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalDetalhes<?php echo $compromisso['id']; ?>">
                                                    <i class="bi bi-info-circle"></i> Ver Detalhes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal de Detalhes -->
                                    <div class="modal fade" id="modalDetalhes<?php echo $compromisso['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <?php echo htmlspecialchars($compromisso['titulo']); ?>
                                                        <?php if ($pode_ver_todos && $compromisso['publico'] != 1): ?>
                                                            <span class="private-badge">Privado</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6><i class="bi bi-calendar-event me-2"></i> Data e Hora</h6>
                                                            <p>
                                                                <?php echo formatarData($compromisso['data']); ?> às 
                                                                <?php echo formatarHora($compromisso['hora']); ?>
                                                            </p>
                                                            
                                                            <h6><i class="bi bi-person me-2"></i> Responsável</h6>
                                                            <p><?php echo htmlspecialchars($compromisso['responsavel'] ?? 'Não informado'); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6><i class="bi bi-geo-alt me-2"></i> Local</h6>
                                                            <p><?php echo htmlspecialchars($compromisso['local'] ?? 'Não informado'); ?></p>
                                                            
                                                            <h6><i class="bi bi-check-circle me-2"></i> Status</h6>
                                                            <p>
                                                                <span class="badge event-status-<?php echo $compromisso['status']; ?>">
                                                                    <?php echo getNomeStatus($compromisso['status']); ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($compromisso['observacoes'])): ?>
                                                        <div class="mt-3">
                                                            <h6><i class="bi bi-card-text me-2"></i> Observações</h6>
                                                            <div class="p-3 bg-light rounded">
                                                                <?php echo nl2br(htmlspecialchars($compromisso['observacoes'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo htmlspecialchars($config['nome_aplicacao']); ?></h5>
                    <p>Sistema de Agenda e Compromissos da Prefeitura</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> - Todos os direitos reservados</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
