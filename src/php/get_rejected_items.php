<?php
session_start();
include_once(__DIR__ . '/../config/conexao.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Buscar tutoriais reprovados (accept = 2 ou que têm rejection_reason)
$tutoriais_query = "SELECT 
    id, 
    name, 
    id_step,
    rejection_reason,
    rejected_by,
    reject_date,
    last_modification,
    created_by
    FROM blocos 
    WHERE active = 1 
    AND (accept = 2 OR (rejection_reason IS NOT NULL AND rejection_reason != ''))
    ORDER BY reject_date DESC";

$tutoriais_result = $mysqli->query($tutoriais_query);
$tutoriais_reprovados = [];

if ($tutoriais_result) {
    while ($row = $tutoriais_result->fetch_assoc()) {
        // Buscar nome do usuário que rejeitou
        if ($row['rejected_by']) {
            $user_stmt = $mysqli->prepare("SELECT user FROM usuarios WHERE id = ?");
            $user_stmt->bind_param("i", $row['rejected_by']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            $row['rejected_by_name'] = $user ? $user['user'] : 'Desconhecido';
        } else {
            $row['rejected_by_name'] = 'Sistema';
        }
        
        $tutoriais_reprovados[] = $row;
    }
}

// Buscar serviços reprovados (accept = 2 ou que têm rejection_reason)
$servicos_query = "SELECT 
    id, 
    name, 
    description,
    departamento,
    rejection_reason,
    rejected_by,
    reject_date,
    last_modification,
    created_by
    FROM services 
    WHERE active = 1 
    AND (accept = 2 OR (rejection_reason IS NOT NULL AND rejection_reason != ''))
    ORDER BY reject_date DESC";

$servicos_result = $mysqli->query($servicos_query);
$servicos_reprovados = [];

if ($servicos_result) {
    while ($row = $servicos_result->fetch_assoc()) {
        // Buscar nome do usuário que rejeitou
        if ($row['rejected_by']) {
            $user_stmt = $mysqli->prepare("SELECT user FROM usuarios WHERE id = ?");
            $user_stmt->bind_param("i", $row['rejected_by']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            $row['rejected_by_name'] = $user ? $user['user'] : 'Desconhecido';
        } else {
            $row['rejected_by_name'] = 'Sistema';
        }
        
        $servicos_reprovados[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'tutoriais' => $tutoriais_reprovados,
    'servicos' => $servicos_reprovados,
    'total' => count($tutoriais_reprovados) + count($servicos_reprovados)
]);
?>
