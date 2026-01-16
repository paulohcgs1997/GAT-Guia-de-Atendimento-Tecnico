<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_viewer();

include_once(__DIR__ . "/../config/conexao.php");
header('Content-Type: application/json');

$dept_id = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$show_all = isset($_GET['show_all']) ? intval($_GET['show_all']) : 0;

if ($type === 'departamentos') {
    // Buscar todos os departamentos com contagem
    $query = "SELECT d.id, d.name, d.src as logo FROM departaments d ORDER BY d.name ASC";
    $result = $mysqli->query($query);
    
    $departamentos = [];
    while ($dept = $result->fetch_assoc()) {
        // Contar serviços
        $servicos_query = "SELECT COUNT(*) as total FROM services 
                          WHERE departamento = ? AND active = 1 
                          AND (
                              (EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'services' AND COLUMN_NAME = 'status') AND status = 'approved')
                              OR (NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'services' AND COLUMN_NAME = 'status') AND accept = 1)
                          )
                          AND is_clone = 0";
        $stmt = $mysqli->prepare($servicos_query);
        $stmt->bind_param("i", $dept['id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $dept['servicos_count'] = $r->fetch_assoc()['total'];
        
        // Contar tutoriais
        $tutoriais_query = "SELECT COUNT(*) as total FROM blocos 
                           WHERE departamento = ? AND active = 1 
                           AND (
                               (EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'blocos' AND COLUMN_NAME = 'status') AND status = 'approved')
                               OR (NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'blocos' AND COLUMN_NAME = 'status') AND accept = 1)
                           )
                           AND is_clone = 0";
        $stmt = $mysqli->prepare($tutoriais_query);
        $stmt->bind_param("i", $dept['id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $dept['tutoriais_count'] = $r->fetch_assoc()['total'];
        
        // Se show_all=1, adicionar todos. Senão, apenas com conteúdo
        if ($show_all == 1 || $dept['servicos_count'] > 0 || $dept['tutoriais_count'] > 0) {
            $departamentos[] = $dept;
        }
    }
    
    echo json_encode($departamentos);
    exit;
}

if (!$dept_id || !$type) {
    echo json_encode([]);
    exit;
}

if ($type === 'servicos') {
    $query = "SELECT id, name, description, blocos, last_modification
              FROM services
              WHERE departamento = ? AND active = 1
              AND (
                  (EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'services' AND COLUMN_NAME = 'status') AND status = 'approved')
                  OR (NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'services' AND COLUMN_NAME = 'status') AND accept = 1)
              )
              AND is_clone = 0
              ORDER BY last_modification DESC";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode($items);
    
} elseif ($type === 'tutoriais') {
    $query = "SELECT id, name, last_modification
              FROM blocos
              WHERE departamento = ? AND active = 1
              AND (
                  (EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'blocos' AND COLUMN_NAME = 'status') AND status = 'approved')
                  OR (NOT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'blocos' AND COLUMN_NAME = 'status') AND accept = 1)
              )
              AND is_clone = 0
              ORDER BY last_modification DESC";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode($items);
    
} else {
    echo json_encode([]);
}
?>
