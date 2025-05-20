<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Obter o usuário atual
$usuario = getCurrentUser();

// Obter a data da URL ou usar a atual
$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Validar a data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    $data = date('Y-m-d');
}

// Obter compromissos do dia
$sql = "SELECT id, titulo, data, hora, responsavel, local, status FROM compromissos WHERE data = ?";
$params = [$data];

// Filtrar por papel do usuário
if ($usuario['papel'] === 'prefeito') {
    $sql .= " AND (criado_por = ? OR compartilhado = 1)";
    $params[] = $usuario['id'];
} elseif ($usuario['papel'] === 'vice') {
    $sql .= " AND (criado_por = ? OR compartilhado = 1)";
    $params[] = $usuario['id'];
} elseif ($usuario['papel'] === 'visualizador') {
    // Obter permissões do visualizador
    $stmt_perm = $pdo->prepare("SELECT tipo_agenda FROM permissoes WHERE usuario_id = ?");
    $stmt_perm->execute([$usuario['id']]);
    $permissoes = $stmt_perm->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($permissoes)) {
        $sql .= " AND 0"; // Sem permissões, não mostra nada
    } else {
        $sql .= " AND (";
        $condicoes = [];
        $params_permissoes = [];
        
        foreach ($permissoes as $tipo) {
            if ($tipo === 'prefeito') {
                $stmt_prefeito = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'prefeito'");
                $stmt_prefeito->execute();
                $id_prefeito = $stmt_prefeito->fetchColumn();
                
                if ($id_prefeito) {
                    $condicoes[] = "criado_por = ?";
                    $params_permissoes[] = $id_prefeito;
                }
            } elseif ($tipo === 'vice') {
                $stmt_vice = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'vice'");
                $stmt_vice->execute();
                $id_vice = $stmt_vice->fetchColumn();
                
                if ($id_vice) {
                    $condicoes[] = "criado_por = ?";
                    $params_permissoes[] = $id_vice;
                }
            }
        }
        
        if (!empty($condicoes)) {
            $sql .= implode(" OR ", $condicoes) . ")";
            $params = array_merge($params, $params_permissoes);
        } else {
            $sql .= " 0)"; // Sem permissões válidas
        }
    }
}

$sql .= " ORDER BY hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$compromissos = $stmt->fetchAll();

// Calcular dia anterior e próximo
$dia_anterior = date('Y-m-d', strtotime("-1 day", strtotime($data)));
$proximo_dia = date('Y-m-d', strtotime("+1 day", strtotime($data)));

$nome_dia = getNomeDiaSemana($data);
$data_formatada = formatarData($data);

$pageTitle = "Agenda Diária - " . $nome_dia . ", " . $data_formatada;
$headerButtons = '';

if (hasRole(['admin', 'prefeito', 'vice'])) {
    $headerButtons .= '<a href="compromisso.php?data=' . $data_formatada . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Novo Compromisso</a>';
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="agenda_diaria.php?data=<?php echo $dia_anterior; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> Dia Anterior
                        </a>
                        <h3 class="mb-0"><?php echo $nome_dia; ?>, <?php echo $data_formatada; ?></h3>
                        <a href="agenda_diaria.php?data=<?php echo $proximo_dia; ?>" class="btn btn-outline-secondary">
                            Próximo Dia <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($compromissos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">Nenhum compromisso agendado para este dia</h4>
                            <?php if (hasRole(['admin', 'prefeito', 'vice'])): ?>
                                <a href="compromisso.php?data=<?php echo $data_formatada; ?>" class="btn btn-primary mt-3">
                                    <i class="bi bi-plus-circle"></i> Adicionar Compromisso
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($compromissos as $index => $compromisso): ?>
                                <div class="row mb-4">
                                    <div class="col-md-2 col-lg-1 text-center">
                                        <div class="bg-light rounded-circle p-3 d-inline-block">
                                            <h5 class="mb-0"><?php echo formatarHora($compromisso['hora']); ?></h5>
                                        </div>
                                    </div>
                                    <div class="col-md-10 col-lg-11">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($compromisso['titulo']); ?></h5>
                                                    <span class="badge bg-<?php echo $compromisso['status'] === 'pendente' ? 'warning text-dark' : 'success'; ?>">
                                                        <?php echo getNomeStatus($compromisso['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="mt-2">
                                                    <p class="card-text mb-1">
                                                        <i class="bi bi-person me-2"></i> <?php echo htmlspecialchars($compromisso['responsavel']); ?>
                                                    </p>
                                                    <?php if (!empty($compromisso['local'])): ?>
                                                        <p class="card-text mb-1">
                                                            <i class="bi bi-geo-alt me-2"></i> <?php echo htmlspecialchars($compromisso['local']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-3">
                                                    <a href="visualizar_compromisso.php?id=<?php echo $compromisso['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Detalhes
                                                    </a>
                                                    <?php if (hasRole(['admin']) || $compromisso['criado_por'] === $usuario['id']): ?>
                                                        <a href="compromisso.php?id=<?php echo $compromisso['id']; ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                                            <i class="bi bi-pencil"></i> Editar
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
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
    
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar para Dashboard
                </a>
                <div>
                    <a href="agenda_mensal.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-calendar-month"></i> Visualização Mensal
                    </a>
                    <a href="agenda_semanal.php" class="btn btn-outline-success">
                        <i class="bi bi-calendar-week"></i> Visualização Semanal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
