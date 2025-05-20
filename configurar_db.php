<?php
// Iniciar a sessão
session_start();

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Mensagens
$mensagem = '';
$erro = '';
$sucesso = false;

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter as credenciais do formulário
    $db_host = isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost';
    $db_name = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';
    $db_user = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
    $db_pass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
    
    // Validar os campos
    if (empty($db_name) || empty($db_user)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Testar a conexão
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Conexão bem-sucedida, salvar as credenciais
            $config_content = "<?php
// Arquivo de configuração do banco de dados
// Gerado automaticamente em " . date('Y-m-d H:i:s') . "

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Configurações do banco de dados
\$db_host = '$db_host';
\$db_name = '$db_name';
\$db_user = '$db_user';
\$db_pass = '$db_pass';

try {
    // Conectar ao banco de dados
    \$pdo = new PDO(\"mysql:host=\$db_host;dbname=\$db_name;charset=utf8mb4\", \$db_user, \$db_pass);
    
    // Configurar o PDO para lançar exceções em caso de erro
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Definir o fuso horário do MySQL para -03:00 (America/Sao_Paulo)
    \$pdo->exec(\"SET time_zone = '-03:00'\");
    
} catch (PDOException \$e) {
    // Em caso de erro na conexão
    die(\"Erro na conexão com o banco de dados: \" . \$e->getMessage());
}
";
            
            // Salvar o arquivo de configuração
            if (file_put_contents('includes/db_config.php', $config_content)) {
                $sucesso = true;
                $mensagem = 'Configuração do banco de dados salva com sucesso!';
                
                // Salvar também em uma sessão para uso imediato
                $_SESSION['db_config'] = [
                    'host' => $db_host,
                    'name' => $db_name,
                    'user' => $db_user,
                    'pass' => $db_pass
                ];
            } else {
                $erro = 'Não foi possível salvar o arquivo de configuração. Verifique as permissões de escrita.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro na conexão com o banco de dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Banco de Dados</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Configurar Banco de Dados</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($sucesso): ?>
                            <div class="alert alert-success">
                                <?php echo $mensagem; ?>
                            </div>
                            <div class="mb-4">
                                <p>O que você deseja fazer agora?</p>
                                <a href="agenda_config.php" class="btn btn-primary">Ver Agenda Pública</a>
                                <a href="index.php" class="btn btn-secondary ms-2">Ir para Página Inicial</a>
                            </div>
                        <?php else: ?>
                            <?php if ($erro): ?>
                                <div class="alert alert-danger">
                                    <?php echo $erro; ?>
                                </div>
                            <?php endif; ?>
                            
                            <p>Por favor, insira as credenciais do seu banco de dados:</p>
                            
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Host do Banco de Dados</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                    <div class="form-text">Geralmente é "localhost"</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Nome do Banco de Dados</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" value="u414602466_prefeitoagenda" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Usuário do Banco de Dados</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Senha do Banco de Dados</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
