<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário tem permissão
requireRole('admin');

// Obter ID do usuário
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = "Usuário não encontrado.";
    header('Location: usuarios.php');
    exit;
}

// Verificar se o usuário existe e é um visualizador
$stmt = $pdo->prepare("SELECT id, nome, papel FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['error'] = "Usuário não encontrado.";
    header('Location: usuarios.php');
    exit;
}

if ($usuario['papel'] !== 'visualizador') {
    $_SESSION['error'] = "Apenas visualizadores podem ter permissões específicas.";
    header('Location: usuarios.php');
    exit;
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpar permissões existentes
    $stmt = $pdo->prepare("DELETE FROM permissoes WHERE usuario_id = ?");
    $stmt->execute([$id]);
    
    // Adicionar novas permissões
    $permissoes = $_POST['permissoes'] ?? [];
    
    if (!empty($permissoes)) {
        $stmt = $pdo->prepare("INSERT INTO permissoes (usuario_id, tipo_agenda) VALUES (?, ?)");
        
        foreach ($permissoes as $tipo_agenda) {
            if (in_array($tipo_agenda, ['prefeito', 'vice'])) {
                $stmt->execute([$id, $tipo_agenda]);
            }
        }
    }
    
    $_SESSION['success'] = "Permissões atualizadas com sucesso.";
    header('Location: usuarios.php');
    exit;
}

// Obter permissões atuais
$stmt = $pdo->prepare("SELECT tipo_agenda FROM permissoes WHERE usuario_id = ?");
$stmt->execute([$id]);
$permissoes_atuais = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Permissões do Usuário: " . htmlspecialchars($usuario['nome']);
$headerButtons = '<a href="usuarios.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted">Selecione quais agendas este visualizador pode acessar:</p>
                    
                    <form method="post" action="">
                        <div class="list-group mb-4">
                            <label class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" name="permissoes[]" value="prefeito" 
                                       <?php echo in_array('prefeito', $permissoes_atuais) ? 'checked' : ''; ?>>
                                Agenda do Prefeito
                            </label>
                            <label class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" name="permissoes[]" value="vice" 
                                       <?php echo in_array('vice', $permissoes_atuais) ? 'checked' : ''; ?>>
                                Agenda do Vice-Prefeito
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar Permissões</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
