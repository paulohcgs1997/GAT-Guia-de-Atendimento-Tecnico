<?php
session_start();
include_once(__DIR__ . '/../config/conexao.php');

// Verificar se está logado e se é admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Apenas admins podem gerenciar usuários
if ($_SESSION['perfil'] != '1') {
    echo json_encode(['success' => false, 'message' => 'Apenas administradores podem gerenciar usuários']);
    exit;
}

$action = $_POST['action'] ?? 'save';

// ========== APROVAR USUÁRIOS EM LOTE ==========
if ($action === 'approve_batch') {
    $user_ids = json_decode($_POST['user_ids'] ?? '[]', true);
    
    if (empty($user_ids) || !is_array($user_ids)) {
        echo json_encode(['success' => false, 'erro' => 'Nenhum usuário selecionado']);
        exit;
    }
    
    // Verificar se existe coluna 'status'
    $sql_check = "SHOW COLUMNS FROM usuarios LIKE 'status'";
    $result_check = $mysqli->query($sql_check);
    $has_status = ($result_check->num_rows > 0);
    
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    
    if ($has_status) {
        $sql = "UPDATE usuarios SET status = 'approved', active = 1 WHERE id IN ($placeholders) AND status = 'pending'";
    } else {
        $sql = "UPDATE usuarios SET active = 1 WHERE id IN ($placeholders)";
    }
    
    $stmt = $mysqli->prepare($sql);
    $types = str_repeat('i', count($user_ids));
    $stmt->bind_param($types, ...$user_ids);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo json_encode(['success' => true, 'message' => "$affected usuário(s) aprovado(s) com sucesso!"]);
    } else {
        echo json_encode(['success' => false, 'erro' => 'Erro ao aprovar usuários: ' . $stmt->error]);
    }
    exit;
}

// ========== APROVAR USUÁRIO INDIVIDUAL ==========
if ($action === 'approve') {
    $id = intval($_POST['id']);
    
    // Verificar se existe coluna 'status'
    $sql_check = "SHOW COLUMNS FROM usuarios LIKE 'status'";
    $result_check = $mysqli->query($sql_check);
    $has_status = ($result_check->num_rows > 0);
    
    if ($has_status) {
        $sql = "UPDATE usuarios SET status = 'approved', active = 1 WHERE id = ? AND status = 'pending'";
    } else {
        $sql = "UPDATE usuarios SET active = 1 WHERE id = ?";
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuário aprovado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'erro' => 'Erro ao aprovar usuário ou usuário não está pendente']);
    }
    exit;
}

// ========== REJEITAR USUÁRIO ==========
if ($action === 'reject') {
    $id = intval($_POST['id']);
    $motivo = $_POST['motivo'] ?? '';
    
    // Verificar se existe coluna 'status'
    $sql_check = "SHOW COLUMNS FROM usuarios LIKE 'status'";
    $result_check = $mysqli->query($sql_check);
    $has_status = ($result_check->num_rows > 0);
    
    if ($has_status) {
        $sql = "UPDATE usuarios SET status = 'rejected' WHERE id = ? AND status = 'pending'";
    } else {
        // Se não tem status, apenas deleta o usuário
        $sql = "DELETE FROM usuarios WHERE id = ? AND active = 0";
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuário rejeitado']);
    } else {
        echo json_encode(['success' => false, 'erro' => 'Erro ao rejeitar usuário']);
    }
    exit;
}

// ========== ALTERNAR STATUS DO USUÁRIO ==========
if ($action === 'toggle_status') {
    $id = intval($_POST['id']);
    $newStatus = intval($_POST['status']);
    
    // Não permitir desativar o próprio usuário
    if ($newStatus == 0 && $id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Você não pode desativar seu próprio usuário']);
        exit;
    }
    
    $sql = "UPDATE usuarios SET active = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $newStatus, $id);
    
    if ($stmt->execute()) {
        $message = $newStatus ? 'Usuário reativado com sucesso' : 'Usuário desativado com sucesso';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar status do usuário']);
    }
    exit;
}

