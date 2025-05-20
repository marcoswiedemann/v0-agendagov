<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || getCurrentUser()['papel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obter configurações do sistema
$config = getConfiguracoes();

$mensagens = [];
$erros = [];

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Ajustar o fuso horário do PHP
        $timezone = 'America/Sao_Paulo';
        $php_ini_file = php_ini_loaded_file();
        
        // Verificar se conseguimos alterar o php.ini
        $can_modify_php_ini = is_writable($php_ini_file);
        
        if ($can_modify_php_ini) {
            // Tentar modificar o php.ini
            $php_ini_content = file_get_contents($php_ini_file);
            $php_ini_content = preg_replace('/date\.timezone\s*=\s*.*/', "date.timezone = $timezone", $php_ini_content);
            
            if (file_put_contents($php_ini_file, $php_ini_content)) {
                $mensagens[] = "Fuso horário do PHP ajustado para $timezone no php.ini.";
            } else {
                $erros[] = "Não foi possível modificar o php.ini. Permissões insuficientes.";
            }
        } else {
            // Definir o fuso horário apenas para a sessão atual
            date_default_timezone_set($timezone);
            $mensagens[] = "Fuso horário do PHP ajustado para $timezone apenas para a sessão atual.";
            $mensagens[] = "Para ajustar permanentemente, adicione 'date.timezone = $timezone' ao seu php.ini.";
        }
        
        // 2. Ajustar o fuso horário do MySQL
        try {
            $pdo->exec("SET time_zone = '-03:00'");
            $mensagens[] = "Fuso horário do MySQL ajustado para -03:00 (America/Sao_Paulo).";
            
            // Verificar se podemos definir o fuso horário global do MySQL
            $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
            $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $has_global_privileges = false;
            
            foreach ($grants as $grant) {
                if (strpos($grant, 'ALL PRIVILEGES') !== false || strpos($grant, 'SUPER') !== false) {
                    $has_global_privileges = true;
                    break;
                }
            }
            
            if ($has_global_privileges) {
                try {
                    $pdo->exec("SET GLOBAL time_zone = '-03:00'");
                    $mensagens[] = "Fuso horário global do MySQL ajustado para -03:00.";
                } catch (PDOException $e) {
                    $erros[] = "Não foi possível ajustar o fuso horário global do MySQL: " . $e->getMessage();
                }
            } else {
                $mensagens[] = "Você não tem privilégios para ajustar o fuso horário global do MySQL.";
                $mensagens[] = "Para ajustar permanentemente, peça ao administrador do banco de dados para executar: SET GLOBAL time_zone = '-03:00';";
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao ajustar o fuso horário do MySQL: " . $e->getMessage();
        }
        
        // 3. Adicionar configuração de fuso horário ao arquivo de configuração do sistema
        $config_file = 'includes/config.php';
        if (file_exists($config_file) && is_writable($config_file)) {
            $config_content = file_get_contents($config_file);
            
            // Verificar se já existe a configuração de fuso horário
            if (strpos($config_content, 'timezone') === false) {
                // Adicionar a configuração
                $config_content = str_replace("<?php", "<?php\n// Configuração de fuso horário\ndate_default_timezone_set('$timezone');", $config_content);
                
                if (file_put_contents($config_file, $config_content)) {
                    $mensagens[] = "Configuração de fuso horário adicionada ao arquivo config.php.";
                } else {
                    $erros[] = "Não foi possível modificar o arquivo config.php.";
                }
            } else {
                $mensagens[] = "Configuração de fuso horário já existe no arquivo config.php.";
            }
        } else {
            $erros[] = "Arquivo config.php não encontrado ou não é gravável.";
        }
        
        // 4. Adicionar configuração de fuso horário ao arquivo de conexão com o banco de dados
        $db_file = 'includes/db.php';
        if (file_exists($db_file) && is_writable($db_file)) {
            $db_content = file_get_contents($db_file);
            
            // Verificar se já existe a configuração de fuso horário
            if (strpos($db_content, 'SET time_zone') === false) {
                // Adicionar a configuração após a conexão PDO
                $db_content = str_replace('$pdo = new PDO', '$pdo = new PDO', $db_content);
                $db_content = str_replace('$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);', '$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);'."\n".'// Definir fuso horário do MySQL'."\n".'$pdo->exec("SET time_zone = \'-03:00\'");', $db_content);
                
                if (file_put_contents($db_file, $db_content)) {
                    $mensagens[] = "Configuração de fuso horário adicionada ao arquivo db.php.";
                } else {
                    $erros[] = "Não foi possível modificar o arquivo db.php.";
                }
            } else {
                $mensagens[] = "Configuração de fuso horário já existe no arquivo db.php.";
            }
        } else {
            $erros[] = "Arquivo db.php não encontrado ou não é gravável.";
        }
        
        // 5. Atualizar as funções de formatação de data e hora
        $functions_file = 'includes/functions.php';
        if (file_exists($functions_file) && is_writable($functions_file)) {
            $functions_content = file_get_contents($functions_file);
            
            // Atualizar a função formatarData
            $nova_funcao_data = "
function formatarData(\$data) {
    if (empty(\$data)) return '';
    \$timestamp = strtotime(\$data);
    return date('d/m/Y', \$timestamp);
}";
            
            // Atualizar a função formatarHora
            $nova_funcao_hora = "
function formatarHora(\$hora) {
    if (empty(\$hora)) return '';
    \$timestamp = strtotime(\$hora);
    return date('H:i', \$timestamp);
}";
            
            // Verificar se as funções existem e substituí-las
            if (strpos($functions_content, 'function formatarData') !== false) {
                $functions_content = preg_replace('/function formatarData.*?\{.*?\}/s', $nova_funcao_data, $functions_content);
                $mensagens[] = "Função formatarData atualizada.";
            } else {
                $functions_content .= $nova_funcao_data;
                $mensagens[] = "Função formatarData adicionada.";
            }
            
            if (strpos($functions_content, 'function formatarHora') !== false) {
                $functions_content = preg_replace('/function formatarHora.*?\{.*?\}/s', $nova_funcao_hora, $functions_content);
                $mensagens[] = "Função formatarHora atualizada.";
            } else {
                $functions_content .= $nova_funcao_hora;
                $mensagens[] = "Função formatarHora adicionada.";
            }
            
            if (file_put_contents($functions_file, $functions_content)) {
                $mensagens[] = "Funções de formatação de data e hora atualizadas.";
            } else {
                $erros[] = "Não foi possível modificar o arquivo functions.php.";
            }
        } else {
            $erros[] = "Arquivo functions.php não encontrado ou não é gravável.";
        }
        
        // 6. Adicionar função para obter a data atual no fuso horário correto
        if (file_exists($functions_file) && is_writable($functions_file)) {
            $functions_content = file_get_contents($functions_file);
            
            // Função para obter a data atual no fuso horário correto
            $nova_funcao_data_atual = "
function getDataAtual() {
    return date('Y-m-d');
}";
            
            // Função para obter a hora atual no fuso horário correto
            $nova_funcao_hora_atual = "
function getHoraAtual() {
    return date('H:i:s');
}";
            
            // Verificar se as funções existem e substituí-las
            if (strpos($functions_content, 'function getDataAtual') !== false) {
                $functions_content = preg_replace('/function getDataAtual.*?\{.*?\}/s', $nova_funcao_data_atual, $functions_content);
                $mensagens[] = "Função getDataAtual atualizada.";
            } else {
                $functions_content .= $nova_funcao_data_atual;
                $mensagens[] = "Função getDataAtual adicionada.";
            }
            
            if (strpos($functions_content, 'function getHoraAtual') !== false) {
                $functions_content = preg_replace('/function getHoraAtual.*?\{.*?\}/s', $nova_funcao_hora_atual, $functions_content);
                $mensagens[] = "Função getHoraAtual atualizada.";
            } else {
                $functions_content .= $nova_funcao_hora_atual;
                $mensagens[] = "Função getHoraAtual adicionada.";
            }
            
            if (file_put_contents($functions_file, $functions_content)) {
                $mensagens[] = "Funções de data e hora atuais adicionadas.";
            } else {
                $erros[] = "Não foi possível modificar o arquivo functions.php.";
            }
        }
        
    } catch (Exception $e) {
        $erros[] = "Erro ao ajustar o fuso horário: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_aplicacao']); ?> - Ajuste de Fuso Horário</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ajuste de Fuso Horário</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagens)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Ajustes realizados:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($mensagens as $mensagem): ?>
                                        <li><?php echo $mensagem; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Erros encontrados:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?php echo $erro; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($mensagens) && empty($erros)): ?>
                            <p>Este script ajustará o fuso horário do sistema para America/Sao_Paulo.</p>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Importante:</strong> Este processo tentará modificar arquivos de configuração do sistema. Certifique-se de que os arquivos tenham permissões de escrita.
                            </div>
                            
                            <form method="post" action="">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-clock me-2"></i>
                                    Ajustar Fuso Horário
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancelar
                                </a>
                            </form>
                        <?php else: ?>
                            <div class="mt-4">
                                <a href="importar_todos_compromissos.php" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>
                                    Prosseguir para Importação
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informações de Fuso Horário Atual</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Fuso horário do PHP:</strong> <?php echo date_default_timezone_get(); ?></p>
                                    <p><strong>Data e hora atual do PHP:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                                    
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT @@time_zone AS timezone, NOW() AS current_time");
                                        $mysql_info = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo "<p><strong>Fuso horário do MySQL:</strong> " . $mysql_info['timezone'] . "</p>";
                                        echo "<p><strong>Data e hora atual do MySQL:</strong> " . $mysql_info['current_time'] . "</p>";
                                    } catch (PDOException $e) {
                                        echo "<p><strong>Erro ao obter informações do MySQL:</strong> " . $e->getMessage() . "</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
