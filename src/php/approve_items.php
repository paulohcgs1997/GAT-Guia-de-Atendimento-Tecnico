<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_approver();

include_once(__DIR__ . '/../config/conexao.php');
require_once(__DIR__ . '/media_manager.php');

header('Content-Type: application/json');

// Garantir que user_id esteja disponível
if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$type = $data['type'] ?? '';
$id = $data['id'] ?? 0;

// Log para debug
error_log("Approve Items - Action: $action, Type: $type, ID: $id, User ID: " . ($_SESSION['user_id'] ?? 'N/A'));

// Para approve_service_complete, não precisa de validação de type e id
if ($action === 'approve_service_complete') {
    $service_id = $data['service_id'] ?? 0;
    
    if (!$service_id) {
        echo json_encode(['success' => false, 'message' => 'ID do serviço inválido']);
        exit;
    }
    
    // Buscar dados do serviço
    $stmt = $mysqli->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servico = $result->fetch_assoc();
    
    if (!$servico) {
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado']);
        exit;
    }
    
    $mysqli->begin_transaction();
    
    try {
        $tutorialsApproved = 0;
        
        // Se for clone, atualizar o original
        if ($servico['is_clone'] == 1 && $servico['original_id']) {
            // Atualizar serviço original
            $update_stmt = $mysqli->prepare("UPDATE services SET 
                name = ?,
                keywords = ?,
                accept = 1,
                rejection_reason = NULL,
                rejected_by = NULL,
                reject_date = NULL,
                last_modification = NOW()
                WHERE id = ?");
            
            $update_stmt->bind_param("ssi", 
                $servico['name'],
                $servico['keywords'],
                $servico['original_id']
            );
            $update_stmt->execute();
            
            // Deletar clone do serviço
            $delete_stmt = $mysqli->prepare("DELETE FROM services WHERE id = ?");
            $delete_stmt->bind_param("i", $service_id);
            $delete_stmt->execute();
            
            $service_id_to_use = $servico['original_id'];
        } else {
            // Serviço novo, apenas aprovar
            $stmt = $mysqli->prepare("UPDATE services SET accept = 1, rejection_reason = NULL, rejected_by = NULL, reject_date = NULL, last_modification = NOW() WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            
            $service_id_to_use = $service_id;
        }
        
        // Buscar todos os tutoriais vinculados ao serviço usando o campo blocos
        // O campo blocos contém IDs separados por vírgula
        $tutorial_ids = [];
        if (!empty($servico['blocos'])) {
            $tutorial_ids = explode(',', $servico['blocos']);
            $tutorial_ids = array_filter(array_map('intval', $tutorial_ids));
        }
        
        // Aprovar cada tutorial
        foreach ($tutorial_ids as $tutorial_id) {
            $tutoriais_stmt = $mysqli->prepare("SELECT id, is_clone, original_id, name, id_step FROM blocos WHERE id = ? AND active = 1");
            $tutoriais_stmt->bind_param("i", $tutorial_id);
            $tutoriais_stmt->execute();
            $tutoriais_result = $tutoriais_stmt->get_result();
            $tutorial = $tutoriais_result->fetch_assoc();
            
            if (!$tutorial) {
                continue; // Tutorial não encontrado, pular
            }
            
            if ($tutorial['is_clone'] == 1 && $tutorial['original_id']) {
                // Atualizar tutorial original
                $update_tut = $mysqli->prepare("UPDATE blocos SET 
                    name = ?,
                    id_step = ?,
                    accept = 1,
                    rejection_reason = NULL,
                    rejected_by = NULL,
                    reject_date = NULL,
                    last_modification = NOW()
                    WHERE id = ?");
                
                $update_tut->bind_param("ssi", 
                    $tutorial['name'],
                    $tutorial['id_step'],
                    $tutorial['original_id']
                );
                $update_tut->execute();
                
                // Deletar clone do tutorial
                $delete_tut = $mysqli->prepare("DELETE FROM blocos WHERE id = ?");
                $delete_tut->bind_param("i", $tutorial['id']);
                $delete_tut->execute();
            } else {
                // Tutorial novo, apenas aprovar
                $approve_tut = $mysqli->prepare("UPDATE blocos SET accept = 1, rejection_reason = NULL, rejected_by = NULL, reject_date = NULL, last_modification = NOW() WHERE id = ?");
                $approve_tut->bind_param("i", $tutorial['id']);
                $approve_tut->execute();
            }
            
            $tutorialsApproved++;
        }
        
        $mysqli->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Serviço e tutoriais aprovados com sucesso!',
            'data' => [
                'service_name' => $servico['name'],
                'tutorials_approved' => $tutorialsApproved
            ]
        ]);
        exit;
        
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao aprovar: ' . $e->getMessage()]);
        exit;
    }
}

// Validação padrão para outras ações
if (!$action || !$type || !$id) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos', 'debug' => [
        'action' => $action,
        'type' => $type,
        'id' => $id
    ]]);
    exit;
}

