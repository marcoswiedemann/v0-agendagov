<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário tem permissão
requireRole('admin');

// Obter configurações atuais
$config = getConfiguracoes();

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_aplicacao = $_POST['nome_aplicacao'] ?? '';
    $cor_primaria = $_POST['cor_primaria'] ?? '';
    $cor_secundaria = $_POST['cor_secundaria'] ?? '';
    
    // Validar campos obrigatórios
    if (empty($nome_aplicacao)) {
        $_SESSION['error'] = "Por favor, informe o nome da aplicação.";
    } elseif (!preg_match('/^#[a-f0-9]{6}$/i', $cor_primaria) || !preg_match('/^#[a-f0-9]{6}$/i', $cor_secundaria)) {
        $_SESSION['error'] = "As cores devem estar no formato hexadecimal (ex: #FF5500).";
    } else {
        // Processar upload de logo claro
        $logo_claro = $config['logo_claro'];
        if (isset($_FILES['logo_claro']) && $_FILES['logo_claro']['error'] === UPLOAD_ERR_OK) {
            $logo_claro = processarUploadLogo('logo_claro');
            if ($logo_claro === false) {
                // Erro já definido na função
                $logo_claro = $config['logo_claro'];
            }
        }
        
        // Processar upload de logo escuro
        $logo_escuro = $config['logo_escuro'];
        if (isset($_FILES['logo_escuro']) && $_FILES['logo_escuro']['error'] === UPLOAD_ERR_OK) {
            $logo_escuro = processarUploadLogo('logo_escuro');
            if ($logo_escuro === false) {
                // Erro já definido na função
                $logo_escuro = $config['logo_escuro'];
            }
        }
        
        // Verificar se é para resetar os logos
        if (isset($_POST['resetar_logo_claro'])) {
            if (!empty($config['logo_claro']) && file_exists($config['logo_claro'])) {
                unlink($config['logo_claro']);
            }
            $logo_claro = null;
        }
        
        if (isset($_POST['resetar_logo_escuro'])) {
            if (!empty($config['logo_escuro']) && file_exists($config['logo_escuro'])) {
                unlink($config['logo_escuro']);
            }
            $logo_escuro = null;
        }
        
        // Atualizar configurações
        $stmt = $pdo->prepare("UPDATE configuracoes SET 
                              nome_aplicacao = ?, 
                              cor_primaria = ?, 
                              cor_secundaria = ?, 
                              logo_claro = ?, 
                              logo_escuro = ? 
                              WHERE id = 1");
        
        $stmt->execute([
            $nome_aplicacao,
            $cor_primaria,
            $cor_secundaria,
            $logo_claro,
            $logo_escuro
        ]);
        
        $_SESSION['success'] = "Configurações atualizadas com sucesso.";
        header('Location: configuracoes.php');
        exit;
    }
}

// Função para processar upload de logo
function processarUploadLogo($campo) {
    $diretorio_upload = 'uploads/';
    
    // Criar diretório se não existir
    if (!file_exists($diretorio_upload)) {
        mkdir($diretorio_upload, 0755, true);
    }
    
    $arquivo_temp = $_FILES[$campo]['tmp_name'];
    $nome_arquivo = $_FILES[$campo]['name'];
    $tipo_arquivo = $_FILES[$campo]['type'];
    
    // Verificar tipo de arquivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($tipo_arquivo, $tipos_permitidos)) {
        $_SESSION['error'] = "Tipo de arquivo não permitido. Apenas JPG, PNG e GIF são aceitos.";
        return false;
    }
    
    // Gerar nome único para o arquivo
    $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
    $novo_nome = uniqid('logo_') . '.' . $extensao;
    $caminho_destino = $diretorio_upload . $novo_nome;
    
    // Mover arquivo para o diretório de destino
    if (move_uploaded_file($arquivo_temp, $caminho_destino)) {
        return $caminho_destino;
    } else {
        $_SESSION['error'] = "Erro ao fazer upload do arquivo.";
        return false;
    }
}

$pageTitle = "Configurações do Sistema";
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="mb-4">
                            <h5>Informações Gerais</h5>
                            <div class="mb-3">
                                <label for="nome_aplicacao" class="form-label">Nome da Aplicação *</label>
                                <input type="text" class="form-control" id="nome_aplicacao" name="nome_aplicacao" required 
                                       value="<?php echo htmlspecialchars($config['nome_aplicacao']); ?>">
                                <div class="form-text">Este nome será exibido no título da página e no cabeçalho.</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Cores</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cor_primaria" class="form-label">Cor Primária *</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="cor_primaria_picker" 
                                                   value="<?php echo $config['cor_primaria']; ?>" onchange="document.getElementById('cor_primaria').value = this.value;">
                                            <input type="text" class="form-control" id="cor_primaria" name="cor_primaria" required 
                                                   value="<?php echo $config['cor_primaria']; ?>" pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                        <div class="form-text">Formato hexadecimal (ex: #007BFF).</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cor_secundaria" class="form-label">Cor Secundária *</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="cor_secundaria_picker" 
                                                   value="<?php echo $config['cor_secundaria']; ?>" onchange="document.getElementById('cor_secundaria').value = this.value;">
                                            <input type="text" class="form-control" id="cor_secundaria" name="cor_secundaria" required 
                                                   value="<?php echo $config['cor_secundaria']; ?>" pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                        <div class="form-text">Formato hexadecimal (ex: #6C757D).</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 p-3 rounded" style="background-color: <?php echo $config['cor_primaria']; ?>; color: white;">
                                Exemplo de cor primária
                            </div>
                            <div class="mt-2 p-3 rounded" style="background-color: <?php echo $config['cor_secundaria']; ?>; color: white;">
                                Exemplo de cor secundária
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Logotipos</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="logo_claro" class="form-label">Logo (Tema Claro)</label>
                                        <input type="file" class="form-control" id="logo_claro" name="logo_claro" accept="image/*">
                                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho recomendado: 200x80px.</div>
                                        
                                        <?php if (!empty($config['logo_claro'])): ?>
                                            <div class="mt-2 p-3 bg-light rounded text-center">
                                                <img src="<?php echo $config['logo_claro']; ?>" alt="Logo Claro" class="img-fluid" style="max-height: 80px;">
                                                <div class="mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="resetar_logo_claro" name="resetar_logo_claro">
                                                        <label class="form-check-label" for="resetar_logo_claro">
                                                            Remover logo
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="logo_escuro" class="form-label">Logo (Tema Escuro)</label>
                                        <input type="file" class="form-control" id="logo_escuro" name="logo_escuro" accept="image/*">
                                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho recomendado: 200x80px.</div>
                                        
                                        <?php if (!empty($config['logo_escuro'])): ?>
                                            <div class="mt-2 p-3 bg-dark rounded text-center">
                                                <img src="<?php echo $config['logo_escuro']; ?>" alt="Logo Escuro" class="img-fluid" style="max-height: 80px;">
                                                <div class="mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="resetar_logo_escuro" name="resetar_logo_escuro">
                                                        <label class="form-check-label text-white" for="resetar_logo_escuro">
                                                            Remover logo
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
