<?php
// Verificar se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);

// Obter configurações do sistema
$config = getConfiguracoes();

// Verificar se é a página de login
$is_login_page = basename($_SERVER['PHP_SELF']) === 'index.php' && !$is_logged_in;

// Obter o usuário atual se estiver logado
$current_user = $is_logged_in ? getCurrentUser() : null;

// Definir a classe do sidebar com base no cookie
$sidebar_class = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true' ? 'sidebar-collapsed' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_aplicacao']); ?> <?php echo isset($pageTitle) ? ' - ' . $pageTitle : ''; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo $config['cor_primaria'] ?? '#2563eb'; ?>;
            --primary-hover: <?php echo adjustBrightness($config['cor_primaria'] ?? '#2563eb', -15); ?>;
            --primary-light: <?php echo adjustBrightness($config['cor_primaria'] ?? '#2563eb', 90); ?>;
            --secondary-color: <?php echo $config['cor_secundaria'] ?? '#475569'; ?>;
            --secondary-hover: <?php echo adjustBrightness($config['cor_secundaria'] ?? '#475569', -15); ?>;
        }
    </style>
</head>
<body class="<?php echo $sidebar_class; ?>">

<?php if ($is_login_page): ?>
    <!-- Login Page Layout -->
    <div class="login-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="login-card fade-in">
                        <div class="login-header">
                            <?php if (!empty($config['logo_claro'])): ?>
                                <img src="<?php echo $config['logo_claro']; ?>" alt="Logo" class="img-fluid mb-3" style="max-height: 60px;">
                            <?php else: ?>
                                <h1 class="login-title"><?php echo htmlspecialchars($config['nome_aplicacao']); ?></h1>
                            <?php endif; ?>
                            <p class="login-subtitle">Sistema de Agenda e Compromissos</p>
                        </div>
                        <div class="login-body">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                </div>
                            <?php endif; ?>
<?php else: ?>
    <!-- Main Layout -->
    <?php if ($is_logged_in): ?>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <?php if (!empty($config['logo_claro'])): ?>
                    <a href="dashboard.php" class="sidebar-brand">
                        <img src="<?php echo $config['logo_claro']; ?>" alt="Logo" class="img-fluid" style="max-height: 40px;">
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="sidebar-brand">
                        <?php echo htmlspecialchars($config['nome_aplicacao']); ?>
                    </a>
                <?php endif; ?>
                <button class="sidebar-toggle d-none d-lg-block" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <button class="sidebar-toggle d-lg-none" id="sidebarClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="sidebar-body">
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="dashboard.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="bi bi-speedometer2 sidebar-nav-icon"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="agenda_mensal.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'agenda_mensal.php' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-month sidebar-nav-icon"></i>
                            Agenda Mensal
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="agenda_semanal.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'agenda_semanal.php' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-week sidebar-nav-icon"></i>
                            Agenda Semanal
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="agenda_diaria.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'agenda_diaria.php' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-day sidebar-nav-icon"></i>
                            Agenda Diária
                        </a>
                    </li>
                    
                    <?php if (hasRole(['admin', 'prefeito', 'vice'])): ?>
                        <li class="sidebar-nav-item">
                            <a href="compromisso.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'compromisso.php' ? 'active' : ''; ?>">
                                <i class="bi bi-plus-circle sidebar-nav-icon"></i>
                                Novo Compromisso
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('admin')): ?>
                        <li class="sidebar-nav-item">
                            <a href="agenda_publica.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'agenda_publica.php' ? 'active' : ''; ?>">
                                <i class="bi bi-globe sidebar-nav-icon"></i>
                                Agenda Pública
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('admin')): ?>
                        <li class="sidebar-nav-item">
                            <a href="usuarios.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'usuarios.php' || basename($_SERVER['PHP_SELF']) === 'usuario_form.php' ? 'active' : ''; ?>">
                                <i class="bi bi-people sidebar-nav-icon"></i>
                                Usuários
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a href="tentativas_login.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'tentativas_login.php' ? 'active' : ''; ?>">
                                <i class="bi bi-shield-lock sidebar-nav-icon"></i>
                                Tentativas de Login
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a href="configuracoes.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'configuracoes.php' ? 'active' : ''; ?>">
                                <i class="bi bi-gear sidebar-nav-icon"></i>
                                Configurações
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        <?php echo strtoupper(substr($current_user['nome'], 0, 1)); ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($current_user['nome']); ?></div>
                        <div class="sidebar-user-role"><?php echo getNomePapel($current_user['papel']); ?></div>
                    </div>
                    <div class="dropdown ms-2">
                        <button class="btn btn-sm btn-icon-only" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="perfil.php">
                                    <i class="bi bi-person me-2"></i> Meu Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Top Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                    </div>
                    <div>
                        <?php if (isset($headerButtons)): ?>
                            <?php echo $headerButtons; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Alerts -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center fade-in" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success d-flex align-items-center fade-in" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    </div>
                <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
