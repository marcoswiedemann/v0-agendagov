<?php
// Iniciar a sessão
session_start();

// Verificar se está logado como admin (simplificado para diagnóstico)
$admin_mode = isset($_GET['admin']) && $_GET['admin'] == 'true';

// Definir o fuso horário para America/Sao_Paulo
date_default_timezone_set('America/Sao_Paulo');

// Configurações do banco de dados
$db_host = 'localhost';
$db_name = 'u414602466_prefeitoagenda';
$db_user = 'seu_usuario';
$db_pass = 'sua_senha';

// Mensagens e erros
$mensagens = [];
$erros = [];
$diagnostico = [];

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $diagnostico[] = "✅ Conexão com o banco de dados estabelecida com sucesso.";
} catch (PDOException $e) {
    $erros[] = "❌ Erro na conexão com o banco de dados: " . $e->getMessage();
}

// Verificar o fuso horário do PHP
$diagnostico[] = "📌 Fuso horário do PHP: " . date_default_timezone_get();
$diagnostico[] = "📌 Data e hora atual do PHP: " . date('d/m/Y H:i:s');

// Verificar o fuso horário do MySQL
try {
    $stmt = $pdo->query("SELECT @@time_zone AS timezone, NOW() AS current_time");
    $mysql_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $diagnostico[] = "📌 Fuso horário do MySQL: " . $mysql_info['timezone'];
    $diagnostico[] = "📌 Data e hora atual do MySQL: " . $mysql_info['current_time'];
} catch (PDOException $e) {
    $erros[] = "❌ Erro ao verificar o fuso horário do MySQL: " . $e->getMessage();
}

// Verificar a estrutura da tabela compromissos
try {
    $stmt = $pdo->query("DESCRIBE compromissos");
    $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $diagnostico[] = "📌 Colunas da tabela compromissos: " . implode(", ", $colunas);
    
    // Verificar se as colunas necessárias existem
    $colunas_necessarias = ['id', 'titulo', 'data', 'hora', 'local', 'responsavel', 'observacoes', 'status', 'publico', 'criado_por'];
    $colunas_faltando = array_diff($colunas_necessarias, $colunas);
    
    if (empty($colunas_faltando)) {
        $diagnostico[] = "✅ Todas as colunas necessárias existem na tabela compromissos.";
    } else {
        $erros[] = "❌ Colunas faltando na tabela compromissos: " . implode(", ", $colunas_faltando);
    }
} catch (PDOException $e) {
    $erros[] = "❌ Erro ao verificar a estrutura da tabela compromissos: " . $e->getMessage();
}

// Verificar se existem compromissos
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM compromissos");
    $total_compromissos = $stmt->fetchColumn();
    $diagnostico[] = "📌 Total de compromissos: " . $total_compromissos;
    
    if ($total_compromissos == 0) {
        $erros[] = "❌ Não existem compromissos cadastrados.";
    } else {
        $diagnostico[] = "✅ Existem compromissos cadastrados.";
    }
} catch (PDOException $e) {
    $erros[] = "❌ Erro ao verificar os compromissos: " . $e->getMessage();
}

// Verificar se existem usuários
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total_usuarios = $stmt->fetchColumn();
    $diagnostico[] = "📌 Total de usuários: " . $total_usuarios;
    
    if ($total_usuarios == 0) {
        $erros[] = "❌ Não existem usuários cadastrados.";
    } else {
        $diagnostico[] = "✅ Existem usuários cadastrados.";
    }
} catch (PDOException $e) {
    $erros[] = "❌ Erro ao verificar os usuários: " . $e->getMessage();
}

// Verificar se existem prefeito e vice-prefeito
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'prefeito'");
    $total_prefeito = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'vice'");
    $total_vice = $stmt->fetchColumn();
    
    $diagnostico[] = "📌 Total de prefeitos: " . $total_prefeito;
    $diagnostico[] = "📌 Total de vice-prefeitos: " . $total_vice;
    
    if ($total_prefeito == 0) {
        $erros[] = "❌ Não existe prefeito cadastrado.";
    }
    
    if ($total_vice == 0) {
        $erros[] = "❌ Não existe vice-prefeito cadastrado.";
    }
} catch (PDOException $e) {
    $erros[] = "❌ Erro ao verificar prefeito e vice-prefeito: " . $e->getMessage();
}

