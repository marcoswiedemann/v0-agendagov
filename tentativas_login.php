<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário tem permissão
requireRole('admin');

// Processar limpeza de tentativas
if (isset($_POST['limpar']) && $_POST['limpar'] === 'todas') {
    try {
        $stmt = $pdo->prepare("TRUNCATE TABLE tentativas_login");
        $stmt->execute();
        $_SESSION['success'] = "Todas as tentativas de login foram removidas com sucesso.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao limpar tentativas de login: " . $e->getMessage();
    }
    header('Location: tentativas_login.php');
    exit;
}

// Processar limpeza de tentativas antigas
if (isset($_POST['limpar']) && $_POST['limpar'] === 'antigas') {
    try {
        $dias = isset($_POST['dias']) ? (int)$_POST['dias'] : 30;
        $stmt = $pdo->prepare("DELETE FROM tentativas_login WHERE time < (NOW() - INTERVAL ? DAY)");
        $stmt->execute([$dias]);
        $_SESSION['success'] = "Tentativas de login com mais de $dias dias foram removidas com sucesso.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao limpar tentativas de login antigas: " . $e->getMessage();
    }
    header('Location: tentativas_login.php');
    exit;
}

// Obter estatísticas
$stats = [
    'total' => 0,
    'sucesso' => 0,
    'falha' => 0,
    'hoje' => 0,
    'semana' => 0
];

try {
    // Total de tentativas
    $stmt = $pdo->query("SELECT COUNT(*) FROM tentativas_login");
    $stats['total'] = $stmt->fetchColumn();
    
    // Tentativas com sucesso
    $stmt = $pdo->query("SELECT COUNT(*) FROM tentativas_login WHERE success = 1");
    $stats['sucesso'] = $stmt->fetchColumn();
    
    // Tentativas com falha
    $stmt = $pdo->query("SELECT COUNT(*) FROM tentativas_login WHERE success = 0");
    $stats['falha'] = $stmt->fetchColumn();
    
    // Tentativas hoje
    $stmt = $pdo->query("SELECT COUNT(*) FROM tentativas_login WHERE DATE(time) = CURDATE()");
    $stats['hoje'] = $stmt->fetchColumn();
    
    // Tentativas na última semana
    $stmt = $pdo->query("SELECT COUNT(*) FROM tentativas_login WHERE time >= (NOW() - INTERVAL 7 DAY)");
    $stats['semana'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao obter estatísticas: " . $e->getMessage();
}

// Obter lista de tentativas de login (paginada)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$tentativas = [];
$total_pages = 0;

try {
    // Contar total para paginação
    $stmt = $pdo->query("SELECT COUNT(*) FROM tentativas_login");
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Obter tentativas para a página atual
    $stmt = $pdo->prepare("SELECT id, usuario, ip, success, time FROM tentativas_login ORDER BY time DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tentativas = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao obter tentativas de login: " . $e->getMessage();
}

$pageTitle = "Tentativas de Login";
$headerButtons = '
    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#limparModal">
        <i class="bi bi-trash"></i> Limpar Tentativas
    </button>
';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="stats-card stats-card-primary">
                <div class="stats-card-icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="stats-card-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stats-card-label">Total de Tentativas</div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="stats-card stats-card-success">
                <div class="stats-card-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-card-value"><?php echo number_format($stats['sucesso']); ?></div>
                <div class="stats-card-label">Logins com Sucesso</div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="stats-card stats-card-danger">
                <div class="stats-card-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stats-card-value"><?php echo number_format($stats['falha']); ?></div>
                <div class="stats-card-label">Logins Falhos</div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="stats-card stats-card-info">
                <div class="stats-card-icon">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div class="stats-card-value"><?php echo number_format($stats['hoje']); ?></div>
                <div class="stats-card-label">Tentativas Hoje</div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="stats-card stats-card-warning">
                <div class="stats-card-icon">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div class="stats-card-value"><?php echo number_format($stats['semana']); ?></div>
                <div class="stats-card-label">Últimos 7 Dias</div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="stats-card" style="background-color: #6c757d; color: white;">
                <div class="stats-card-icon">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stats-card-value">
                    <?php 
                    $taxa_sucesso = $stats['total'] > 0 ? round(($stats['sucesso'] / $stats['total']) * 100) : 0;
                    echo $taxa_sucesso . '%';
                    ?>
                </div>
                <div class="stats-card-label">Taxa de Sucesso</div>
            </div>
        </div>
    </div>
    
    <!-- Lista de Tentativas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if (empty($tentativas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-shield-lock text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">Nenhuma tentativa de login registrada</h4>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>IP</th>
                                <th>Status</th>
                                <th>Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tentativas as $tentativa): ?>
                                <tr>
                                    <td><?php echo $tentativa['id']; ?></td>
                                    <td><?php echo htmlspecialchars($tentativa['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($tentativa['ip']); ?></td>
                                    <td>
                                        <?php if ($tentativa['success']): ?>
                                            <span class="badge bg-success">Sucesso</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Falha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($tentativa['time'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navegação de página">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                                }
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Próximo">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Limpar Tentativas -->
<div class="modal fade" id="limparModal" tabindex="-1" aria-labelledby="limparModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="limparModalLabel">Limpar Tentativas de Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6>Limpar Tentativas Antigas</h6>
                    <form action="tentativas_login.php" method="post">
                        <div class="input-group mb-3">
                            <span class="input-group-text">Remover tentativas com mais de</span>
                            <input type="number" class="form-control" name="dias" value="30" min="1" max="365">
                            <span class="input-group-text">dias</span>
                            <input type="hidden" name="limpar" value="antigas">
                            <button class="btn btn-outline-danger" type="submit">Limpar</button>
                        </div>
                    </form>
                </div>
                
                <div>
                    <h6>Limpar Todas as Tentativas</h6>
                    <form action="tentativas_login.php" method="post" onsubmit="return confirm('Tem certeza que deseja remover TODAS as tentativas de login? Esta ação não pode ser desfeita.')">
                        <input type="hidden" name="limpar" value="todas">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Remover Todas as Tentativas
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
