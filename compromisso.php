<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário tem permissão
requireRole(['admin', 'prefeito', 'vice']);

// Obter o usuário atual
$usuario = getCurrentUser();

// Verificar se é edição
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edicao = $id > 0;

// Obter dados do compromisso para edição
$compromisso = [];
if ($edicao) {
    $stmt = $pdo->prepare("SELECT * FROM compromissos WHERE id = ?");
    $stmt->execute([$id]);
    $compromisso = $stmt->fetch();

    // Verificar se o compromisso existe
    if (!$compromisso) {
        $_SESSION['error'] = "Compromisso não encontrado.";
        header('Location: dashboard.php');
        exit;
    }

    // Verificar permissão para editar
    if ($usuario['papel'] !== 'admin' && $compromisso['criado_por'] !== $usuario['id']) {
        $_SESSION['error'] = "Você não tem permissão para editar este compromisso.";
        header('Location: dashboard.php');
        exit;
    }
}

// Verificar se há uma data pré-selecionada na URL
$data_preset = isset($_GET['data']) ? $_GET['data'] : '';
if (!empty($data_preset) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_preset)) {
    // Converter para o formato do banco
    $data_preset = formatarDataBanco($data_preset);
}

// Obter usuários para compartilhamento
$usuarios_compartilhamento = [];
if ($usuario['papel'] === 'prefeito') {
    // Prefeito pode compartilhar com o vice
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE papel = 'vice'");
    $stmt->execute();
    $usuarios_compartilhamento = $stmt->fetchAll();
} elseif ($usuario['papel'] === 'vice') {
    // Vice pode compartilhar com o prefeito
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE papel = 'prefeito'");
    $stmt->execute();
    $usuarios_compartilhamento = $stmt->fetchAll();
} elseif ($usuario['papel'] === 'admin') {
    // Admin pode compartilhar com prefeito e vice
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE papel IN ('prefeito', 'vice')");
    $stmt->execute();
    $usuarios_compartilhamento = $stmt->fetchAll();
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $responsavel = $_POST['responsavel'] ?? '';
    $local = $_POST['local'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    $pessoa_contato = $_POST['pessoa_contato'] ?? '';
    $participantes = $_POST['participantes'] ?? '';
    $status = $_POST['status'] ?? 'pendente';
    $compartilhado = isset($_POST['compartilhado']) ? 1 : 0;
    $compartilhado_com = isset($_POST['compartilhado_com']) ? (int)$_POST['compartilhado_com'] : null;
    $publico = isset($_POST['publico']) ? 1 : 0;

    // Validar campos obrigatórios
    if (empty($titulo) || empty($data) || empty($hora) || empty($responsavel)) {
        $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        // Formatar data para o banco
        $data = formatarDataBanco($data);
        
        // Se compartilhado está marcado, mas nenhum usuário selecionado, definir como não compartilhado
        if ($compartilhado && empty($compartilhado_com)) {
            $compartilhado = 0;
            $compartilhado_com = null;
        }
        
        if ($edicao) {
            // Atualizar compromisso
            $stmt = $pdo->prepare("UPDATE compromissos SET 
                titulo = ?, data = ?, hora = ?, responsavel = ?, local = ?, 
                observacoes = ?, pessoa_contato = ?, participantes = ?, 
                status = ?, compartilhado = ?, compartilhado_com = ?, 
                status_compartilhamento = ?, data_compartilhamento = ?, publico = ? 
                WHERE id = ?");
            
            // Se o compartilhamento foi alterado, resetar o status
            $status_compartilhamento = 'pendente';
            $data_compartilhamento = date('Y-m-d H:i:s');
            
            // Se não está compartilhado, definir valores como null
            if (!$compartilhado) {
                $compartilhado_com = null;
                $status_compartilhamento = null;
                $data_compartilhamento = null;
            }
            
            $stmt->execute([
                $titulo, $data, $hora, $responsavel, $local, 
                $observacoes, $pessoa_contato, $participantes, 
                $status, $compartilhado, $compartilhado_com, 
                $status_compartilhamento, $data_compartilhamento, $publico, $id
            ]);
            
            $_SESSION['success'] = "Compromisso atualizado com sucesso.";
        } else {
            // Inserir novo compromisso
            $stmt = $pdo->prepare("INSERT INTO compromissos 
                (titulo, data, hora, responsavel, local, observacoes, pessoa_contato, 
                participantes, status, compartilhado, compartilhado_com, 
                status_compartilhamento, data_compartilhamento, publico, criado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Definir valores de compartilhamento
            $status_compartilhamento = $compartilhado ? 'pendente' : null;
            $data_compartilhamento = $compartilhado ? date('Y-m-d H:i:s') : null;
            
            $stmt->execute([
                $titulo, $data, $hora, $responsavel, $local, 
                $observacoes, $pessoa_contato, $participantes, 
                $status, $compartilhado, $compartilhado_com, 
                $status_compartilhamento, $data_compartilhamento, $publico, $usuario['id']
            ]);
            
            $_SESSION['success'] = "Compromisso criado com sucesso.";
        }
        
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = $edicao ? "Editar Compromisso" : "Novo Compromisso";
$headerButtons = '<a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>';
require_once 'includes/header.php';
?>

<!-- Adicionar CSS para os seletores de data e hora -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="titulo" class="form-label">Título *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required 
                                       value="<?php echo htmlspecialchars($compromisso['titulo'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="data" class="form-label">Data *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                    <input type="text" class="form-control" id="data" name="data" required 
                                           placeholder="DD/MM/AAAA" 
                                           value="<?php 
                                                if (isset($compromisso['data'])) {
                                                    echo formatarData($compromisso['data']);
                                                } elseif (!empty($data_preset)) {
                                                    echo formatarData($data_preset);
                                                }
                                           ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="hora" class="form-label">Hora *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                    <input type="text" class="form-control" id="hora" name="hora" required 
                                           value="<?php echo $compromisso['hora'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="responsavel" class="form-label">Responsável *</label>
                                <input type="text" class="form-control" id="responsavel" name="responsavel" required 
                                       value="<?php echo htmlspecialchars($compromisso['responsavel'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="local" class="form-label">Local</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" class="form-control" id="local" name="local" 
                                           value="<?php echo htmlspecialchars($compromisso['local'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="pessoa_contato" class="form-label">Pessoa de Contato</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="pessoa_contato" name="pessoa_contato" 
                                           value="<?php echo htmlspecialchars($compromisso['pessoa_contato'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="participantes" class="form-label">Participantes</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-people"></i></span>
                                    <input type="text" class="form-control" id="participantes" name="participantes" 
                                           value="<?php echo htmlspecialchars($compromisso['participantes'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($compromisso['observacoes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pendente" <?php echo (isset($compromisso['status']) && $compromisso['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="realizado" <?php echo (isset($compromisso['status']) && $compromisso['status'] === 'realizado') ? 'selected' : ''; ?>>Realizado</option>
                                </select>
                            </div>
                            
                            <?php if (!empty($usuarios_compartilhamento)): ?>
                            <div class="col-md-8">
                                <div class="card mt-2">
                                    <div class="card-header bg-light">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="compartilhado" name="compartilhado" 
                                                   <?php echo (isset($compromisso['compartilhado']) && $compromisso['compartilhado']) ? 'checked' : ''; ?>
                                                   onchange="toggleCompartilhamento()">
                                            <label class="form-check-label" for="compartilhado">
                                                Compartilhar este compromisso
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body" id="compartilhamentoOptions" style="display: <?php echo (isset($compromisso['compartilhado']) && $compromisso['compartilhado']) ? 'block' : 'none'; ?>;">
                                        <label class="form-label">Compartilhar com:</label>
                                        <select class="form-select" name="compartilhado_com" id="compartilhado_com">
                                            <option value="">Selecione...</option>
                                            <?php foreach ($usuarios_compartilhamento as $u): ?>
                                                <option value="<?php echo $u['id']; ?>" 
                                                        <?php echo (isset($compromisso['compartilhado_com']) && $compromisso['compartilhado_com'] == $u['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($u['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <?php if (isset($compromisso['status_compartilhamento'])): ?>
                                            <div class="mt-2">
                                                <p class="mb-1">Status do compartilhamento:</p>
                                                <?php if ($compromisso['status_compartilhamento'] === 'pendente'): ?>
                                                    <span class="badge bg-warning text-dark">Pendente de aprovação</span>
                                                <?php elseif ($compromisso['status_compartilhamento'] === 'aceito'): ?>
                                                    <span class="badge bg-success">Aceito</span>
                                                <?php elseif ($compromisso['status_compartilhamento'] === 'recusado'): ?>
                                                    <span class="badge bg-danger">Recusado</span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($compromisso['data_compartilhamento'])): ?>
                                                    <p class="text-muted small mt-1">
                                                        Compartilhado em: <?php echo formatarDataHora($compromisso['data_compartilhamento']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="publico" name="publico" 
                                   <?php echo (isset($compromisso['publico']) && $compromisso['publico']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="publico">
                                Exibir na Agenda Pública
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edicao ? 'Atualizar' : 'Salvar'; ?> Compromisso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os seletores de data e hora -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.pt-BR.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar o datepicker
    $('#data').datepicker({
        format: 'dd/mm/yyyy',
        language: 'pt-BR',
        autoclose: true,
        todayHighlight: true
    });
    
    // Inicializar o timepicker
    $('#hora').timepicker({
        showMeridian: false,
        defaultTime: '08:00',
        minuteStep: 5,
        showInputs: false,
        disableFocus: true
    });
});

// Função para mostrar/esconder opções de compartilhamento
function toggleCompartilhamento() {
    const compartilhado = document.getElementById('compartilhado').checked;
    const compartilhamentoOptions = document.getElementById('compartilhamentoOptions');
    
    if (compartilhado) {
        compartilhamentoOptions.style.display = 'block';
    } else {
        compartilhamentoOptions.style.display = 'none';
        document.getElementById('compartilhado_com').value = '';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
