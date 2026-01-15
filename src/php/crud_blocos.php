<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_gestor();

include_once(__DIR__ . '/../config/conexao.php');
require_once(__DIR__ . '/media_manager.php'); // Gerenciador de mídias

// Ação de listar blocos (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'list') {
        $sql = "SELECT id, name, id_step, accept FROM blocos WHERE active = 1 ORDER BY name ASC";
        $result = $mysqli->query($sql);
        
        $blocos = [];
        while ($row = $result->fetch_assoc()) {
            $blocos[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $blocos]);
        exit;
    } elseif ($_GET['action'] === 'get' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM blocos WHERE id = ? AND active = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'bloco' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bloco não encontrado']);
        }
        exit;
    }
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
    
    error_log("CRUD Blocos - Action: " . $action);
    error_log("CRUD Blocos - Data: " . print_r($data, true));
    
    // Verificar permissões para ações de edição
    if ($action === 'save' || $action === 'update' || $action === 'delete') {
        // Apenas Admin (1) e Gestor (2) podem editar blocos/tutoriais
        if ($_SESSION['perfil'] != '1' && $_SESSION['perfil'] != '2') {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar tutoriais']);
            exit;
        }
    }
    
    if ($action === 'delete') {
        $id = intval($data['id']);
        
        // Deletar todas as mídias associadas ao tutorial antes de marcar como inativo
        deleteTutorialMedia($mysqli, $id);
        
        $sql = "UPDATE blocos SET active = 0 WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            error_log("Tutorial $id excluído e mídias deletadas");
            echo json_encode(['success' => true, 'message' => 'Bloco excluído com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir bloco']);
        }
        exit;
    }
    
    // Para save/update, validar campos
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $id_step = $data['id_step'] ?? '';
    $clear_rejection = $data['clear_rejection'] ?? false;
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
        exit;
    }
    
    if ($id) {
        // Preparar SQL baseado se deve limpar rejeição
        if ($clear_rejection) {
            $sql = "UPDATE blocos SET 
                    name = ?, 
                    id_step = ?,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    reject_date = NULL,
                    accept = 0,
                    last_modification = NOW()
                    WHERE id = ?";
        } else {
            $sql = "UPDATE blocos SET 
                    name = ?, 
                    id_step = ?,
                    last_modification = NOW()
                    WHERE id = ?";
        }
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $name, $id_step, $id);
        
        if ($stmt->execute()) {
            $message = $clear_rejection ? 'Tutorial corrigido e enviado para aprovação' : 'Bloco atualizado com sucesso';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar bloco']);
        }
    } else {
        $sql = "INSERT INTO blocos (name, id_step, active, accept, created_by) VALUES (?, ?, 1, 0, ?)";
        $user_id = $_SESSION['user_id'];
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $name, $id_step, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bloco criado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar bloco']);
        }
    }
    exit;
}
?>
