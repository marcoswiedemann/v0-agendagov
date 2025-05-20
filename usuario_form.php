<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário tem permissão
requireRole('admin');

// Verificar se é edição
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edicao = $id > 0;

// Obter dados do usuário para edição
$usuario = [];
if ($edicao) {
    $stmt = $pdo->prepare("SELECT id, nome, email, usuario, papel, ativo FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    // Verificar se o usuário existe
    if (!$usuario) {
        $_SESSION['error'] = "Usuário não encontrado.";
        header('Location: usuarios.php');
        exit;
    }
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $papel = $_POST['papel'] ?? '';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($email) || empty($username) || empty($papel)) {
        $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!$edicao && empty($senha)) {
        $_SESSION['error'] = "Por favor, defina uma senha para o novo usuário.";
    } elseif (!empty($senha) && $senha !== $confirmar_senha) {
        $_SESSION['error'] = "As senhas não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Por favor, informe um email válido.";
    } else {
        // Verificar se o email já existe (exceto para o próprio usuário em edição)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edicao ? $id : 0]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Este email já está em uso por outro usuário.";
        } else {
            // Verificar se o nome de usuário já existe (exceto para o próprio usuário em edição)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmt->execute([$username, $edicao ? $id : 0]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "Este nome de usuário já está em uso.";
            } else {
                if ($edicao) {
                    // Atualizar usuário
                    if (!empty($senha)) {
                        // Com nova senha
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, usuario = ?, senha = ?, papel = ?, ativo = ? WHERE id = ?");
                        $stmt->execute([
                            $nome,
                            $email,
                            $username,
                            password_hash($senha, PASSWORD_DEFAULT),
                            $papel,
                            $ativo,
                            $id
                        ]);
                    } else {
                        // Sem alterar a senha
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, usuario = ?, papel = ?, ativo = ? WHERE id = ?");
                        $stmt->execute([
                            $nome,
                            $email,
                            $username,
                            $papel,
                            $ativo,
                            $id
                        ]);
                    }
                    
                    $_SESSION['success'] = "Usuário atualizado com sucesso.";
                } else {
                    // Inserir novo usuário
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, usuario, senha, papel, ativo) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $nome,
                        $email,
                        $username,
                        password_hash($senha, PASSWORD_DEFAULT),
                        $papel,
                        $ativo
                    ]);
                    
                    $_SESSION['success'] = "Usuário criado com sucesso.";
                }
                
                header('Location: usuarios.php');
                exit;
            }
        }
    }
}

$pageTitle = $edicao ? "Editar Usuário" : "Novo Usuário";
$headerButtons = '<a href="usuarios.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nome de Usuário *</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required 
                                   value="<?php echo htmlspecialchars($usuario['usuario'] ?? ''); ?>">
                            <div class="form-text">Este será o nome de usuário para login no sistema.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label"><?php echo $edicao ? 'Nova Senha (deixe em branco para manter a atual)' : 'Senha *'; ?></label>
                            <input type="password" class="form-control" id="senha" name="senha" <?php echo $edicao ? '' : 'required'; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                        </div>
                        
                        <div class="mb-3">
                            <label for="papel" class="form-label">Papel *</label>
                            <select class="form-select" id="papel" name="papel" required>
                                <option value="">Selecione...</option>
                                <option value="admin" <?php echo (isset($usuario['papel']) && $usuario['papel'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                <option value="prefeito" <?php echo (isset($usuario['papel']) && $usuario['papel'] === 'prefeito') ? 'selected' : ''; ?>>Prefeito</option>
                                <option value="vice" <?php echo (isset($usuario['papel']) && $usuario['papel'] === 'vice') ? 'selected' : ''; ?>>Vice-Prefeito</option>
                                <option value="visualizador" <?php echo (isset($usuario['papel']) && $usuario['papel'] === 'visualizador') ? 'selected' : ''; ?>>Visualizador</option>
                            </select>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" 
                                   <?php echo (!isset($usuario['ativo']) || $usuario['ativo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edicao ? 'Atualizar' : 'Salvar'; ?> Usuário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
