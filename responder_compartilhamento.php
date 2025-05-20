<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Token CSRF inválido');
}

// Obter parâmetros
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$resposta = isset($_POST['resposta']) ? $_POST['resposta'] : '';

// Validar parâmetros
if ($id <= 0 || !in_array($resposta, ['aceito', 'recusado'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parâmetros inválidos');
}

// Obter o usuário atual
$usuario = getCurrentUser();
$usuario_id = $usuario['id'];

// Verificar se o compromisso existe e foi compartilhado com o usuário atual
$stmt = $pdo->prepare("SELECT * FROM compromissos WHERE id = ? AND compartilhado_com = ? AND status_compartilhamento = 'pendente'");
$stmt->execute([$id, $usuario_id]);
$compromisso = $stmt->fetch();

if (!$compromisso) {
    header('HTTP/1.1 404 Not Found');
    exit('Compromisso não encontrado ou não compartilhado com você');
}

// Atualizar o status do compartilhamento
$stmt = $pdo->prepare("UPDATE compromissos SET status_compartilhamento = ? WHERE id = ?");
$stmt->execute([$resposta, $id]);

// Registrar a ação
logActivity(
    'compartilhamento_' . $resposta, 
    "Compromisso ID $id " . ($resposta === 'aceito' ? 'aceito' : 'recusado') . " por " . $usuario['nome']
);

// Responder com sucesso
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Resposta registrada com sucesso']);
?>
