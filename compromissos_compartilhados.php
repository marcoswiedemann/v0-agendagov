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

// Obter status de filtro
$status_filtro = isset($_GET['status']) ? $_GET['status'] : 'todos';

// Construir a consulta SQL com base no filtro
$sql = "SELECT c.*, u.nome as criador_nome, u.papel as criador_papel 
        FROM compromissos c 
        LEFT JOIN usuarios u ON c.criado_por = u.id 
        WHERE c.compartilhado = 1 AND ";

if ($papel === 'admin') {
    // Admin vê todos os compromissos compartilhados
    $sql .= "1=1";
    $params = [];
} else {
    // Usuários normais veem apenas os compartilhados com eles ou por eles
    $sql .= "(c.compartilhado_com = ? OR c.criado_por = ?)";
    $params = [$usuario_id, $usuario_id];
}

// Adicionar filtro de status
if ($status_filtro !== 'todos') {
    $sql .= " AND c.status_compartilhamento = ?";
    $params[] = $status_filtro;
}

$sql .= " ORDER BY c.data_compartilhamento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$compromissos = $stmt->fetchAll();

// Contar compromissos por status
$sql_count = "SELECT 
                SUM(CASE WHEN status_compartilhamento = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status_compartilhamento = 'aceito' THEN 1 ELSE 0 END) as aceitos,
                SUM(CASE WHEN status_compartilhamento = 'recusado' THEN 1 ELSE 0 END) as recusados,
                COUNT(*) as total
              FROM compromissos 
              WHERE compartilhado = 1 AND ";

if ($papel === 'admin') {
    $sql_count .= "1=1";
    $params_count = [];
} else {
    $sql_count .= "(compartilhado_com = ? OR criado_por = ?)";
    $params_count = [$usuario_id, $usuario_id];
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$contagem = $stmt_count->fetch();

$pageTitle = "Compromissos Compartilhados";
$headerButtons = '<a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Compromissos Compartilhados</h4>
                            <p class="text-muted mb-0">
                                Gerencie compromissos compartilhados com você ou por você
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Total</h5>
                    <h2 class="mb-0"><?php echo $contagem['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">Pendentes</h5>
                    <h2 class="mb-0"><?php echo $contagem['pendentes']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Aceitos</h5>
                    <h2 class="mb-0"><?php echo $contagem['aceitos']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Recusados</h5>
                    <h2 class="mb-0"><?php echo $contagem['recusados']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Filtrar por status:</label>
                            <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                <option value="todos" <?php echo $status_filtro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                <option value="pendente" <?php echo $status_filtro === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                                <option value="aceito" <?php echo $status_filtro === 'aceito' ? 'selected' : ''; ?>>Aceitos</option>
                                <option value="recusado" <?php echo $status_filtro === 'recusado' ? 'selected' : ''; ?>>Recusados</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Compromissos -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-share me-2"></i> 
                        Compromissos Compartilhados
                    </h5>
                    <span class="badge bg-primary rounded-pill">
                        <?php echo count($compromissos); ?> compromisso(s)
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($compromissos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-share text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Nenhum compromisso compartilhado</h5>
                            <p class="text-muted">Não há compromissos compartilhados para exibir.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Título</th>
                                        <th>Compartilhado por</th>
                                        <th>Compartilhado com</th>
                                        <th>Status</th>
                                        <th>Data do Compartilhamento</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compromissos as $compromisso): ?>
                                        <?php 
                                        // Determinar classe de linha com base no status do compartilhamento
                                        $rowClass = '';
                                        if ($compromisso['status_compartilhamento'] == 'pendente') {
                                            $rowClass = 'table-warning';
                                        } elseif ($compromisso['status_compartilhamento'] == 'aceito') {
                                            $rowClass = 'table-success';
                                        } elseif ($compromisso['status_compartilhamento'] == 'recusado') {
                                            $rowClass = 'table-danger';
                                        }
                                        
                                        // Obter nome do usuário com quem foi compartilhado
                                        $stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
                                        $stmt_user->execute([$compromisso['compartilhado_com']]);
                                        $nome_compartilhado = $stmt_user->fetchColumn();
                                        ?>
                                        <tr class="<?php echo $rowClass; ?>">
                                            <td><?php echo formatarData($compromisso['data']); ?></td>
                                            <td><?php echo htmlspecialchars($compromisso['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($compromisso['criador_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($nome_compartilhado); ?></td>
                                            <td>
                                                <?php if ($compromisso['status_compartilhamento'] == 'pendente'): ?>
                                                    <span class="badge bg-warning text-dark">Pendente</span>
                                                <?php elseif ($compromisso['status_compartilhamento'] == 'aceito'): ?>
                                                    <span class="badge bg-success">Aceito</span>
                                                <?php elseif ($compromisso['status_compartilhamento'] == 'recusado'): ?>
                                                    <span class="badge bg-danger">Recusado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatarDataHora($compromisso['data_compartilhamento']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="visualizar_compromisso.php?id=<?php echo $compromisso['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($compromisso['criado_por'] == $usuario_id): ?>
                                                    <a href="compromisso.php?id=<?php echo $compromisso['id']; ?>" class="btn btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($compromisso['compartilhado_com'] == $usuario_id && $compromisso['status_compartilhamento'] == 'pendente'): ?>
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
