<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_login();

require_once(__DIR__ . '/../config/conexao.php');

// Verificar se a conexão foi estabelecida
if (!isset($mysqli) || $mysqli === null || $mysqli->connect_errno) {
    error_log('Erro: Conexão com banco não disponível no user_actions.php');
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
            exit;
        }
        
        // Validar senha atual
        $sql = "SELECT password FROM usuarios WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        
        if (!$stmt) {
            error_log('Erro ao preparar query: ' . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação']);
            exit;
        }
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            exit;
        }
        
        // Verificar senha atual
        if (!password_verify($currentPassword, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
            exit;
        }
        
        // Validar nova senha
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres']);
            exit;
        }
        
        // Atualizar senha
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSql = "UPDATE usuarios SET password = ? WHERE id = ?";
        $updateStmt = $mysqli->prepare($updateSql);
        
        if (!$updateStmt) {
            error_log('Erro ao preparar update: ' . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação']);
            exit;
        }
        
        $updateStmt->bind_param('si', $hashedPassword, $userId);
        
        if ($updateStmt->execute()) {
            error_log("Senha alterada com sucesso para usuário ID: $userId");
            echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
        } else {
            error_log('Erro ao executar update: ' . $updateStmt->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha']);
        }
        
        $stmt->close();
        $updateStmt->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);
