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

// Calcular o início e fim da semana
$dia_semana = date('w', strtotime($data));
$inicio_semana = date('Y-m-d', strtotime("-{$dia_semana} days", strtotime($data)));
$fim_semana = date('Y-m-d', strtotime("+6 days", strtotime($inicio_semana)));

// Obter compromissos da semana
$sql = "SELECT id, titulo, data, hora, local, status FROM compromissos WHERE data BETWEEN ? AND ?";
$params = [$inicio_semana, $fim_semana];

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

$sql .= " ORDER BY data ASC, hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$compromissos = $stmt->fetchAll();

// Organizar compromissos por dia
$compromissos_por_dia = [];
for ($i = 0; $i < 7; $i++) {
    $data_atual = date('Y-m-d', strtotime("+{$i} days", strtotime($inicio_semana)));
    $compromissos_por_dia[$data_atual] = [];
}

foreach ($compromissos as $compromisso) {
    $compromissos_por_dia[$compromisso['data']][] = $compromisso;
}

// Calcular semana anterior e próxima
$semana_anterior = date('Y-m-d', strtotime("-7 days", strtotime($inicio_semana)));
$proxima_semana = date('Y-m-d', strtotime("+7 days", strtotime($inicio_semana)));

$pageTitle = "Agenda Semanal - " . formatarData($inicio_semana) . " a " . formatarData($fim_semana);
$headerButtons = '';

if (hasRole(['admin', 'prefeito', 'vice'])) {
    $headerButtons .= '<a href="compromisso.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Novo Compromisso</a>';
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="agenda_semanal.php?data=<?php echo $semana_anterior; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> Semana Anterior
                        </a>
                        <h3 class="mb-0">Semana de <?php echo formatarData($inicio_semana); ?> a <?php echo formatarData($fim_semana); ?></h3>
                        <a href="agenda_semanal.php?data=<?php echo $proxima_semana; ?>" class="btn btn-outline-secondary">
                            Próxima Semana <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Dia</th>
                                    <th>Compromissos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 0; $i < 7; $i++) {
                                    $data_atual = date('Y-m-d', strtotime("+{$i} days", strtotime($inicio_semana)));
                                    $nome_dia = getNomeDiaSemana($data_atual);
                                    $e_hoje = $data_atual === date('Y-m-d');
                                    
                                    // Classe para destacar o dia atual
                                    $classe_dia = $e_hoje ? 'bg-primary bg-opacity-10' : '';
                                    
                                    echo "<tr class='{$classe_dia}'>";
                                    echo "<td class='align-middle'>";
                                    echo "<div class='d-flex justify-content-between align-items-center'>";
                                    echo "<div>";
                                    echo "<div class='fw-bold" . ($e_hoje ? " text-primary" : "") . "'>{$nome_dia}</div>";
                                    echo "<div>" . formatarData($data_atual) . "</div>";
                                    echo "</div>";
                                    
                                    if (hasRole(['admin', 'prefeito', 'vice'])) {
                                        echo "<a href='compromisso.php?data=" . formatarData($data_atual) . "' class='btn btn-sm btn-outline-primary' title='Novo Compromisso'>";
                                        echo "<i class='bi bi-plus-circle'></i>";
                                        echo "</a>";
                                    }
                                    
                                    echo "</div>";
                                    echo "</td>";
                                    
                                    echo "<td>";
                                    if (empty($compromissos_por_dia[$data_atual])) {
                                        echo "<div class='text-center text-muted py-3'>Nenhum compromisso agendado</div>";
                                    } else {
                                        echo "<div class='list-group'>";
                                        foreach ($compromissos_por_dia[$data_atual] as $compromisso) {
                                            $status_class = $compromisso['status'] === 'pendente' ? 'warning' : 'success';
                                            
                                            echo "<a href='visualizar_compromisso.php?id={$compromisso['id']}' class='list-group-item list-group-item-action'>";
                                            echo "<div class='d-flex w-100 justify-content-between'>";
                                            echo "<h5 class='mb-1'>" . htmlspecialchars($compromisso['titulo']) . "</h5>";
                                            echo "<small class='badge bg-{$status_class}'>" . getNomeStatus($compromisso['status']) . "</small>";
                                            echo "</div>";
                                            echo "<div class='d-flex w-100 justify-content-between'>";
                                            echo "<p class='mb-1'>";
                                            echo "<i class='bi bi-clock me-1'></i> " . formatarHora($compromisso['hora']);
                                            if (!empty($compromisso['local'])) {
                                                echo " <i class='bi bi-geo-alt me-1 ms-3'></i> " . htmlspecialchars($compromisso['local']);
                                            }
                                            echo "</p>";
                                            echo "<small><i class='bi bi-arrow-right'></i></small>";
                                            echo "</div>";
                                            echo "</a>";
                                        }
                                        echo "</div>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
                    <a href="agenda_diaria.php" class="btn btn-outline-success">
                        <i class="bi bi-calendar-day"></i> Visualização Diária
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