// Ações de correção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Definir o fuso horário do MySQL
    if (isset($_POST['corrigir_timezone_mysql'])) {
        try {
            $pdo->exec("SET time_zone = '-03:00'");
            $mensagens[] = "✅ Fuso horário do MySQL ajustado para -03:00 (America/Sao_Paulo).";
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao ajustar o fuso horário do MySQL: " . $e->getMessage();
        }
    }
    
    // Adicionar colunas faltantes
    if (isset($_POST['adicionar_colunas'])) {
        try {
            if (!in_array('publico', $colunas)) {
                $pdo->exec("ALTER TABLE compromissos ADD COLUMN publico TINYINT(1) NOT NULL DEFAULT 1");
                $mensagens[] = "✅ Coluna 'publico' adicionada à tabela compromissos.";
            }
            
            if (!in_array('criado_por', $colunas)) {
                $pdo->exec("ALTER TABLE compromissos ADD COLUMN criado_por INT");
                $mensagens[] = "✅ Coluna 'criado_por' adicionada à tabela compromissos.";
            }
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao adicionar colunas: " . $e->getMessage();
        }
    }
    
    // Criar usuários padrão
    if (isset($_POST['criar_usuarios'])) {
        try {
            // Verificar se já existe um prefeito
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'prefeito'");
            $total_prefeito = $stmt->fetchColumn();
            
            if ($total_prefeito == 0) {
                $senha_hash = password_hash('prefeito123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Prefeito', 'prefeito@exemplo.com', $senha_hash, 'prefeito', 1]);
                $mensagens[] = "✅ Usuário prefeito criado com sucesso.";
            }
            
            // Verificar se já existe um vice-prefeito
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'vice'");
            $total_vice = $stmt->fetchColumn();
            
            if ($total_vice == 0) {
                $senha_hash = password_hash('vice123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Vice-Prefeito', 'vice@exemplo.com', $senha_hash, 'vice', 1]);
                $mensagens[] = "✅ Usuário vice-prefeito criado com sucesso.";
            }
            
            // Verificar se já existe um administrador
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'admin'");
            $total_admin = $stmt->fetchColumn();
            
            if ($total_admin == 0) {
                $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Administrador', 'admin@exemplo.com', $senha_hash, 'admin', 1]);
                $mensagens[] = "✅ Usuário administrador criado com sucesso.";
            }
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao criar usuários: " . $e->getMessage();
        }
    }
    
    // Importar compromissos de exemplo
    if (isset($_POST['importar_compromissos'])) {
        try {
            // Obter IDs do prefeito e vice-prefeito
            $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
            $id_prefeito = $stmt->fetchColumn() ?: 1;
            
            $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
            $id_vice = $stmt->fetchColumn() ?: 2;
            
            // Compromissos de exemplo
            $compromissos = [
                [
                    'titulo' => 'REUNIÃO COM SECRETÁRIOS',
                    'data' => date('Y-m-d'),
                    'hora' => '09:00:00',
                    'local' => 'Gabinete do Prefeito',
                    'responsavel' => 'Secretário de Administração',
                    'observacoes' => 'Reunião para discutir o orçamento anual',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_prefeito
                ],
                [
                    'titulo' => 'VISITA À ESCOLA MUNICIPAL',
                    'data' => date('Y-m-d'),
                    'hora' => '14:00:00',
                    'local' => 'Escola Municipal Central',
                    'responsavel' => 'Secretário de Educação',
                    'observacoes' => 'Visita para inauguração da nova biblioteca',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_prefeito
                ],
                [
                    'titulo' => 'REUNIÃO COM EMPRESÁRIOS',
                    'data' => date('Y-m-d'),
                    'hora' => '10:00:00',
                    'local' => 'Câmara de Comércio',
                    'responsavel' => 'Secretário de Desenvolvimento',
                    'observacoes' => 'Discussão sobre incentivos fiscais',
                    'status' => 'pendente',
                    'publico' => 0,
                    'criado_por' => $id_vice
                ],
                [
                    'titulo' => 'EVENTO BENEFICENTE',
                    'data' => date('Y-m-d'),
                    'hora' => '19:00:00',
                    'local' => 'Centro de Convenções',
                    'responsavel' => 'Primeira-dama',
                    'observacoes' => 'Arrecadação de fundos para hospital infantil',
                    'status' => 'pendente',
                    'publico' => 1,
                    'criado_por' => $id_vice
                ]
            ];
            
            // Inserir os compromissos
            $stmt = $pdo->prepare("
                INSERT INTO compromissos 
                (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $count = 0;
            foreach ($compromissos as $c) {
                $stmt->execute([
                    $c['titulo'],
                    $c['data'],
                    $c['hora'],
                    $c['local'],
                    $c['responsavel'],
                    $c['observacoes'],
                    $c['status'],
                    $c['publico'],
                    $c['criado_por']
                ]);
                $count++;
            }
            
            $mensagens[] = "✅ $count compromissos de exemplo importados com sucesso.";
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao importar compromissos: " . $e->getMessage();
        }
    }
    
    // Limpar todos os compromissos
    if (isset($_POST['limpar_compromissos']) && $admin_mode) {
        try {
            $pdo->exec("TRUNCATE TABLE compromissos");
            $mensagens[] = "✅ Todos os compromissos foram removidos.";
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao limpar compromissos: " . $e->getMessage();
        }
    }
    
    // Criar tabela compromissos
    if (isset($_POST['criar_tabela_compromissos']) && $admin_mode) {
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS compromissos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(255) NOT NULL,
                data DATE NOT NULL,
                hora TIME NOT NULL,
                local VARCHAR(255),
                responsavel VARCHAR(255),
                observacoes TEXT,
                status VARCHAR(20) NOT NULL DEFAULT 'pendente',
                publico TINYINT(1) NOT NULL DEFAULT 1,
                criado_por INT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql);
            $mensagens[] = "✅ Tabela compromissos criada/atualizada com sucesso.";
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao criar tabela compromissos: " . $e->getMessage();
        }
    }
    
    // Criar tabela usuários
    if (isset($_POST['criar_tabela_usuarios']) && $admin_mode) {
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                papel ENUM('admin', 'prefeito', 'vice', 'secretario', 'assessor') NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                ultimo_login DATETIME,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql);
            $mensagens[] = "✅ Tabela usuários criada/atualizada com sucesso.";
        } catch (PDOException $e) {
            $erros[] = "❌ Erro ao criar tabela usuários: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico e Correção do Sistema</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .diagnostic-item {
            margin-bottom: 8px;
            padding: 8px;
            border-radius: 4px;
        }
        .diagnostic-item:nth-child(odd) {
            background-color: #f8f9fa;
        }
        .action-card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="display-5 mb-4">Diagnóstico e Correção do Sistema</h1>
                
                <?php if (!empty($mensagens)): ?>
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle-fill me-2"></i>Ações realizadas com sucesso:</h5>
                        <ul class="mb-0">
                            <?php foreach ($mensagens as $mensagem): ?>
                                <li><?php echo $mensagem; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Erros encontrados:</h5>
                        <ul class="mb-0">
                            <?php foreach ($erros as $erro): ?>
                                <li><?php echo $erro; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Diagnóstico do Sistema</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($diagnostico as $item): ?>
                            <div class="diagnostic-item">
                                <?php echo $item; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Ações de Correção</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="action-card">
                                <h6><i class="bi bi-clock me-2"></i>Fuso Horário</h6>
                                <p class="small text-muted">Ajusta o fuso horário do MySQL para America/Sao_Paulo (-03:00).</p>
                                <button type="submit" name="corrigir_timezone_mysql" class="btn btn-primary btn-sm">
                                    Corrigir Fuso Horário
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-table me-2"></i>Estrutura da Tabela</h6>
                                <p class="small text-muted">Adiciona colunas faltantes à tabela de compromissos.</p>
                                <button type="submit" name="adicionar_colunas" class="btn btn-primary btn-sm">
                                    Adicionar Colunas Faltantes
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-people me-2"></i>Usuários</h6>
                                <p class="small text-muted">Cria usuários padrão (prefeito, vice-prefeito e administrador).</p>
                                <button type="submit" name="criar_usuarios" class="btn btn-primary btn-sm">
                                    Criar Usuários Padrão
                                </button>
                            </div>
                            
                            <div class="action-card">
                                <h6><i class="bi bi-calendar-event me-2"></i>Compromissos</h6>
                                <p class="small text-muted">Importa compromissos de exemplo para testar o sistema.</p>
                                <button type="submit" name="importar_compromissos" class="btn btn-primary btn-sm">
                                    Importar Compromissos de Exemplo
                                </button>
                            </div>
                            
                            <?php if ($admin_mode): ?>
                                <hr>
                                <div class="action-card">
                                    <h6><i class="bi bi-database me-2"></i>Ações Avançadas</h6>
                                    <p class="small text-muted">Estas ações são irreversíveis. Use com cuidado.</p>
                                    
                                    <button type="submit" name="criar_tabela_compromissos" class="btn btn-warning btn-sm me-2 mb-2">
                                        Criar/Atualizar Tabela Compromissos
                                    </button>
                                    
                                    <button type="submit" name="criar_tabela_usuarios" class="btn btn-warning btn-sm me-2 mb-2">
                                        Criar/Atualizar Tabela Usuários
                                    </button>
                                    
                                    <button type="submit" name="limpar_compromissos" class="btn btn-danger btn-sm mb-2" onclick="return confirm('Tem certeza que deseja remover TODOS os compromissos?');">
                                        Limpar Todos os Compromissos
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-link me-2"></i>Links Úteis</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="index.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-house me-2"></i>Página Inicial
                            </a>
                            <a href="agenda_publica.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-calendar-event me-2"></i>Agenda Pública
                            </a>
                            <a href="agenda_publica_prefeito.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-badge me-2"></i>Agenda do Prefeito
                            </a>
                            <a href="agenda_publica_vice.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-badge-fill me-2"></i>Agenda do Vice-Prefeito
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
