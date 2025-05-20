<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário tem permissão
requireRole('admin');

// Processar exclusão de usuário
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    
    // Não permitir excluir o próprio usuário
    if ($id_excluir === $_SESSION['user_id']) {
        $_SESSION['error'] = "Você não pode excluir seu próprio usuário.";
    } else {
        // Verificar se o usuário existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$id_excluir]);
        
        if ($stmt->rowCount() > 0) {
            // Excluir permissões do usuário
            $stmt = $pdo->prepare("DELETE FROM permissoes WHERE usuario_id = ?");
            $stmt->execute([$id_excluir]);
            
            // Excluir usuário
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_excluir]);
            
            $_SESSION['success'] = "Usuário excluído com sucesso.";
        } else {
            $_SESSION['error'] = "Usuário não encontrado.";
        }
    }
    
    header('Location: usuarios.php');
    exit;
}

// Processar alteração de status (ativar/desativar)
if (isset($_GET['status']) && is_numeric($_GET['status']) && isset($_GET['valor'])) {
    $id_status = (int)$_GET['status'];
    $valor = $_GET['valor'] === '1' ? 1 : 0;
    
    // Não permitir desativar o próprio usuário
    if ($id_status === $_SESSION['user_id'] && $valor === 0) {
        $_SESSION['error'] = "Você não pode desativar seu próprio usuário.";
    } else {
        // Verificar se o usuário existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$id_status]);
        
        if ($stmt->rowCount() > 0) {
            // Atualizar status
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
            $stmt->execute([$valor, $id_status]);
            
            $_SESSION['success'] = "Status do usuário atualizado com sucesso.";
        } else {
            $_SESSION['error'] = "Usuário não encontrado.";
        }
    }
    
    header('Location: usuarios.php');
    exit;
}

// Obter lista de usuários
$stmt = $pdo->query("SELECT id, nome, email, usuario, papel, ativo FROM usuarios ORDER BY nome");
$usuarios = $stmt->fetchAll();

$pageTitle = "Gerenciamento de Usuários";
$headerButtons = '<a href="usuario_form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Novo Usuário</a>';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($usuarios)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">Nenhum usuário cadastrado</h4>
                            <a href="usuario_form.php" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle"></i> Adicionar Usuário
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Papel</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo getNomePapel($usuario['papel']); ?></span></td>
                                            <td>
                                                <?php if ($usuario['ativo']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="usuario_form.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    
                                                    <?php if ($usuario['papel'] === 'visualizador'): ?>
                                                        <a href="usuario_permissoes.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-info">
                                                            <i class="bi bi-key"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($usuario['id'] !== $_SESSION['user_id']): ?>
                                                        <?php if ($usuario['ativo']): ?>
                                                            <a href="usuarios.php?status=<?php echo $usuario['id']; ?>&valor=0" class="btn btn-sm btn-outline-warning" onclick="return confirm('Tem certeza que deseja desativar este usuário?')">
                                                                <i class="bi bi-toggle-off"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="usuarios.php?status=<?php echo $usuario['id']; ?>&valor=1" class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-toggle-on"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="usuarios.php?excluir=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
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

<?php require_once 'includes/footer.php'; ?>
