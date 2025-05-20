<?php
// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Informações básicas
echo "<h2>Informações do Sistema</h2>";
echo "<p>Fuso horário do PHP: " . date_default_timezone_get() . "</p>";
echo "<p>Data e hora atual: " . date('Y-m-d H:i:s') . "</p>";

// Conexão com o banco de dados
$db_host = 'localhost';
$db_name = 'u414602466_prefeitoagenda';
$db_user = 'seu_usuario';
$db_pass = 'sua_senha';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>Conexão com o banco de dados estabelecida com sucesso!</p>";
    
    // Definir o fuso horário do MySQL
    $pdo->exec("SET time_zone = '-03:00'");
    echo "<p>Fuso horário do MySQL definido para -03:00</p>";
    
    // Verificar o fuso horário do MySQL
    $stmt = $pdo->query("SELECT @@time_zone AS timezone, NOW() AS current_time");
    $mysql_info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Fuso horário do MySQL: " . $mysql_info['timezone'] . "</p>";
    echo "<p>Data e hora do MySQL: " . $mysql_info['current_time'] . "</p>";
    
    // Verificar a tabela compromissos
    $stmt = $pdo->query("SHOW TABLES LIKE 'compromissos'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Tabela compromissos existe!</p>";
        
        // Contar compromissos
        $stmt = $pdo->query("SELECT COUNT(*) FROM compromissos");
        $total = $stmt->fetchColumn();
        echo "<p>Total de compromissos: $total</p>";
        
        // Verificar compromissos para hoje
        $data_atual = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM compromissos WHERE data = ?");
        $stmt->execute([$data_atual]);
        $hoje = $stmt->fetchColumn();
        echo "<p>Compromissos para hoje ($data_atual): $hoje</p>";
    } else {
        echo "<p style='color:red'>Tabela compromissos não existe!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro na conexão com o banco de dados: " . $e->getMessage() . "</p>";
}

// Formulário para ações simples
echo "<h2>Ações de Correção</h2>";
echo "<form method='post' action=''>";

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        
        if ($acao === 'timezone') {
            try {
                $pdo->exec("SET GLOBAL time_zone = '-03:00'");
                $pdo->exec("SET time_zone = '-03:00'");
                echo "<p style='color:green'>Fuso horário do MySQL corrigido!</p>";
            } catch (Exception $e) {
                echo "<p style='color:red'>Erro ao corrigir fuso horário: " . $e->getMessage() . "</p>";
            }
        }
        
        if ($acao === 'criar_compromissos') {
            try {
                // Obter ID do prefeito
                $stmt = $pdo->query("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
                $id_prefeito = $stmt->fetchColumn();
                
                if (!$id_prefeito) {
                    // Criar usuário prefeito
                    $senha_hash = password_hash('prefeito123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute(['Prefeito', 'prefeito@exemplo.com', $senha_hash, 'prefeito', 1]);
                    $id_prefeito = $pdo->lastInsertId();
                    echo "<p>Usuário prefeito criado com ID: $id_prefeito</p>";
                }
                
                // Data atual
                $data_hoje = date('Y-m-d');
                
                // Inserir compromissos simples
                $stmt = $pdo->prepare("INSERT INTO compromissos (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    'REUNIÃO MATINAL', 
                    $data_hoje, 
                    '08:00:00', 
                    'Gabinete do Prefeito', 
                    'Prefeito', 
                    'Reunião com a equipe', 
                    'pendente', 
                    1, 
                    $id_prefeito
                ]);
                
                $stmt->execute([
                    'ALMOÇO COM EMPRESÁRIOS', 
                    $data_hoje, 
                    '12:00:00', 
                    'Restaurante Central', 
                    'Prefeito', 
                    'Discussão sobre novos investimentos', 
                    'pendente', 
                    1, 
                    $id_prefeito
                ]);
                
                echo "<p style='color:green'>2 compromissos criados com sucesso!</p>";
            } catch (Exception $e) {
                echo "<p style='color:red'>Erro ao criar compromissos: " . $e->getMessage() . "</p>";
            }
        }
    }
}

echo "<button type='submit' name='acao' value='timezone' style='margin-right: 10px;'>Corrigir Fuso Horário</button>";
echo "<button type='submit' name='acao' value='criar_compromissos'>Criar Compromissos de Teste</button>";
echo "</form>";

// Links úteis
echo "<h2>Links Úteis</h2>";
echo "<ul>";
echo "<li><a href='index.php'>Página Inicial</a></li>";
echo "<li><a href='agenda_publica.php'>Agenda Pública</a></li>";
echo "</ul>";
?>
