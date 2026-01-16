<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// Validações
if (empty($current_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres']);
    exit;
}

if ($new_password === 'Mudar@123') {
    echo json_encode(['success' => false, 'message' => 'Você não pode usar a senha padrão como sua nova senha']);
    exit;
}

// Buscar usuário
$sql = "SELECT password FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    exit;
}

$userData = $result->fetch_assoc();

// Verificar senha atual
if (!password_verify($current_password, $userData['password'])) {
    echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
    exit;
}

// Atualizar senha
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Verificar se coluna force_password_change existe
$checkColumn = $mysqli->query("SHOW COLUMNS FROM usuarios LIKE 'force_password_change'");
$hasColumn = $checkColumn->num_rows > 0;

if ($hasColumn) {
    $updateSql = "UPDATE usuarios SET password = ?, force_password_change = 0 WHERE id = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param('si', $new_password_hash, $user_id);
} else {
    // Fallback para BD sem a coluna
    $updateSql = "UPDATE usuarios SET password = ? WHERE id = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param('si', $new_password_hash, $user_id);
}

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha: ' . $mysqli->error]);
}
?>