// Aprovar item
if ($action === 'approve') {
    if ($type === 'tutorial') {
        // Buscar dados do tutorial
        $stmt = $mysqli->prepare("SELECT * FROM blocos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tutorial = $result->fetch_assoc();
        
        if (!$tutorial) {
            echo json_encode(['success' => false, 'message' => 'Tutorial não encontrado']);
            exit;
        }
        
        // Se for um clone, substituir o original
        if ($tutorial['is_clone'] == 1 && $tutorial['original_id']) {
            $mysqli->begin_transaction();
            
            try {
                // IMPORTANTE: Substituir tutorial original pelo clone (deletar original antes)
                replaceOriginalWithClone($mysqli, $id, $tutorial['original_id']);
                
                // Atualizar o original com os dados do clone
                $update_stmt = $mysqli->prepare("UPDATE blocos SET 
                    name = ?,
                    id_step = ?,
                    accept = 1,
                    last_modification = NOW()
                    WHERE id = ?");
                
                $update_stmt->bind_param("ssi", 
                    $tutorial['name'],
                    $tutorial['id_step'],
                    $tutorial['original_id']
                );
                $update_stmt->execute();
                
                // Deletar o registro do clone (mas não as mídias, pois agora pertencem ao original)
                $delete_stmt = $mysqli->prepare("DELETE FROM blocos WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                
                $mysqli->commit();
                echo json_encode(['success' => true, 'message' => 'Tutorial atualizado e aprovado com sucesso!']);
                
            } catch (Exception $e) {
                $mysqli->rollback();
                echo json_encode(['success' => false, 'message' => 'Erro ao aprovar: ' . $e->getMessage()]);
            }
            
        } else {
            // Tutorial novo, apenas marcar como aprovado e limpar rejeição anterior
            $stmt = $mysqli->prepare("UPDATE blocos SET accept = 1, rejection_reason = NULL, rejected_by = NULL, reject_date = NULL, last_modification = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Tutorial aprovado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao aprovar tutorial']);
            }
        }
        
    } elseif ($type === 'servico') {
        // Buscar dados do serviço
        $stmt = $mysqli->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $servico = $result->fetch_assoc();
        
        if (!$servico) {
            echo json_encode(['success' => false, 'message' => 'Serviço não encontrado']);
            exit;
        }
        
        // Se for um clone, substituir o original
        if ($servico['is_clone'] == 1 && $servico['original_id']) {
            $mysqli->begin_transaction();
            
            try {
                // Atualizar o original com os dados do clone
                $update_stmt = $mysqli->prepare("UPDATE services SET 
                    name = ?,
                    description = ?,
                    departamento = ?,
                    blocos = ?,
                    word_keys = ?,
                    accept = 1,
                    last_modification = NOW()
                    WHERE id = ?");
                
                $update_stmt->bind_param("ssissi", 
                    $servico['name'],
                    $servico['description'],
                    $servico['departamento'],
                    $servico['blocos'],
                    $servico['word_keys'],
                    $servico['original_id']
                );
                $update_stmt->execute();
                
                // Deletar o clone
                $delete_stmt = $mysqli->prepare("DELETE FROM services WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                
                $mysqli->commit();
                echo json_encode(['success' => true, 'message' => 'Serviço atualizado e aprovado com sucesso!']);
                
            } catch (Exception $e) {
                $mysqli->rollback();
                echo json_encode(['success' => false, 'message' => 'Erro ao aprovar: ' . $e->getMessage()]);
            }
            
        } else {
            // Serviço novo, apenas marcar como aprovado e limpar rejeição anterior
            $stmt = $mysqli->prepare("UPDATE services SET accept = 1, rejection_reason = NULL, rejected_by = NULL, reject_date = NULL, last_modification = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Serviço aprovado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao aprovar serviço']);
            }
        }
    }
}

// Rejeitar item (marcar como inativo e deletar se for clone)
elseif ($action === 'reject') {
    $reason = $data['reason'] ?? '';
    
    error_log("Reject - Reason: $reason, Type: $type, ID: $id");
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Motivo da rejeição é obrigatório']);
        exit;
    }
    
    $table = $type === 'tutorial' ? 'blocos' : 'services';
    $rejectedBy = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
    
    error_log("Reject - Table: $table, Rejected By: $rejectedBy");
    
    if ($rejectedBy == 0) {
        echo json_encode(['success' => false, 'message' => 'Erro: usuário não identificado', 'debug' => [
            'session' => $_SESSION
        ]]);
        exit;
    }
    
    // Se for clone, apenas deletar (mas antes salvar o motivo no original)
    $check_stmt = $mysqli->prepare("SELECT is_clone, original_id FROM $table WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $item = $check_result->fetch_assoc();
    
    error_log("Reject - Item found: " . ($item ? 'yes' : 'no'));
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item não encontrado', 'debug' => [
            'table' => $table,
            'id' => $id
        ]]);
        exit;
    }
    
    if ($item['is_clone'] == 1 && $item['original_id']) {
        $mysqli->begin_transaction();
        
        try {
            // Deletar todas as mídias e steps do clone antes de remover
            if ($type === 'tutorial') {
                deleteCloneMedia($mysqli, $id);
            }
            
            // Salvar motivo no original para notificar o criador
            $update_stmt = $mysqli->prepare("UPDATE $table SET rejection_reason = ?, rejected_by = ?, reject_date = NOW() WHERE id = ?");
            $update_stmt->bind_param("sii", $reason, $rejectedBy, $item['original_id']);
            $update_stmt->execute();
            
            // Deletar o registro do clone
            $stmt = $mysqli->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $mysqli->commit();
            echo json_encode(['success' => true, 'message' => 'Atualização rejeitada. O criador foi notificado e as mídias foram removidas.']);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar: ' . $e->getMessage()]);
        }
    } else {
        // Item novo - marcar como rejeitado (accept = 2) para remover da lista de pendentes
        $stmt = $mysqli->prepare("UPDATE $table SET rejection_reason = ?, rejected_by = ?, reject_date = NOW(), accept = 2 WHERE id = ?");
        $stmt->bind_param("sii", $reason, $rejectedBy, $id);
        
        error_log("Reject - Executing update query for table: $table, id: $id");
        
        if ($stmt->execute()) {
            error_log("Reject - SUCCESS! Affected rows: " . $stmt->affected_rows);
            echo json_encode(['success' => true, 'message' => 'Item rejeitado. Motivo salvo para correção.']);
        } else {
            error_log("Reject - ERROR: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar item: ' . $stmt->error]);
        }
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
?>
