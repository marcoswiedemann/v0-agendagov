<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Obter dados do usuário
$stmt = $pdo->prepare("SELECT id, nome, email, papel FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($email)) {
        $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Por favor, informe um email válido.";
    } else {
        // Verificar se o email já existe (exceto para o próprio usuário)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Este email já está em uso por outro usuário.";
        } else {
            // Verificar se o usuário quer alterar a senha
            if (!empty($senha_atual) || !empty($nova_senha) || !empty($confirmar_senha)) {
                // Verificar se a senha atual está correta
                $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $hash_senha = $stmt->fetchColumn();
                
                if (!password_verify($senha_atual, $hash_senha)) {
                    $_SESSION['error'] = "Senha atual incorreta.";
                } elseif (empty($nova_senha)) {
                    $_SESSION['error'] = "Por favor, informe a nova senha.";
                } elseif ($nova_senha !== $confirmar_senha) {
                    $_SESSION['error'] = "As senhas não coincidem.";
                } else {
                    // Atualizar usuário com nova senha
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
                    $stmt->execute([
                        $nome,
                        $email,
                        password_hash($nova_senha, PASSWORD_DEFAULT),
                        $_SESSION['user_id']
                    ]);
                    
                    $_SESSION['success'] = "Perfil atualizado com sucesso.";
                    $_SESSION['user_nome'] = $nome;
                    $_SESSION['user_email'] = $email;
                    
                    header('Location: perfil.php');
                    exit;
                }
            } else {
                // Atualizar usuário sem alterar a senha
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmt->execute([
                    $nome,
                    $email,
                    $_SESSION['user_id']
                ]);
                
                $_SESSION['success'] = "Perfil atualizado com sucesso.";
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_email'] = $email;
                
                header('Location: perfil.php');
                exit;
            }
        }
    }
}

$pageTitle = "Meu Perfil";
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                            <i class="bi bi-person-circle text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                        <span class="badge bg-secondary"><?php echo getNomePapel($usuario['papel']); ?></span>
                    </div>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   value="<?php echo htmlspecialchars($usuario['nome']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>">
                        </div>
                        
                        <hr class="my-4">
                        <h5>Alterar Senha</h5>
                        <p class="text-muted small">Preencha os campos abaixo apenas se desejar alterar sua senha.</p>
                        
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
