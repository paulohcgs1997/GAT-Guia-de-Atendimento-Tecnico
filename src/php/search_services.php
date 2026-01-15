<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_viewer();

include_once(__DIR__ . "/../config/conexao.php");
global $mysqli;

header('Content-Type: application/json');



if (!isset($_POST['query']) || empty(trim($_POST['query']))) {
    echo json_encode([]);
    exit;
}

$query = trim($_POST['query']);

// Buscar serviços ativos que correspondam às palavras-chave
$searchTerm = '%' . $mysqli->real_escape_string($query) . '%';

$sql = "SELECT s.id, s.name, s.description, s.word_keys, d.src as dept_logo, d.name as dept_name
        FROM services s
        LEFT JOIN departaments d ON s.departamento = d.id
        WHERE s.active = 1 
        AND s.accept = 1
        AND (
            s.name LIKE ? 
            OR s.description LIKE ? 
            OR s.word_keys LIKE ?
        )
        LIMIT 10";

$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $results = [];
    
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode($results);
} else {
    echo json_encode(['error' => 'Erro ao preparar consulta: ' . $mysqli->error]);
}
?>