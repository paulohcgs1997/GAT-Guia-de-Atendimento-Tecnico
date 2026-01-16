<?php
session_start();
require_once(__DIR__ . '/../config/conexao.php');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$action = $_POST['action'] ?? 'update';
$user_id = $_SESSION['user_id'];

// ========== BUSCAR DADOS DO PERFIL ==========
if ($action === 'get') {
    $stmt = $mysqli->prepare("SELECT id, user, nome_completo, email, telefone, foto, perfil, departamento FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    }
    exit;
}

// ========== ATUALIZAR PERFIL ==========
if ($action === 'update') {
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validações
    if (empty($nome_completo)) {
        echo json_encode(['success' => false, 'message' => 'Nome completo é obrigatório']);
        exit;
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email é obrigatório']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    // Verificar se email já existe (exceto o próprio usuário)
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param('si', $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este email já está em uso']);
        exit;
    }
    
    // Atualizar dados
    if (!empty($password)) {
        // Atualizar com nova senha
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, telefone = ?, password = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $nome_completo, $email, $telefone, $password_hash, $user_id);
    } else {
        // Atualizar sem alterar senha
        $stmt = $mysqli->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, telefone = ? WHERE id = ?");
        $stmt->bind_param('sssi', $nome_completo, $email, $telefone, $user_id);
    }
    
    if ($stmt->execute()) {
        // Atualizar sessão
        $_SESSION['user_nome'] = $nome_completo;
        $_SESSION['user_email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil']);
    }
    exit;
}

// ========== UPLOAD DE FOTO ==========
if ($action === 'upload_foto') {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erro no upload da imagem']);
        exit;
    }
    
    $file = $_FILES['foto'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagem não permitido. Use JPG, PNG, GIF ou WEBP']);
        exit;
    }
    
    // Limite de 2MB
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Imagem muito grande. Máximo 2MB']);
        exit;
    }
    
    // Criar diretório se não existir
    $upload_dir = __DIR__ . '/../../uploads/avatars/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Remover foto antiga se existir
    $stmt = $mysqli->prepare("SELECT foto FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $old_file = __DIR__ . '/../../' . $row['foto'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
    }
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Salvar caminho no banco
        $foto_path = 'uploads/avatars/' . $filename;
        $stmt = $mysqli->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
        $stmt->bind_param('si', $foto_path, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_foto'] = $foto_path;
            echo json_encode(['success' => true, 'message' => 'Foto atualizada com sucesso!', 'foto' => $foto_path]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar foto no banco de dados']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo']);
    }
    exit;
}

// ========== REMOVER FOTO ==========
if ($action === 'remove_foto') {
    $stmt = $mysqli->prepare("SELECT foto FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $old_file = __DIR__ . '/../../' . $row['foto'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
    }
    
    $stmt = $mysqli->prepare("UPDATE usuarios SET foto = NULL WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    
    if ($stmt->execute()) {
        unset($_SESSION['user_foto']);
        echo json_encode(['success' => true, 'message' => 'Foto removida com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover foto']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);
