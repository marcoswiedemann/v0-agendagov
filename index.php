<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário já está logado
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Processar o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Erro de validação do formulário. Por favor, tente novamente.";
        header('Location: index.php');
        exit;
    }
    
    $username = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Validar campos
    if (empty($username) || empty($senha)) {
        $_SESSION['error'] = "Por favor, preencha todos os campos.";
    } else {
        // Verificar se o usuário não está bloqueado por muitas tentativas
        if (!checkLoginAttempts($username)) {
            $_SESSION['error'] = "Muitas tentativas de login. Por favor, tente novamente mais tarde.";
            header('Location: index.php');
            exit;
        }
        
        try {
            // Verificar credenciais - buscando pelo campo 'usuario' específico
            $stmt = $pdo->prepare("SELECT id, nome, email, usuario, senha, papel, ativo FROM usuarios WHERE usuario = ?");
            $stmt->execute([$username]);
            $usuario_dados = $stmt->fetch();
            
            if ($usuario_dados && password_verify($senha, $usuario_dados['senha'])) {
                if ($usuario_dados['ativo']) {
                    // Login bem-sucedido
                    loginUser(
                        $usuario_dados['id'],
                        $usuario_dados['nome'],
                        $usuario_dados['email'],
                        $usuario_dados['usuario'],
                        $usuario_dados['papel'],
                        $usuario_dados['senha']
                    );
                    
                    // Registrar tentativa bem-sucedida
                    recordLoginAttempt($username, 1);
                    
                    try {
                        // Registrar último login
                        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                        $stmt->execute([$usuario_dados['id']]);
                    } catch (PDOException $e) {
                        // Ignorar erro ao atualizar último login
                        error_log("Erro ao atualizar último login: " . $e->getMessage());
                    }
                    
                    // Redirecionar para o dashboard com GET em vez de POST para evitar reenvio de formulário
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $_SESSION['error'] = "Sua conta está desativada. Entre em contato com o administrador.";
                    // Registrar tentativa de login em conta desativada
                    logActivity('login_failed', "Tentativa de login em conta desativada: $username");
                }
            } else {
                $_SESSION['error'] = "Usuário ou senha incorretos.";
                // Registrar tentativa de login falha
                recordLoginAttempt($username, 0);
                logActivity('login_failed', "Credenciais inválidas para: $username");
            }
        } catch (PDOException $e) {
            // Registrar erro no log do servidor
            error_log("Erro no processo de login: " . $e->getMessage());
            $_SESSION['error'] = "Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.";
        }
    }
}

// Gerar token CSRF para o formulário
$csrf_token = generateCSRFToken();

$pageTitle = "Login";
require_once 'includes/header.php';
?>

<form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="mb-3">
        <label for="usuario" class="form-label">Usuário</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="senha" class="form-label">Senha</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="senha" name="senha" required>
        </div>
    </div>
    
    <div class="d-grid gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
        </button>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
