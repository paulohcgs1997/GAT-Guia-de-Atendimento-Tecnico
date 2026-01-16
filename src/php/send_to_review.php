<?php
/**
 * Enviar Tutorial/Serviço para Análise
 * Permite que criadores enviem seus trabalhos para aprovação
 */

session_start();
require_once '../config/conexao.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

try {
    // Receber dados
    $type = $_POST['type'] ?? ''; // 'tutorial' ou 'service'
    $id = intval($_POST['id'] ?? 0);
    
    if (!in_array($type, ['tutorial', 'service']) || $id <= 0) {
        throw new Exception('Parâmetros inválidos');
    }
    
    $table = $type === 'tutorial' ? 'blocos' : 'services';
    
    // Verificar se o item existe e pertence ao usuário (ou se é admin)
    $check_stmt = $conexao->prepare("
        SELECT id, status, created_by 
        FROM $table 
        WHERE id = ? AND active = 1
    ");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception(ucfirst($type) . ' não encontrado');
    }
    
    $item = $result->fetch_assoc();
    
    // Verificar permissão (apenas o criador ou admin pode enviar)
    if ($_SESSION['perfil'] != '1' && $item['created_by'] != $user_id) {
        throw new Exception('Você não tem permissão para enviar este item');
    }
    
    // Verificar se já está aprovado
    if ($item['status'] === 'approved') {
        throw new Exception('Este item já foi aprovado e não pode ser reenviado');
    }
    
    // Validações específicas
    if ($type === 'tutorial') {
        // Verificar se o tutorial tem pelo menos 1 passo
        $steps_check = $conexao->prepare("SELECT id_step FROM blocos WHERE id = ?");
        $steps_check->bind_param("i", $id);
        $steps_check->execute();
        $steps_result = $steps_check->get_result();
        $tutorial_data = $steps_result->fetch_assoc();
        
        if (empty($tutorial_data['id_step'])) {
            throw new Exception('O tutorial precisa ter pelo menos 1 passo antes de ser enviado para análise');
        }
    } else {
        // Verificar se o serviço tem pelo menos 1 tutorial vinculado
        $blocos_check = $conexao->prepare("SELECT blocos FROM services WHERE id = ?");
        $blocos_check->bind_param("i", $id);
        $blocos_check->execute();
        $blocos_result = $blocos_check->get_result();
        $service_data = $blocos_result->fetch_assoc();
        
        if (empty($service_data['blocos'])) {
            throw new Exception('O serviço precisa ter pelo menos 1 tutorial vinculado antes de ser enviado para análise');
        }
    }
    
    // Atualizar status para 'pending' e limpar rejection_reason se houver
    $update_stmt = $conexao->prepare("
        UPDATE $table 
        SET status = 'pending', 
            rejection_reason = NULL, 
            rejected_by = NULL,
            reject_date = NULL,
            last_modification = NOW()
        WHERE id = ?
    ");
    $update_stmt->bind_param("i", $id);
    
    if ($update_stmt->execute()) {
        $item_name = $type === 'tutorial' ? 'Tutorial' : 'Serviço';
        $response['success'] = true;
        $response['message'] = "$item_name enviado para análise com sucesso!";
        
        // Log da ação
        error_log("[$item_name #$id] Enviado para análise por usuário #$user_id");
    } else {
        throw new Exception('Erro ao atualizar status: ' . $conexao->error);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Erro ao enviar para análise: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
