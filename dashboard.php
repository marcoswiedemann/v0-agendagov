<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Obter o usuário atual
$usuario = getCurrentUser();
$usuario_id = $usuario['id'];
$papel = $usuario['papel'];

// Obter data atual
$data_atual = date('Y-m-d');
$data_formatada = formatarData($data_atual);
$nome_dia = getNomeDiaSemana($data_atual);

// Obter IDs do prefeito e vice
$stmt_prefeito = $pdo->prepare("SELECT id, nome FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
$stmt_prefeito->execute();
$prefeito = $stmt_prefeito->fetch();
$id_prefeito = $prefeito ? $prefeito['id'] : 0;
$nome_prefeito = $prefeito ? $prefeito['nome'] : 'Prefeito';

$stmt_vice = $pdo->prepare("SELECT id, nome FROM usuarios WHERE papel = 'vice' LIMIT 1");
$stmt_vice->execute();
$vice = $stmt_vice->fetch();
$id_vice = $vice ? $vice['id'] : 0;
$nome_vice = $vice ? $vice['nome'] : 'Vice-Prefeito';

// Determinar quais compromissos mostrar com base no papel do usuário
$sql_compromissos = "SELECT c.*, u.nome as criador_nome, u.papel as criador_papel 
                     FROM compromissos c 
                     LEFT JOIN usuarios u ON c.criado_por = u.id 
                     WHERE c.data = ? AND (";

if ($papel === 'admin') {
    // Admin vê todos os compromissos
    $sql_compromissos .= "1=1";
    $params = [$data_atual];
} else {
    // Prefeito/Vice vê apenas seus próprios compromissos e os compartilhados com ele
    $sql_compromissos .= "c.criado_por = ? OR (c.compartilhado = 1 AND c.compartilhado_com = ?)";
    $params = [$data_atual, $usuario_id, $usuario_id];
}

$sql_compromissos .= ") ORDER BY c.hora ASC";
$stmt = $pdo->prepare($sql_compromissos);
$stmt->execute($params);
$compromissos = $stmt->fetchAll();

// Verificar se há compromissos compartilhados pendentes
$sql_pendentes = "SELECT COUNT(*) FROM compromissos 
                 WHERE compartilhado_com = ? 
                 AND status_compartilhamento = 'pendente'";
$stmt_pendentes = $pdo->prepare($sql_pendentes);
$stmt_pendentes->execute([$usuario_id]);
$compromissos_pendentes = $stmt_pendentes->fetchColumn();

// Título da página
$pageTitle = "Dashboard - " . $data_formatada;

// Botões do cabeçalho
$headerButtons = '
    <a href="compromisso.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Novo Compromisso
    </a>
    <a href="compromisso.php?data=' . $data_formatada . '" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-calendar-plus"></i> Compromisso para Hoje
    </a>
';

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Alertas para compromissos compartilhados pendentes -->
    <?php if ($compromissos_pendentes > 0 && $papel !== 'admin'): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        Você tem <strong><?php echo $compromissos_pendentes; ?></strong> compromisso(s) compartilhado(s) pendente(s) de aprovação.
        <a href="compromissos_compartilhados.php" class="alert-link">Visualizar agora</a>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>

    <!-- Cabeçalho da Página -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Agenda de Hoje</h4>
                            <p class="text-muted mb-0">
                                <?php echo $data_formatada; ?> (<?php echo $nome_dia; ?>)
                                <?php if ($papel === 'admin'): ?>
                                    <span class="badge bg-primary">Visualização Completa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sua Agenda</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <a href="agenda_semanal.php" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="bi bi-calendar-week"></i> Agenda Semanal
                            </a>
                            <a href="agenda_mensal.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar-month"></i> Agenda Mensal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compromissos do Dia -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-check me-2"></i> 
                        Compromissos de Hoje
                    </h5>
                    <span class="badge bg-primary rounded-pill">
                        <?php echo count($compromissos); ?> compromisso(s)
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($compromissos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Nenhum compromisso para hoje</h5>
                            <p class="text-muted">Você não possui compromissos agendados para esta data.</p>
                            <a href="compromisso.php?data=<?php echo $data_formatada; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Adicionar Compromisso
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Título</th>
                                        <th>Local</th>
                                        <th>Responsável</th>
                                        <th>Status</th>
                                        <?php if ($papel === 'admin'): ?>
                                        <th>Agenda</th>
                                        <?php endif; ?>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compromissos as $compromisso): ?>
                                        <?php 
                                        // Determinar classe de linha com base no status do compromisso
                                        $rowClass = '';
                                        $compartilhado = false;
                                        
                                        // Verificar se é um compromisso compartilhado
                                        if ($compromisso['compartilhado'] == 1 && $compromisso['criado_por'] != $usuario_id) {
                                            $compartilhado = true;
                                            if ($compromisso['status_compartilhamento'] == 'pendente') {
                                                $rowClass = 'table-warning';
                                            } elseif ($compromisso['status_compartilhamento'] == 'aceito') {
                                                $rowClass = 'table-info';
                                            } elseif ($compromisso['status_compartilhamento'] == 'recusado') {
                                                $rowClass = 'table-danger';
                                            }
                                        }
                                        ?>
                                        <tr class="<?php echo $rowClass; ?>">
                                            <td><?php echo formatarHora($compromisso['hora']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($compromisso['titulo']); ?>
                                                <?php if ($compartilhado): ?>
                                                    <span class="badge bg-info text-dark">Compartilhado</span>
                                                <?php endif; ?>
                                                <?php if ($compromisso['publico'] == 1): ?>
                                                    <span class="badge bg-success">Público</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Privado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($compromisso['local'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($compromisso['responsavel']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $compromisso['status'] === 'pendente' ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                                    <?php echo getNomeStatus($compromisso['status']); ?>
                                                </span>
                                            </td>
                                            <?php if ($papel === 'admin'): ?>
                                            <td>
                                                <?php if ($compromisso['criado_por'] == $id_prefeito): ?>
                                                    <span class="badge bg-primary"><?php echo $nome_prefeito; ?></span>
                                                <?php elseif ($compromisso['criado_por'] == $id_vice): ?>
                                                    <span class="badge bg-secondary"><?php echo $nome_vice; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark"><?php echo $compromisso['criador_nome']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="visualizar_compromisso.php?id=<?php echo $compromisso['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($compromisso['criado_por'] == $usuario_id || $papel === 'admin'): ?>
                                                    <a href="compromisso.php?id=<?php echo $compromisso['id']; ?>" class="btn btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($compartilhado && $compromisso['status_compartilhamento'] == 'pendente'): ?>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="responderCompartilhamento(<?php echo $compromisso['id']; ?>, 'aceito')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="responderCompartilhamento(<?php echo $compromisso['id']; ?>, 'recusado')">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                Tem certeza que deseja realizar esta ação?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmModalBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para responder a um compartilhamento (aceitar/recusar)
function responderCompartilhamento(id, resposta) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const title = document.getElementById('confirmModalTitle');
    const body = document.getElementById('confirmModalBody');
    const confirmBtn = document.getElementById('confirmModalBtn');
    
    title.textContent = resposta === 'aceito' ? 'Aceitar Compromisso' : 'Recusar Compromisso';
    body.textContent = resposta === 'aceito' 
        ? 'Tem certeza que deseja aceitar este compromisso compartilhado?' 
        : 'Tem certeza que deseja recusar este compromisso compartilhado?';
    
    confirmBtn.className = resposta === 'aceito' 
        ? 'btn btn-success' 
        : 'btn btn-danger';
    
    confirmBtn.textContent = resposta === 'aceito' ? 'Aceitar' : 'Recusar';
    
    confirmBtn.onclick = function() {
        // Enviar resposta via AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'responder_compartilhamento.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Recarregar a página após resposta bem-sucedida
                window.location.reload();
            } else {
                alert('Ocorreu um erro ao processar sua solicitação.');
            }
        };
        xhr.send(`id=${id}&resposta=${resposta}&csrf_token=<?php echo generateCSRFToken(); ?>`);
        
        modal.hide();
    };
    
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