// ========== DELETAR (DESATIVAR) USUÁRIO ==========
if ($action === 'delete') {
    $id = intval($_POST['id']);
    
    // Não permitir desativar o próprio usuário
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Você não pode desativar seu próprio usuário']);
        exit;
    }
    
    // Soft delete - apenas marca como inativo
    $sql = "UPDATE usuarios SET active = 0 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuário desativado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao desativar usuário']);
    }
    exit;
}

// ========== SALVAR/ATUALIZAR USUÁRIO ==========
$id = $_POST['id'] ?? null;
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$perfil = intval($_POST['perfil']);
$departamento = !empty($_POST['departamento']) ? intval($_POST['departamento']) : null;

// Validações
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Nome de usuário é obrigatório']);
    exit;
}

if (empty($perfil)) {
    echo json_encode(['success' => false, 'message' => 'Perfil é obrigatório']);
    exit;
}

// Validar departamento para perfil 3 (Departamento)
if ($perfil == 3 && empty($departamento)) {
    echo json_encode(['success' => false, 'message' => 'Departamento é obrigatório para perfil Departamento']);
    exit;
}

if ($id) {
    // ========== ATUALIZAR USUÁRIO ==========
    
    // Verificar se username já existe em outro usuário
    $sqlCheck = "SELECT id FROM usuarios WHERE user = ? AND id != ?";
    $stmtCheck = $mysqli->prepare($sqlCheck);
    $stmtCheck->bind_param('si', $username, $id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este nome de usuário já está em uso']);
        exit;
    }
    
    // Se senha foi fornecida, atualizar com senha
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres']);
            exit;
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE usuarios SET 
                user = ?, 
                password = ?, 
                perfil = ?,
                departamento = ?
                WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssiii', $username, $passwordHash, $perfil, $departamento, $id);
    } else {
        // Atualizar sem senha
        $sql = "UPDATE usuarios SET 
                user = ?, 
                perfil = ?,
                departamento = ?
                WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('siii', $username, $perfil, $departamento, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário']);
    }
    
} else {
    // ========== CRIAR NOVO USUÁRIO ==========
    
    // Definir senha padrão se não foi fornecida
    $defaultPassword = 'Mudar@123';
    $finalPassword = !empty($password) ? $password : $defaultPassword;
    $forcePasswordChange = empty($password) ? 1 : 0; // Força troca se usou senha padrão
    
    if (strlen($finalPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres']);
        exit;
    }
    
    // Verificar se username já existe
    $sqlCheck = "SELECT id FROM usuarios WHERE user = ?";
    $stmtCheck = $mysqli->prepare($sqlCheck);
    $stmtCheck->bind_param('s', $username);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este nome de usuário já está em uso']);
        exit;
    }
    
    $passwordHash = password_hash($finalPassword, PASSWORD_DEFAULT);
    
    // Verificar se coluna force_password_change existe
    $checkColumn = $mysqli->query("SHOW COLUMNS FROM usuarios LIKE 'force_password_change'");
    $hasColumn = $checkColumn->num_rows > 0;
    
    if ($hasColumn) {
        $sql = "INSERT INTO usuarios (user, password, perfil, departamento, active, force_password_change) 
                VALUES (?, ?, ?, ?, 1, ?)";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssiii', $username, $passwordHash, $perfil, $departamento, $forcePasswordChange);
    } else {
        // Fallback para BD sem a coluna
        $sql = "INSERT INTO usuarios (user, password, perfil, departamento, active) 
                VALUES (?, ?, ?, ?, 1)";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssii', $username, $passwordHash, $perfil, $departamento);
    }
    
    if ($stmt->execute()) {
        $message = empty($password) 
            ? "Usuário criado com sucesso! Senha padrão: $defaultPassword (será solicitada alteração no primeiro login)" 
            : 'Usuário criado com sucesso';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário: ' . $stmt->error]);
    }
}
?>
