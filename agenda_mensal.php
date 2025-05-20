<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Obter o usuário atual
$usuario = getCurrentUser();

// Obter mês e ano da URL ou usar o atual
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Validar mês e ano
if ($mes < 1 || $mes > 12) {
    $mes = (int)date('m');
}
if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date('Y');
}

// Obter o primeiro dia do mês
$primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
$nome_mes = getNomeMes($mes);
$dias_no_mes = date('t', $primeiro_dia);
$dia_semana_inicio = date('w', $primeiro_dia);

// Obter compromissos do mês
$inicio_mes = date('Y-m-d', $primeiro_dia);
$fim_mes = date('Y-m-d', mktime(0, 0, 0, $mes + 1, 0, $ano));

$sql = "SELECT id, titulo, data, hora, status FROM compromissos WHERE data BETWEEN ? AND ?";
$params = [$inicio_mes, $fim_mes];

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
foreach ($compromissos as $compromisso) {
    $dia = (int)date('d', strtotime($compromisso['data']));
    if (!isset($compromissos_por_dia[$dia])) {
        $compromissos_por_dia[$dia] = [];
    }
    $compromissos_por_dia[$dia][] = $compromisso;
}

// Calcular mês anterior e próximo
$mes_anterior = $mes - 1;
$ano_anterior = $ano;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $ano_anterior--;
}

$proximo_mes = $mes + 1;
$proximo_ano = $ano;
if ($proximo_mes > 12) {
    $proximo_mes = 1;
    $proximo_ano++;
}

$pageTitle = "Agenda Mensal - " . $nome_mes . " de " . $ano;
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
                        <a href="agenda_mensal.php?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> Mês Anterior
                        </a>
                        <h3 class="mb-0"><?php echo $nome_mes; ?> de <?php echo $ano; ?></h3>
                        <a href="agenda_mensal.php?mes=<?php echo $proximo_mes; ?>&ano=<?php echo $proximo_ano; ?>" class="btn btn-outline-secondary">
                            Próximo Mês <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="text-center">
                                    <th>Domingo</th>
                                    <th>Segunda</th>
                                    <th>Terça</th>
                                    <th>Quarta</th>
                                    <th>Quinta</th>
                                    <th>Sexta</th>
                                    <th>Sábado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Iniciar a primeira linha do calendário
                                echo "<tr style='height: 120px;'>";
                                
                                // Preencher os dias vazios no início do mês
                                for ($i = 0; $i < $dia_semana_inicio; $i++) {
                                    echo "<td class='bg-light'></td>";
                                }
                                
                                // Preencher os dias do mês
                                for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
                                    $data_atual = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                                    $e_hoje = $data_atual === date('Y-m-d');
                                    
                                    // Iniciar uma nova linha a cada 7 dias
                                    if (($dia + $dia_semana_inicio - 1) % 7 === 0 && $dia !== 1) {
                                        echo "</tr><tr style='height: 120px;'>";
                                    }
                                    
                                    // Classe para destacar o dia atual
                                    $classe_dia = $e_hoje ? 'bg-primary bg-opacity-10' : '';
                                    
                                    echo "<td class='{$classe_dia}' style='vertical-align: top;'>";
                                    echo "<div class='d-flex justify-content-between'>";
                                    echo "<span class='fw-bold" . ($e_hoje ? " text-primary" : "") . "'>{$dia}</span>";
                                    
                                    if (hasRole(['admin', 'prefeito', 'vice'])) {
                                        echo "<a href='compromisso.php?data=" . formatarData($data_atual) . "' class='text-primary' title='Novo Compromisso'>";
                                        echo "<i class='bi bi-plus-circle-dotted'></i>";
                                        echo "</a>";
                                    }
                                    
                                    echo "</div>";
                                    
                                    // Exibir compromissos do dia
                                    if (isset($compromissos_por_dia[$dia])) {
                                        echo "<div class='mt-1'>";
                                        foreach ($compromissos_por_dia[$dia] as $compromisso) {
                                            $status_class = $compromisso['status'] === 'pendente' ? 'warning' : 'success';
                                            echo "<a href='visualizar_compromisso.php?id={$compromisso['id']}' class='d-block text-truncate text-decoration-none mb-1'>";
                                            echo "<span class='badge bg-{$status_class} me-1'>" . formatarHora($compromisso['hora']) . "</span>";
                                            echo "<small>" . htmlspecialchars(limitarTexto($compromisso['titulo'], 15)) . "</small>";
                                            echo "</a>";
                                        }
                                        echo "</div>";
                                    }
                                    
                                    echo "</td>";
                                }
                                
                                // Preencher os dias vazios no final do mês
                                $dias_restantes = 7 - (($dias_no_mes + $dia_semana_inicio) % 7);
                                if ($dias_restantes < 7) {
                                    for ($i = 0; $i < $dias_restantes; $i++) {
                                        echo "<td class='bg-light'></td>";
                                    }
                                }
                                
                                echo "</tr>";
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
                    <a href="agenda_semanal.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-calendar-week"></i> Visualização Semanal
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
