<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_gestor();

include_once(__DIR__ . '/../config/conexao.php');
require_once(__DIR__ . '/media_manager.php'); // Gerenciador de mídias

// GET para buscar serviço específico
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM services WHERE id = ? AND active = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'service' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado']);
    }
    exit;
}

// Processar dados do POST - aceitar tanto JSON quanto form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tentar ler como JSON primeiro
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Se não for JSON válido, usar $_POST
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        $data = $_POST;
    }
    
    $action = $data['action'] ?? 'save';

// Verificar permissões para ações de edição
if ($action === 'save' || $action === 'update' || $action === 'delete' || $action === 'clone_service') {
    // Apenas Admin (1) e Gestor (2) podem editar serviços
    if ($_SESSION['perfil'] != '1' && $_SESSION['perfil'] != '2') {
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar serviços']);
        exit;
    }
}

if ($action === 'approve') {
    // Verificar se usuário é admin
    if ($_SESSION['perfil'] != '1') {
        echo json_encode(['success' => false, 'message' => 'Apenas administradores podem aprovar serviços']);
        exit;
    }
    
    $id = intval($data['id']);
    
    // Atualizar accept e status se o campo existir
    $sql = "UPDATE services SET accept = 1, last_modification = NOW()";
    
    // Verificar se campo status existe
    $check_status = $mysqli->query("SHOW COLUMNS FROM services LIKE 'status'");
    if ($check_status->num_rows > 0) {
        $sql .= ", status = 'approved'";
    }
    
    $sql .= " WHERE id = ? AND active = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Serviço aprovado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao aprovar serviço']);
    }
    exit;
}

// Clonar serviço aprovado para edição
if ($action === 'clone_service') {
    try {
        $mysqli->begin_transaction();
        
        $originalId = intval($data['service_id']);
        
        // Buscar dados do serviço original
        $sql = "SELECT * FROM services WHERE id = ? AND active = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $originalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $original = $result->fetch_assoc();
        
        if (!$original) {
            throw new Exception('Serviço não encontrado');
        }
        
        // Criar o clone
        $sql = "INSERT INTO services (name, description, departamento, blocos, word_keys, accept, active, original_id, is_clone) 
                VALUES (?, ?, ?, ?, ?, 0, 1, ?, 1)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssissi', 
            $original['name'],
            $original['description'],
            $original['departamento'],
            $original['blocos'],
            $original['word_keys'],
            $originalId
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao criar clone: " . $stmt->error);
        }
        
        $cloneId = $mysqli->insert_id;
        
        $mysqli->commit();
        echo json_encode([
            'success' => true, 
            'clone_id' => $cloneId,
            'message' => 'Clone criado. Suas alterações ficarão pendentes até aprovação.'
        ]);
        exit;
        
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar clone: ' . $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'delete') {
    $id = intval($data['id']);
    
    // Soft delete - apenas marca como inativo
    $sql = "UPDATE services SET active = 0 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Serviço excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir serviço']);
    }
    exit;
}

// Salvar/Atualizar
$id = $data['id'] ?? null;
$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$departamento = $data['departamento'] ?? '';
$word_keys = $data['word_keys'] ?? '';
$blocos = isset($data['blocos']) ? (is_array($data['blocos']) ? implode(',', $data['blocos']) : $data['blocos']) : '';
$clear_rejection = $data['clear_rejection'] ?? false;

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
    exit;
}

if ($id) {
    // Preparar SQL baseado se deve limpar rejeição
    if ($clear_rejection) {
        // Verificar se campo status existe
        $check_status_field = $mysqli->query("SHOW COLUMNS FROM services LIKE 'status'");
        $status_exists = $check_status_field->num_rows > 0;
        
        if ($status_exists) {
            $sql = "UPDATE services SET 
                    name = ?, 
                    description = ?, 
                    departamento = ?, 
                    blocos = ?, 
                    word_keys = ?,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    reject_date = NULL,
                    accept = 0,
                    status = 'draft',
                    last_modification = NOW()
                    WHERE id = ?";
        } else {
            $sql = "UPDATE services SET 
                    name = ?, 
                    description = ?, 
                    departamento = ?, 
                    blocos = ?, 
                    word_keys = ?,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    reject_date = NULL,
                    accept = 0,
                    last_modification = NOW()
                    WHERE id = ?";
        }
    } else {
        $sql = "UPDATE services SET 
                name = ?, 
                description = ?, 
                departamento = ?, 
                blocos = ?, 
                word_keys = ?,
                last_modification = NOW()
                WHERE id = ?";
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssissi', $name, $description, $departamento, $blocos, $word_keys, $id);
    
    if ($stmt->execute()) {
        $message = $clear_rejection ? 'Serviço corrigido e salvo como rascunho' : 'Serviço atualizado com sucesso';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar serviço']);
    }
} else {
    // Criar
    $user_id = $_SESSION['user_id'];
    
    // Verificar se campo status existe
    $check_status = $mysqli->query("SHOW COLUMNS FROM services LIKE 'status'");
    $has_status = $check_status->num_rows > 0;
    
    if ($has_status) {
        $sql = "INSERT INTO services (name, description, departamento, blocos, word_keys, active, accept, created_by, status) 
                VALUES (?, ?, ?, ?, ?, 1, 0, ?, 'draft')";
    } else {
        $sql = "INSERT INTO services (name, description, departamento, blocos, word_keys, active, accept, created_by) 
                VALUES (?, ?, ?, ?, ?, 1, 0, ?)";
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssissi', $name, $description, $departamento, $blocos, $word_keys, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Serviço criado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar serviço']);
    }
}
    exit;
}
?>
