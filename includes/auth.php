<?php
session_start([
    'cookie_httponly' => true,     // Previne acesso ao cookie via JavaScript
    'cookie_secure' => true,       // Requer HTTPS
    'cookie_samesite' => 'Lax',    // Proteção contra CSRF
    'gc_maxlifetime' => 3600,      // Tempo de vida da sessão (1 hora)
    'use_strict_mode' => true      // Modo estrito para sessões
]);

// Verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['login_string']);
}

// Verificar se o usuário tem o papel necessário
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['user_papel'], $roles);
}

// Redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
    
    // Verificar se a sessão é válida
    if (!checkLoginSession()) {
        logout();
        $_SESSION['error'] = "Sua sessão expirou. Por favor, faça login novamente.";
        header('Location: index.php');
        exit;
    }
    
    // Renovar sessão periodicamente
    if (isset($_SESSION['last_regeneration']) && time() - $_SESSION['last_regeneration'] > 900) {
        regenerateSession();
    }
}

// Redirecionar se não tiver o papel necessário
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        $_SESSION['error'] = "Você não tem permissão para acessar esta página.";
        header('Location: dashboard.php');
        exit;
    }
}

// Obter dados do usuário logado
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, nome, email, usuario, papel FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Verificar se o usuário tem permissão para ver uma agenda específica
function hasAgendaPermission($tipoAgenda) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin, prefeito e vice têm acesso a todas as agendas
    if (in_array($_SESSION['user_papel'], ['admin', 'prefeito', 'vice'])) {
        return true;
    }
    
    // Para visualizadores, verificar permissões específicas
    if ($_SESSION['user_papel'] === 'visualizador') {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissoes WHERE usuario_id = ? AND tipo_agenda = ?");
        $stmt->execute([$_SESSION['user_id'], $tipoAgenda]);
        return $stmt->fetchColumn() > 0;
    }
    
    return false;
}

// Criar uma string de login segura para verificação de sessão
function createLoginString($user_id, $senha_hash) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    return hash('sha512', $senha_hash . $user_agent);
}

// Verificar se a sessão de login é válida
function checkLoginSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_string'])) {
        return false;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $senha_hash = $stmt->fetchColumn();
    
    if (!$senha_hash) {
        return false;
    }
    
    $login_check = createLoginString($_SESSION['user_id'], $senha_hash);
    
    return hash_equals($_SESSION['login_string'], $login_check);
}

// Regenerar ID da sessão para prevenir fixação de sessão
function regenerateSession() {
    $old_session_id = session_id();
    session_regenerate_id(true);
    $new_session_id = session_id();
    
    $_SESSION['last_regeneration'] = time();
    
    // Registrar regeneração de sessão (opcional)
    logActivity('session_regenerated', "Sessão regenerada de $old_session_id para $new_session_id");
}

// Função para fazer login
function loginUser($user_id, $nome, $email, $username, $papel, $senha_hash) {
    // Limpar todas as variáveis de sessão anteriores
    $_SESSION = array();
    
    // Regenerar ID da sessão
    session_regenerate_id(true);
    
    try {
        // Definir variáveis de sessão
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_nome'] = $nome;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_username'] = $username;
        $_SESSION['user_papel'] = $papel;
        $_SESSION['login_string'] = createLoginString($user_id, $senha_hash);
        $_SESSION['last_regeneration'] = time();
        $_SESSION['login_time'] = time();
        
        // Registrar login bem-sucedido
        logActivity('login_success', "Login bem-sucedido para usuário $username");
    } catch (Exception $e) {
        // Registrar erro no log do servidor
        error_log("Erro ao criar sessão de login: " . $e->getMessage());
        return false;
    }
    
    return true;
}

// Função para fazer logout
function logout() {
    // Registrar logout
    if (isset($_SESSION['user_username'])) {
        logActivity('logout', "Logout para usuário " . $_SESSION['user_username']);
    }
    
    // Limpar todas as variáveis de sessão
    $_SESSION = array();
    
    // Obter parâmetros do cookie de sessão
    $params = session_get_cookie_params();
    
    // Deletar o cookie de sessão
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
    
    // Destruir a sessão
    session_destroy();
}

// Registrar atividade de login/logout
function logActivity($action, $description) {
    global $pdo;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO log_atividades (user_id, acao, descricao, ip, user_agent, data_hora) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $description, $ip, $user_agent]);
    } catch (PDOException $e) {
        // Silenciosamente falha se a tabela não existir
    }
}

// Verificar e limitar tentativas de login
function checkLoginAttempts($username) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $max_attempts = 5; // Máximo de tentativas
    $lockout_time = 15 * 60; // 15 minutos de bloqueio
    
    try {
        // Verificar se a tabela existe
        $pdo->query("SELECT 1 FROM tentativas_login LIMIT 1");
        
        // Limpar tentativas antigas
        $stmt = $pdo->prepare("DELETE FROM tentativas_login WHERE time < (NOW() - INTERVAL ? SECOND)");
        $stmt->execute([$lockout_time]);
        
        // Contar tentativas recentes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tentativas_login WHERE (usuario = ? OR ip = ?) AND success = 0");
        $stmt->execute([$username, $ip]);
        $count = $stmt->fetchColumn();
        
        if ($count >= $max_attempts) {
            return false; // Bloqueado
        }
        
        return true; // Não bloqueado
    } catch (PDOException $e) {
        // Se a tabela não existir, criar
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS tentativas_login (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario VARCHAR(255) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                success TINYINT(1) NOT NULL DEFAULT 0,
                time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            return true;
        } catch (PDOException $e) {
            // Se não conseguir criar, permitir login
            return true;
        }
    }
}

// Registrar tentativa de login
function recordLoginAttempt($username, $success = 0) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tentativas_login (usuario, ip, success) VALUES (?, ?, ?)");
        $stmt->execute([$username, $ip, $success]);
    } catch (PDOException $e) {
        // Silenciosamente falha se a tabela não existir
    }
}

// Gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}
?>
