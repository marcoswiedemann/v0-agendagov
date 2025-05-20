<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Obter o ID do compromisso
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = "Compromisso não encontrado.";
    header('Location: dashboard.php');
    exit;
}

// Obter dados do compromisso
$stmt = $pdo->prepare("SELECT c.*, u.nome as criador_nome 
                      FROM compromissos c 
                      LEFT JOIN usuarios u ON c.criado_por = u.id 
                      WHERE c.id = ?");
$stmt->execute([$id]);
$compromisso = $stmt->fetch();

// Verificar se o compromisso existe
if (!$compromisso) {
    $_SESSION['error'] = "Compromisso não encontrado.";
    header('Location: dashboard.php');
    exit;
}

// Verificar permissão para visualizar
$usuario = getCurrentUser();

if ($usuario['papel'] !== 'admin' && 
    $compromisso['criado_por'] !== $usuario['id'] && 
    !($compromisso['compartilhado'] && in_array($usuario['papel'], ['prefeito', 'vice']))) {
    
    // Para visualizadores, verificar permissões específicas
    if ($usuario['papel'] === 'visualizador') {
        $tem_permissao = false;
        
        // Obter o papel do criador
        $stmt_criador = $pdo->prepare("SELECT papel FROM usuarios WHERE id = ?");
        $stmt_criador->execute([$compromisso['criado_por']]);
        $papel_criador = $stmt_criador->fetchColumn();
        
        if ($papel_criador) {
            // Verificar se o visualizador tem permissão para a agenda do criador
            $stmt_perm = $pdo->prepare("SELECT COUNT(*) FROM permissoes 
                                       WHERE usuario_id = ? AND tipo_agenda = ?");
            $stmt_perm->execute([$usuario['id'], $papel_criador]);
            $tem_permissao = $stmt_perm->fetchColumn() > 0;
        }
        
        if (!$tem_permissao) {
            $_SESSION['error'] = "Você não tem permissão para visualizar este compromisso.";
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Você não tem permissão para visualizar este compromisso.";
        header('Location: dashboard.php');
        exit;
    }
}

// Verificar se é para excluir
if (isset($_GET['excluir']) && $_GET['excluir'] === '1') {
    // Verificar permissão para excluir
    if ($usuario['papel'] === 'admin' || $compromisso['criado_por'] === $usuario['id']) {
        $stmt = $pdo->prepare("DELETE FROM compromissos WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Compromisso excluído com sucesso.";
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = "Você não tem permissão para excluir este compromisso.";
        header('Location: dashboard.php');
        exit;
    }
}

// Verificar se é para alterar status
if (isset($_GET['status']) && in_array($_GET['status'], ['pendente', 'realizado'])) {
    // Verificar permissão para alterar status
    if ($usuario['papel'] === 'admin' || $compromisso['criado_por'] === $usuario['id']) {
        $novo_status = $_GET['status'];
        
        $stmt = $pdo->prepare("UPDATE compromissos SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $id]);
        
        $_SESSION['success'] = "Status do compromisso alterado para " . getNomeStatus($novo_status) . ".";
        header('Location: visualizar_compromisso.php?id=' . $id);
        exit;
    } else {
        $_SESSION['error'] = "Você não tem permissão para alterar o status deste compromisso.";
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = "Visualizar Compromisso";
$headerButtons = '<a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>';

// Botões adicionais para edição/exclusão
if ($usuario['papel'] === 'admin' || $compromisso['criado_por'] === $usuario['id']) {
    $headerButtons .= ' <a href="compromisso.php?id=' . $id . '" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Editar</a>';
    $headerButtons .= ' <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal"><i class="bi bi-trash"></i> Excluir</button>';
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detalhes do Compromisso</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4><?php echo htmlspecialchars($compromisso['titulo']); ?></h4>
                            <p class="text-muted mb-0">
                                <i class="bi bi-calendar-event me-1"></i> <?php echo formatarData($compromisso['data']); ?>
                                <i class="bi bi-clock ms-3 me-1"></i> <?php echo formatarHora($compromisso['hora']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-<?php echo $compromisso['status'] === 'pendente' ? 'warning text-dark' : 'success'; ?> p-2">
                                <i class="bi bi-<?php echo $compromisso['status'] === 'pendente' ? 'hourglass-split' : 'check-circle'; ?> me-1"></i>
                                <?php echo getNomeStatus($compromisso['status']); ?>
                            </span>
                            
                            <?php if ($compromisso['compartilhado']): ?>
                                <span class="badge bg-info p-2 ms-2">
                                    <i class="bi bi-people me-1"></i> Compartilhado
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($compromisso['publico']): ?>
                                <span class="badge bg-primary p-2 ms-2">
                                    <i class="bi bi-globe me-1"></i> Público
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Responsável</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($compromisso['responsavel']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Local</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($compromisso['local'] ?? 'Não informado'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Pessoa de Contato</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($compromisso['pessoa_contato'] ?? 'Não informado'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Participantes</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($compromisso['participantes'] ?? 'Não informado'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Observações</label>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($compromisso['observacoes'] ?? 'Nenhuma observação.')); ?>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Criado por</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($compromisso['criador_nome'] ?? 'Desconhecido'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Data de Criação</label>
                                <div class="fw-bold"><?php echo formatarData(substr($compromisso['data_criacao'], 0, 10)); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        
                        <?php if ($usuario['papel'] === 'admin' || $compromisso['criado_por'] === $usuario['id']): ?>
                            <div>
                                <?php if ($compromisso['status'] === 'pendente'): ?>
                                    <a href="visualizar_compromisso.php?id=<?php echo $id; ?>&status=realizado" class="btn btn-success me-2">
                                        <i class="bi bi-check-circle"></i> Marcar como Realizado
                                    </a>
                                <?php else: ?>
                                    <a href="visualizar_compromisso.php?id=<?php echo $id; ?>&status=pendente" class="btn btn-warning me-2">
                                        <i class="bi bi-hourglass-split"></i> Marcar como Pendente
                                    </a>
                                <?php endif; ?>
                                
                                <a href="compromisso.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excluirModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este compromisso?</p>
                <p class="text-danger"><strong>Esta ação não pode ser desfeita.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="visualizar_compromisso.php?id=<?php echo $id; ?>&excluir=1" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
