<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_viewer();

include_once(__DIR__ . '/../config/conexao.php');

$ids = $_GET['ids'] ?? '';

if (empty($ids)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Limpar e validar IDs
$idArray = array_filter(array_map('intval', explode(',', $ids)));

if (empty($idArray)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($idArray), '?'));
$sql = "SELECT id, name, text, proximo FROM questions WHERE id IN ($placeholders)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param(str_repeat('i', count($idArray)), ...$idArray);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

echo json_encode(['success' => true, 'data' => $questions]);
?>
